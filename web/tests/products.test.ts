import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// ---------------------------------------------------------------------------

const mockFetch = vi.fn();

vi.stubGlobal("$fetch", Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }));

vi.stubGlobal("defineNuxtPlugin", (fn: (app: unknown) => unknown) => fn({}));

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// useNuxtApp: throw so composable falls back to global $fetch.
vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context — using $fetch fallback");
});

// ---------------------------------------------------------------------------
// Stub Nuxt's useState / computed
// useState is keyed reactive state; we back it with Vue refs in tests.
// ---------------------------------------------------------------------------
type AnyRef = ReturnType<typeof ref>;
const stateStore: Record<string, AnyRef> = {};

vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

vi.stubGlobal("computed", computed);

// useApi: stub so composable can call API via mockFetch without Nuxt context.
// The real useApi falls back to $fetch when useNuxtApp throws, but useApi
// itself is a Nuxt auto-import and therefore undefined in test scope.
vi.stubGlobal("useApi", () => mockFetch);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeProductListResponse(overrides: Record<string, unknown> = {}) {
    return {
        data: [
            { id: 1, name: "PLA Filament", slug: "pla-filament", price: "19.99" },
            { id: 2, name: "PETG Filament", slug: "petg-filament", price: "24.99" },
        ],
        meta: {
            current_page: 1,
            last_page: 3,
            total: 45,
            per_page: 15,
            ...overrides,
        },
    };
}

function makeSingleProductResponse() {
    return {
        data: {
            id: 1,
            name: "PLA Filament",
            slug: "pla-filament",
            description: "High quality PLA",
            price: "19.99",
            images: [],
            variants: [{ id: 10, sku: "PLA-RED", price: "19.99", attributes: { color: "Red" } }],
            attributes: { material: "PLA" },
        },
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("useProducts composable", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();

        // Reset state store between tests
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();
    });

    // -------------------------------------------------------------------------
    // fetchProducts — URL construction
    // -------------------------------------------------------------------------

    it("fetchProducts calls GET /products with default page and per_page params", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse());

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProducts } = useProducts();

        await fetchProducts();

        expect(mockFetch).toHaveBeenCalledWith(
            "/products",
            expect.objectContaining({
                query: expect.objectContaining({ page: 1, per_page: 15 }),
            })
        );
    });

    it("fetchProducts passes custom page and per_page to the API", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse({ current_page: 2, per_page: 10 }));

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProducts } = useProducts();

        await fetchProducts(2, 10);

        expect(mockFetch).toHaveBeenCalledWith(
            "/products",
            expect.objectContaining({
                query: expect.objectContaining({ page: 2, per_page: 10 }),
            })
        );
    });

    it("fetchProducts passes optional filters as additional query params", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse());

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProducts } = useProducts();

        await fetchProducts(1, 15, { category: "pla", color: "red" });

        expect(mockFetch).toHaveBeenCalledWith(
            "/products",
            expect.objectContaining({
                query: expect.objectContaining({
                    page: 1,
                    per_page: 15,
                    category: "pla",
                    color: "red",
                }),
            })
        );
    });

    // -------------------------------------------------------------------------
    // fetchProducts — reactive state updates
    // -------------------------------------------------------------------------

    it("fetchProducts updates products ref with response data", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse());

        const { useProducts } = await import("../composables/useProducts");
        const { products, fetchProducts } = useProducts();

        await fetchProducts();

        expect(products.value).toHaveLength(2);
        expect(products.value[0].slug).toBe("pla-filament");
    });

    it("fetchProducts updates currentPage and pageSize from response meta", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse({ current_page: 2, per_page: 10 }));

        const { useProducts } = await import("../composables/useProducts");
        const { currentPage, pageSize, fetchProducts } = useProducts();

        await fetchProducts(2, 10);

        expect(currentPage.value).toBe(2);
        expect(pageSize.value).toBe(10);
    });

    it("fetchProducts updates total and lastPage from response meta", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse({ total: 45, last_page: 5 }));

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProducts, totalProducts, totalPages } = useProducts();

        await fetchProducts();

        expect(totalProducts.value).toBe(45);
        expect(totalPages.value).toBe(5);
    });

    // -------------------------------------------------------------------------
    // Pagination computed properties
    // -------------------------------------------------------------------------

    it("totalProducts reflects the total from meta", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse({ total: 100 }));

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProducts, totalProducts } = useProducts();

        await fetchProducts();

        expect(totalProducts.value).toBe(100);
    });

    it("totalPages reflects last_page from meta", async () => {
        mockFetch.mockResolvedValueOnce(makeProductListResponse({ last_page: 7 }));

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProducts, totalPages } = useProducts();

        await fetchProducts();

        expect(totalPages.value).toBe(7);
    });

    // -------------------------------------------------------------------------
    // fetchProductBySlug
    // -------------------------------------------------------------------------

    it("fetchProductBySlug calls GET /products/{slug}", async () => {
        mockFetch.mockResolvedValueOnce(makeSingleProductResponse());

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();

        await fetchProductBySlug("pla-filament");

        expect(mockFetch).toHaveBeenCalledWith(
            "/products/pla-filament",
            expect.objectContaining({})
        );
    });

    it("fetchProductBySlug returns the product data and updates currentProduct", async () => {
        mockFetch.mockResolvedValueOnce(makeSingleProductResponse());

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug, currentProduct } = useProducts();

        const result = await fetchProductBySlug("pla-filament");

        expect(result).not.toBeNull();
        expect(result!.slug).toBe("pla-filament");
        expect(result!.variants).toHaveLength(1);
        expect(currentProduct.value?.slug).toBe("pla-filament");
    });

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    it("fetchProductBySlug returns null and sets error on 404", async () => {
        const notFoundError = Object.assign(new Error("Not Found"), { statusCode: 404 });
        mockFetch.mockRejectedValueOnce(notFoundError);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug, error } = useProducts();

        const result = await fetchProductBySlug("non-existent-slug");

        expect(result).toBeNull();
        expect(error.value).toBe("Product not found");
    });

    it("fetchProductBySlug re-throws non-404 errors", async () => {
        const serverError = Object.assign(new Error("Server Error"), { statusCode: 500 });
        mockFetch.mockRejectedValueOnce(serverError);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug } = useProducts();

        await expect(fetchProductBySlug("pla-filament")).rejects.toThrow("Server Error");
    });

    it("fetchProductBySlug clears error before each call", async () => {
        // First call: 404
        const notFoundError = Object.assign(new Error("Not Found"), { statusCode: 404 });
        mockFetch.mockRejectedValueOnce(notFoundError);

        const { useProducts } = await import("../composables/useProducts");
        const { fetchProductBySlug, error } = useProducts();

        await fetchProductBySlug("bad-slug");
        expect(error.value).toBe("Product not found");

        // Second call: success — error should be cleared
        mockFetch.mockResolvedValueOnce(makeSingleProductResponse());
        await fetchProductBySlug("pla-filament");
        expect(error.value).toBeNull();
    });
});
