/**
 * Tests for categories/index.vue — shadcn Card migration (T4.5)
 *
 * Acceptance criteria:
 *  - No <style> or <style scoped> block in component source
 *  - No BEM class names (categories-page, categories-grid, category-card) remain
 *  - Uses Card and CardContent from @/components/ui/card
 *  - Responsive grid via Tailwind: grid-cols-1, sm:grid-cols-2, lg:grid-cols-3
 *  - Container uses Tailwind: max-w-5xl mx-auto
 *  - Error state uses text-red-600 (or Tailwind error colour)
 *  - Loading state uses text-gray-500
 *  - NuxtLink routing preserved (links to /<slug>)
 *  - Subcategories section preserved with Tailwind flex utilities
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed, onMounted } from "vue";
import { readFileSync } from "fs";
import { resolve } from "path";

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
        template:
            '<a :href="parentSlug ? `/${parentSlug}/${category.slug}` : `/${category.slug}`">{{ category.name }}</a>',
        props: ["category", "parentSlug"],
    },
    Card: {
        template: '<div class="card"><slot /></div>',
    },
    CardContent: {
        template: '<div class="card-content"><slot /></div>',
    },
};

// ---------------------------------------------------------------------------
// Source-level checks
// ---------------------------------------------------------------------------

const componentSource = readFileSync(
    resolve(__dirname, "../pages/categories/index.vue"),
    "utf-8",
);

/** BEM classes that must NOT appear as CSS class attributes (class="...") */
const BEM_CSS_CLASSES = [
    "categories-page",
    "categories-grid",
    "category-card__subcategories",
    "category-card__subcategory-chip",
];

describe("categories/index.vue — shadcn migration (source checks)", () => {
    it("has no <style scoped> block", () => {
        expect(componentSource).not.toMatch(/<style\s+scoped/);
    });

    it("has no <style> block at all", () => {
        expect(componentSource).not.toMatch(/<style[\s>]/);
    });

    it("imports Card and CardContent from @/components/ui/card", () => {
        expect(componentSource).toMatch(/from ['"]@\/components\/ui\/card['"]/);
        expect(componentSource).toContain("Card");
        expect(componentSource).toContain("CardContent");
    });

    it("has no BEM class names used as CSS class attributes in source", () => {
        for (const cls of BEM_CSS_CLASSES) {
            // Check class="..." patterns specifically
            expect(componentSource).not.toMatch(new RegExp(`class="[^"]*${cls}[^"]*"`));
        }
        // category-card as a standalone class (not as testid value)
        expect(componentSource).not.toMatch(/class="[^"]*\bcategory-card\b[^"]*"/);
    });

    it("uses Tailwind grid utilities for responsive layout", () => {
        expect(componentSource).toContain("grid-cols-1");
        expect(componentSource).toContain("grid-cols-2");
        expect(componentSource).toContain("grid-cols-3");
    });

    it("uses Tailwind container utilities (max-w)", () => {
        expect(componentSource).toMatch(/max-w-\w+/);
    });

    it("uses Tailwind typography for heading (font-bold)", () => {
        expect(componentSource).toContain("font-bold");
    });

    it("uses Tailwind text-red colour for error state", () => {
        expect(componentSource).toMatch(/text-red-\d+/);
    });

    it("uses Tailwind text-gray colour for loading state", () => {
        expect(componentSource).toMatch(/text-gray-\d+/);
    });

    it("uses hover:shadow-lg for card hover effect", () => {
        expect(componentSource).toContain("hover:shadow-lg");
    });
});

// ---------------------------------------------------------------------------
// Rendering checks (via mount)
// ---------------------------------------------------------------------------

describe("categories/index.vue — shadcn migration (render checks)", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
        vi.resetModules();
    });

    it("rendered HTML has no BEM class names", async () => {
        mockApi.mockResolvedValueOnce({
            data: [{ id: 1, name: "PLA", slug: "pla", children: [] }],
        });

        const { default: CategoriesPage } = await import(
            "../pages/categories/index.vue"
        );
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        // Check BEM class names are not used as CSS classes (data-testid values are OK)
        for (const cls of BEM_CSS_CLASSES) {
            expect(html).not.toMatch(new RegExp(`class="[^"]*${cls}[^"]*"`));
        }
        // category-card must not appear as a CSS class
        expect(html).not.toMatch(/class="[^"]*\bcategory-card\b[^"]*"/);
    });

    it("renders category names after data loads", async () => {
        mockApi.mockResolvedValueOnce({
            data: [
                { id: 1, name: "PLA", slug: "pla", children: [] },
                { id: 2, name: "PETG", slug: "petg", children: [] },
            ],
        });

        const { default: CategoriesPage } = await import(
            "../pages/categories/index.vue"
        );
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("PLA");
        expect(wrapper.text()).toContain("PETG");
    });

    it("renders NuxtLink to category slug", async () => {
        mockApi.mockResolvedValueOnce({
            data: [{ id: 1, name: "PLA", slug: "pla", children: [] }],
        });

        const { default: CategoriesPage } = await import(
            "../pages/categories/index.vue"
        );
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const links = wrapper.findAll("a");
        const hrefs = links.map((l) => l.attributes("href"));
        expect(hrefs).toContain("/pla");
    });

    it("shows loading state with Tailwind text-gray class", async () => {
        mockApi.mockReturnValueOnce(new Promise(() => {}));

        const { default: CategoriesPage } = await import(
            "../pages/categories/index.vue"
        );
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        const html = wrapper.html();
        expect(html).toMatch(/text-gray-\d+/);
        expect(wrapper.text().toLowerCase()).toContain("loading");
    });

    it("shows error state with Tailwind text-red class", async () => {
        mockApi.mockRejectedValueOnce(new Error("Network error"));

        const { default: CategoriesPage } = await import(
            "../pages/categories/index.vue"
        );
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        expect(html).toMatch(/text-red-\d+/);
    });

    it("renders subcategory chips inside subcategories section", async () => {
        mockApi.mockResolvedValueOnce({
            data: [
                {
                    id: 1,
                    name: "PLA",
                    slug: "pla",
                    children: [
                        { id: 10, name: "PLA Silk", slug: "pla-silk", children: [] },
                    ],
                },
            ],
        });

        const { default: CategoriesPage } = await import(
            "../pages/categories/index.vue"
        );
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const subcatSection = wrapper.find('[data-testid="subcategories"]');
        expect(subcatSection.exists()).toBe(true);
        expect(subcatSection.text()).toContain("PLA Silk");
    });

    it("subcategories section has Tailwind flex classes", async () => {
        mockApi.mockResolvedValueOnce({
            data: [
                {
                    id: 1,
                    name: "PLA",
                    slug: "pla",
                    children: [
                        { id: 10, name: "PLA Silk", slug: "pla-silk", children: [] },
                    ],
                },
            ],
        });

        const { default: CategoriesPage } = await import(
            "../pages/categories/index.vue"
        );
        const wrapper = mount(CategoriesPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const subcatSection = wrapper.find('[data-testid="subcategories"]');
        expect(subcatSection.exists()).toBe(true);
        expect(subcatSection.classes()).toContain("flex");
    });
});
