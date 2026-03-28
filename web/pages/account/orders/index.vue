<template>
    <div class="account-orders">
        <h1>My Orders</h1>

        <!-- Loading state -->
        <div v-if="loading" class="loading">Loading orders…</div>

        <!-- Error state -->
        <div v-else-if="error" class="error">{{ error }}</div>

        <!-- Empty state -->
        <div v-else-if="orders.length === 0" class="empty-state">
            <p>You haven't placed any orders yet</p>
            <NuxtLink to="/">Browse products</NuxtLink>
        </div>

        <!-- Order list -->
        <ul v-else class="orders-list">
            <li v-for="order in orders" :key="order.id" class="order-item">
                <NuxtLink :to="`/account/orders/${order.id}`" class="order-link">
                    <span class="order-id">#{{ order.id }}</span>
                    <span class="order-date">{{ formatDate(order.created_at) }}</span>
                    <span class="order-status">{{ order.status }}</span>
                    <span class="order-total">{{ order.total_amount }}</span>
                </NuxtLink>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
// Protect this route — unauthenticated visitors are redirected by the middleware.
definePageMeta({ middleware: "auth" });

const { isAuthenticated } = useAuth();
const { orders, loading, error, fetchOrders } = useOrders();
if (!isAuthenticated.value) {
    navigateTo("/login");
}

onMounted(async () => {
    if (isAuthenticated.value) {
        await fetchOrders();
    }
});

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString();
}
</script>
