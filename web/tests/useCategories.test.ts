/**
 * Tests for useCategories() composable — fetchCategoryBySlug() method.
 *
 * Acceptance criteria:
 *  - fetchCategoryBySlug(slug) calls GET /categories/{slug}
 *  - Returned CategoryResource includes locale field
 *  - Returned CategoryResource includes slugs array (SlugRecord[])
 *  - Returned CategoryResource includes full hierarchy (children with slugs)
 *  - Children subcategories also include their own slugs arrays
 *  - API call uses useApi() wrapper
 *  - Rejects with error when category not found (404)
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("ref", ref);

const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context");
});

// ---------------------------------------------------------------------------
// useApi mock
// ---------------------------------------------------------------------------

const mockApi = vi.fn();
vi.stubGlobal("useApi", () => mockApi);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeCategoryWithSlugs() {
    return {
        data: {
            id: 1,
            name: "PLA Category",
            slug: "pla-category",
            locale: "en",
            image: null,
            slugs: [
                { locale: "en", slug: "pla-category" },
                { locale: "es", slug: "categoria-pla" },
            ],
            children: [
                {
                    id: 2,
                    name: "PLA Silk",
                    slug: "pla-silk",
                    image: null,
                    children: [],
                    slugs: [
                        { locale: "en", slug: "pla-silk" },
                        { locale: "es", slug: "pla-seda" },
                    ],
                },
            ],
        },
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("useCategories() — fetchCategoryBySlug()", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
    });

    it("calls the API with /categories/{slug}", async () => {
        mockApi.mockResolvedValueOnce(makeCategoryWithSlugs());

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategoryBySlug } = useCategories();

        await fetchCategoryBySlug("pla-category");

        expect(mockApi).toHaveBeenCalledWith("/categories/pla-category", expect.anything());
    });

    it("returns the CategoryResource from the response data", async () => {
        mockApi.mockResolvedValueOnce(makeCategoryWithSlugs());

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategoryBySlug } = useCategories();

        const result = await fetchCategoryBySlug("pla-category");

        expect(result.id).toBe(1);
        expect(result.name).toBe("PLA Category");
    });

    it("returned resource includes the locale field", async () => {
        mockApi.mockResolvedValueOnce(makeCategoryWithSlugs());

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategoryBySlug } = useCategories();

        const result = await fetchCategoryBySlug("pla-category");

        expect(result.locale).toBe("en");
    });

    it("returned resource includes slugs array with locale/slug pairs", async () => {
        mockApi.mockResolvedValueOnce(makeCategoryWithSlugs());

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategoryBySlug } = useCategories();

        const result = await fetchCategoryBySlug("pla-category");

        expect(result.slugs).toEqual([
            { locale: "en", slug: "pla-category" },
            { locale: "es", slug: "categoria-pla" },
        ]);
    });

    it("returned resource includes children with their own slugs arrays", async () => {
        mockApi.mockResolvedValueOnce(makeCategoryWithSlugs());

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategoryBySlug } = useCategories();

        const result = await fetchCategoryBySlug("pla-category");

        expect(result.children).toHaveLength(1);
        expect(result.children[0].slugs).toEqual([
            { locale: "en", slug: "pla-silk" },
            { locale: "es", slug: "pla-seda" },
        ]);
    });

    it("rejects with an error when the category is not found (404)", async () => {
        const notFoundError = Object.assign(new Error("Not Found"), { statusCode: 404 });
        mockApi.mockRejectedValueOnce(notFoundError);

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategoryBySlug } = useCategories();

        await expect(fetchCategoryBySlug("nonexistent-slug")).rejects.toThrow();
    });

    it("re-throws non-404 errors", async () => {
        const serverError = Object.assign(new Error("Internal Server Error"), { statusCode: 500 });
        mockApi.mockRejectedValueOnce(serverError);

        const { useCategories } = await import("../composables/useCategories");
        const { fetchCategoryBySlug } = useCategories();

        await expect(fetchCategoryBySlug("pla-category")).rejects.toMatchObject({
            statusCode: 500,
        });
    });
});

// ---------------------------------------------------------------------------
// CategoryResource interface shape
// ---------------------------------------------------------------------------

describe("CategoryResource interface", () => {
    it("includes locale and slugs fields in addition to base fields", async () => {
        const { useCategories } = await import("../composables/useCategories");
        void useCategories; // just verify the import works

        // Type-level check: CategoryResource should accept locale and slugs
        const { type: _type } = await import("../composables/useCategories");
        void _type;

        // Runtime structural check via the response shape
        mockApi.mockResolvedValueOnce(makeCategoryWithSlugs());
        const { fetchCategoryBySlug } = useCategories();
        const cat = await fetchCategoryBySlug("pla-category");

        expect(cat).toHaveProperty("locale");
        expect(cat).toHaveProperty("slugs");
        expect(cat).toHaveProperty("children");
    });
});
