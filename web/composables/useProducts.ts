// composables/useProducts.ts
// Provides reactive product data operations: listing with pagination and
// single-product retrieval by slug.
//
// Usage:
//   const { products, fetchProducts, fetchProductBySlug, totalPages, totalProducts, error } = useProducts()
//   await fetchProducts(1, 15, { category: 'pla' })
//   const product = await fetchProductBySlug('pla-filament')

import type { SlugRecord } from "~/composables/useSlug";

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

/** Matches the shape returned by AttributeResource.php (name/value pair). */
export interface AttributeResource {
    name: string;
    value: string;
}

/**
 * Minimal category shape returned by the API for product categories.
 * The API returns a flat slug string (not a slugs array) for product categories.
 * Phase 3+ multi-locale slug resolution uses ProductResource.slugs instead.
 */
export interface ProductCategoryEntity {
    id: number;
    name: string;
    slug: string;
}

export interface ProductVariantResource {
    id: number;
    sku: string;
    price: string;
    regular_price?: string | null;
    special_price?: string | null;
    attributes: AttributeResource[];
}

export interface ProductResource {
    id: number;
    name: string;
    slug: string;
    description?: string;
    price: string;
    images: string[];
    variants: ProductVariantResource[];
    attributes: Record<string, string>;
    /** The locale of the slug that was used to resolve this product (Phase 3+). */
    locale?: string;
    /** All available language slugs for this product (Phase 3+). */
    slugs?: SlugRecord[];
    /**
     * Category hierarchy for the product (Phase 4+).
     * categories[0] = top-level category, categories[1] = subcategory.
     * Used by ProductCard to build multi-segment, language-aware URLs.
     */
    categories?: ProductCategoryEntity[];
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
}

interface SingleResponse<T> {
    data: T;
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useProducts() {
    // Reactive state (shared via Nuxt useState key)
    const products = useState<ProductResource[]>("products.list", () => []);
    const currentProduct = useState<ProductResource | null>("products.current", () => null);
    const currentPage = useState<number>("products.currentPage", () => 1);
    const pageSize = useState<number>("products.pageSize", () => 15);
    const total = useState<number>("products.total", () => 0);
    const lastPage = useState<number>("products.lastPage", () => 1);
    const error = useState<string | null>("products.error", () => null);

    // Derived state
    const totalProducts = computed(() => total.value);
    const totalPages = computed(() => lastPage.value);

    const api = useApi();

    /**
     * Fetch a paginated list of products.
     *
     * @param page    - Page number (1-based). Defaults to 1.
     * @param perPage - Items per page. Defaults to 15.
     * @param filters - Optional additional query parameters (e.g. category, color).
     */
    async function fetchProducts(
        page = 1,
        perPage = 15,
        filters?: Record<string, string>
    ): Promise<void> {
        const query: Record<string, string | number> = {
            page,
            per_page: perPage,
            ...filters,
        };

        const response = await api<PaginatedResponse<ProductResource>>("/products", { query });

        products.value = response.data;
        currentPage.value = response.meta.current_page;
        pageSize.value = response.meta.per_page;
        total.value = response.meta.total;
        lastPage.value = response.meta.last_page;
    }

    /**
     * Fetch a single product by its slug.
     * Returns the product or null on 404.
     * Re-throws all other errors.
     *
     * @param slug - The product's URL slug.
     */
    async function fetchProductBySlug(slug: string): Promise<ProductResource | null> {
        error.value = null;

        try {
            const response = await api<SingleResponse<ProductResource>>(`/products/${slug}`, {});
            currentProduct.value = response.data;
            return response.data;
        } catch (e: unknown) {
            const status =
                (e as { statusCode?: number })?.statusCode ??
                (e as { response?: { status?: number } })?.response?.status;

            if (status === 404) {
                error.value = "Product not found";
                return null;
            }

            throw e;
        }
    }

    return {
        products,
        currentProduct,
        currentPage,
        pageSize,
        totalProducts,
        totalPages,
        error,
        fetchProducts,
        fetchProductBySlug,
    };
}
