<template>
    <div>
        <h1>Your Cart</h1>

        <!-- Empty state -->
        <div v-if="items.length === 0">
            <p>Your cart is empty.</p>
            <Button variant="outline" as-child>
                <NuxtLink to="/">Continue Shopping</NuxtLink>
            </Button>
        </div>

        <!-- Cart with items -->
        <div v-else>
            <div>
                <CartItem v-for="item in items" :key="item.id" :item="item" />
            </div>

            <!-- Order summary -->
            <Card>
                <CardHeader>
                    <CardTitle>Order Summary</CardTitle>
                </CardHeader>
                <CardContent>
                    <div>
                        <span>Subtotal</span>
                        <span>${{ subtotal.toFixed(2) }}</span>
                    </div>
                    <Separator />
                    <div>
                        <span>Total</span>
                        <span>${{ cart?.total.toFixed(2) ?? "0.00" }}</span>
                    </div>
                    <Button v-if="isAuthenticated" as-child>
                        <NuxtLink to="/checkout">Proceed to Checkout</NuxtLink>
                    </Button>
                    <Button v-else as-child>
                        <NuxtLink to="/login?redirect=/checkout">Login to Checkout</NuxtLink>
                    </Button>
                    <Button variant="secondary" as-child>
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
