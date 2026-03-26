<template>
  <div class="order-confirmation">
    <h1 class="confirmation-heading">Thank You for Your Order!</h1>
    <p class="confirmation-subtitle">Your order has been placed successfully.</p>

    <div class="confirmation-summary">
      <p><strong>Order ID:</strong> #{{ orderId }}</p>
      <p><strong>Total:</strong> ${{ totalAmount }}</p>
      <p class="delivery-message">Expected delivery in 3–5 business days.</p>
    </div>

    <div class="confirmation-actions">
      <NuxtLink :to="`/account/orders/${orderId}`" class="btn btn-primary">
        View Order Details
      </NuxtLink>
      <NuxtLink to="/" class="btn btn-secondary">
        Continue Shopping
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  orderId: number
  totalAmount: string
  createdAt: string
}>()
</script>

<style scoped>
.order-confirmation {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.confirmation-heading {
  font-size: 1.75rem;
  font-weight: 700;
  color: #15803d;
  margin-bottom: 0;
}

.confirmation-subtitle {
  margin: 0;
  color: #374151;
}

.confirmation-summary {
  background-color: #f0fdf4;
  border: 1px solid #86efac;
  border-radius: 0.5rem;
  padding: 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.confirmation-summary p {
  margin: 0;
}

.delivery-message {
  color: #6b7280;
  font-style: italic;
  margin-top: 0.5rem !important;
}

.confirmation-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.625rem 1.25rem;
  border-radius: 0.375rem;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: opacity 0.15s ease;
}

.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover {
  background-color: #d1d5db;
}
</style>
