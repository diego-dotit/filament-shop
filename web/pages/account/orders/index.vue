<template>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">My Orders</h1>

        <!-- Loading state -->
        <div v-if="loading" class="text-gray-500">Loading orders…</div>

        <!-- Error state -->
        <Alert v-else-if="error" variant="destructive">
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <!-- Empty state -->
        <div v-else-if="orders.length === 0" class="text-center py-12">
            <p class="text-gray-500 mb-4">You haven't placed any orders yet</p>
            <NuxtLink to="/" class="text-blue-600 hover:underline">Browse products</NuxtLink>
        </div>

        <!-- Order list -->
        <Table v-else>
            <TableHeader>
                <TableRow>
                    <TableHead>Order ID</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Total</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="order in orders" :key="order.id">
                    <TableCell>
                        <NuxtLink
                            :to="`/account/orders/${order.id}`"
                            class="text-blue-600 hover:underline"
                        >
                            #{{ order.id }}
                        </NuxtLink>
                    </TableCell>
                    <TableCell>{{ formatDate(order.created_at) }}</TableCell>
                    <TableCell>{{ order.status }}</TableCell>
                    <TableCell>{{ order.total_amount }}</TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>

<script setup lang="ts">
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
} from "@/components/ui/table";
import { Alert, AlertDescription } from "@/components/ui/alert";

// Protect this route — unauthenticated visitors are redirected by the middleware.
definePageMeta({ middleware: "auth", ssr: false });

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
