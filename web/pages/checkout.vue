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

            <!-- ── Logged-in user: address selection ──────────────────────── -->
            <!-- T2.6: add v-else here for guest checkout flow -->
            <template v-if="isAuthenticated">
                <!-- Loading addresses -->
                <p v-if="loadingAddresses" class="loading-text">Loading addresses…</p>

                <!-- No addresses -->
                <div v-else-if="addresses.length === 0" class="no-addresses">
                    <p>No saved addresses found. Please add an address to continue.</p>
                    <NuxtLink
                        :to="'/account/addresses/new?redirect=/checkout'"
                        class="btn btn-primary"
                    >
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
                                    {{ address.address_line_1
                                    }}<span v-if="address.address_line_2"
                                        >, {{ address.address_line_2 }}</span
                                    >, {{ address.city }}, {{ address.postcode }},
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
                                    {{ address.address_line_1
                                    }}<span v-if="address.address_line_2"
                                        >, {{ address.address_line_2 }}</span
                                    >, {{ address.city }}, {{ address.postcode }},
                                    {{ address.country }}
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Add new address -->
                    <button
                        type="button"
                        class="btn btn-secondary add-address-btn"
                        @click="showAddressModal = true"
                    >
                        Add new address
                    </button>

                    <!-- Address Modal Overlay -->
                    <div
                        v-if="showAddressModal"
                        class="modal-overlay"
                        role="dialog"
                        aria-labelledby="address-modal-title"
                        aria-modal="true"
                        ref="modalElement"
                        tabindex="-1"
                        @click.self="showAddressModal = false"
                        @keydown.esc="showAddressModal = false"
                        @keydown.tab="handleModalTabKey"
                    >
                        <div class="modal-content">
                            <header class="modal-header">
                                <h2 id="address-modal-title">Add New Address</h2>
                            </header>
                            <form class="modal-form" @submit.prevent="handleModalSubmit">
                                <p v-if="modalError" class="error-msg">{{ modalError }}</p>
                                <div class="form-group">
                                    <label for="country">Country *</label>
                                    <input
                                        id="country"
                                        v-model="modalFormData.country"
                                        type="text"
                                        required
                                    />
                                </div>
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input
                                        id="city"
                                        v-model="modalFormData.city"
                                        type="text"
                                        required
                                    />
                                </div>
                                <div class="form-group">
                                    <label for="address_line_1">Address Line 1 *</label>
                                    <input
                                        id="address_line_1"
                                        v-model="modalFormData.address_line_1"
                                        type="text"
                                        required
                                    />
                                </div>
                                <div class="form-group">
                                    <label for="address_line_2">Address Line 2</label>
                                    <input
                                        id="address_line_2"
                                        v-model="modalFormData.address_line_2"
                                        type="text"
                                    />
                                </div>
                                <div class="form-group">
                                    <label for="postcode">Postcode *</label>
                                    <input
                                        id="postcode"
                                        v-model="modalFormData.postcode"
                                        type="text"
                                        required
                                    />
                                </div>
                                <div class="form-actions">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="modalSubmitting"
                                    >
                                        {{ modalSubmitting ? "Saving..." : "Save Address" }}
                                    </button>
                                </div>
                            </form>
                            <footer class="modal-footer">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    @click="closeModal"
                                >
                                    Cancel
                                </button>
                            </footer>
                        </div>
                    </div>

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
                        {{ isSubmitting ? "Placing Order…" : "Submit Order" }}
                    </button>
                </div>
            </template>
            <!-- T2.6: <template v-else> guest checkout form goes here </template> -->
        </section>
    </div>
</template>

<script setup lang="ts">
import type { CustomerAddress } from "~/composables/useCheckout";

// Protect this route — unauthenticated visitors are redirected by the middleware.
definePageMeta({ middleware: "auth" });

// Reactive auth state — also used in the template for v-if="isAuthenticated"
// so T2.6 can add v-else guest blocks alongside the logged-in flow.
const { isAuthenticated } = useAuth();

// Client-side safety net: redirect if somehow not authenticated.
// NOTE: no `await` here — keeping setup() synchronous prevents the Suspense
// requirement that would make the component un-renderable in tests and in
// non-Suspense contexts. The `middleware: "auth"` above is the primary guard.
if (!isAuthenticated.value) {
    navigateTo("/login");
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
} = useCheckout();

const api = useApi();

const loadingAddresses = ref(false);
const showAddressModal = ref(false);
const modalElement = ref<HTMLElement | null>(null);
const modalError = ref<string | null>(null);
const modalSubmitting = ref(false);

const modalFormData = reactive({
    country: "",
    city: "",
    address_line_1: "",
    address_line_2: "",
    postcode: "",
});

function resetModalForm(): void {
    Object.assign(modalFormData, {
        country: "",
        city: "",
        address_line_1: "",
        address_line_2: "",
        postcode: "",
    });
}

function closeModal(): void {
    resetModalForm();
    showAddressModal.value = false;
}

async function handleModalSubmit(): Promise<void> {
    modalError.value = null;
    modalSubmitting.value = true;
    try {
        const response = await api<{ data: CustomerAddress }>("/customers/me/addresses", {
            method: "POST",
            body: { ...modalFormData },
        });
        const newAddress = response?.data;
        if (newAddress?.id) {
            selectBillingAddress(newAddress.id);
            selectShippingAddress(newAddress.id);
        }
        try {
            await fetchAddresses();
        } catch {
            error.value = "Failed to refresh addresses.";
        }
        closeModal();
    } catch (err: unknown) {
        const e = err as { data?: { message?: string }; message?: string } | null;
        modalError.value = e?.data?.message ?? e?.message ?? "Failed to save address.";
    } finally {
        modalSubmitting.value = false;
    }
}

// Focus the modal when it opens so keyboard navigation starts inside it.
watch(showAddressModal, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            modalElement.value?.focus();
        });
    }
});

/**
 * Manual focus trap: cycle Tab / Shift+Tab within the modal's focusable elements.
 */
function handleModalTabKey(event: KeyboardEvent): void {
    const modal = modalElement.value;
    if (!modal) return;

    const focusableSelectors =
        'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    const focusable = Array.from(modal.querySelectorAll<HTMLElement>(focusableSelectors));
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = document.activeElement as HTMLElement;

    if (event.shiftKey) {
        if (active === first) {
            event.preventDefault();
            last.focus();
        }
    } else {
        if (active === last) {
            event.preventDefault();
            first.focus();
        }
    }
}

// Persist order confirmation in sessionStorage so it survives page reload.
const SESSION_KEY = "checkout.orderConfirmation";

onMounted(async () => {
    // Restore confirmation from sessionStorage if present (page reload scenario)
    if (!orderConfirmation.value) {
        const stored = sessionStorage.getItem(SESSION_KEY);
        if (stored) {
            try {
                orderConfirmation.value = JSON.parse(stored);
            } catch {
                sessionStorage.removeItem(SESSION_KEY);
            }
        }
    }

    // Fetch addresses only when no confirmation is shown
    if (!orderConfirmation.value) {
        loadingAddresses.value = true;
        try {
            await fetchAddresses();
        } finally {
            loadingAddresses.value = false;
        }
    }
});

onBeforeRouteLeave(() => {
    orderConfirmation.value = null;
    sessionStorage.removeItem(SESSION_KEY);
});

async function handleSubmitOrder(): Promise<void> {
    await submitOrder();

    // Persist confirmation to sessionStorage for reload support
    if (orderConfirmation.value) {
        sessionStorage.setItem(SESSION_KEY, JSON.stringify(orderConfirmation.value));
    }
}

// Expose handleSubmitOrder, showAddressModal, and modalFormData so tests (and any parent component) can access them.
defineExpose({ handleSubmitOrder, showAddressModal, modalFormData, resetModalForm, modalError, modalSubmitting });
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

/* ── Address Modal Overlay ── */
.modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.modal-content {
    background-color: #ffffff;
    border-radius: 0.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 560px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 0.5rem;
}

/* ── Modal form field styles (mirrors /account/addresses/new.vue) ── */
.modal-form .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-bottom: 0;
}

.modal-form .form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.modal-form .form-group input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    transition: border-color 0.15s;
}

.modal-form .form-group input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.modal-form .error-msg {
    color: #b91c1c;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 0.375rem;
    padding: 0.625rem 1rem;
    font-size: 0.9rem;
    margin-bottom: 0;
}
</style>
