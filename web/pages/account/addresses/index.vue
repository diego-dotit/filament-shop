<template>
    <div class="max-w-2xl mx-auto my-8 px-6">
        <!-- Page header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">My Addresses</h1>
            <Button as-child>
                <NuxtLink to="/account/addresses/new">Add New Address</NuxtLink>
            </Button>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="text-gray-500">Loading addresses…</div>

        <!-- Error state -->
        <Alert v-else-if="error" variant="destructive">
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <!-- Empty state -->
        <div v-else-if="addresses.length === 0" class="text-center py-12 text-gray-500">
            <p>No saved addresses</p>
            <NuxtLink to="/account/addresses/new" class="mt-3 inline-block text-primary underline">
                Add your first address
            </NuxtLink>
        </div>

        <!-- Address list -->
        <div v-else class="flex flex-col gap-4">
            <Card v-for="address in addresses" :key="address.id">
                <CardContent class="flex items-start justify-between gap-4 pt-6">
                    <div class="flex-1">
                        <p class="text-sm text-gray-700 leading-relaxed">
                            {{ address.address_line_1 }}
                        </p>
                        <p
                            v-if="address.address_line_2"
                            class="text-sm text-gray-700 leading-relaxed"
                        >
                            {{ address.address_line_2 }}
                        </p>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            {{ address.city }}, {{ address.postcode }}
                        </p>
                        <p class="text-sm text-gray-700 leading-relaxed">{{ address.country }}</p>
                    </div>
                    <div class="flex gap-2 items-center flex-shrink-0">
                        <Button as-child variant="outline" size="sm" data-testid="edit-address">
                            <NuxtLink :to="`/account/addresses/${address.id}/edit`">Edit</NuxtLink>
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            data-testid="delete-address"
                            :disabled="deletingId === address.id"
                            @click="handleDelete(address.id)"
                        >
                            Delete
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { CustomerAddress } from "~/composables/useCheckout";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

definePageMeta({ middleware: "auth", ssr: false });

const { isAuthenticated } = useAuth();

if (!isAuthenticated.value) {
    navigateTo("/login");
}

const api = useApi();
const addresses = ref<CustomerAddress[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const deletingId = ref<number | null>(null);

onMounted(async () => {
    if (!isAuthenticated.value) return;

    loading.value = true;
    error.value = null;

    try {
        const response = await api<{ data: CustomerAddress[] }>("/customers/me/addresses");
        addresses.value = response.data;
    } catch {
        error.value = "Failed to load addresses";
    } finally {
        loading.value = false;
    }
});

async function handleDelete(id: number): Promise<void> {
    if (!confirm("Are you sure you want to delete this address?")) {
        return;
    }

    deletingId.value = id;
    try {
        await api(`/customers/me/addresses/${id}`, { method: "DELETE" });
        addresses.value = addresses.value.filter((a) => a.id !== id);
    } catch {
        error.value = "Failed to delete address";
    } finally {
        deletingId.value = null;
    }
}
</script>
