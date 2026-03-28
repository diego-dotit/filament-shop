// composables/useCategories.ts
// Provides reactive category data: listing all top-level categories,
// and fetching a single category by slug with locale and slugs support.
//
// Usage:
//   const { categories, fetchCategories, fetchCategoryBySlug, error } = useCategories()
//   await fetchCategories()
//   const category = await fetchCategoryBySlug('pla-category')

import type { SlugRecord } from "~/composables/useSlug";

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface CategoryResource {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    locale?: string;
    slugs?: SlugRecord[];
    children: CategoryResource[];
    parent?: { id: number; name: string; slug: string } | null;
}

interface CategoriesResponse {
    data: CategoryResource[];
}

interface SingleCategoryResponse {
    data: CategoryResource;
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useCategories() {
    const categories = useState<CategoryResource[]>("categories.list", () => []);
    const error = useState<string | null>("categories.error", () => null);

    const api = useApi();

    /**
     * Fetch the list of all categories.
     * Errors are caught and stored in `error` rather than thrown.
     */
    async function fetchCategories(): Promise<void> {
        error.value = null;

        try {
            const response = await api<CategoriesResponse>("/categories", {});
            categories.value = response.data;
        } catch (e: unknown) {
            error.value = (e as Error)?.message ?? "Failed to load categories";
        }
    }

    /**
     * Fetch a single category by its slug.
     * Returns the full CategoryResource including locale, slugs array,
     * and the complete hierarchy with all children also carrying slugs.
     * Re-throws all errors (including 404) so the caller can handle them.
     *
     * @param slug - The category's URL slug.
     */
    async function fetchCategoryBySlug(slug: string): Promise<CategoryResource> {
        const response = await api<SingleCategoryResponse>(`/categories/${slug}`, {});
        return response.data;
    }

    return {
        categories,
        error,
        fetchCategories,
        fetchCategoryBySlug,
    };
}
