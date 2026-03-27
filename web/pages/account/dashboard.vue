<script setup lang="ts">
import { computed, watch } from "vue";

definePageMeta({ middleware: "auth" });

const { user, isAuthenticated } = useAuth();

if (!isAuthenticated.value) {
    navigateTo("/login");
}

watch(isAuthenticated, (authenticated) => {
    if (!authenticated) {
        navigateTo("/login");
    }
});

const userRecord = computed(() => user.value as Record<string, unknown>);
</script>

<template>
    <div class="account-dashboard">
        <h1>My Account</h1>
        <p v-if="user" data-testid="greeting">
            Welcome, {{ userRecord.first_name }} {{ userRecord.last_name }}
        </p>
        <nav class="account-nav">
            <NuxtLink to="/account/orders" data-testid="nav-orders">My Orders</NuxtLink>
            <NuxtLink to="/account/edit" data-testid="nav-edit">Edit Profile</NuxtLink>
            <NuxtLink to="/account/addresses/new" data-testid="nav-addresses">Addresses</NuxtLink>
        </nav>
    </div>
</template>
