<template>
    <div class="address-form-page">
        <h1>Add New Address</h1>
        <form @submit.prevent="handleSubmit">
            <div class="form-group">
                <label for="country">Country *</label>
                <input id="country" v-model="form.country" type="text" required />
            </div>
            <div class="form-group">
                <label for="city">City *</label>
                <input id="city" v-model="form.city" type="text" required />
            </div>
            <div class="form-group">
                <label for="address_line_1">Address Line 1 *</label>
                <input id="address_line_1" v-model="form.address_line_1" type="text" required />
            </div>
            <div class="form-group">
                <label for="address_line_2">Address Line 2</label>
                <input id="address_line_2" v-model="form.address_line_2" type="text" />
            </div>
            <div class="form-group">
                <label for="postcode">Postcode *</label>
                <input id="postcode" v-model="form.postcode" type="text" required />
            </div>
            <p v-if="error" class="error-msg">{{ error }}</p>
            <div class="form-actions">
                <button type="submit" :disabled="submitting">
                    {{ submitting ? "Saving..." : "Save Address" }}
                </button>
                <NuxtLink to="/checkout">Cancel</NuxtLink>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: "auth" });

const api = useApi();
const route = useRoute();

const form = reactive({
    country: "",
    city: "",
    address_line_1: "",
    address_line_2: "",
    postcode: "",
});

const submitting = ref(false);
const error = ref<string | null>(null);

async function handleSubmit() {
    submitting.value = true;
    error.value = null;
    try {
        await api("/customers/me/addresses", {
            method: "POST",
            body: form,
        });
        const redirect = (route.query.redirect as string) || "/checkout";
        await navigateTo(redirect);
    } catch (err: unknown) {
        const e = err as { data?: { message?: string }; message?: string } | null;
        error.value = e?.data?.message ?? e?.message ?? "Failed to save address.";
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
.address-form-page {
    max-width: 560px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.address-form-page h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-bottom: 1rem;
}

.form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.form-group input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    transition: border-color 0.15s;
}

.form-group input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.error-msg {
    color: #b91c1c;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 0.375rem;
    padding: 0.625rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
}

.form-actions button {
    padding: 0.625rem 1.25rem;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.15s;
}

.form-actions button:hover:not(:disabled) {
    background: #1d4ed8;
}

.form-actions button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.form-actions a {
    color: #6b7280;
    text-decoration: underline;
    font-size: 0.95rem;
}

.form-actions a:hover {
    color: #374151;
}
</style>
