<template>
    <div class="flex flex-col gap-6">
        <h1 class="text-3xl font-bold text-green-700">Thank You for Your Order!</h1>
        <p class="text-gray-700">Your order has been placed successfully.</p>

        <Alert class="border-green-300 bg-green-50 text-green-800">
            <div class="flex flex-col gap-2">
                <p class="m-0"><strong>Order ID:</strong> #{{ orderId }}</p>
                <p class="m-0"><strong>Total:</strong> ${{ totalAmount }}</p>
                <p class="text-gray-500 italic text-sm mt-2">Expected delivery in 3–5 business days.</p>
            </div>
        </Alert>

        <div class="flex gap-4 flex-wrap">
            <Button as-child variant="default">
                <NuxtLink :to="`/account/orders/${orderId}`">View Order Details</NuxtLink>
            </Button>
            <Button as-child variant="secondary">
                <NuxtLink to="/">Continue Shopping</NuxtLink>
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";

defineProps<{
    orderId: number;
    totalAmount: string;
    createdAt: string;
}>();
</script>

