<template>
    <article class="product-card">
        <NuxtLink :to="productUrl" class="product-card__link">
            <div class="product-card__image-wrapper">
                <img :src="imageSrc" :alt="product.name" class="product-card__image" />
            </div>
            <div class="product-card__body">
                <h2 class="product-card__name">{{ product.name }}</h2>
                <p class="product-card__price">${{ lowestPrice }}</p>
            </div>
        </NuxtLink>
    </article>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { ProductResource } from "~/composables/useProducts";

const PLACEHOLDER = "/images/placeholder.png";

const props = defineProps<{
    product: ProductResource;
}>();

const productUrl = computed<string>(() => '/' + props.product.slug);

const imageSrc = computed<string>(() =>
    props.product.images && props.product.images.length > 0 ? props.product.images[0] : PLACEHOLDER
);

const lowestPrice = computed<string>(() => {
    const variants = props.product.variants;
    if (!variants || variants.length === 0) {
        return props.product.price;
    }

    let min = Infinity;
    for (const variant of variants) {
        const effectivePrice = variant.special_price ?? variant.regular_price ?? variant.price;
        const parsed = parseFloat(effectivePrice ?? "");
        if (!isNaN(parsed) && parsed < min) {
            min = parsed;
        }
    }

    if (min === Infinity) {
        return props.product.price;
    }

    return min.toFixed(2);
});
</script>

<style scoped>
.product-card {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.product-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.product-card__link {
    display: block;
    text-decoration: none;
    color: inherit;
}
.product-card__image-wrapper {
    aspect-ratio: 1;
    background: #f3f4f6;
    overflow: hidden;
}
.product-card__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.product-card__body {
    padding: 1rem;
}
.product-card__name {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
}
.product-card__price {
    font-size: 0.95rem;
    color: #374151;
    margin: 0;
}
</style>
