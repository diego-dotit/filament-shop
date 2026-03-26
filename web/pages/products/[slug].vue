<template>
    <div class="product-detail">
        <!-- 404 / Error state -->
        <div v-if="error" class="product-detail__error">
            <p>{{ error }}</p>
        </div>

        <!-- Loading state -->
        <div v-else-if="loading" class="product-detail__loading">
            <p>Loading product...</p>
        </div>

        <!-- Product content -->
        <div v-else-if="product" class="product-detail__content">
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
                <p class="product-detail__description">{{ product.description }}</p>

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
                        <span
                            v-if="selectedVariant.special_price"
                            class="product-detail__regular-price"
                        >
                            <s>${{ selectedVariant.regular_price }}</s>
                        </span>
                    </p>
                    <p class="product-detail__stock" :class="{ 'out-of-stock': isOutOfStock }">
                        <span v-if="isOutOfStock">Out of stock</span>
                        <span v-else
                            >In stock ({{ selectedVariant.stock_quantity }} available)</span
                        >
                    </p>
                </div>

                <!-- Quantity Input -->
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

                <!-- Add to Cart Button -->
                <button
                    data-testid="add-to-cart"
                    class="product-detail__add-to-cart"
                    :disabled="!canAddToCart"
                    @click="handleAddToCart"
                >
                    <span v-if="addingToCart">Adding...</span>
                    <span v-else>Add to Cart</span>
                </button>

                <!-- Success / Error Feedback -->
                <p v-if="cartSuccess" class="product-detail__cart-success" role="alert">
                    Added to cart successfully!
                </p>
                <p v-if="cartError" class="product-detail__cart-error" role="alert">
                    {{ cartError }}
                </p>

                <!-- Product Specifications -->
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
        </div>

        <!-- Review Submission Form -->
        <section v-if="product" class="product-detail__review-form">
            <h2>Leave a Review</h2>
            <ReviewForm :product-id="product.id" :already-reviewed="hasUserReviewed" />
        </section>

        <!-- Reviews Section -->
        <section v-if="product" class="product-detail__reviews">
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
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";

// ---------------------------------------------------------------------------
// Types (extending ProductResource with reviews)
// ---------------------------------------------------------------------------

interface ReviewResource {
    id: number;
    rating: number;
    comment: string;
    customer_name: string;
}

interface ProductVariantWithStock {
    id: number;
    sku: string;
    regular_price: string;
    special_price?: string;
    stock_quantity: number;
    attributes: Record<string, string>;
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
}

// ---------------------------------------------------------------------------
// Composables
// ---------------------------------------------------------------------------

const route = useRoute();
const { fetchProductBySlug, error } = useProducts();
const { addItem } = useCart();
const { user } = useAuth();

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
    return product.value.reviews.some((r) => r.customer_name === user.value!.name);
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function formatVariantLabel(variant: ProductVariantWithStock): string {
    const parts = Object.entries(variant.attributes).map(([k, v]) => `${k}: ${v}`);
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
    } finally {
        loading.value = false;
    }
});
</script>
