<template>
    <div>
        <!-- Loading state -->
        <div v-if="loading" class="slug-page__loading">
            <p>Loading...</p>
        </div>

        <!-- ── Product page ─────────────────────────────────────────────── -->
        <div v-else-if="product" class="product-detail">
            <Breadcrumb :items="productBreadcrumb" />

            <!-- Image Gallery -->
            <section class="product-detail__gallery">
                <div v-if="product.images && product.images.length > 0" class="gallery">
                    <img :src="selectedImage" :alt="product.name" class="gallery__primary" />
                    <div class="gallery__thumbnails">
                        <img
                            v-for="(image, index) in product.images"
                            :key="index"
                            :src="image"
                            :alt="`${product.name} thumbnail ${index + 1}`"
                            class="gallery__thumbnail"
                            :class="{ 'gallery__thumbnail--active': selectedImage === image }"
                            @click="selectedImage = image"
                        />
                    </div>
                </div>
                <div v-else class="gallery__placeholder">
                    <p>No images available</p>
                </div>
            </section>

            <!-- Product Info -->
            <section class="product-detail__info">
                <h1 class="product-detail__name">{{ product.name }}</h1>
                <p v-if="product.description" class="product-detail__description">
                    {{ product.description }}
                </p>

                <!-- Variant Selector -->
                <div class="product-detail__variants">
                    <label for="variant-select" class="product-detail__label">Select Variant</label>
                    <select
                        id="variant-select"
                        v-model="selectedVariantId"
                        class="product-detail__select"
                    >
                        <option value="">-- Select a variant --</option>
                        <option
                            v-for="variant in product.variants"
                            :key="variant.id"
                            :value="variant.id"
                        >
                            {{ formatVariantLabel(variant) }}
                        </option>
                    </select>
                </div>

                <!-- Selected Variant Info -->
                <div v-if="selectedVariant" class="product-detail__variant-info">
                    <p class="product-detail__price">
                        Price:
                        <strong v-if="selectedVariant.special_price"
                            >${{ selectedVariant.special_price }}</strong
                        >
                        <strong v-else>${{ selectedVariant.regular_price }}</strong>
                        <span v-if="selectedVariant.special_price" class="product-detail__regular-price">
                            <s>${{ selectedVariant.regular_price }}</s>
                        </span>
                    </p>
                    <p class="product-detail__stock" :class="{ 'out-of-stock': isOutOfStock }">
                        <span v-if="isOutOfStock">Out of stock</span>
                        <span v-else>In stock ({{ selectedVariant.stock_quantity }} available)</span>
                    </p>
                </div>

                <!-- Quantity -->
                <div class="product-detail__quantity">
                    <label for="quantity-input" class="product-detail__label">Quantity</label>
                    <input
                        id="quantity-input"
                        v-model.number="quantity"
                        type="number"
                        min="1"
                        class="product-detail__quantity-input"
                    />
                </div>

                <!-- Add to Cart -->
                <button
                    data-testid="add-to-cart"
                    class="product-detail__add-to-cart"
                    :disabled="!canAddToCart"
                    @click="handleAddToCart"
                >
                    <span v-if="addingToCart">Adding...</span>
                    <span v-else>Add to Cart</span>
                </button>

                <p v-if="cartSuccess" class="product-detail__cart-success" role="alert">
                    Added to cart successfully!
                </p>
                <p v-if="cartError" class="product-detail__cart-error" role="alert">
                    {{ cartError }}
                </p>

                <!-- Specifications -->
                <div
                    v-if="product.attributes && Object.keys(product.attributes).length > 0"
                    class="product-detail__specs"
                >
                    <h2>Specifications</h2>
                    <dl class="specs-list">
                        <template v-for="(value, key) in product.attributes" :key="key">
                            <dt>{{ key }}</dt>
                            <dd>{{ value }}</dd>
                        </template>
                    </dl>
                </div>
            </section>

            <!-- Review Form -->
            <section class="product-detail__review-form">
                <h2>Leave a Review</h2>
                <ReviewForm :product-id="product.id" :already-reviewed="hasUserReviewed" />
            </section>

            <!-- Reviews -->
            <section class="product-detail__reviews">
                <h2>Customer Reviews</h2>
                <div v-if="product.reviews && product.reviews.length > 0">
                    <div v-for="review in product.reviews" :key="review.id" class="review">
                        <p class="review__customer">{{ review.customer_name }}</p>
                        <p class="review__rating">Rating: {{ review.rating }}/5</p>
                        <p class="review__comment">{{ review.comment }}</p>
                    </div>
                </div>
                <p v-else class="product-detail__no-reviews">No reviews yet</p>
            </section>
        </div>

        <!-- ── Category page ─────────────────────────────────────────────── -->
        <div v-else-if="category" class="category-page">
            <!-- Breadcrumb -->
            <nav class="category-page__breadcrumb">
                <NuxtLink to="/">Home</NuxtLink>
                <span
                    v-for="(cat, i) in resolvedCategories"
                    :key="cat.id"
                    class="category-page__breadcrumb-segment"
                >
                    <span class="category-page__breadcrumb-sep"> → </span>
                    <NuxtLink :to="'/' + slugSegments.slice(0, i + 1).join('/')">{{ cat.name }}</NuxtLink>
                </span>
                <span class="category-page__breadcrumb-segment">
                    <span class="category-page__breadcrumb-sep"> → </span>
                    <span>{{ category.name }}</span>
                </span>
            </nav>

            <!-- Category header -->
            <header class="category-page__header">
                <img
                    v-if="category.image"
                    :src="category.image"
                    :alt="category.name"
                    class="category-page__image"
                />
                <h1 class="category-page__title">{{ category.name }}</h1>
            </header>

            <!-- Subcategories -->
            <section
                v-if="category.children && category.children.length > 0"
                class="category-page__subcategories"
                data-testid="subcategories"
            >
                <h2 class="category-page__subcategories-title">Subcategories</h2>
                <div class="category-page__subcategories-list">
                    <CategoryChip
                        v-for="child in category.children"
                        :key="child.id"
                        :category="child"
                        :parent-slug="category.slug"
                        data-testid="subcategory-chip"
                    />
                </div>
            </section>

            <!-- Products grid -->
            <section class="category-page__products">
                <div class="product-grid">
                    <ProductCard
                        v-for="catProduct in categoryProducts"
                        :key="catProduct.id"
                        :product="catProduct"
                    />
                </div>
            </section>

            <!-- Pagination -->
            <nav
                v-if="totalPages > 1"
                class="category-page__pagination"
                data-testid="pagination"
            >
                <button :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                    Previous
                </button>
                <span>Page {{ currentPage }} of {{ totalPages }}</span>
                <button :disabled="currentPage >= totalPages" @click="goToPage(currentPage + 1)">
                    Next
                </button>
            </nav>
        </div>

        <!-- 404 -->
        <div v-else class="slug-page__error">
            <p>Page not found.</p>
            <NuxtLink to="/">← Back to home</NuxtLink>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import Breadcrumb from "~/components/Breadcrumb.vue";
import type { BreadcrumbItem } from "~/components/Breadcrumb.vue";
import type { CategoryResource } from "~/composables/useCategories";

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface ReviewResource {
    id: number;
    rating: number;
    comment: string;
    customer_name: string;
    customer_id?: number;
}

interface ProductVariantWithStock {
    id: number;
    sku: string;
    regular_price: string;
    special_price?: string;
    stock_quantity: number;
    attributes: Array<{ name: string; value: string }>;
}

interface ProductWithReviews {
    id: number;
    name: string;
    slug: string;
    description?: string;
    price: string;
    images: string[];
    variants: ProductVariantWithStock[];
    attributes: Record<string, string>;
    reviews?: ReviewResource[];
    categories?: Array<{ id: number; name: string; slug: string }>;
}

type CategoryProduct = {
    id: number;
    name: string;
    slug: string;
    price: string;
    images: string[];
    variants: ProductVariantWithStock[];
};

// ---------------------------------------------------------------------------
// Composables
// ---------------------------------------------------------------------------

const route = useRoute();
const { fetchProductBySlug } = useProducts();
const { fetchCategoryBySlug } = useCategories();
const { applyLocale } = useAutoLanguage();
const { addItem } = useCart();
const { user } = useAuth();
const api = useApi();

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const product = ref<ProductWithReviews | null>(null);
const category = ref<CategoryResource | null>(null);
const resolvedCategories = ref<CategoryResource[]>([]);
const slugSegments = ref<string[]>([]);
const categoryProducts = ref<CategoryProduct[]>([]);
const currentPage = ref(1);
const totalPages = ref(1);
const loading = ref(true);

// Product-specific state
const selectedVariantId = ref<number | "">("");
const quantity = ref(1);
const addingToCart = ref(false);
const cartSuccess = ref(false);
const cartError = ref<string | null>(null);
const selectedImage = ref("");

// ---------------------------------------------------------------------------
// Derived state (product)
// ---------------------------------------------------------------------------

const selectedVariant = computed<ProductVariantWithStock | null>(() => {
    if (!product.value || selectedVariantId.value === "") return null;
    return product.value.variants.find((v) => v.id === selectedVariantId.value) ?? null;
});

const isOutOfStock = computed<boolean>(() => {
    if (!selectedVariant.value) return true;
    return (selectedVariant.value.stock_quantity ?? 0) <= 0;
});

const canAddToCart = computed<boolean>(() => {
    return (
        selectedVariantId.value !== "" &&
        !isOutOfStock.value &&
        quantity.value >= 1 &&
        quantity.value <= (selectedVariant.value?.stock_quantity ?? 0)
    );
});

const hasUserReviewed = computed<boolean>(() => {
    if (!user.value || !product.value?.reviews) return false;
    return product.value.reviews.some((r) => r.customer_id === user.value!.id);
});

// ---------------------------------------------------------------------------
// Breadcrumbs
// ---------------------------------------------------------------------------

const productBreadcrumb = computed<BreadcrumbItem[]>(() => {
    if (!product.value) return [];

    // URL-provided category hierarchy takes priority; fall back to product.categories from API
    const catSources =
        resolvedCategories.value.length > 0
            ? resolvedCategories.value
            : (product.value.categories ?? []);

    const categoryItems: BreadcrumbItem[] = catSources.map((cat, i) => ({
        id: cat.id,
        name: cat.name,
        slugs: [],
        url:
            resolvedCategories.value.length > 0
                ? `/${slugSegments.value.slice(0, i + 1).join("/")}`
                : `/${cat.slug}`,
    }));

    return [
        ...categoryItems,
        { id: product.value.id, name: product.value.name, slugs: [] },
    ];
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function formatVariantLabel(variant: ProductVariantWithStock): string {
    const parts = variant.attributes.map((attr) => `${attr.name}: ${attr.value}`);
    return parts.length > 0 ? parts.join(", ") : variant.sku;
}

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

async function handleAddToCart() {
    if (!canAddToCart.value || selectedVariantId.value === "") return;

    addingToCart.value = true;
    cartSuccess.value = false;
    cartError.value = null;

    try {
        await addItem(
            selectedVariantId.value as number,
            quantity.value,
            product.value
                ? {
                      product: {
                          id: product.value.id,
                          name: product.value.name,
                          slug: product.value.slug,
                      },
                      variant: { id: selectedVariant.value!.id, sku: selectedVariant.value!.sku },
                      price: parseFloat(
                          selectedVariant.value!.special_price ??
                              selectedVariant.value!.regular_price
                      ),
                  }
                : undefined
        );
        cartSuccess.value = true;
    } catch (err: unknown) {
        const apiErr = err as { data?: { message?: string }; message?: string } | null;
        cartError.value =
            apiErr?.data?.message ??
            apiErr?.message ??
            "Failed to add item to cart. Please try again.";
    } finally {
        addingToCart.value = false;
    }
}

async function fetchCategoryProducts(slug: string, page = 1): Promise<void> {
    const response = await api<{
        data: CategoryProduct[];
        meta: { current_page: number; last_page: number };
    }>("/products", { query: { category_slug: slug, page, per_page: 15 } });

    categoryProducts.value = response.data;
    currentPage.value = response.meta.current_page;
    totalPages.value = response.meta.last_page;
}

async function goToPage(page: number): Promise<void> {
    if (!category.value) return;
    await fetchCategoryProducts(category.value.slug, page);
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

onMounted(async () => {
    slugSegments.value = Array.isArray(route.params.slug)
        ? (route.params.slug as string[])
        : [route.params.slug as string];

    const lastSlug = slugSegments.value[slugSegments.value.length - 1];
    const parentSegments = slugSegments.value.slice(0, -1);

    try {
        // Resolve intermediate URL segments as parent categories (for breadcrumb)
        if (parentSegments.length > 0) {
            try {
                resolvedCategories.value = await Promise.all(
                    parentSegments.map((seg) => fetchCategoryBySlug(seg))
                );
            } catch {
                throw createError({ statusCode: 404, statusMessage: "Category not found" });
            }
        }

        // Try product first
        const productResult = await fetchProductBySlug(lastSlug);

        if (productResult !== null) {
            product.value = productResult as ProductWithReviews;
            applyLocale(productResult);

            if (product.value.images?.length) {
                selectedImage.value = product.value.images[0];
            }

            // Fetch reviews
            try {
                const reviewsResponse = await api<{ data: ReviewResource[] }>(
                    `/products/${product.value.id}/reviews`
                );
                product.value = { ...product.value, reviews: reviewsResponse?.data ?? [] };
            } catch {
                // Reviews are non-critical
            }
        } else {
            // Try category
            try {
                const categoryResult = await fetchCategoryBySlug(lastSlug);
                category.value = categoryResult;
                await fetchCategoryProducts(lastSlug);
            } catch {
                throw createError({ statusCode: 404, statusMessage: "Page not found" });
            }
        }
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
/* ── Loading / error ─────────────────────────────────────────────────────── */
.slug-page__loading,
.slug-page__error {
    padding: 3rem 1.5rem;
    text-align: center;
    color: #6b7280;
}

/* ── Product detail (reuse classes from products/[slug].vue) ─────────────── */
.product-detail {
    padding: 1.5rem;
}
.product-detail__gallery {
    margin-bottom: 2rem;
}
.gallery__primary {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 0.5rem;
}
.gallery__thumbnails {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.gallery__thumbnail {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 0.25rem;
    cursor: pointer;
    border: 2px solid transparent;
}
.gallery__thumbnail--active {
    border-color: #2563eb;
}
.gallery__placeholder {
    padding: 2rem;
    text-align: center;
    background: #f3f4f6;
    border-radius: 0.5rem;
    color: #6b7280;
}
.product-detail__content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}
@media (max-width: 640px) {
    .product-detail__content {
        grid-template-columns: 1fr;
    }
}
.product-detail__name {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.75rem;
}
.product-detail__description {
    color: #374151;
    margin-bottom: 1rem;
}
.product-detail__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.25rem;
}
.product-detail__select,
.product-detail__quantity-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}
.product-detail__variants,
.product-detail__quantity {
    margin-bottom: 1rem;
}
.product-detail__price {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}
.product-detail__regular-price {
    margin-left: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}
.product-detail__stock {
    font-size: 0.875rem;
    color: #16a34a;
    margin-bottom: 1rem;
}
.product-detail__stock.out-of-stock {
    color: #dc2626;
}
.product-detail__add-to-cart {
    display: inline-flex;
    align-items: center;
    padding: 0.625rem 1.5rem;
    background: #2563eb;
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s;
}
.product-detail__add-to-cart:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}
.product-detail__cart-success {
    color: #16a34a;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}
.product-detail__cart-error {
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}
.product-detail__specs {
    margin-top: 1.5rem;
}
.specs-list {
    display: grid;
    grid-template-columns: max-content 1fr;
    gap: 0.25rem 1rem;
    font-size: 0.875rem;
}
.specs-list dt {
    color: #6b7280;
    font-weight: 500;
    text-transform: capitalize;
}
.specs-list dd {
    color: #111827;
    margin: 0;
}
.product-detail__review-form,
.product-detail__reviews {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}
.review {
    padding: 1rem 0;
    border-bottom: 1px solid #f3f4f6;
}
.review__customer {
    font-weight: 600;
}
.review__rating {
    font-size: 0.875rem;
    color: #f59e0b;
}
.review__comment {
    color: #374151;
}
.product-detail__no-reviews {
    color: #9ca3af;
}

/* ── Category page ───────────────────────────────────────────────────────── */
.category-page {
    padding: 1.5rem;
}
.category-page__breadcrumb {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0;
}
.category-page__breadcrumb-segment {
    display: inline-flex;
    align-items: center;
}
.category-page__breadcrumb-sep {
    margin: 0 0.25rem;
}
.category-page__image {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
.category-page__title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 1.5rem;
}
.category-page__subcategories {
    margin-bottom: 1.5rem;
}
.category-page__subcategories-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}
.category-page__subcategories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
}
.category-page__pagination {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: center;
}
</style>
