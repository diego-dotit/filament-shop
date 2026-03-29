<template>
    <Card class="overflow-hidden hover:shadow-lg transition-shadow">
        <NuxtLink :to="productUrl" class="block no-underline text-inherit">
            <div class="aspect-square bg-gray-100 overflow-hidden">
                <img :src="imageSrc" :alt="product.name" class="w-full h-full object-cover" />
            </div>
            <CardContent class="pt-4">
                <h2 class="text-base font-semibold mb-2">{{ product.name }}</h2>
                <p class="text-sm text-gray-700">${{ lowestPrice }}</p>
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

const productUrl = computed<string>(() => '/' + props.product.slug);

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
