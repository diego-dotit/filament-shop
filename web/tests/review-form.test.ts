import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context");
});
vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const makeReview = (overrides: Record<string, unknown> = {}) => ({
    id: 1,
    rating: 5,
    comment: "Excellent!",
    customer_name: "Alice",
    status: "pending",
    ...overrides,
});

// ---------------------------------------------------------------------------
// Helper: stub useAuth for each test
// ---------------------------------------------------------------------------

function setupAuthStub({
    isAuthenticated = true,
    userName = "Alice",
}: {
    isAuthenticated?: boolean;
    userName?: string;
} = {}) {
    const user = isAuthenticated
        ? ref({ id: 1, name: userName, email: "alice@example.com" })
        : ref(null);
    vi.stubGlobal("useAuth", () => ({
        user,
        isAuthenticated: computed(() => user.value !== null),
    }));
}

// ---------------------------------------------------------------------------
// Tests for ReviewForm.vue component
// ---------------------------------------------------------------------------

describe("ReviewForm component", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    // ── Visibility ────────────────────────────────────────────────────────────

    it("renders the review form when user is authenticated", async () => {
        setupAuthStub({ isAuthenticated: true });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        expect(wrapper.find('[data-testid="review-form"]').exists()).toBe(true);
    });

    it("does not render the form when user is not authenticated", async () => {
        setupAuthStub({ isAuthenticated: false });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        expect(wrapper.find('[data-testid="review-form"]').exists()).toBe(false);
    });

    it("hides the form when user already submitted a review (alreadyReviewed prop)", async () => {
        setupAuthStub({ isAuthenticated: true });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1, alreadyReviewed: true },
        });

        expect(wrapper.find('[data-testid="review-form"]').exists()).toBe(false);
        expect(wrapper.text()).toContain("already submitted");
    });

    // ── Form fields ───────────────────────────────────────────────────────────

    it("renders star rating inputs (1-5)", async () => {
        setupAuthStub({ isAuthenticated: true });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        // Should have 5 star buttons or radio inputs
        const starButtons = wrapper.findAll('[data-testid^="star-"]');
        expect(starButtons.length).toBe(5);
    });

    it("renders a comment textarea", async () => {
        setupAuthStub({ isAuthenticated: true });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        expect(wrapper.find("textarea").exists()).toBe(true);
    });

    // ── Validation ────────────────────────────────────────────────────────────

    it("shows validation error when submitting without a rating", async () => {
        setupAuthStub({ isAuthenticated: true });
        const mockApi = vi.fn();
        vi.stubGlobal("useApi", () => mockApi);

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        const submitBtn = wrapper.find('[data-testid="submit-review"]');
        await submitBtn.trigger("click");

        expect(mockApi).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain("rating is required");
    });

    it("shows character counter and validates comment max 500 chars", async () => {
        setupAuthStub({ isAuthenticated: true });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        const textarea = wrapper.find("textarea");
        const longComment = "a".repeat(501);
        await textarea.setValue(longComment);

        // Should show character counter / limit warning
        expect(wrapper.text()).toContain("500");
    });

    // ── Submission ────────────────────────────────────────────────────────────

    it("calls POST /products/{productId}/reviews with rating and comment on submit", async () => {
        setupAuthStub({ isAuthenticated: true });
        const mockApi = vi.fn().mockResolvedValue(makeReview());
        vi.stubGlobal("useApi", () => mockApi);

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 42 },
        });

        // Select rating 4 (click star 4)
        const star4 = wrapper.find('[data-testid="star-4"]');
        await star4.trigger("click");

        // Enter comment
        const textarea = wrapper.find("textarea");
        await textarea.setValue("Great product!");

        // Submit
        const submitBtn = wrapper.find('[data-testid="submit-review"]');
        await submitBtn.trigger("click");
        await new Promise((r) => setTimeout(r, 0));

        expect(mockApi).toHaveBeenCalledWith("/products/42/reviews", {
            method: "POST",
            body: { rating: 4, comment: "Great product!" },
        });
    });

    it("shows success message and clears form after successful submission", async () => {
        setupAuthStub({ isAuthenticated: true });
        const mockApi = vi.fn().mockResolvedValue(makeReview({ status: "pending" }));
        vi.stubGlobal("useApi", () => mockApi);

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        // Select rating 5
        await wrapper.find('[data-testid="star-5"]').trigger("click");
        await wrapper.find("textarea").setValue("Awesome!");

        await wrapper.find('[data-testid="submit-review"]').trigger("click");
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("awaiting moderation");
        // Form should be cleared (hidden after success or reset)
        expect(wrapper.find('[data-testid="review-form"]').exists()).toBe(false);
    });

    it("shows duplicate review error message when API returns 409", async () => {
        setupAuthStub({ isAuthenticated: true });
        const mockApi = vi.fn().mockRejectedValue({
            status: 409,
            statusCode: 409,
            data: { message: "You have already reviewed this product." },
        });
        vi.stubGlobal("useApi", () => mockApi);

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        await wrapper.find('[data-testid="star-3"]').trigger("click");
        await wrapper.find('[data-testid="submit-review"]').trigger("click");
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("already reviewed");
    });

    it("shows generic error message when API returns other errors", async () => {
        setupAuthStub({ isAuthenticated: true });
        const mockApi = vi.fn().mockRejectedValue({
            status: 422,
            data: { message: "Validation failed." },
        });
        vi.stubGlobal("useApi", () => mockApi);

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, {
            props: { productId: 1 },
        });

        await wrapper.find('[data-testid="star-2"]').trigger("click");
        await wrapper.find('[data-testid="submit-review"]').trigger("click");
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("Failed to submit");
    });
});

// ---------------------------------------------------------------------------
// Tests for product detail page integration ([slug].vue)
// ---------------------------------------------------------------------------

describe("Product detail page — review form integration", () => {
    const makeProduct = (overrides: Record<string, unknown> = {}) => ({
        id: 1,
        name: "PLA Filament",
        slug: "pla-filament",
        description: "High quality PLA.",
        price: "19.99",
        images: ["https://example.com/image1.jpg"],
        variants: [
            {
                id: 10,
                sku: "SKU-10",
                price: "29.99",
                stock_quantity: 5,
                attributes: { color: "Red" },
            },
        ],
        attributes: { material: "PLA" },
        reviews: [{ id: 1, rating: 5, comment: "Excellent!", customer_name: "Alice" }],
        ...overrides,
    });

    function setupPageStubs({
        product = makeProduct(),
        isAuthenticated = true,
        userName = "Bob",
    }: {
        product?: ReturnType<typeof makeProduct> | null;
        isAuthenticated?: boolean;
        userName?: string;
    } = {}) {
        vi.stubGlobal("useRoute", () => ({ params: { slug: "pla-filament" } }));
        vi.stubGlobal("useProducts", () => ({
            fetchProductBySlug: vi.fn().mockResolvedValue(product),
            currentProduct: ref(product),
            error: ref(null),
        }));
        vi.stubGlobal("useCart", () => ({
            addItem: vi.fn().mockResolvedValue(undefined),
            cart: ref(null),
            itemCount: computed(() => 0),
        }));

        const user = isAuthenticated
            ? ref({ id: 1, name: userName, email: `${userName.toLowerCase()}@example.com` })
            : ref(null);
        vi.stubGlobal("useAuth", () => ({
            user,
            isAuthenticated: computed(() => user.value !== null),
        }));
    }

    beforeEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    it("shows ReviewForm on product detail page when authenticated", async () => {
        setupPageStubs({ isAuthenticated: true, userName: "Bob" });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" } },
                components: { ReviewForm },
            },
        });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="review-form"]').exists()).toBe(true);
    });

    it("hides ReviewForm on product detail page when not authenticated", async () => {
        setupPageStubs({ isAuthenticated: false });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" } },
                components: { ReviewForm },
            },
        });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="review-form"]').exists()).toBe(false);
    });

    it("passes alreadyReviewed=true when user has already reviewed product", async () => {
        // 'Alice' has already reviewed (review.customer_name === 'Alice')
        setupPageStubs({ isAuthenticated: true, userName: "Alice" });

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" } },
                components: { ReviewForm },
            },
        });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Review form is present but should have alreadyReviewed=true meaning it shows "already submitted"
        expect(wrapper.text()).toContain("already submitted");
    });
});
