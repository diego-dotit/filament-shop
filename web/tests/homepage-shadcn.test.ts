/**
 * Tests for index.vue shadcn migration (T4.4)
 * Verifies:
 * - No <style> or <style scoped> block in component source
 * - No BEM/custom CSS class names in source (homepage, filter-btn, pagination__)
 * - Category filter buttons use shadcn Button component (data-slot="button")
 * - Tailwind utility classes applied for layout
 * - All Vue logic preserved: category filtering, pagination, product fetching
 * - Pagination buttons have proper disabled states
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed, onMounted } from "vue";
import * as fs from "node:fs";
import * as path from "node:path";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE importing any component under test
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("onMounted", onMounted);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("definePageMeta", vi.fn());
vi.stubGlobal("navigateTo", vi.fn());

const mockFetchProducts = vi.fn().mockResolvedValue(undefined);
const mockProducts = ref([
    {
        id: 1,
        name: "PLA Filament",
        slug: "pla-filament",
        price: "19.99",
        images: ["https://example.com/pla.jpg"],
        variants: [],
        attributes: {},
    },
    {
        id: 2,
        name: "PETG Filament",
        slug: "petg-filament",
        price: "24.99",
        images: [],
        variants: [],
        attributes: {},
    },
]);
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

const mockFetchCategories = vi.fn().mockResolvedValue(undefined);
const mockCategories = ref([
    { id: 1, name: "PLA", slug: "pla" },
    { id: 2, name: "PETG", slug: "petg" },
]);

vi.stubGlobal("useCategories", () => ({
    categories: mockCategories,
    fetchCategories: mockFetchCategories,
    error: ref(null),
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
// Constants
// ---------------------------------------------------------------------------

const BEM_CLASSES = [
    "homepage",
    "homepage__title",
    "homepage__filters",
    "homepage__grid",
    "filter-btn",
    "pagination__btn",
    "pagination__info",
];

const globalStubs = {
    NuxtLink: {
        template: '<a :href="to"><slot /></a>',
        props: ["to"],
    },
    ProductCard: {
        template: '<div class="product-card-stub">{{ product.name }}</div>',
        props: ["product"],
    },
};

// ---------------------------------------------------------------------------
// Source-level tests
// ---------------------------------------------------------------------------

describe("index.vue shadcn migration — source checks", () => {
    it("source file has no <style> block", () => {
        const filePath = path.resolve(__dirname, "../pages/index.vue");
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no BEM/legacy CSS class names", () => {
        const filePath = path.resolve(__dirname, "../pages/index.vue");
        const source = fs.readFileSync(filePath, "utf-8");
        for (const cls of BEM_CLASSES) {
            expect(source, `Expected source NOT to contain "${cls}"`).not.toContain(`"${cls}"`);
        }
    });

    it("source file imports Button from shadcn-vue", () => {
        const filePath = path.resolve(__dirname, "../pages/index.vue");
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/button");
    });

    it("source file uses Tailwind grid classes for product grid", () => {
        const filePath = path.resolve(__dirname, "../pages/index.vue");
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("grid");
        expect(source).toContain("gap-");
    });

    it("source file uses Tailwind flex classes for filter area", () => {
        const filePath = path.resolve(__dirname, "../pages/index.vue");
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("flex");
        expect(source).toContain("flex-wrap");
    });
});

// ---------------------------------------------------------------------------
// Rendered HTML tests
// ---------------------------------------------------------------------------

describe("index.vue shadcn migration — rendered HTML", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockCurrentPage.value = 1;
    });

    it("rendered HTML has no BEM/legacy class names", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const html = wrapper.html();
        for (const cls of BEM_CLASSES) {
            expect(html, `Expected rendered HTML NOT to contain "${cls}"`).not.toContain(cls);
        }
    });

    it("filter buttons render as shadcn Button (data-slot=button)", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const buttons = wrapper.findAll('[data-slot="button"]');
        // At minimum: "All Products" + 2 categories + Previous + Next = 5 buttons
        expect(buttons.length).toBeGreaterThanOrEqual(5);
    });

    it("active filter button has variant='default'", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        // "All Products" should be active (default variant) at start
        const allButton = wrapper
            .findAll('[data-slot="button"]')
            .find((b) => b.text().includes("All"));
        expect(allButton).toBeDefined();
        expect(allButton?.attributes("data-variant")).toBe("default");
    });

    it("inactive filter buttons have variant='outline'", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const categoryButtons = wrapper
            .findAll('[data-slot="button"]')
            .filter((b) => b.text() === "PLA" || b.text() === "PETG");
        expect(categoryButtons.length).toBe(2);
        for (const btn of categoryButtons) {
            expect(btn.attributes("data-variant")).toBe("outline");
        }
    });

    it("Previous button is disabled on first page", async () => {
        mockCurrentPage.value = 1;
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const buttons = wrapper.findAll("button");
        const prevBtn = buttons.find((b) => /prev(ious)?/i.test(b.text()));
        expect(prevBtn?.attributes("disabled")).toBeDefined();
    });

    it("Next button is disabled on last page", async () => {
        mockCurrentPage.value = 3;
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const buttons = wrapper.findAll("button");
        const nextBtn = buttons.find((b) => /next/i.test(b.text()));
        expect(nextBtn?.attributes("disabled")).toBeDefined();
    });

    it("displays page info text (Page X of Y)", async () => {
        mockCurrentPage.value = 2;
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        expect(wrapper.text()).toMatch(/page\s+2\s+of\s+3/i);
    });

    it("renders product cards for each product", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const cards = wrapper.findAll(".product-card-stub");
        expect(cards).toHaveLength(2);
    });

    it("clicking a category button calls fetchProducts with category slug", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const categoryButtons = wrapper
            .findAll('[data-slot="button"]')
            .filter((b) => b.text() === "PLA");
        expect(categoryButtons.length).toBe(1);
        await categoryButtons[0].trigger("click");
        expect(mockFetchProducts).toHaveBeenCalledWith(1, 15, { category_slug: "pla" });
    });

    it("clicking 'All Products' clears category filter", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const allButton = wrapper
            .findAll('[data-slot="button"]')
            .find((b) => b.text().includes("All"));
        await allButton?.trigger("click");
        expect(mockFetchProducts).toHaveBeenCalledWith(1, 15);
    });

    it("clicking Next button calls fetchProducts with page+1", async () => {
        mockCurrentPage.value = 1;
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        const buttons = wrapper.findAll("button");
        const nextBtn = buttons.find((b) => /next/i.test(b.text()));
        await nextBtn?.trigger("click");
        expect(mockFetchProducts).toHaveBeenCalledWith(2, 15);
    });
});
