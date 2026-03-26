<template>
    <div class="cart-item">
        <div class="cart-item__details">
            <p class="cart-item__product-name">{{ item.product.name }}</p>
            <p class="cart-item__variant-sku">{{ item.variant.sku }}</p>
        </div>

        <div class="cart-item__quantity">
            <button
                class="cart-item__qty-btn"
                data-testid="decrement"
                :disabled="item.quantity <= 1"
                @click="decrement"
            >
                −
            </button>
            <span class="cart-item__qty-value">{{ item.quantity }}</span>
            <button class="cart-item__qty-btn" data-testid="increment" @click="increment">+</button>
        </div>

        <div class="cart-item__pricing">
            <p class="cart-item__line-total">${{ item.line_total.toFixed(2) }}</p>
        </div>

        <button class="cart-item__remove" data-testid="remove" @click="remove">Remove</button>
    </div>
</template>

<script setup lang="ts">
import type { CartItem } from "../composables/useCart";

const props = defineProps<{
    item: CartItem;
}>();

const { updateItemQuantity, removeItem } = useCart();

function increment() {
    updateItemQuantity(props.item.id, props.item.quantity + 1);
}

function decrement() {
    if (props.item.quantity > 1) {
        updateItemQuantity(props.item.id, props.item.quantity - 1);
    }
}

function remove() {
    removeItem(props.item.id);
}
</script>

<style scoped>
.cart-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.cart-item__details {
    flex: 1;
}

.cart-item__product-name {
    font-weight: 600;
    margin: 0 0 0.25rem;
}

.cart-item__variant-sku {
    font-size: 0.85rem;
    color: #888;
    margin: 0;
}

.cart-item__quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-item__qty-btn {
    width: 2rem;
    height: 2rem;
    border: 1px solid #ccc;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-item__qty-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.cart-item__qty-value {
    min-width: 2rem;
    text-align: center;
    font-weight: 600;
}

.cart-item__pricing {
    min-width: 6rem;
    text-align: right;
}

.cart-item__line-total {
    font-weight: 700;
    margin: 0;
}

.cart-item__remove {
    background: transparent;
    border: none;
    color: #e94560;
    cursor: pointer;
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
    text-decoration: underline;
}

.cart-item__remove:hover {
    color: #c73350;
}
</style>
