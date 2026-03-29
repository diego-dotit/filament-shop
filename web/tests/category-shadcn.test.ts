/**
 * T4.6 — Category detail page shadcn-vue migration tests.
 *
 * Acceptance criteria:
 *  - No manual breadcrumb nav with "→" separators (replaced by Breadcrumb component)
 *  - Breadcrumb component is rendered with correct items built from category data
 *  - Parent category link is included in breadcrumb when present
 *  - Pagination buttons use shadcn Button component (data-slot="button")
 *  - Error state uses data-testid="category-error" and Tailwind classes
 *  - Loading state uses data-testid="category-loading" and Tailwind classes
 *  - Product grid and header use Tailwind utility classes
 *  - No BEM CSS class selectors remain in the template (no category-page__ prefix)
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

vi.stubGlobal("useLocalization", () => ({ language: ref("en") }));
vi.stubGlobal("useAuth", () => ({
    user: ref(null),
    isAuthenticated: computed(() => false),
    logout: vi.fn(),
}));
vi.stubGlobal("useCart", () => ({
    cart: ref(null),
    itemCount: computed(() => 0),
}));

const mockRoute = { params: { slug: "pla-category" } };
vi.stubGlobal("useRoute", () => mockRoute);

const mockApi = vi.fn();
vi.stubGlobal("useApi", () => mockApi);
vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

vi.stubGlobal("navigateTo", vi.fn());
vi.stubGlobal("useNuxtApp", () => { throw new Error("outside Nuxt context"); });

const mockCreateError = vi.fn((opts: { statusCode: number; statusMessage?: string }) => {
    const err = new Error(opts.statusMessage ?? String(opts.statusCode));
    (err as unknown as Record<string, unknown>).statusCode = opts.statusCode;
    return err;
});
vi.stubGlobal("createError", mockCreateError);

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

function makeCategoryResponse(overrides: Record<string, unknown> = {}) {
    return {
        data: {
            id: 1,
            name: "PLA Category",
            slug: "pla-category",
            image: "https://example.com/pla.jpg",
            children: [],
            parent: null,
            ...overrides,
        },
    };
}

function makeProductListResponse() {
    return {
        data: [
            { id: 1, name: "PLA Filament", slug: "pla-filament", price: "19.99", images: [] },
        ],
        meta: { current_page: 1, last_page: 3, total: 15, per_page: 15 },
    };
}

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
    CategoryChip: {
        props: ["category", "parentSlug"],
        template: '<span data-testid="subcategory-chip">{{ category.name }}</span>',
    },
    ProductCard: {
        template: '<div class="product-card">{{ product.name }}</div>',
        props: ["product"],
    },
    // Stub Breadcrumb component so we can detect it was rendered
    Breadcrumb: {
        props: ["items"],
        template: '<nav aria-label="breadcrumb" data-testid="breadcrumb-component"><slot /></nav>',
    },
    // Stub Button component so we can detect data-slot="button"
    Button: {
        props: ["variant", "disabled"],
        template: '<button data-slot="button" :disabled="disabled"><slot /></button>',
    },
};

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("Category page [slug].vue — shadcn migration (T4.6)", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockRoute.params.slug = "pla-category";
        mockCreateError.mockClear();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
        vi.resetModules();
    });

    it("renders Breadcrumb component instead of manual nav with arrows", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Should render the Breadcrumb component (stubbed with data-testid)
        expect(wrapper.find('[data-testid="breadcrumb-component"]').exists()).toBe(true);

        // Should NOT contain the arrow separator character "→"
        expect(wrapper.html()).not.toContain("→");
    });

    it("builds breadcrumb items with only current category when no parent", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse({ parent: null }))
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");

        // Use a tracking component to capture props after reactivity updates
        const BreadcrumbTracker = {
            props: ["items"],
            template: `<nav data-testid="breadcrumb-component">
                <span v-for="item in items" :key="item.id" :data-name="item.name" :data-has-url="!!item.url" data-testid="bc-item"></span>
            </nav>`,
        };

        const wrapper = mount(CategoryPage, { global: { stubs: { ...globalStubs, Breadcrumb: BreadcrumbTracker } } });

        await new Promise((r) => setTimeout(r, 50));
        await wrapper.vm.$nextTick();

        const items = wrapper.findAll('[data-testid="bc-item"]');
        expect(items).toHaveLength(1);
        expect(items[0].attributes("data-name")).toBe("PLA Category");
        expect(items[0].attributes("data-has-url")).toBe("false");
    });

    it("builds breadcrumb items with parent category link when parent exists", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse({
                parent: { id: 10, name: "All Filaments", slug: "all-filaments" },
            }))
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");

        const BreadcrumbTracker = {
            props: ["items"],
            template: `<nav data-testid="breadcrumb-component">
                <span v-for="item in items" :key="item.id" :data-name="item.name" :data-url="item.url || ''" data-testid="bc-item"></span>
            </nav>`,
        };

        const wrapper = mount(CategoryPage, { global: { stubs: { ...globalStubs, Breadcrumb: BreadcrumbTracker } } });

        await new Promise((r) => setTimeout(r, 50));
        await wrapper.vm.$nextTick();

        // Two items: parent (with url) + current category (no url)
        const items = wrapper.findAll('[data-testid="bc-item"]');
        expect(items).toHaveLength(2);
        expect(items[0].attributes("data-name")).toBe("All Filaments");
        expect(items[0].attributes("data-url")).toBe("/all-filaments");
        expect(items[1].attributes("data-name")).toBe("PLA Category");
        expect(items[1].attributes("data-url")).toBe("");
    });

    it("pagination uses shadcn Button components (data-slot='button')", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const paginationButtons = wrapper.findAll('[data-slot="button"]');
        expect(paginationButtons.length).toBeGreaterThanOrEqual(2);
    });

    it("first pagination button is disabled on page 1", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const buttons = wrapper.findAll('[data-slot="button"]');
        const prevButton = buttons.find((b) => b.text().toLowerCase().includes("previous"));
        expect(prevButton).toBeDefined();
        expect(prevButton!.attributes("disabled")).toBeDefined();
    });

    it("error state uses data-testid='category-error'", async () => {
        const notFoundError = Object.assign(new Error("Not Found"), { statusCode: 404 });
        mockApi.mockRejectedValueOnce(notFoundError);

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="category-error"]').exists()).toBe(true);
    });

    it("loading state uses data-testid='category-loading'", async () => {
        // Never resolves during the test — stays in loading state
        mockApi.mockReturnValue(new Promise(() => {}));

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        // Before async resolves, loading should be visible
        expect(wrapper.find('[data-testid="category-loading"]').exists()).toBe(true);
    });

    it("product grid section has Tailwind grid classes", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Product grid div should use Tailwind grid classes
        const html = wrapper.html();
        expect(html).toMatch(/class="[^"]*grid[^"]*"/);
    });

    it("has no BEM category-page__ class names remaining in template", async () => {
        mockApi
            .mockResolvedValueOnce(makeCategoryResponse())
            .mockResolvedValueOnce(makeProductListResponse());

        const { default: CategoryPage } = await import("../pages/categories/[slug].vue");
        const wrapper = mount(CategoryPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.html()).not.toContain("category-page__");
    });
});
