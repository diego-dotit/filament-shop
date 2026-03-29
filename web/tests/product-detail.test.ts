import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

vi.stubGlobal("definePageMeta", vi.fn());

vi.stubGlobal("useApi", () => vi.fn());

const mockCreateError = vi.fn((opts: { statusCode: number; statusMessage?: string }) => {
    const err = new Error(opts.statusMessage ?? String(opts.statusCode));
    (err as unknown as Record<string, unknown>).statusCode = opts.statusCode;
    return err;
});
vi.stubGlobal("createError", mockCreateError);

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

/** Variant attribute shape returned by the API (array of {name, value} objects) */
type VariantAttribute = { name: string; value: string };

const makeVariant = (id: number, attrs: VariantAttribute[], stock = 5) => ({
    id,
    sku: `SKU-${id}`,
    price: "29.99",
    stock_quantity: stock,
    attributes: attrs,
});

const makeProduct = (overrides: Record<string, unknown> = {}) => ({
    id: 1,
    name: "PLA Filament",
    slug: "pla-filament",
    description: "High quality PLA filament for 3D printing.",
    price: "19.99",
    images: ["https://example.com/image1.jpg", "https://example.com/image2.jpg"],
    variants: [
        makeVariant(10, [{ name: "Color", value: "Red" }, { name: "Size", value: "1kg" }], 5),
        makeVariant(11, [{ name: "Color", value: "Blue" }, { name: "Size", value: "1kg" }], 0),
    ],
    attributes: { material: "PLA" },
    reviews: [
        { id: 1, rating: 5, comment: "Excellent filament!", customer_name: "Alice" },
        { id: 2, rating: 4, comment: "Good value.", customer_name: "Bob" },
    ],
    ...overrides,
});

// ---------------------------------------------------------------------------
// Default stubs
// ---------------------------------------------------------------------------

/**
 * Stub shadcn Select components as native <select>/<option> so tests that
 * assert on select/option elements continue to work after the shadcn migration.
 */
const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
    // Shadcn Select → render as a plain wrapper (v-model passes through via value prop)
    Select: {
        props: ["modelValue"],
        template: '<div class="select-stub"><slot /></div>',
    },
    SelectTrigger: { template: "<div><slot /></div>" },
    SelectValue: { template: "<span><slot /></span>" },
    SelectContent: { template: "<div><slot /></div>" },
    // SelectItem → render as <option> so findAll("option") works
    SelectItem: {
        props: ["value"],
        template: '<option :value="value"><slot /></option>',
    },
    SelectScrollUpButton: { template: "<span />" },
    SelectScrollDownButton: { template: "<span />" },
    // ReviewForm stub — keeps gallery/review tests lean
    ReviewForm: { template: "<div data-testid='review-form-stub'></div>" },
};

// ---------------------------------------------------------------------------
// Helper: stub useProducts and useCart and useRoute for each test
// ---------------------------------------------------------------------------

function setupStubs({
    product = makeProduct(),
    fetchProductBySlug = vi.fn().mockResolvedValue(product),
    addItem = vi.fn().mockResolvedValue(undefined),
    slug = "pla-filament",
    error = ref<string | null>(null),
    user = ref<{ name: string } | null>(null),
    apiMock,
}: {
    product?: ReturnType<typeof makeProduct> | null;
    fetchProductBySlug?: ReturnType<typeof vi.fn>;
    addItem?: ReturnType<typeof vi.fn>;
    slug?: string;
    error?: ReturnType<typeof ref<string | null>>;
    user?: ReturnType<typeof ref<{ name: string } | null>>;
    /** Override the useApi mock.  Defaults to returning product.reviews for the reviews endpoint. */
    apiMock?: ReturnType<typeof vi.fn>;
} = {}) {
    // Build a default api mock that returns the product's reviews for the reviews endpoint.
    // This keeps existing tests working while allowing targeted override in new tests.
    const defaultApiMock = vi.fn().mockImplementation((path: string) => {
        if (typeof path === "string" && path.includes("/reviews")) {
            return Promise.resolve({ data: product?.reviews ?? [] });
        }
        return Promise.resolve(undefined);
    });

    vi.stubGlobal("useApi", () => apiMock ?? defaultApiMock);

    vi.stubGlobal("useRoute", () => ({
        params: { slug },
    }));

    vi.stubGlobal("useProducts", () => ({
        fetchProductBySlug,
        currentProduct: ref(product),
        error,
    }));

    vi.stubGlobal("useCart", () => ({
        addItem,
        cart: ref(null),
        itemCount: computed(() => 0),
    }));

    vi.stubGlobal("useAuth", () => ({
        user,
        isAuthenticated: computed(() => user.value !== null),
        logout: vi.fn(),
    }));

    return { apiMock: apiMock ?? defaultApiMock };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("Product detail page ([slug].vue)", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
        mockCreateError.mockClear();
    });

    // ── Slug used to fetch product ───────────────────────────────────────────

    it("calls fetchProductBySlug with the route slug on mount", async () => {
        const fetchProductBySlug = vi.fn().mockResolvedValue(makeProduct());
        setupStubs({ fetchProductBySlug, slug: "pla-filament" });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        mount(ProductDetailPage, { global: { stubs: globalStubs } });

        // Wait for the async onMounted to complete
        await new Promise((r) => setTimeout(r, 0));

        expect(fetchProductBySlug).toHaveBeenCalledWith("pla-filament");
    });

    // ── Product info displayed ───────────────────────────────────────────────

    it("displays the product name and description", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("PLA Filament");
        expect(wrapper.text()).toContain("High quality PLA filament for 3D printing.");
    });

    // ── Image gallery ────────────────────────────────────────────────────────

    it("renders product images in a gallery", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const images = wrapper.findAll("img");
        expect(images.length).toBeGreaterThan(0);
    });

    // ── Variant selector ─────────────────────────────────────────────────────

    it("renders variant selector options from product variants", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // SelectItem is stubbed as <option> so we can assert options exist
        const hasOptions = wrapper.findAll("option").length > 0;
        expect(hasOptions).toBe(true);
    });

    // ── Add to cart ──────────────────────────────────────────────────────────

    it("calls addItem with selected variantId and quantity when Add to Cart is clicked", async () => {
        const addItem = vi.fn().mockResolvedValue(undefined);
        setupStubs({ addItem });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Select a variant (the first in-stock one) directly via component state
        // This works with both HTML <select> and shadcn Select components
        (wrapper.vm as Record<string, unknown>).selectedVariantId = 10;
        await wrapper.vm.$nextTick();

        const addToCartBtn = wrapper.find('[data-testid="add-to-cart"]');
        expect(addToCartBtn.exists()).toBe(true);
        await addToCartBtn.trigger("click");
        await new Promise((r) => setTimeout(r, 0));

        expect(addItem).toHaveBeenCalledWith(
            10,
            1,
            expect.objectContaining({
                product: expect.objectContaining({
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                }),
                variant: expect.objectContaining({ id: 10, sku: "SKU-10" }),
            })
        );
    });

    it("disables Add to Cart button when no variant is selected", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const addToCartBtn = wrapper.find('[data-testid="add-to-cart"]');
        expect(addToCartBtn.exists()).toBe(true);
        // Button should be disabled when no variant selected
        expect(addToCartBtn.attributes("disabled")).toBeDefined();
    });

    // ── Image gallery (T2.8) ─────────────────────────────────────────────────

    it("sets the first image as the featured (primary) image", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Primary image has alt = product.name (no BEM class after shadcn migration)
        const primaryImg = wrapper.findAll("img").find((img) => img.attributes("alt") === "PLA Filament");
        expect(primaryImg).toBeDefined();
        expect(primaryImg!.exists()).toBe(true);
        expect(primaryImg!.attributes("src")).toBe("https://example.com/image1.jpg");
    });

    it("renders all images as thumbnails with correct alt text", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Thumbnails iterate over product.images with alt="${name} thumbnail N"
        const thumbnails = wrapper.findAll("img").filter((img) =>
            img.attributes("alt")?.includes("thumbnail")
        );
        expect(thumbnails).toHaveLength(2);
        expect(thumbnails[0].attributes("alt")).toBe("PLA Filament thumbnail 1");
        expect(thumbnails[1].attributes("alt")).toBe("PLA Filament thumbnail 2");
    });

    it("switches the featured image when a thumbnail is clicked", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Click the second thumbnail (has click handler @click="selectedImage = image")
        const thumbnails = wrapper.findAll("img").filter((img) =>
            img.attributes("alt")?.includes("thumbnail")
        );
        await thumbnails[1].trigger("click");
        await wrapper.vm.$nextTick();

        // After click, primary image (alt = product.name) should show image2
        const primaryImg = wrapper.findAll("img").find((img) => img.attributes("alt") === "PLA Filament");
        expect(primaryImg!.attributes("src")).toBe("https://example.com/image2.jpg");
    });

    it("marks the active thumbnail with the active CSS class", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Initially first thumbnail should be active (selectedImage === images[0])
        const thumbnails = wrapper.findAll("img").filter((img) =>
            img.attributes("alt")?.includes("thumbnail")
        );
        expect(thumbnails[0].classes()).toContain("gallery__thumbnail--active");
        expect(thumbnails[1].classes()).not.toContain("gallery__thumbnail--active");
    });

    it('shows "No images available" placeholder when product has no images', async () => {
        setupStubs({ product: makeProduct({ images: [] }) });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("No images available");
        // No primary image should exist (template uses v-if on images.length > 0)
        const primaryImg = wrapper.findAll("img").find((img) => img.attributes("alt") === "PLA Filament");
        expect(primaryImg).toBeUndefined();
    });

    it("renders the gallery correctly for a product with a single image", async () => {
        setupStubs({ product: makeProduct({ images: ["https://example.com/only.jpg"] }) });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Primary image (alt = product.name)
        const primaryImg = wrapper.findAll("img").find((img) => img.attributes("alt") === "PLA Filament");
        expect(primaryImg).toBeDefined();
        expect(primaryImg!.exists()).toBe(true);
        expect(primaryImg!.attributes("src")).toBe("https://example.com/only.jpg");

        // One thumbnail
        const thumbnails = wrapper.findAll("img").filter((img) =>
            img.attributes("alt")?.includes("thumbnail")
        );
        expect(thumbnails).toHaveLength(1);
        expect(thumbnails[0].attributes("alt")).toBe("PLA Filament thumbnail 1");
    });

    // ── Reviews section ──────────────────────────────────────────────────────

    it("displays approved reviews with rating and comment", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("Excellent filament!");
        expect(wrapper.text()).toContain("Good value.");
        expect(wrapper.text()).toContain("Alice");
    });

    it('shows "No reviews yet" when product has no reviews', async () => {
        setupStubs({ product: makeProduct({ reviews: [] }) });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("No reviews yet");
    });

    // ── Review API fetching (T1.7) ───────────────────────────────────────────

    it("calls GET /products/{id}/reviews API endpoint on mount", async () => {
        const { apiMock } = setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));

        expect(apiMock).toHaveBeenCalledWith(
            expect.stringMatching(/\/products\/1\/reviews/)
        );
    });

    it("displays reviews returned by the reviews API, ordered as received", async () => {
        const apiReviews = [
            { id: 99, rating: 3, comment: "API review newest", customer_name: "Carol" },
            { id: 88, rating: 4, comment: "API review older", customer_name: "Dave" },
        ];
        const apiMock = vi.fn().mockResolvedValue({ data: apiReviews });

        // Product has NO inline reviews — they must come purely from the API
        setupStubs({ product: makeProduct({ reviews: [] }), apiMock });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("API review newest");
        expect(wrapper.text()).toContain("API review older");
        expect(wrapper.text()).toContain("Carol");
    });

    it('shows "No reviews yet" when reviews API returns empty array', async () => {
        const apiMock = vi.fn().mockResolvedValue({ data: [] });

        // Product has inline reviews in fixture — they should be replaced by empty API response
        setupStubs({ apiMock });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("No reviews yet");
    });

    // ── 404 handling ─────────────────────────────────────────────────────────

    it("calls createError with statusCode 404 when product is not found", async () => {
        setupStubs({
            product: null,
            fetchProductBySlug: vi.fn().mockResolvedValue(null),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await new Promise((r) => setTimeout(r, 0));

        expect(mockCreateError).toHaveBeenCalledWith(expect.objectContaining({ statusCode: 404 }));
    });

    it("shows error message when product is not found (404)", async () => {
        const errorRef = ref<string | null>("Product not found");
        setupStubs({
            product: null,
            fetchProductBySlug: vi.fn().mockResolvedValue(null),
            error: errorRef,
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));

        // createError should be thrown when product is null
        expect(mockCreateError).toHaveBeenCalledWith(expect.objectContaining({ statusCode: 404 }));
    });

    // ── Variant label formatting (T1.2) ──────────────────────────────────────

    it("formats variant option labels as 'Name: Value' pairs from attribute array", async () => {
        setupStubs({
            product: makeProduct({
                variants: [
                    makeVariant(10, [{ name: "Color", value: "Red" }, { name: "Size", value: "Large" }], 5),
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const options = wrapper.findAll("option");
        const labelOption = options.find((o) => o.attributes("value") === "10");
        expect(labelOption).toBeDefined();
        expect(labelOption!.text()).toContain("Color: Red");
        expect(labelOption!.text()).toContain("Size: Large");
    });

    it("does not show '[object Object]' in variant option labels", async () => {
        setupStubs({
            product: makeProduct({
                variants: [
                    makeVariant(10, [{ name: "Color", value: "Red" }], 5),
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).not.toContain("[object Object]");
    });

    it("falls back to SKU when variant has no attributes", async () => {
        setupStubs({
            product: makeProduct({
                variants: [makeVariant(10, [], 5)],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const options = wrapper.findAll("option");
        const labelOption = options.find((o) => o.attributes("value") === "10");
        expect(labelOption).toBeDefined();
        expect(labelOption!.text()).toBe("SKU-10");
    });

    it("formats multiple variant options with their own attribute arrays", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const options = wrapper.findAll("option");
        const redOption = options.find((o) => o.attributes("value") === "10");
        const blueOption = options.find((o) => o.attributes("value") === "11");

        expect(redOption!.text()).toContain("Color: Red");
        expect(blueOption!.text()).toContain("Color: Blue");
    });

    // ── Breadcrumb navigation (T1.2) ─────────────────────────────────────────

    it("renders Breadcrumb component when product has one category", async () => {
        setupStubs({
            product: makeProduct({
                categories: [
                    { id: 5, name: "PLA Filaments", slug: "pla-filaments" },
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // After shadcn migration, Breadcrumb renders <nav aria-label="breadcrumb">
        const breadcrumb = wrapper.find('nav[aria-label="breadcrumb"]');
        expect(breadcrumb.exists()).toBe(true);
    });

    it("breadcrumb shows category name as a clickable link", async () => {
        setupStubs({
            product: makeProduct({
                categories: [
                    { id: 5, name: "PLA Filaments", slug: "pla-filaments" },
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Breadcrumb always includes a "Home" link first; category link is after that.
        // After shadcn migration, links are <a> elements inside nav[aria-label="breadcrumb"].
        const categoryLinks = wrapper.findAll('nav[aria-label="breadcrumb"] a');
        // Home link + 1 category link = 2 total
        expect(categoryLinks.length).toBe(2);
        expect(categoryLinks[1].text()).toContain("PLA Filaments");
    });

    it("breadcrumb shows product name as non-clickable current page indicator", async () => {
        setupStubs({
            product: makeProduct({
                categories: [
                    { id: 5, name: "PLA Filaments", slug: "pla-filaments" },
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // After shadcn migration, BreadcrumbPage renders <span aria-current="page">
        const currentItem = wrapper.find('span[aria-current="page"]');
        expect(currentItem.exists()).toBe(true);
        expect(currentItem.text()).toBe("PLA Filament");
    });

    it("breadcrumb renders correctly when product has no categories", async () => {
        setupStubs({
            product: makeProduct({ categories: [] }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const breadcrumb = wrapper.find('nav[aria-label="breadcrumb"]');
        expect(breadcrumb.exists()).toBe(true);

        const currentItem = wrapper.find('span[aria-current="page"]');
        expect(currentItem.exists()).toBe(true);
        expect(currentItem.text()).toBe("PLA Filament");
    });

    it("breadcrumb renders correctly with multiple categories", async () => {
        setupStubs({
            product: makeProduct({
                categories: [
                    { id: 5, name: "PLA Filaments", slug: "pla-filaments" },
                    { id: 6, name: "1.75mm", slug: "1-75mm" },
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Home link + 2 category links = 3 total <a> elements inside nav
        const links = wrapper.findAll('nav[aria-label="breadcrumb"] a');
        expect(links.length).toBe(3);
        expect(links[1].text()).toContain("PLA Filaments");
        expect(links[2].text()).toContain("1.75mm");

        const currentItem = wrapper.find('span[aria-current="page"]');
        expect(currentItem.text()).toBe("PLA Filament");
    });

    // ── Breadcrumb with single slug string (T2.3) ────────────────────────────

    it("breadcrumb renders without error when category uses single slug string", async () => {
        setupStubs({
            product: makeProduct({
                categories: [
                    { id: 5, name: "PLA Filaments", slug: "pla-filaments" },
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const breadcrumb = wrapper.find('nav[aria-label="breadcrumb"]');
        expect(breadcrumb.exists()).toBe(true);
        const categoryLinks = wrapper.findAll('nav[aria-label="breadcrumb"] a');
        // Home link + 1 category link = 2
        expect(categoryLinks.length).toBe(2);
        expect(categoryLinks[1].text()).toContain("PLA Filaments");
    });

    it("breadcrumb category link URL is /{slug} when category has slug string", async () => {
        setupStubs({
            product: makeProduct({
                categories: [
                    { id: 5, name: "PLA Filaments", slug: "pla-filaments" },
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, {
            global: {
                stubs: {
                    // Declare `to` as a prop so class stays in $attrs and is
                    // forwarded automatically (inheritAttrs: true is the default).
                    NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' },
                    ...globalStubs,
                    // Override NuxtLink stub to pass href
                    NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' },
                },
            },
        });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const links = wrapper.findAll('nav[aria-label="breadcrumb"] a');
        // links[0] = Home (/), links[1] = category
        expect(links.length).toBe(2);
        expect(links[1].attributes("href")).toBe("/pla-filaments");
    });

    it("breadcrumb category link falls back to /{id} when slug is empty", async () => {
        setupStubs({
            product: makeProduct({
                categories: [
                    { id: 7, name: "Misc", slug: "" },
                ],
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, {
            global: {
                stubs: {
                    // Declare `to` as a prop so class stays in $attrs and is
                    // forwarded automatically (inheritAttrs: true is the default).
                    NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' },
                    ...globalStubs,
                    // Override NuxtLink stub to pass href
                    NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' },
                },
            },
        });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const links = wrapper.findAll('nav[aria-label="breadcrumb"] a');
        expect(links.length).toBe(2);
        expect(links[1].attributes("href")).toBe("/7");
    });

    // ── Flat product attributes rendering (T2.5) ─────────────────────────────

    it("renders flat product attributes as key-value pairs in Specifications section", async () => {
        setupStubs({
            product: makeProduct({
                attributes: { material: "PLA", diameter: "1.75mm" },
            }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("material");
        expect(wrapper.text()).toContain("PLA");
        expect(wrapper.text()).toContain("diameter");
        expect(wrapper.text()).toContain("1.75mm");
    });

    it("does not render Specifications section when product has no attributes", async () => {
        setupStubs({
            product: makeProduct({ attributes: {} }),
        });

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // When attributes is empty, the v-if hides the entire specs section (no <dl>)
        expect(wrapper.find("dl").exists()).toBe(false);
    });
});
