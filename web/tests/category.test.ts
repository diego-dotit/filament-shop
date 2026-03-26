import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

// useState: simulate Nuxt's shared state via a plain ref per key
const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

// Stubs for other composables Header/Footer may need
vi.stubGlobal("useAuth", () => ({
    user: ref(null),
    isAuthenticated: computed(() => false),
    logout: vi.fn(),
}));

vi.stubGlobal("useCart", () => ({
    cart: ref(null),
    itemCount: computed(() => 0),
}));

// ---------------------------------------------------------------------------
// Route stub — controls the slug param
// ---------------------------------------------------------------------------

const mockRoute = { params: { slug: "pla-category" } };
vi.stubGlobal("useRoute", () => mockRoute);

// ---------------------------------------------------------------------------
// useApi / $fetch stub
// ---------------------------------------------------------------------------

const mockApi = vi.fn();
vi.stubGlobal("useApi", () => mockApi);
vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// ---------------------------------------------------------------------------
// Stub navigateTo / useNuxtApp
// ---------------------------------------------------------------------------

vi.stubGlobal("navigateTo", vi.fn());
vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context");
});

const mockCreateError = vi.fn((opts: { statusCode: number; statusMessage?: string }) => {
    const err = new Error(opts.statusMessage ?? String(opts.statusCode));
    (err as unknown as Record<string, unknown>).statusCode = opts.statusCode;
    return err;
});
vi.stubGlobal("createError", mockCreateError);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeCategoryResponse() {
    return {
        data: {
            id: 1,
            name: "PLA Category",
            slug: "pla-category",
            image: "https://example.com/pla.jpg",
            children: [],
        },
    };
}

function makeProductListResponse(overrides: Record<string, unknown> = {}) {
    return {
        data: [
            { id: 1, name: "PLA Filament", slug: "pla-filament", price: "19.99", images: [] },
            { id: 2, name: "PETG Filament", slug: "petg-filament", price: "24.99", images: [] },
        ],
        meta: {
            current_page: 1,
            last_page: 3,
            total: 45,
            per_page: 15,
            ...overrides,
        },
    };
}

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
    ProductCard: {
        template: '<div class="product-card">{{ product.name }}</div>',
        props: ["product"],
    },
};

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("Category page [slug].vue", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockRoute.params.slug = "pla-category";
        mockCreateError.mockClear();

        // Reset state store
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();
    });

    it("fetches category using the slug from route params", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        mount(CategoryPage, { global: { stubs: globalStubs } });

        // Allow onMounted async calls to settle
        await new Promise((r) => setTimeout(r, 0));

        // First API call should be for the category
        expect(mockApi).toHaveBeenCalledWith("/categories/pla-category", expect.anything());
    });

    it("fetches products using category_slug filter", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));

        // Second API call should include category_slug filter
        expect(mockApi).toHaveBeenCalledWith(
            "/products",
            expect.objectContaining({
                query: expect.objectContaining({ category_slug: "pla-category" }),
            })
        );
    });

    it("displays the category name after loading", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("PLA Category");
    });

    it("renders a product card for each product in the category", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const cards = wrapper.findAll(".product-card");
        expect(cards).toHaveLength(2);
    });

    it("calls createError with statusCode 404 when category is not found", async () => {
        const notFoundError = Object.assign(new Error("Not Found"), { statusCode: 404 });
        mockApi.mockRejectedValueOnce(notFoundError);

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await new Promise((r) => setTimeout(r, 0));

        expect(mockCreateError).toHaveBeenCalledWith(expect.objectContaining({ statusCode: 404 }));
    });

    it("shows a 404 error message when category is not found", async () => {
        const notFoundError = Object.assign(new Error("Not Found"), { statusCode: 404 });
        mockApi.mockRejectedValueOnce(notFoundError);

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await new Promise((r) => setTimeout(r, 0));

        // createError should have been called with 404 (Nuxt handles rendering error.vue)
        expect(mockCreateError).toHaveBeenCalledWith(expect.objectContaining({ statusCode: 404 }));
    });

    it("renders a back link to the homepage", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // NuxtLink is stubbed as <a>, so look for a link to '/'
        const links = wrapper.findAll("a");
        const homeLink = links.find(
            (l) =>
                l.text().toLowerCase().includes("home") ||
                l.attributes("href") === "/" ||
                l.attributes("to") === "/"
        );
        expect(homeLink).toBeDefined();
    });

    it("displays pagination controls when there are multiple pages", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse({ last_page: 3, current_page: 1 }));

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Pagination should show page navigation (buttons or page numbers)
        expect(wrapper.find('[data-testid="pagination"]').exists()).toBe(true);
    });

    // ---------------------------------------------------------------------------
    // Subcategories (T2.7)
    // ---------------------------------------------------------------------------

    it("hides subcategories section when category has no children", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse()) // children: []
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="subcategories"]').exists()).toBe(false);
    });

    it("displays subcategories section when category has children", async () => {
        mockApi
            .mockResolvedValueOnce({
                data: {
                    id: 1,
                    name: "PLA Category",
                    slug: "pla-category",
                    image: null,
                    children: [
                        { id: 2, name: "PLA Silk", slug: "pla-silk", image: null, children: [] },
                        { id: 3, name: "PLA Matte", slug: "pla-matte", image: null, children: [] },
                    ],
                },
            })
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="subcategories"]').exists()).toBe(true);
    });

    it("renders a chip/link for each child category", async () => {
        mockApi
            .mockResolvedValueOnce({
                data: {
                    id: 1,
                    name: "PLA Category",
                    slug: "pla-category",
                    image: null,
                    children: [
                        { id: 2, name: "PLA Silk", slug: "pla-silk", image: null, children: [] },
                        { id: 3, name: "PLA Matte", slug: "pla-matte", image: null, children: [] },
                    ],
                },
            })
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const chips = wrapper.findAll('[data-testid="subcategory-chip"]');
        expect(chips).toHaveLength(2);
        expect(chips[0].text()).toBe("PLA Silk");
        expect(chips[1].text()).toBe("PLA Matte");
    });

    it("subcategory chip links to /categories/[child-slug]", async () => {
        mockApi
            .mockResolvedValueOnce({
                data: {
                    id: 1,
                    name: "PLA Category",
                    slug: "pla-category",
                    image: null,
                    children: [
                        { id: 2, name: "PLA Silk", slug: "pla-silk", image: null, children: [] },
                    ],
                },
            })
            .mockResolvedValueOnce(makeProductListResponse());

        const stubsWithNuxtLink = {
            ...globalStubs,
            NuxtLink: {
                template: '<a :href="to"><slot /></a>',
                props: ["to"],
            },
        };

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: stubsWithNuxtLink } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const chip = wrapper.find('[data-testid="subcategory-chip"]');
        expect(chip.attributes("href")).toBe("/categories/pla-silk");
    });

    it("subcategories section appears before the products grid", async () => {
        mockApi
            .mockResolvedValueOnce({
                data: {
                    id: 1,
                    name: "PLA Category",
                    slug: "pla-category",
                    image: null,
                    children: [
                        { id: 2, name: "PLA Silk", slug: "pla-silk", image: null, children: [] },
                    ],
                },
            })
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        const subcatPos = html.indexOf('data-testid="subcategories"');
        const productsPos = html.indexOf("category-page__products");
        expect(subcatPos).toBeGreaterThan(-1);
        expect(subcatPos).toBeLessThan(productsPos);
    });
});
