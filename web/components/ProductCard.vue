<template>
    <Card>
        <NuxtLink :to="productUrl">
            <div>
                <img :src="imageSrc" :alt="product.name" />
            </div>
            <CardContent>
                <h2>{{ product.name }}</h2>
                <p>${{ lowestPrice }}</p>
            </CardContent>
        </NuxtLink>
    </Card>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { ProductResource } from "~/composables/useProducts";
import { Card, CardContent } from "@/components/ui/card";

const PLACEHOLDER = "/images/placeholder.png";

const props = defineProps<{
    product: ProductResource;
}>();

const productUrl = computed<string>(() => "/" + props.product.slug);

const imageSrc = computed<string>(() =>
    props.product.images && props.product.images.length > 0 ? props.product.images[0] : PLACEHOLDER
);

const lowestPrice = computed<string>(() => {
    const variants = props.product.variants;
    if (!variants || variants.length === 0) {
        return Number(props.product.price).toFixed(2);
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
        return Number(props.product.price).toFixed(2);
    }

    return min.toFixed(2);
});
</script>
