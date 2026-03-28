/**
 * Tests for ProductCard — language-aware slug links (T4.7)
 *
 * Acceptance criteria:
 *  - ProductCard builds product links using buildProductUrl() with current language
 *  - Links use full multi-segment slug URL: /{category-slug}/{subcategory-slug}/{product-slug}
 *  - Links update reactively when the language changes
 *  - Falls back to /products/{slug} when category info is missing or buildProductUrl returns null
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { computed, ref } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

// Shared reactive language ref — tests can mutate this to simulate language changes.
const mockLanguage = ref("en");

vi.stubGlobal("useState", <T>(_key: string, init?: () => T) => {
    if (_key === "localization.language") return mockLanguage;
    return ref<T>(init ? init() : (undefined as T));
});

vi.stubGlobal("useLocalization", () => ({
    language: mockLanguage,
}));

// ---------------------------------------------------------------------------
// Component stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: {
        template: '<a :href="to"><slot /></a>',
        props: ["to"],
    },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

type SlugRecord = { locale: string; slug: string };
type CategoryEntity = { id: number; slugs: SlugRecord[] };

function makeProduct(overrides: Record<string, unknown> = {}) {
    return {
        id: 1,
        name: "PLA Red",
        slug: "pla-red",
        price: "19.99",
        images: [],
        variants: [],
        attributes: {},
        ...overrides,
    };
}

function makeCategory(id: number, slugs: SlugRecord[]): CategoryEntity {
    return { id, slugs };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("ProductCard — language-aware slug links", () => {
    beforeEach(() => {
        mockLanguage.value = "en";
        vi.resetModules();
    });

    it("builds a 3-segment URL when product has categories[0], categories[1] and slugs", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const product = makeProduct({
            slugs: [{ locale: "en", slug: "pla-red" }],
            categories: [
                makeCategory(10, [{ locale: "en", slug: "filaments" }]),
                makeCategory(20, [{ locale: "en", slug: "pla" }]),
            ],
        });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/filaments/pla/pla-red");
    });

    it("uses the current language to select the correct slugs", async () => {
        mockLanguage.value = "es";
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const product = makeProduct({
            slugs: [
                { locale: "en", slug: "pla-red" },
                { locale: "es", slug: "pla-rojo" },
            ],
            categories: [
                makeCategory(10, [
                    { locale: "en", slug: "filaments" },
                    { locale: "es", slug: "filamentos" },
                ]),
                makeCategory(20, [
                    { locale: "en", slug: "pla" },
                    { locale: "es", slug: "pla-es" },
                ]),
            ],
        });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/filamentos/pla-es/pla-rojo");
    });

    it("URL updates reactively when language changes", async () => {
        mockLanguage.value = "en";
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const product = makeProduct({
            slugs: [
                { locale: "en", slug: "pla-red" },
                { locale: "es", slug: "pla-rojo" },
            ],
            categories: [
                makeCategory(10, [
                    { locale: "en", slug: "filaments" },
                    { locale: "es", slug: "filamentos" },
                ]),
                makeCategory(20, [
                    { locale: "en", slug: "pla" },
                    { locale: "es", slug: "pla-es" },
                ]),
            ],
        });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/filaments/pla/pla-red");

        mockLanguage.value = "es";
        await wrapper.vm.$nextTick();

        expect(wrapper.find("a").attributes("href")).toBe("/filamentos/pla-es/pla-rojo");
    });

    it("falls back to /products/{slug} when product has no categories field", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const product = makeProduct({ slug: "pla-red" });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/products/pla-red");
    });

    it("falls back to /products/{slug} when categories has only one item (no subcategory)", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const product = makeProduct({
            slug: "pla-red",
            slugs: [{ locale: "en", slug: "pla-red" }],
            categories: [makeCategory(10, [{ locale: "en", slug: "filaments" }])],
        });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/products/pla-red");
    });

    it("falls back to /products/{slug} when categories is an empty array", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const product = makeProduct({
            slug: "pla-red",
            categories: [],
        });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/products/pla-red");
    });

    it("falls back to /products/{slug} when buildProductUrl returns null (product has no slugs)", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const product = makeProduct({
            slug: "pla-red",
            slugs: [], // empty slugs → buildProductUrl returns null
            categories: [
                makeCategory(10, [{ locale: "en", slug: "filaments" }]),
                makeCategory(20, [{ locale: "en", slug: "pla" }]),
            ],
        });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/products/pla-red");
    });

    it("falls back to English slug when requested language has no translation", async () => {
        mockLanguage.value = "fr";
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        // Only English slugs available — should fall back to English
        const product = makeProduct({
            slugs: [{ locale: "en", slug: "pla-red" }],
            categories: [
                makeCategory(10, [{ locale: "en", slug: "filaments" }]),
                makeCategory(20, [{ locale: "en", slug: "pla" }]),
            ],
        });

        const wrapper = mount(ProductCard, {
            props: { product },
            global: { stubs: globalStubs },
        });

        // Falls back to English (default locale)
        expect(wrapper.find("a").attributes("href")).toBe("/filaments/pla/pla-red");
    });
});
