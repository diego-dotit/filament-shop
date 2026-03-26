<template>
  <div class="checkout-page">
    <!-- Order Confirmation -->
    <section v-if="orderConfirmation" class="confirmation">
      <OrderConfirmation
        :order-id="orderConfirmation.id"
        :total-amount="orderConfirmation.total_amount"
        :created-at="orderConfirmation.created_at"
      />
    </section>

    <!-- Checkout Form -->
    <section v-else class="checkout-form">
      <h1>Checkout</h1>

      <!-- Loading addresses -->
      <p v-if="loadingAddresses" class="loading-text">Loading addresses…</p>

      <!-- No addresses -->
      <div v-else-if="addresses.length === 0" class="no-addresses">
        <p>No saved addresses found. Please add an address to continue.</p>
        <NuxtLink to="/account/addresses/new" class="btn btn-primary">
          Add Address
        </NuxtLink>
      </div>

      <!-- Address selection -->
      <div v-else class="address-selection">
        <!-- Billing Address -->
        <fieldset class="address-fieldset">
          <legend>Billing Address</legend>
          <div
            v-for="address in addresses"
            :key="`billing-${address.id}`"
            class="address-option"
          >
            <label :for="`billing-address-${address.id}`">
              <input
                :id="`billing-address-${address.id}`"
                type="radio"
                name="billing_address"
                :value="address.id"
                :checked="billingAddressId === address.id"
                @change="selectBillingAddress(address.id)"
              />
              <span>
                {{ address.street }}, {{ address.city }}, {{ address.state }} {{ address.zip }},
                {{ address.country }}
              </span>
            </label>
          </div>
        </fieldset>

        <!-- Shipping Address -->
        <fieldset class="address-fieldset">
          <legend>Shipping Address</legend>
          <div
            v-for="address in addresses"
            :key="`shipping-${address.id}`"
            class="address-option"
          >
            <label :for="`shipping-address-${address.id}`">
              <input
                :id="`shipping-address-${address.id}`"
                type="radio"
                name="shipping_address"
                :value="address.id"
                :checked="shippingAddressId === address.id"
                @change="selectShippingAddress(address.id)"
              />
              <span>
                {{ address.street }}, {{ address.city }}, {{ address.state }} {{ address.zip }},
                {{ address.country }}
              </span>
            </label>
          </div>
        </fieldset>

        <!-- Error message -->
        <div v-if="error" class="error-message" role="alert">
          <p>{{ error }}</p>
          <p>Please fix the issue above and try again.</p>
        </div>

        <!-- Submit Order -->
        <button
          class="btn btn-primary submit-order-btn"
          :disabled="isSubmitting || !billingAddressId || !shippingAddressId"
          @click="handleSubmitOrder"
        >
          {{ isSubmitting ? 'Placing Order…' : 'Submit Order' }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
// Protect this route — unauthenticated visitors are redirected by the middleware.
definePageMeta({ middleware: 'auth' })

// Redirect unauthenticated users to login
const { isAuthenticated } = useAuth()
if (!isAuthenticated.value) {
  await navigateTo('/login')
}

const {
  addresses,
  billingAddressId,
  shippingAddressId,
  orderConfirmation,
  error,
  isSubmitting,
  fetchAddresses,
  selectBillingAddress,
  selectShippingAddress,
  submitOrder,
} = useCheckout()

const loadingAddresses = ref(false)

// Persist order confirmation in sessionStorage so it survives page reload.
const SESSION_KEY = 'checkout.orderConfirmation'

onMounted(async () => {
  // Restore confirmation from sessionStorage if present (page reload scenario)
  if (!orderConfirmation.value) {
    const stored = sessionStorage.getItem(SESSION_KEY)
    if (stored) {
      try {
        orderConfirmation.value = JSON.parse(stored)
      } catch {
        sessionStorage.removeItem(SESSION_KEY)
      }
    }
  }

  // Fetch addresses only when no confirmation is shown
  if (!orderConfirmation.value) {
    loadingAddresses.value = true
    try {
      await fetchAddresses()
    } finally {
      loadingAddresses.value = false
    }
  }
})

async function handleSubmitOrder(): Promise<void> {
  await submitOrder()

  // Persist confirmation to sessionStorage for reload support
  if (orderConfirmation.value) {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(orderConfirmation.value))
  }
}
</script>

<style scoped>
.checkout-page {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

h1 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.loading-text {
  color: #6b7280;
  font-style: italic;
}

.no-addresses {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.address-selection {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.address-fieldset {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem 1.5rem;
}

.address-fieldset legend {
  font-weight: 600;
  padding: 0 0.5rem;
}

.address-option {
  margin: 0.75rem 0;
}

.address-option label {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  cursor: pointer;
}

.address-option input[type="radio"] {
  margin-top: 0.2rem;
  flex-shrink: 0;
}

.error-message {
  background-color: #fef2f2;
  border: 1px solid #fca5a5;
  border-radius: 0.5rem;
  padding: 1rem 1.25rem;
  color: #b91c1c;
}

.error-message p {
  margin: 0.25rem 0;
}

.submit-order-btn {
  align-self: flex-start;
}

/* Confirmation */
.confirmation {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.confirmation h1 {
  color: #15803d;
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

/* Buttons */
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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
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
