<template>
    <div>
        <!-- Loading state -->
        <div v-if="loading">
            <p>Loading...</p>
        </div>

        <!-- ── Product page ─────────────────────────────────────────────── -->
        <div v-else-if="product">
            <Breadcrumb :items="productBreadcrumb" />

            <!-- Image Gallery + Product Info (responsive 2-col) -->
            <div>
                <!-- Image Gallery -->
                <section>
                    <div v-if="product.images && product.images.length > 0">
                        <img :src="selectedImage" :alt="product.name" />
                        <div>
                            <img
                                v-for="(image, index) in product.images"
                                :key="index"
                                :src="image"
                                :alt="`${product.name} thumbnail ${index + 1}`"
                                :class="
                                    selectedImage === image
                                        ? 'border-blue-500'
                                        : 'border-transparent'
                                "
                                @click="selectedImage = image"
                            />
                        </div>
                    </div>
                    <div v-else>
                        <p>No images available</p>
                    </div>
                </section>

                <!-- Product Info -->
                <section>
                    <h1>{{ product.name }}</h1>
                    <p v-if="product.description">
                        {{ product.description }}
                    </p>

                    <!-- Variant Selector -->
                    <div>
                        <label for="variant-select"> Select Variant </label>
                        <Select v-model="selectedVariantIdStr">
                            <SelectTrigger id="variant-select">
                                <SelectValue placeholder="-- Select a variant --" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="variant in product.variants"
                                    :key="variant.id"
                                    :value="String(variant.id)"
                                >
                                    {{ formatVariantLabel(variant) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Selected Variant Info -->
                    <div v-if="selectedVariant">
                        <p>
                            Price:
                            <strong v-if="selectedVariant.special_price">
                                ${{ selectedVariant.special_price }}
                            </strong>
                            <strong v-else>${{ selectedVariant.regular_price }}</strong>
                            <span v-if="selectedVariant.special_price">
                                <s>${{ selectedVariant.regular_price }}</s>
                            </span>
                        </p>
                        <p :class="isOutOfStock ? 'text-red-600' : 'text-green-600'">
                            <span v-if="isOutOfStock">Out of stock</span>
                            <span v-else
                                >In stock ({{ selectedVariant.stock_quantity }} available)</span
                            >
                        </p>
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label for="quantity-input"> Quantity </label>
                        <input
                            id="quantity-input"
                            v-model.number="quantity"
                            type="number"
                            min="1"
                        />
                    </div>

                    <!-- Add to Cart -->
                    <Button
                        data-testid="add-to-cart"
                        :disabled="!canAddToCart"
                        @click="handleAddToCart"
                    >
                        <span v-if="addingToCart">Adding...</span>
                        <span v-else>Add to Cart</span>
                    </Button>

                    <p v-if="cartSuccess" role="alert">Added to cart successfully!</p>
                    <p v-if="cartError" role="alert">
                        {{ cartError }}
                    </p>

                    <!-- Specifications -->
                    <div v-if="product.attributes && Object.keys(product.attributes).length > 0">
                        <h2>Specifications</h2>
                        <dl>
                            <template v-for="(value, key) in product.attributes" :key="key">
                                <dt>{{ key }}</dt>
                                <dd>{{ value }}</dd>
                            </template>
                        </dl>
                    </div>
                </section>
            </div>

            <!-- Review Form -->
            <section>
                <h2>Leave a Review</h2>
                <ReviewForm :product-id="product.id" :already-reviewed="hasUserReviewed" />
            </section>

            <!-- Reviews -->
            <section>
                <h2>Customer Reviews</h2>
                <div v-if="product.reviews && product.reviews.length > 0">
                    <div v-for="review in product.reviews" :key="review.id">
                        <p>{{ review.customer_name }}</p>
                        <p>Rating: {{ review.rating }}/5</p>
                        <p>{{ review.comment }}</p>
                    </div>
                </div>
                <p v-else>No reviews yet</p>
            </section>
        </div>

        <!-- ── Category page ─────────────────────────────────────────────── -->
        <div v-else-if="category">
            <!-- Breadcrumb -->
            <Breadcrumb :items="categoryBreadcrumb" />

            <!-- Category header -->
            <header>
                <img v-if="category.image" :src="category.image" :alt="category.name" />
                <h1>{{ category.name }}</h1>
            </header>

            <!-- Subcategories -->
            <section
                v-if="category.children && category.children.length > 0"
                data-testid="subcategories"
            >
                <h2>Subcategories</h2>
                <div>
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
            <section>
                <div>
                    <ProductCard
                        v-for="catProduct in categoryProducts"
                        :key="catProduct.id"
                        :product="catProduct"
                    />
                </div>
            </section>

            <!-- Pagination -->
            <nav v-if="totalPages > 1" data-testid="pagination">
                <Button
                    variant="outline"
                    :disabled="currentPage <= 1"
                    @click="goToPage(currentPage - 1)"
                >
                    Previous
                </Button>
                <span>Page {{ currentPage }} of {{ totalPages }}</span>
                <Button
                    variant="outline"
                    :disabled="currentPage >= totalPages"
                    @click="goToPage(currentPage + 1)"
                >
                    Next
                </Button>
            </nav>
        </div>

        <!-- 404 -->
        <div v-else>
            <p>Page not found.</p>
            <NuxtLink to="/">← Back to home</NuxtLink>
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ ssr: false });
import { ref, computed, onMounted } from "vue";
import Breadcrumb from "~/components/Breadcrumb.vue";
import type { BreadcrumbItem } from "~/components/Breadcrumb.vue";
import type { CategoryResource } from "~/composables/useCategories";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

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

// String-based computed for shadcn Select (which works with string values)
const selectedVariantIdStr = computed<string>({
    get: () => (selectedVariantId.value === "" ? "" : String(selectedVariantId.value)),
    set: (val: string) => {
        selectedVariantId.value = val === "" ? "" : parseInt(val, 10);
    },
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

    return [...categoryItems, { id: product.value.id, name: product.value.name, slugs: [] }];
});

const categoryBreadcrumb = computed<BreadcrumbItem[]>(() => {
    if (!category.value) return [];

    const parentItems: BreadcrumbItem[] = resolvedCategories.value.map((cat, i) => ({
        id: cat.id,
        name: cat.name,
        slugs: [],
        url: `/${slugSegments.value.slice(0, i + 1).join("/")}`,
    }));

    return [...parentItems, { id: category.value.id, name: category.value.name, slugs: [] }];
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
