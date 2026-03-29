/**
 * Tests for fetchProductBySlug() in useProducts composable.
 *
 * Acceptance criteria:
 *  - fetchProductBySlug(slug) calls GET /products/{slug}
 *  - Returns ProductResource with `locale` and `slugs` fields
 *  - `slugs` is Array<{locale: string; slug: string}>
 *  - `locale` is the language of the resolved slug
 *  - Rejects with error on non-404 errors
 *  - Returns null on 404 (existing contract)
 *  - ProductResource interface includes locale?: string and slugs?: SlugRecord[]
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";
import type { ProductResource } from "../composables/useProducts";
import type { SlugRecord } from "../composables/useSlug";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE importing the composable under test.
// ---------------------------------------------------------------------------

const mockApi = vi.fn();

vi.stubGlobal("useApi", () => mockApi);

vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context");
});

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

type AnyRef = ReturnType<typeof ref>;
const stateStore: Record<string, AnyRef> = {};

vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

vi.stubGlobal("computed", computed);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeProductResponse(overrides: Partial<ProductResource> = {}): { data: ProductResource } {
    return {
        data: {
            id: 1,
            name: "PLA Filament",
            slug: "pla-filament",
            price: "19.99",
            images: [],
            variants: [],
            attributes: {},
            locale: "en",
            slugs: [
                { locale: "en", slug: "pla-filament" },
                { locale: "es", slug: "filamento-pla" },
            ],
            ...overrides,
        },
    };
}

// ---------------------------------------------------------------------------
// ProductResource interface — locale and slugs fields
// ---------------------------------------------------------------------------

describe("ProductResource interface — locale and slugs fields", () => {
    it("accepts a ProductResource with locale field", () => {
        const product: ProductResource = {
            id: 1,
            name: "PLA",
            slug: "pla-filament",
            price: "9.99",
            images: [],
            variants: [],
            attributes: {},
            locale: "en",
        };

        expect(product.locale).toBe("en");
    });

    it("accepts a ProductResource with slugs array", () => {
        const slugs: SlugRecord[] = [
            { locale: "en", slug: "pla-filament" },
            { locale: "es", slug: "filamento-pla" },
        ];

        const product: ProductResource = {
            id: 1,
            name: "PLA",
            slug: "pla-filament",
            price: "9.99",
            images: [],
            variants: [],
            attributes: {},
            slugs,
        };

        expect(product.slugs).toHaveLength(2);
        expect(product.slugs![0].locale).toBe("en");
        expect(product.slugs![1].slug).toBe("filamento-pla");
    });

    it("ProductResource without locale or slugs is still valid (optional fields)", () => {
        const product: ProductResource = {
            id: 2,
            name: "ABS",
            slug: "abs-filament",
            price: "14.99",
            images: [],
            variants: [],
            attributes: {},
        };

        expect(product.locale).toBeUndefined();
        expect(product.slugs).toBeUndefined();
    });
});

// ---------------------------------------------------------------------------
// fetchProductBySlug() — happy path
// ---------------------------------------------------------------------------

describe("fetchProductBySlug() — fetches product by slug path parameter", () => {
    beforeEach(() => {
        mockApi.mockReset();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
        vi.resetModules();
    });

    it("calls GET /products/{slug} using path parameter", async () => {
        const responseData = makeProductResponse();
        mockApi.mockResolvedValue(responseData);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();
        await fetchProductBySlug("pla-filament");

        expect(mockApi).toHaveBeenCalledWith("/products/pla-filament", {});
    });

    it("returns a ProductResource with locale field from response", async () => {
        mockApi.mockResolvedValue(makeProductResponse({ locale: "es", slug: "filamento-pla" }));

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();
        const product = await fetchProductBySlug("filamento-pla");

        expect(product).not.toBeNull();
        expect(product!.locale).toBe("es");
    });

    it("returns a ProductResource with slugs array", async () => {
        const slugs: SlugRecord[] = [
            { locale: "en", slug: "pla-filament" },
            { locale: "es", slug: "filamento-pla" },
        ];
        mockApi.mockResolvedValue(makeProductResponse({ slugs }));

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();
        const product = await fetchProductBySlug("pla-filament");

        expect(product!.slugs).toHaveLength(2);
        expect(product!.slugs![0]).toEqual({ locale: "en", slug: "pla-filament" });
        expect(product!.slugs![1]).toEqual({ locale: "es", slug: "filamento-pla" });
    });

    it("returns product with the matched slug in the slug field", async () => {
        mockApi.mockResolvedValue(
            makeProductResponse({ slug: "filamento-pla", locale: "es" })
        );

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();
        const product = await fetchProductBySlug("filamento-pla");

        expect(product!.slug).toBe("filamento-pla");
    });

    it("sets currentProduct state to the fetched product", async () => {
        const responseData = makeProductResponse();
        mockApi.mockResolvedValue(responseData);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug, currentProduct } = useProducts();
        await fetchProductBySlug("pla-filament");

        expect(currentProduct.value).toEqual(responseData.data);
    });
});

// ---------------------------------------------------------------------------
// fetchProductBySlug() — error handling
// ---------------------------------------------------------------------------

describe("fetchProductBySlug() — error handling", () => {
    beforeEach(() => {
        mockApi.mockReset();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
        vi.resetModules();
    });

    it("returns null and sets error state on 404", async () => {
        const notFoundError = { statusCode: 404 };
        mockApi.mockRejectedValue(notFoundError);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug, error } = useProducts();
        const result = await fetchProductBySlug("non-existent-slug");

        expect(result).toBeNull();
        expect(error.value).toBe("Product not found");
    });

    it("re-throws non-404 errors", async () => {
        const serverError = { statusCode: 500, message: "Internal server error" };
        mockApi.mockRejectedValue(serverError);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();

        await expect(fetchProductBySlug("pla-filament")).rejects.toEqual(serverError);
    });

    it("handles 404 detected via response.status", async () => {
        const notFoundError = { response: { status: 404 } };
        mockApi.mockRejectedValue(notFoundError);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();
        const result = await fetchProductBySlug("not-found");

        expect(result).toBeNull();
    });
});
