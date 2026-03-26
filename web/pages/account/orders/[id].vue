<template>
    <div class="order-detail">
        <!-- Back link -->
        <NuxtLink to="/account/orders" class="back-link">← Back to My Orders</NuxtLink>

        <!-- Loading state -->
        <div v-if="loading" class="loading">Loading order…</div>

        <!-- Error state -->
        <div v-else-if="error" class="error">{{ error }}</div>

        <!-- Order detail -->
        <div v-else-if="currentOrder" class="order-content">
            <h1>Order #{{ currentOrder.id }}</h1>

            <section class="order-summary">
                <dl>
                    <dt>Status</dt>
                    <dd>{{ currentOrder.status }}</dd>

                    <dt>Date</dt>
                    <dd>{{ formatDate(currentOrder.created_at) }}</dd>

                    <dt>Total</dt>
                    <dd>{{ currentOrder.total_amount }}</dd>

                    <template v-if="currentOrder.subtotal">
                        <dt>Subtotal</dt>
                        <dd>{{ currentOrder.subtotal }}</dd>
                    </template>
                </dl>
            </section>

            <!-- Order Items -->
            <section class="order-items">
                <h2>Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in currentOrder.items" :key="item.id">
                            <td>{{ item.product_name }}</td>
                            <td>{{ item.variant_name }}</td>
                            <td>{{ item.quantity }}</td>
                            <td>{{ item.price }}</td>
                            <td>{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Addresses -->
            <div class="order-addresses">
                <section class="billing-address">
                    <h2>Billing Address</h2>
                    <address>
                        <div>{{ currentOrder.billing_address.name }}</div>
                        <div>{{ currentOrder.billing_address.line1 }}</div>
                        <div>
                            {{ currentOrder.billing_address.city }},
                            {{ currentOrder.billing_address.country }}
                        </div>
                    </address>
                </section>

                <section class="shipping-address">
                    <h2>Shipping Address</h2>
                    <address>
                        <div>{{ currentOrder.shipping_address.name }}</div>
                        <div>{{ currentOrder.shipping_address.line1 }}</div>
                        <div>
                            {{ currentOrder.shipping_address.city }},
                            {{ currentOrder.shipping_address.country }}
                        </div>
                    </address>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// Protect this route — unauthenticated visitors are redirected by the middleware.
definePageMeta({ middleware: "auth" });

const { isAuthenticated } = useAuth();
const { currentOrder, loading, error, fetchOrder } = useOrders();
const route = useRoute();

// Redirect unauthenticated users
if (!isAuthenticated.value) {
    navigateTo("/login");
}

onMounted(async () => {
    if (isAuthenticated.value) {
        await fetchOrder(route.params.id as string);
    }
});

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString();
}
</script>
