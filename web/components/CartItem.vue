<template>
    <div>
        <div>
            <p>{{ item.product.name }}</p>
            <p>{{ item.variant.sku }}</p>
        </div>

        <div>
            <Button
                variant="outline"
                size="sm"
                data-testid="decrement"
                :disabled="item.quantity <= 1"
                @click="decrement"
            >
                −
            </Button>
            <span>{{ item.quantity }}</span>
            <Button variant="outline" size="sm" data-testid="increment" @click="increment">
                +
            </Button>
        </div>

        <div>
            <p>${{ item.line_total.toFixed(2) }}</p>
        </div>

        <Button variant="ghost" size="sm" data-testid="remove" @click="remove"> Remove </Button>
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
