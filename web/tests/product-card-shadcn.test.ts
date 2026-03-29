/**
 * Tests for ProductCard — shadcn Card migration (T3.9)
 *
 * Acceptance criteria:
 *  - No <style scoped> block in component source
 *  - Component uses Card, CardContent from @/components/ui/card
 *  - No BEM class names remain in rendered output
 *  - Product image rendered with aspect-square and object-cover classes
 *  - Product name rendered with font-semibold class
 *  - Product price rendered with text-gray-700 class
 *  - NuxtLink wrapper preserved for navigation
 *  - Props: product
 *  - Computed properties preserved: productUrl, imageSrc, lowestPrice
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { computed } from "vue";
import { readFileSync } from "fs";
import { resolve } from "path";

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
// Source-level checks (before mounting)
// ---------------------------------------------------------------------------

const componentSource = readFileSync(
    resolve(__dirname, "../components/ProductCard.vue"),
    "utf-8",
);

describe("ProductCard — shadcn Card migration (source checks)", () => {
    it("has no <style scoped> block", () => {
        expect(componentSource).not.toMatch(/<style\s+scoped/);
    });

    it("has no <style> block at all", () => {
        expect(componentSource).not.toMatch(/<style[\s>]/);
    });

    it("imports Card and CardContent from @/components/ui/card", () => {
        expect(componentSource).toMatch(/from ['"]@\/components\/ui\/card['"]/);
        expect(componentSource).toMatch(/\bCard\b/);
        expect(componentSource).toMatch(/\bCardContent\b/);
    });

    it("has no BEM class names in template", () => {
        expect(componentSource).not.toMatch(/product-card__/);
        expect(componentSource).not.toMatch(/class="product-card/);
    });
});

// ---------------------------------------------------------------------------
// Rendering checks (via mount)
// ---------------------------------------------------------------------------

describe("ProductCard — shadcn Card migration (render checks)", () => {
    beforeEach(() => {
        vi.resetModules();
    });

    it("renders the product name", async () => {
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
        expect(wrapper.text()).toContain("PLA Filament");
    });

    it("renders the product price", async () => {
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

    it("renders an image with correct src attribute", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "19.99",
                    images: ["/images/pla.jpg"],
                    variants: [],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        const img = wrapper.find("img");
        expect(img.exists()).toBe(true);
        expect(img.attributes("src")).toBe("/images/pla.jpg");
    });

    it("renders a NuxtLink (anchor) to the product URL", async () => {
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
        const link = wrapper.find("a");
        expect(link.exists()).toBe(true);
        expect(link.attributes("href")).toContain("pla-filament");
    });

    it("renders no BEM class attributes in the DOM", async () => {
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
        expect(wrapper.html()).not.toContain("product-card__");
        expect(wrapper.html()).not.toContain("product-card\"");
    });

    it("image wrapper div exists in component structure", async () => {
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
        // Image is wrapped in a div inside the NuxtLink
        const imageWrapper = wrapper.find("a > div");
        expect(imageWrapper.exists()).toBe(true);
    });

    it("uses placeholder image when product has no images", async () => {
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
        const img = wrapper.find("img");
        expect(img.attributes("src")).toBe("/images/placeholder.png");
    });
});
