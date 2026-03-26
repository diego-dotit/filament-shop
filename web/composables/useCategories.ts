// composables/useCategories.ts
// Provides reactive category data: listing all top-level categories.
//
// Usage:
//   const { categories, fetchCategories, error } = useCategories()
//   await fetchCategories()

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface CategoryResource {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    children: CategoryResource[];
}

interface CategoriesResponse {
    data: CategoryResource[];
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

    return {
        categories,
        error,
        fetchCategories,
    };
}
