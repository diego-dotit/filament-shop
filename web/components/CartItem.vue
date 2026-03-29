<template>
    <div class="flex items-center gap-4 py-4 border-b border-gray-200">
        <div class="flex-1">
            <p class="font-semibold mb-1">{{ item.product.name }}</p>
            <p class="text-xs text-gray-500">{{ item.variant.sku }}</p>
        </div>

        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                data-testid="decrement"
                :disabled="item.quantity <= 1"
                @click="decrement"
            >
                −
            </Button>
            <span class="min-w-8 text-center font-semibold">{{ item.quantity }}</span>
            <Button variant="outline" size="sm" data-testid="increment" @click="increment">
                +
            </Button>
        </div>

        <div class="min-w-24 text-right">
            <p class="font-bold">${{ item.line_total.toFixed(2) }}</p>
        </div>

        <Button
            variant="ghost"
            size="sm"
            data-testid="remove"
            class="text-red-500 hover:text-red-700"
            @click="remove"
        >
            Remove
        </Button>
    </div>
</template>

<script setup lang="ts">
import type { CartItem } from "~/composables/useCart";
import { Button } from "@/components/ui/button";

const props = defineProps<{
    item: CartItem;
}>();

const { updateItemQuantity, removeItem } = useCart();

async function increment() {
    try {
        await updateItemQuantity(props.item.id, props.item.quantity + 1);
    } catch (err: unknown) {
        console.error("Failed to update cart item quantity:", err);
    }
}

async function decrement() {
    if (props.item.quantity > 1) {
        try {
            await updateItemQuantity(props.item.id, props.item.quantity - 1);
        } catch (err: unknown) {
            console.error("Failed to update cart item quantity:", err);
        }
    }
}

async function remove() {
    try {
        await removeItem(props.item.id);
    } catch (err: unknown) {
        console.error("Failed to remove cart item:", err);
    }
}
</script>
