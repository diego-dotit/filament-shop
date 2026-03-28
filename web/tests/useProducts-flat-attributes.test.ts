/**
 * Tests for ProductResource.attributes flat shape and ProductCategoryEntity.slug field.
 *
 * Acceptance criteria:
 *  - ProductResource.attributes is Record<string, string> (flat key-value object, NOT an array)
 *  - ProductResource interface is exported from useProducts.ts
 *  - ProductCategoryEntity has a flat slug: string field (not slugs: SlugRecord[])
 *  - Components can iterate product.attributes using Object.entries / v-for (value, key)
 *  - No breaking changes to other ProductResource fields
 */

import { describe, it, expect } from "vitest";
import type { ProductResource, ProductCategoryEntity } from "../composables/useProducts";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeProductWithFlatAttributes(): ProductResource {
    return {
        id: 1,
        name: "PLA Filament",
        slug: "pla-filament",
        description: "High quality PLA filament",
        price: "19.99",
        images: ["https://example.com/pla.jpg"],
        variants: [],
        attributes: {
            material: "PLA",
            diameter: "1.75mm",
            weight: "1kg",
        },
    };
}

function makeProductCategoryEntity(): ProductCategoryEntity {
    return {
        id: 5,
        name: "Filaments",
        slug: "filaments",
    };
}

// ---------------------------------------------------------------------------
// ProductResource.attributes — flat Record<string, string>
// ---------------------------------------------------------------------------

describe("ProductResource.attributes is Record<string, string>", () => {
    it("attributes is a plain object (not an array)", () => {
        const product = makeProductWithFlatAttributes();

        expect(Array.isArray(product.attributes)).toBe(false);
        expect(typeof product.attributes).toBe("object");
    });

    it("attributes values are accessible by key", () => {
        const product = makeProductWithFlatAttributes();

        expect(product.attributes["material"]).toBe("PLA");
        expect(product.attributes["diameter"]).toBe("1.75mm");
        expect(product.attributes["weight"]).toBe("1kg");
    });

    it("can iterate attributes with Object.entries (matches v-for (value, key) pattern)", () => {
        const product = makeProductWithFlatAttributes();

        const entries = Object.entries(product.attributes);

        expect(entries).toHaveLength(3);
        expect(entries).toContainEqual(["material", "PLA"]);
        expect(entries).toContainEqual(["diameter", "1.75mm"]);
        expect(entries).toContainEqual(["weight", "1kg"]);
    });

    it("can iterate attributes with Object.keys", () => {
        const product = makeProductWithFlatAttributes();

        const keys = Object.keys(product.attributes);

        expect(keys).toContain("material");
        expect(keys).toContain("diameter");
        expect(keys).toContain("weight");
    });

    it("attributes can be an empty object when product has no specifications", () => {
        const product: ProductResource = {
            id: 2,
            name: "Basic Product",
            slug: "basic-product",
            price: "9.99",
            images: [],
            variants: [],
            attributes: {},
        };

        expect(Object.keys(product.attributes)).toHaveLength(0);
    });
});

// ---------------------------------------------------------------------------
// ProductResource — other fields not broken
// ---------------------------------------------------------------------------

describe("ProductResource other fields remain intact", () => {
    it("product has required id, name, slug, price, images, variants, attributes fields", () => {
        const product = makeProductWithFlatAttributes();

        expect(product).toHaveProperty("id");
        expect(product).toHaveProperty("name");
        expect(product).toHaveProperty("slug");
        expect(product).toHaveProperty("price");
        expect(product).toHaveProperty("images");
        expect(product).toHaveProperty("variants");
        expect(product).toHaveProperty("attributes");
    });

    it("product optional fields (locale, slugs, categories) are allowed", () => {
        const product: ProductResource = {
            id: 3,
            name: "PLA Filament ES",
            slug: "filamento-pla",
            price: "19.99",
            images: [],
            variants: [],
            attributes: { material: "PLA" },
            locale: "es",
            slugs: [
                { locale: "en", slug: "pla-filament" },
                { locale: "es", slug: "filamento-pla" },
            ],
            categories: [makeProductCategoryEntity()],
        };

        expect(product.locale).toBe("es");
        expect(product.slugs).toHaveLength(2);
        expect(product.categories).toHaveLength(1);
    });
});

// ---------------------------------------------------------------------------
// ProductCategoryEntity — flat slug: string (not slugs: SlugRecord[])
// ---------------------------------------------------------------------------

describe("ProductCategoryEntity has flat slug: string field", () => {
    it("category entity can be constructed with slug: string", () => {
        const category = makeProductCategoryEntity();

        expect(category.slug).toBe("filaments");
        expect(typeof category.slug).toBe("string");
    });

    it("category entity has required id, name, slug fields", () => {
        const category = makeProductCategoryEntity();

        expect(category).toHaveProperty("id", 5);
        expect(category).toHaveProperty("name", "Filaments");
        expect(category).toHaveProperty("slug", "filaments");
    });

    it("category slug can be used directly in URL construction", () => {
        const category = makeProductCategoryEntity();

        // This mirrors the breadcrumb URL pattern in [slug].vue
        const url = `/categories/${category.slug}`;

        expect(url).toBe("/categories/filaments");
    });

    it("product categories array works with flat slug", () => {
        const product: ProductResource = {
            id: 1,
            name: "PLA Filament",
            slug: "pla-filament",
            price: "19.99",
            images: [],
            variants: [],
            attributes: {},
            categories: [
                { id: 1, name: "Plastics", slug: "plastics" },
                { id: 5, name: "Filaments", slug: "filaments" },
            ],
        };

        const categorySlugs = (product.categories ?? []).map((c) => c.slug);
        expect(categorySlugs).toEqual(["plastics", "filaments"]);
    });
});
