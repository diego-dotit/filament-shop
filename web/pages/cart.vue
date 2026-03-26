<template>
  <div class="cart-page">
    <h1 class="cart-page__title">Your Cart</h1>

    <!-- Empty state -->
    <div v-if="items.length === 0" class="cart-page__empty">
      <p>Your cart is empty.</p>
      <NuxtLink to="/" class="cart-page__continue-link">Continue Shopping</NuxtLink>
    </div>

    <!-- Cart with items -->
    <div v-else class="cart-page__content">
      <div class="cart-page__items">
        <CartItem
          v-for="item in items"
          :key="item.id"
          :item="item"
        />
      </div>

      <!-- Order summary -->
      <aside class="cart-page__summary">
        <h2 class="cart-page__summary-title">Order Summary</h2>

        <div class="cart-page__summary-row">
          <span>Subtotal</span>
          <span>${{ subtotal.toFixed(2) }}</span>
        </div>

        <div class="cart-page__summary-row cart-page__summary-total">
          <span>Total</span>
          <span>${{ cart?.total.toFixed(2) ?? '0.00' }}</span>
        </div>

        <NuxtLink to="/checkout" class="cart-page__checkout-btn">
          Proceed to Checkout
        </NuxtLink>

        <NuxtLink to="/" class="cart-page__continue-link">
          Continue Shopping
        </NuxtLink>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'

definePageMeta({
  middleware: 'auth',
})

const { isAuthenticated } = useAuth()
const { cart, items, fetchCart } = useCart()

const subtotal = computed<number>(() =>
  items.value.reduce((sum, item) => sum + item.line_total, 0),
)

onMounted(async () => {
  if (!isAuthenticated.value) {
    await navigateTo('/login')
    return
  }
  await fetchCart()
})
</script>

<style scoped>
.cart-page {
  max-width: 1100px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}

.cart-page__title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.cart-page__empty {
  text-align: center;
  padding: 3rem 0;
}

.cart-page__empty p {
  font-size: 1.2rem;
  color: #666;
  margin-bottom: 1rem;
}

.cart-page__content {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}

.cart-page__items {
  flex: 1;
}

.cart-page__summary {
  width: 300px;
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 1.5rem;
  background: #fafafa;
}

.cart-page__summary-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin: 0 0 1rem;
}

.cart-page__summary-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
}

.cart-page__summary-total {
  font-weight: 700;
  font-size: 1.1rem;
  border-bottom: none;
  margin-top: 0.5rem;
}

.cart-page__checkout-btn {
  display: block;
  width: 100%;
  margin-top: 1.5rem;
  padding: 0.75rem 1rem;
  background: #e94560;
  color: #fff;
  text-align: center;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 1rem;
  transition: background 0.15s;
}

.cart-page__checkout-btn:hover {
  background: #c73350;
}

.cart-page__continue-link {
  display: block;
  text-align: center;
  margin-top: 0.75rem;
  color: #666;
  text-decoration: underline;
  font-size: 0.9rem;
}

.cart-page__continue-link:hover {
  color: #333;
}
</style>
