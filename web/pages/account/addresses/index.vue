<template>
    <div class="account-addresses">
        <div class="page-header">
            <h1>My Addresses</h1>
            <NuxtLink to="/account/addresses/new" class="btn-add">Add New Address</NuxtLink>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="loading">Loading addresses…</div>

        <!-- Error state -->
        <div v-else-if="error" class="error">{{ error }}</div>

        <!-- Empty state -->
        <div v-else-if="addresses.length === 0" class="empty-state">
            <p>No saved addresses</p>
            <NuxtLink to="/account/addresses/new">Add your first address</NuxtLink>
        </div>

        <!-- Address list -->
        <ul v-else class="addresses-list">
            <li v-for="address in addresses" :key="address.id" class="address-item">
                <div class="address-details">
                    <p class="address-line">{{ address.address_line_1 }}</p>
                    <p v-if="address.address_line_2" class="address-line">{{ address.address_line_2 }}</p>
                    <p class="address-line">{{ address.city }}, {{ address.postcode }}</p>
                    <p class="address-line">{{ address.country }}</p>
                </div>
                <div class="address-actions">
                    <NuxtLink
                        :to="`/account/addresses/${address.id}/edit`"
                        class="btn-edit"
                        data-testid="edit-address"
                    >
                        Edit
                    </NuxtLink>
                    <button
                        class="btn-delete"
                        data-testid="delete-address"
                        @click="handleDelete(address.id)"
                    >
                        Delete
                    </button>
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import type { CustomerAddress } from "~/composables/useCheckout";

definePageMeta({ middleware: "auth" });

const { isAuthenticated } = useAuth();

if (!isAuthenticated.value) {
    navigateTo("/login");
}

const api = useApi();
const addresses = ref<CustomerAddress[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

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

    try {
        await api(`/customers/me/addresses/${id}`, { method: "DELETE" });
        addresses.value = addresses.value.filter((a) => a.id !== id);
    } catch {
        error.value = "Failed to delete address";
    }
}
</script>

<style scoped>
.account-addresses {
    max-width: 640px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
}

.btn-add {
    padding: 0.5rem 1rem;
    background: #2563eb;
    color: #fff;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
}

.btn-add:hover {
    background: #1d4ed8;
}

.loading {
    color: #6b7280;
    font-size: 1rem;
}

.error {
    color: #b91c1c;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
}

.empty-state {
    color: #6b7280;
    text-align: center;
    padding: 2rem 0;
}

.empty-state a {
    display: inline-block;
    margin-top: 0.75rem;
    color: #2563eb;
    text-decoration: underline;
}

.addresses-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.address-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
}

.address-details {
    flex: 1;
}

.address-line {
    margin: 0;
    font-size: 0.95rem;
    color: #374151;
    line-height: 1.5;
}

.address-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-shrink: 0;
}

.btn-edit {
    padding: 0.375rem 0.75rem;
    background: #f3f4f6;
    color: #374151;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
}

.btn-edit:hover {
    background: #e5e7eb;
}

.btn-delete {
    padding: 0.375rem 0.75rem;
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
}

.btn-delete:hover {
    background: #fee2e2;
}
</style>
