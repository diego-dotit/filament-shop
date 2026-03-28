/**
 * Tests for the ProductVariantResource.attributes type definition.
 *
 * Acceptance criteria:
 *  - AttributeResource is exported from useProducts.ts
 *  - ProductVariantResource.attributes is AttributeResource[] (array of {name, value})
 *  - The shape matches what AttributeResource.php returns via ::collection()
 */

import { describe, it, expect } from "vitest";
import type { AttributeResource, ProductVariantResource } from "../composables/useProducts";

// ---------------------------------------------------------------------------
// Helper: build a ProductVariantResource using the correct API shape
// ---------------------------------------------------------------------------

function makeVariantWithArrayAttributes(): ProductVariantResource {
    return {
        id: 10,
        sku: "PLA-RED-1KG",
        price: "19.99",
        attributes: [
            { name: "color", value: "Red" },
            { name: "weight", value: "1kg" },
        ],
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("AttributeResource type definition", () => {
    it("AttributeResource has name and value string fields", () => {
        // TypeScript type annotation enforces shape; runtime validates the object
        const attr: AttributeResource = { name: "color", value: "Red" };

        expect(attr.name).toBe("color");
        expect(attr.value).toBe("Red");
    });

    it("AttributeResource object with different name/value pairs is valid", () => {
        const attrs: AttributeResource[] = [
            { name: "weight", value: "1kg" },
            { name: "material", value: "PLA" },
        ];

        expect(attrs).toHaveLength(2);
        expect(attrs[0].name).toBe("weight");
        expect(attrs[1].value).toBe("PLA");
    });
});

describe("ProductVariantResource.attributes is AttributeResource[]", () => {
    it("variant attributes is an array (not a Record/plain object)", () => {
        const variant = makeVariantWithArrayAttributes();

        expect(Array.isArray(variant.attributes)).toBe(true);
    });

    it("variant attributes array items each have name and value fields", () => {
        const variant = makeVariantWithArrayAttributes();

        expect(variant.attributes).toHaveLength(2);

        const first = variant.attributes[0];
        expect(first).toHaveProperty("name");
        expect(first).toHaveProperty("value");
        expect(first.name).toBe("color");
        expect(first.value).toBe("Red");
    });

    it("variant attributes matches the shape returned by AttributeResource.php collection", () => {
        // API returns: [{"name":"color","value":"Red"},{"name":"weight","value":"1kg"}]
        // which is AttributeResource::collection($variant->attributes)
        const apiResponseAttributes: AttributeResource[] = [
            { name: "color", value: "Red" },
            { name: "weight", value: "1kg" },
        ];

        const variant: ProductVariantResource = {
            id: 10,
            sku: "PLA-RED-1KG",
            price: "19.99",
            attributes: apiResponseAttributes,
        };

        expect(variant.attributes[0].name).toBe("color");
        expect(variant.attributes[1].name).toBe("weight");
    });

    it("can iterate over variant attributes to build a display map", () => {
        const variant = makeVariantWithArrayAttributes();

        // Common consumption pattern: reduce array to display map
        const displayMap = variant.attributes.reduce<Record<string, string>>(
            (acc, attr) => ({ ...acc, [attr.name]: attr.value }),
            {}
        );

        expect(displayMap["color"]).toBe("Red");
        expect(displayMap["weight"]).toBe("1kg");
    });
});
