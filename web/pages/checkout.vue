<template>
    <div>
        <!-- Order Confirmation -->
        <section v-if="orderConfirmation">
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
                <p v-if="loadingAddresses">Loading addresses…</p>

                <!-- No addresses -->
                <div v-else-if="addresses.length === 0">
                    <p>No saved addresses found. Please add an address to continue.</p>
                    <Button as-child>
                        <NuxtLink :to="'/account/addresses/new?redirect=/checkout'"
                            >Add Address</NuxtLink
                        >
                    </Button>
                </div>

                <!-- Address selection -->
                <div v-else data-testid="address-selection">
                    <!-- Billing Address -->
                    <div>
                        <h3>Billing Address</h3>
                        <RadioGroup
                            :model-value="String(billingAddressId)"
                            @update:model-value="(v) => selectBillingAddress(Number(v))"
                        >
                            <div v-for="address in addresses" :key="`billing-${address.id}`">
                                <RadioGroupItem
                                    :id="`billing-${address.id}`"
                                    :value="String(address.id)"
                                />
                                <Label :for="`billing-${address.id}`">
                                    {{ address.address_line_1
                                    }}<span v-if="address.address_line_2"
                                        >, {{ address.address_line_2 }}</span
                                    >, {{ address.city }}, {{ address.postcode }},
                                    {{ address.country }}
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <!-- Shipping Address -->
                    <div>
                        <h3>Shipping Address</h3>
                        <RadioGroup
                            :model-value="String(shippingAddressId)"
                            @update:model-value="(v) => selectShippingAddress(Number(v))"
                        >
                            <div v-for="address in addresses" :key="`shipping-${address.id}`">
                                <RadioGroupItem
                                    :id="`shipping-${address.id}`"
                                    :value="String(address.id)"
                                />
                                <Label :for="`shipping-${address.id}`">
                                    {{ address.address_line_1
                                    }}<span v-if="address.address_line_2"
                                        >, {{ address.address_line_2 }}</span
                                    >, {{ address.city }}, {{ address.postcode }},
                                    {{ address.country }}
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <!-- Add new address -->
                    <Button
                        type="button"
                        variant="outline"
                        data-testid="add-address-btn"
                        @click="showAddressModal = true"
                    >
                        Add new address
                    </Button>

                    <!-- Address Modal (Dialog handles overlay, ESC, and focus trap) -->
                    <Dialog v-model:open="showAddressModal">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add New Address</DialogTitle>
                            </DialogHeader>
                            <form @submit.prevent="handleModalSubmit">
                                <Alert
                                    v-if="modalError"
                                    variant="destructive"
                                    data-testid="modal-error"
                                >
                                    <AlertDescription>{{ modalError }}</AlertDescription>
                                </Alert>
                                <div>
                                    <Label for="country">Country *</Label>
                                    <Input id="country" v-model="modalFormData.country" required />
                                </div>
                                <div>
                                    <Label for="city">City *</Label>
                                    <Input id="city" v-model="modalFormData.city" required />
                                </div>
                                <div>
                                    <Label for="address_line_1">Address Line 1 *</Label>
                                    <Input
                                        id="address_line_1"
                                        v-model="modalFormData.address_line_1"
                                        required
                                    />
                                </div>
                                <div>
                                    <Label for="address_line_2">Address Line 2</Label>
                                    <Input
                                        id="address_line_2"
                                        v-model="modalFormData.address_line_2"
                                    />
                                </div>
                                <div>
                                    <Label for="postcode">Postcode *</Label>
                                    <Input
                                        id="postcode"
                                        v-model="modalFormData.postcode"
                                        required
                                    />
                                </div>
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        data-testid="modal-cancel"
                                        @click="closeModal"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        data-testid="modal-submit"
                                        :disabled="modalSubmitting"
                                    >
                                        {{ modalSubmitting ? "Saving..." : "Save Address" }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <!-- Checkout error -->
                    <Alert v-if="error" variant="destructive" data-testid="checkout-error">
                        <AlertDescription>{{ error }}</AlertDescription>
                    </Alert>

                    <!-- Submit Order -->
                    <Button
                        :disabled="isSubmitting || !billingAddressId || !shippingAddressId"
                        data-testid="submit-order-btn"
                        @click="handleSubmitOrder"
                    >
                        {{ isSubmitting ? "Placing Order…" : "Submit Order" }}
                    </Button>
                </div>
            </template>
            <!-- T2.6: <template v-else> guest checkout form goes here </template> -->
        </section>
    </div>
</template>

<script setup lang="ts">
import type { CustomerAddress } from "~/composables/useCheckout";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";

// Protect this route — unauthenticated visitors are redirected by the middleware.
definePageMeta({ middleware: "auth", ssr: false });
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
defineExpose({
    handleSubmitOrder,
    showAddressModal,
    modalFormData,
    resetModalForm,
    modalError,
    modalSubmitting,
});
</script>
