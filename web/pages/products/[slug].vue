<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <!-- 404 / Error state -->
        <div v-if="error" class="text-red-500">
            <p>{{ error }}</p>
        </div>

        <!-- Loading state -->
        <div v-else-if="loading" class="text-gray-500">
            <p>Loading product...</p>
        </div>

        <!-- Product content -->
        <div v-else-if="product" class="mt-4">
            <!-- Breadcrumb navigation -->
            <Breadcrumb :items="breadcrumbItems" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                <!-- Image Gallery -->
                <section>
                    <div v-if="product.images && product.images.length > 0" class="flex flex-col gap-2">
                        <img
                            :src="selectedImage"
                            :alt="product.name"
                            class="gallery__primary w-full max-h-96 object-cover rounded-lg"
                        />
                        <div class="flex gap-1 flex-wrap mt-2">
                            <img
                                v-for="(image, index) in product.images"
                                :key="index"
                                :src="image"
                                :alt="`${product.name} thumbnail ${index + 1}`"
                                class="gallery__thumbnail w-16 h-16 object-cover rounded border-2 border-transparent cursor-pointer hover:border-blue-500"
                                :class="{ 'gallery__thumbnail--active border-blue-500': selectedImage === image }"
                                @click="selectedImage = image"
                            />
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center h-48 rounded-lg border text-gray-400">
                        <p>No images available</p>
                    </div>
                </section>

                <!-- Product Info -->
                <section class="flex flex-col gap-4">
                    <h1 class="text-2xl font-bold">{{ product.name }}</h1>
                    <p class="text-gray-600">{{ product.description }}</p>

                    <!-- Variant Selector -->
                    <div class="flex flex-col gap-1">
                        <label for="variant-select" class="text-sm font-medium">Select Variant</label>
                        <Select v-model="selectedVariantId">
                            <SelectTrigger id="variant-select" class="w-full">
                                <SelectValue placeholder="-- Select a variant --" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="variant in product.variants"
                                    :key="variant.id"
                                    :value="variant.id"
                                >
                                    {{ formatVariantLabel(variant) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Selected Variant Info -->
                    <div v-if="selectedVariant" class="flex flex-col gap-1">
                        <p class="text-lg">
                            Price:
                            <strong v-if="selectedVariant.special_price" class="text-red-600"
                                >${{ selectedVariant.special_price }}</strong
                            >
                            <strong v-else>${{ selectedVariant.regular_price }}</strong>
                            <span
                                v-if="selectedVariant.special_price"
                                class="text-gray-500 line-through ml-2 text-sm font-normal"
                            >
                                ${{ selectedVariant.regular_price }}
                            </span>
                        </p>
                        <p
                            class="text-sm"
                            :class="isOutOfStock ? 'text-red-500' : 'text-green-600'"
                        >
                            <span v-if="isOutOfStock">Out of stock</span>
                            <span v-else
                                >In stock ({{ selectedVariant.stock_quantity }} available)</span
                            >
                        </p>
                    </div>

                    <!-- Quantity Input -->
                    <div class="flex flex-col gap-1">
                        <label for="quantity-input" class="text-sm font-medium">Quantity</label>
                        <input
                            id="quantity-input"
                            v-model.number="quantity"
                            type="number"
                            min="1"
                            class="border rounded px-2 py-1 w-24 text-sm"
                        />
                    </div>

                    <!-- Add to Cart Button -->
                    <Button
                        data-testid="add-to-cart"
                        variant="default"
                        :disabled="!canAddToCart"
                        @click="handleAddToCart"
                    >
                        <span v-if="addingToCart">Adding...</span>
                        <span v-else>Add to Cart</span>
                    </Button>

                    <!-- Success / Error Feedback -->
                    <p v-if="cartSuccess" class="text-green-600 text-sm" role="alert">
                        Added to cart successfully!
                    </p>
                    <p v-if="cartError" class="text-red-500 text-sm" role="alert">
                        {{ cartError }}
                    </p>

                    <!-- Product Specifications -->
                    <div
                        v-if="product.attributes && Object.keys(product.attributes).length > 0"
                        class="mt-2"
                    >
                        <h2 class="text-lg font-semibold mb-2">Specifications</h2>
                        <dl class="grid grid-cols-[max-content_1fr] gap-1 gap-x-4 text-sm">
                            <template v-for="(value, key) in product.attributes" :key="key">
                                <dt class="text-gray-500 font-medium">{{ key }}</dt>
                                <dd class="text-gray-900">{{ value }}</dd>
                            </template>
                        </dl>
                    </div>
                </section>
            </div>
        </div>

        <!-- Review Submission Form -->
        <section v-if="product" class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Leave a Review</h2>
            <ReviewForm :product-id="product.id" :already-reviewed="hasUserReviewed" />
        </section>

        <!-- Reviews Section -->
        <section v-if="product" class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Customer Reviews</h2>
            <div v-if="product.reviews && product.reviews.length > 0">
                <div v-for="review in product.reviews" :key="review.id" class="border-b py-3">
                    <p class="font-semibold">{{ review.customer_name }}</p>
                    <p class="text-sm text-gray-600">Rating: {{ review.rating }}/5</p>
                    <p class="text-sm mt-1">{{ review.comment }}</p>
                </div>
            </div>
            <p v-else class="text-gray-500">No reviews yet</p>
        </section>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ ssr: false });
import { ref, computed, onMounted } from "vue";
import Breadcrumb from "~/components/Breadcrumb.vue";
import type { BreadcrumbItem } from "~/components/Breadcrumb.vue";
import ReviewForm from "~/components/ReviewForm.vue";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";


// ---------------------------------------------------------------------------
// Types (extending ProductResource with reviews)
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

interface ProductCategoryWithName {
    id: number;
    name: string;
    slug: string;
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
    categories?: ProductCategoryWithName[];
}

// ---------------------------------------------------------------------------
// Composables
// ---------------------------------------------------------------------------

const route = useRoute();
const { fetchProductBySlug, error } = useProducts();
const { addItem } = useCart();
const { user } = useAuth();
const api = useApi();

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const product = ref<ProductWithReviews | null>(null);
const loading = ref(true);
const selectedVariantId = ref<number | "">("");
const quantity = ref(1);
const addingToCart = ref(false);
const cartSuccess = ref(false);
const cartError = ref<string | null>(null);

// ---------------------------------------------------------------------------
// Image gallery
// ---------------------------------------------------------------------------

const selectedImage = ref("");

// ---------------------------------------------------------------------------
// Derived state
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

const breadcrumbItems = computed<BreadcrumbItem[]>(() => {
    if (!product.value) return [];

    const categoryItems: BreadcrumbItem[] = (product.value.categories ?? []).map((cat) => ({
        id: cat.id,
        name: cat.name,
        slugs: [],
        url: `/${cat.slug || String(cat.id)}`,
    }));

    const productItem: BreadcrumbItem = {
        id: product.value.id,
        name: product.value.name,
        slugs: [],
        // no url → renders as non-clickable current page indicator
    };

    return [...categoryItems, productItem];
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function formatVariantLabel(variant: ProductVariantWithStock): string {
    const parts = variant.attributes.map(attr => `${attr.name}: ${attr.value}`);
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

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

onMounted(async () => {
    const slug = route.params.slug as string;
    loading.value = true;

    try {
        const result = await fetchProductBySlug(slug);
        product.value = result as ProductWithReviews | null;

        if (product.value === null) {
            throw createError({ statusCode: 404, statusMessage: "Product not found" });
        }

        if (product.value?.images?.length) {
            selectedImage.value = product.value.images[0];
        }

        // Fetch approved reviews from the dedicated endpoint (ordered latest first by API).
        try {
            const reviewsResponse = await api<{ data: ReviewResource[] }>(
                `/products/${product.value.id}/reviews`
            );
            if (product.value) {
                product.value = { ...product.value, reviews: reviewsResponse?.data ?? [] };
            }
        } catch (err: unknown) {
            console.error("Failed to load reviews:", err);
        }
    } finally {
        loading.value = false;
    }
});
</script>
