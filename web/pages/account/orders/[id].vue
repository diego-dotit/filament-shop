<template>
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
        <!-- Back link -->
        <NuxtLink to="/account/orders" class="text-sm text-blue-600 hover:underline"
            >← Back to My Orders</NuxtLink
        >

        <!-- Loading state -->
        <div v-if="loading" class="text-gray-500 py-4">Loading order…</div>

        <!-- Error state -->
        <Alert v-else-if="error" variant="destructive">
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <!-- Order detail -->
        <div v-else-if="currentOrder" class="space-y-8">
            <h1 class="text-2xl font-bold">Order #{{ currentOrder.id }}</h1>

            <!-- Order Summary -->
            <section class="space-y-2">
                <h2 class="text-lg font-semibold">Order Summary</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="font-medium text-gray-600">Status</dt>
                    <dd>{{ currentOrder.status }}</dd>

                    <dt class="font-medium text-gray-600">Date</dt>
                    <dd>{{ formatDate(currentOrder.created_at) }}</dd>

                    <dt class="font-medium text-gray-600">Total</dt>
                    <dd>{{ currentOrder.total_amount }}</dd>

                    <template v-if="currentOrder.subtotal">
                        <dt class="font-medium text-gray-600">Subtotal</dt>
                        <dd>{{ currentOrder.subtotal }}</dd>
                    </template>
                </dl>
            </section>

            <!-- Order Items -->
            <section class="space-y-4">
                <h2 class="text-lg font-semibold">Items</h2>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="px-4 py-2">Product</TableHead>
                            <TableHead class="px-4 py-2">Variant</TableHead>
                            <TableHead class="px-4 py-2">Quantity</TableHead>
                            <TableHead class="px-4 py-2">Price</TableHead>
                            <TableHead class="px-4 py-2">Line Total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in currentOrder.items" :key="item.id">
                            <TableCell class="px-4 py-2">{{ item.product_name }}</TableCell>
                            <TableCell class="px-4 py-2">{{ item.variant_name }}</TableCell>
                            <TableCell class="px-4 py-2">{{ item.quantity }}</TableCell>
                            <TableCell class="px-4 py-2">{{ item.price }}</TableCell>
                            <TableCell class="px-4 py-2">{{ item.line_total }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </section>

            <!-- Addresses -->
            <div class="flex flex-col sm:flex-row gap-8">
                <section class="flex-1 space-y-2">
                    <h2 class="text-lg font-semibold">Billing Address</h2>
                    <address class="not-italic text-sm space-y-1">
                        <div>{{ currentOrder.billing_address.name }}</div>
                        <div>{{ currentOrder.billing_address.line1 }}</div>
                        <div>
                            {{ currentOrder.billing_address.city }},
                            {{ currentOrder.billing_address.country }}
                        </div>
                    </address>
                </section>

                <section class="flex-1 space-y-2">
                    <h2 class="text-lg font-semibold">Shipping Address</h2>
                    <address class="not-italic text-sm space-y-1">
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
