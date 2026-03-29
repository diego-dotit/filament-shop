<template>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold mb-6">Your Cart</h1>

        <!-- Empty state -->
        <div v-if="items.length === 0" class="text-center py-12">
            <p class="text-muted-foreground text-lg mb-4">Your cart is empty.</p>
            <Button variant="outline" as-child>
                <NuxtLink to="/">Continue Shopping</NuxtLink>
            </Button>
        </div>

        <!-- Cart with items -->
        <div v-else class="flex gap-8 items-start">
            <div class="flex-1">
                <CartItem v-for="item in items" :key="item.id" :item="item" />
            </div>

            <!-- Order summary -->
            <Card class="w-72 shrink-0">
                <CardHeader>
                    <CardTitle>Order Summary</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-2">
                    <div class="flex justify-between py-1 text-sm">
                        <span>Subtotal</span>
                        <span>${{ subtotal.toFixed(2) }}</span>
                    </div>
                    <Separator />
                    <div class="flex justify-between py-1 font-bold">
                        <span>Total</span>
                        <span>${{ cart?.total.toFixed(2) ?? "0.00" }}</span>
                    </div>
                    <Button v-if="isAuthenticated" as-child class="w-full mt-4">
                        <NuxtLink to="/checkout">Proceed to Checkout</NuxtLink>
                    </Button>
                    <Button v-else as-child class="w-full mt-4">
                        <NuxtLink to="/login?redirect=/checkout">Login to Checkout</NuxtLink>
                    </Button>
                    <Button variant="secondary" as-child class="w-full">
                        <NuxtLink to="/">Continue Shopping</NuxtLink>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ ssr: false });
import { computed, onMounted } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Button } from "@/components/ui/button";

const { isAuthenticated } = useAuth();
const { cart, items, fetchCart } = useCart();

const subtotal = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.line_total, 0)
);

onMounted(async () => {
    await fetchCart();
});
</script>
