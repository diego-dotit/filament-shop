import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

const globalStubs = {
    NuxtLink: {
        template: '<a :href="to"><slot /></a>',
        props: ["to"],
    },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeVariant(overrides: Record<string, unknown> = {}) {
    return {
        id: 1,
        sku: "SKU-1",
        price: "19.99",
        regular_price: "19.99",
        special_price: null,
        attributes: {},
        ...overrides,
    };
}

// ---------------------------------------------------------------------------
// Tests — ProductCard variant pricing
// ---------------------------------------------------------------------------

describe("ProductCard — lowest variant price", () => {
    beforeEach(() => {
        vi.resetModules();
    });

    it("displays the regular_price when no special_price exists", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "99.99",
                    images: [],
                    variants: [makeVariant({ regular_price: "29.99", special_price: null })],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("29.99");
    });

    it("uses special_price instead of regular_price when special_price exists", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "99.99",
                    images: [],
                    variants: [makeVariant({ regular_price: "29.99", special_price: "19.99" })],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("19.99");
        expect(wrapper.text()).not.toContain("29.99");
    });

    it("displays the lowest price across multiple variants", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "99.99",
                    images: [],
                    variants: [
                        makeVariant({ id: 1, regular_price: "35.00", special_price: null }),
                        makeVariant({ id: 2, regular_price: "29.99", special_price: null }),
                        makeVariant({ id: 3, regular_price: "40.00", special_price: null }),
                    ],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("29.99");
    });

    it("picks lowest effective price when some variants have special_price", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "99.99",
                    images: [],
                    variants: [
                        makeVariant({ id: 1, regular_price: "35.00", special_price: "25.00" }),
                        makeVariant({ id: 2, regular_price: "20.00", special_price: null }),
                    ],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        // special_price 25.00 vs regular_price 20.00 → lowest is 20.00
        expect(wrapper.text()).toContain("20.00");
        expect(wrapper.text()).not.toContain("25.00");
        expect(wrapper.text()).not.toContain("35.00");
    });

    it("falls back to product.price when variants array is empty", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "19.99",
                    images: [],
                    variants: [],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("19.99");
    });

    it("handles zero price correctly and displays it", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "Free Sample",
                    slug: "free-sample",
                    price: "0.00",
                    images: [],
                    variants: [makeVariant({ regular_price: "0.00", special_price: null })],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("0.00");
    });

    it("handles variants with null/missing prices gracefully", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "19.99",
                    images: [],
                    variants: [
                        makeVariant({ regular_price: null, special_price: null }),
                        makeVariant({ id: 2, regular_price: "15.00", special_price: null }),
                    ],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        // Should still show the valid price and not crash
        expect(wrapper.text()).toContain("15.00");
    });
});
