import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed, onMounted } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("onMounted", onMounted);
vi.stubGlobal("ref", ref);

const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

const mockApi = vi.fn();
vi.stubGlobal("useApi", () => mockApi);
vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

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
// Shared stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: {
        template: '<a :href="to"><slot /></a>',
        props: ["to"],
    },
    CategoryChip: {
        template: '<a :href="parentSlug ? \`/${parentSlug}/${category.slug}\` : \`/${category.slug}\`">{{ category.name }}</a>',
        props: ["category", "parentSlug"],
    },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeFlatCategoriesResponse() {
    return {
        data: [
            { id: 1, name: "PLA", slug: "pla", image: null, children: [] },
            { id: 2, name: "PETG", slug: "petg", image: null, children: [] },
        ],
    };
}

function makeCategoriesWithChildrenResponse() {
    return {
        data: [
            {
                id: 1,
                name: "PLA",
                slug: "pla",
                image: null,
                children: [
                    { id: 10, name: "PLA Silk", slug: "pla-silk", image: null, children: [] },
                    { id: 11, name: "PLA Matte", slug: "pla-matte", image: null, children: [] },
                ],
            },
            { id: 2, name: "PETG", slug: "petg", image: null, children: [] },
        ],
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("Categories overview page (index.vue)", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
        vi.resetModules();
    });

    it("renders all top-level category names", async () => {
        mockApi.mockResolvedValueOnce(makeFlatCategoriesResponse());

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("PLA");
        expect(wrapper.text()).toContain("PETG");
    });

    it("renders a link to each top-level category detail page", async () => {
        mockApi.mockResolvedValueOnce(makeFlatCategoriesResponse());

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const links = wrapper.findAll("a");
        const hrefs = links.map((l) => l.attributes("href"));
        expect(hrefs).toContain("/pla");
        expect(hrefs).toContain("/petg");
    });

    it("does not render subcategories when category has no children", async () => {
        mockApi.mockResolvedValueOnce(makeFlatCategoriesResponse());

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="subcategories"]').exists()).toBe(false);
    });

    it("renders subcategory names nested under their parent when children exist", async () => {
        mockApi.mockResolvedValueOnce(makeCategoriesWithChildrenResponse());

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("PLA Silk");
        expect(wrapper.text()).toContain("PLA Matte");
    });

    it("renders a subcategories section for categories with children", async () => {
        mockApi.mockResolvedValueOnce(makeCategoriesWithChildrenResponse());

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="subcategories"]').exists()).toBe(true);
    });

    it("renders a link for each subcategory navigating to /[parent-slug]/[child-slug]", async () => {
        mockApi.mockResolvedValueOnce(makeCategoriesWithChildrenResponse());

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const links = wrapper.findAll("a");
        const hrefs = links.map((l) => l.attributes("href"));
        expect(hrefs).toContain("/pla/pla-silk");
        expect(hrefs).toContain("/pla/pla-matte");
    });

    it("subcategory links are nested inside the parent category card", async () => {
        mockApi.mockResolvedValueOnce(makeCategoriesWithChildrenResponse());

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const parentCard = wrapper.find('[data-testid="category-card"]');
        expect(parentCard.exists()).toBe(true);

        const subcatSection = parentCard.find('[data-testid="subcategories"]');
        expect(subcatSection.exists()).toBe(true);

        const subcatLinks = subcatSection.findAll("a");
        expect(subcatLinks.length).toBeGreaterThanOrEqual(2);
    });

    it("shows loading state while fetching", async () => {
        // Never resolve to keep it in loading state
        mockApi.mockReturnValueOnce(new Promise(() => {}));

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        expect(wrapper.text().toLowerCase()).toContain("loading");
    });

    it("shows empty state when no categories returned", async () => {
        mockApi.mockResolvedValueOnce({ data: [] });

        const { default: CategoriesPage } = await import("../pages/categories/index.vue");
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text().toLowerCase()).toContain("no categories");
    });
});
