<template>
    <div>
        <h1>Thank You for Your Order!</h1>
        <p>Your order has been placed successfully.</p>

        <Alert>
            <div>
                <p><strong>Order ID:</strong> #{{ orderId }}</p>
                <p><strong>Total:</strong> ${{ totalAmount }}</p>
                <p>Expected delivery in 3–5 business days.</p>
            </div>
        </Alert>

        <div>
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
