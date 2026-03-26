import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed, onMounted } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("onMounted", onMounted);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

const mockApi = vi.fn();
vi.stubGlobal("useApi", () => mockApi);
vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// useProducts mock
const mockFetchProducts = vi.fn();
const mockProducts = ref<
    Array<{ id: number; name: string; slug: string; price: string; images: string[] }>
>([]);
const mockCurrentPage = ref(1);
const mockPageSize = ref(15);
const mockTotalPages = computed(() => 3);

vi.stubGlobal("useProducts", () => ({
    products: mockProducts,
    currentPage: mockCurrentPage,
    pageSize: mockPageSize,
    totalPages: mockTotalPages,
    fetchProducts: mockFetchProducts,
}));

// useCategories mock
const mockFetchCategories = vi.fn();
const mockCategories = ref([
    { id: 1, name: "PLA", slug: "pla", children: [] },
    { id: 2, name: "PETG", slug: "petg", children: [] },
]);

vi.stubGlobal("useCategories", () => ({
    categories: mockCategories,
    fetchCategories: mockFetchCategories,
}));

// Stub useAuth and useCart for Header (used in layout)
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
    ProductCard: { template: '<div class="product-card"></div>', props: ["product"] },
};

// ---------------------------------------------------------------------------
// Homepage category filter UI tests
// ---------------------------------------------------------------------------

describe("Homepage category filter", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockCurrentPage.value = 1;
        mockProducts.value = [];
        mockCategories.value = [
            { id: 1, name: "PLA", slug: "pla", children: [] },
            { id: 2, name: "PETG", slug: "petg", children: [] },
        ];
    });

    it('renders an "All Products" button in the category filter', async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toMatch(/all products?/i);
    });

    it("renders a button for each category", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("PLA");
        expect(wrapper.text()).toContain("PETG");
    });

    it("calls fetchCategories on mount", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));

        expect(mockFetchCategories).toHaveBeenCalled();
    });

    it("calls fetchProducts with category_slug when a category button is clicked", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const buttons = wrapper.findAll("button");
        const plaBtn = buttons.find((b) => b.text().trim() === "PLA");
        expect(plaBtn).toBeDefined();
        await plaBtn!.trigger("click");

        expect(mockFetchProducts).toHaveBeenCalledWith(1, 15, { category_slug: "pla" });
    });

    it("resets to page 1 when a category is selected", async () => {
        mockCurrentPage.value = 3;
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const buttons = wrapper.findAll("button");
        const plaBtn = buttons.find((b) => b.text().trim() === "PLA");
        await plaBtn!.trigger("click");

        // Should always call with page=1 when changing category
        expect(mockFetchProducts).toHaveBeenLastCalledWith(1, 15, { category_slug: "pla" });
    });

    it('calls fetchProducts without filter when "All Products" is clicked', async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // First select a category
        const buttons = wrapper.findAll("button");
        const plaBtn = buttons.find((b) => b.text().trim() === "PLA");
        await plaBtn!.trigger("click");

        // Then clear the filter
        const allBtn = buttons.find((b) => /all products?/i.test(b.text()));
        await allBtn!.trigger("click");

        // Last call should have no category filter
        expect(mockFetchProducts).toHaveBeenLastCalledWith(1, 15);
    });

    it("marks the selected category button with an active class", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const buttons = wrapper.findAll("button");
        const plaBtn = buttons.find((b) => b.text().trim() === "PLA");
        await plaBtn!.trigger("click");
        await wrapper.vm.$nextTick();

        expect(plaBtn!.classes()).toContain("active");
    });

    it("gracefully handles API errors when fetching categories", async () => {
        mockFetchCategories.mockRejectedValueOnce(new Error("Network error"));

        const { default: IndexPage } = await import("../pages/index.vue");
        // Should not throw during mount
        expect(() => mount(IndexPage, { global: { stubs: globalStubs } })).not.toThrow();
        await new Promise((r) => setTimeout(r, 0));
        // Page still renders products area
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        expect(wrapper.find(".homepage__grid").exists()).toBe(true);
    });
});

// ---------------------------------------------------------------------------
// useCategories composable tests
// ---------------------------------------------------------------------------

describe("useCategories composable", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.resetModules();

        // Reset state store
        const stateStore: Record<string, ReturnType<typeof ref>> = {};
        vi.stubGlobal("useState", (key: string, init: () => unknown) => {
            if (!stateStore[key]) {
                stateStore[key] = ref(init());
            }
            return stateStore[key];
        });
    });

    it("fetchCategories calls GET /categories", async () => {
        mockApi.mockResolvedValueOnce({
            data: [{ id: 1, name: "PLA", slug: "pla", children: [] }],
        });

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategories } = useCategories();

        await fetchCategories();

        expect(mockApi).toHaveBeenCalledWith("/categories", expect.anything());
    });

    it("fetchCategories updates categories ref with response data", async () => {
        mockApi.mockResolvedValueOnce({
            data: [
                { id: 1, name: "PLA", slug: "pla", children: [] },
                { id: 2, name: "PETG", slug: "petg", children: [] },
            ],
        });

        const { useCategories } = await import("../composables/useCategories");
        const { categories, fetchCategories } = useCategories();

        await fetchCategories();

        expect(categories.value).toHaveLength(2);
        expect(categories.value[0].slug).toBe("pla");
        expect(categories.value[1].name).toBe("PETG");
    });

    it("fetchCategories sets error state on API failure", async () => {
        mockApi.mockRejectedValueOnce(new Error("Server error"));

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategories, error } = useCategories();

        await fetchCategories();

        expect(error.value).toBeTruthy();
    });

    it("fetchCategories does not throw on API failure", async () => {
        mockApi.mockRejectedValueOnce(new Error("Server error"));

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategories } = useCategories();

        await expect(fetchCategories()).resolves.toBeUndefined();
    });
});
