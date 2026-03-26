<script setup lang="ts">
import { ref, reactive, watch } from "vue";

// Protect this route — unauthenticated visitors are redirected by the middleware.
definePageMeta({ middleware: "auth" });

// Nuxt auto-imports: useAuth, useApi, navigateTo, definePageMeta
const { user, isAuthenticated } = useAuth();
const api = useApi();

// ── Auth guard ──────────────────────────────────────────────────────────────

if (!isAuthenticated.value) {
    navigateTo("/login");
}

// Also watch reactively in case auth state changes after mount
watch(isAuthenticated, (authenticated) => {
    if (!authenticated) {
        navigateTo("/login");
    }
});

// ── Form state ───────────────────────────────────────────────────────────────

const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

// Pre-fill form from current user on mount
const form = reactive({
    first_name: ((user.value as Record<string, unknown>)?.first_name as string) ?? "",
    last_name: ((user.value as Record<string, unknown>)?.last_name as string) ?? "",
    email: user.value?.email ?? "",
    phone: ((user.value as Record<string, unknown>)?.phone as string) ?? "",
});

// ── Actions ─────────────────────────────────────────────────────────────────

function cancel(): void {
    navigateTo("/account/dashboard");
}

async function submitEdit(): Promise<void> {
    successMessage.value = null;
    errorMessage.value = null;
    try {
        const response = await api<{ data: Record<string, unknown> }>("/customers/me", {
            method: "PUT",
            body: {
                first_name: form.first_name,
                last_name: form.last_name,
                email: form.email,
                phone: form.phone,
            },
        });

        // Update user reactive state with updated data
        if (user.value && response.data) {
            Object.assign(user.value, response.data);
        }

        successMessage.value = "Profile updated successfully.";
    } catch (err: unknown) {
        const error = err as {
            data?: { errors?: Record<string, string[]>; message?: string };
            message?: string;
        };
        if (error?.data?.errors) {
            const firstField = Object.values(error.data.errors)[0];
            errorMessage.value = Array.isArray(firstField) ? firstField[0] : String(firstField);
        } else {
            errorMessage.value =
                error?.data?.message ?? error?.message ?? "An error occurred. Please try again.";
        }
    }
}
</script>

<template>
    <div class="account-edit">
        <h1>Edit Profile</h1>

        <!-- Success message -->
        <p v-if="successMessage" data-testid="success-msg" class="success-message">
            {{ successMessage }}
        </p>

        <!-- Error message -->
        <p v-if="errorMessage" data-testid="error-msg" class="error-message">
            {{ errorMessage }}
        </p>

        <!-- Edit form -->
        <form data-testid="edit-form" @submit.prevent="submitEdit">
            <div>
                <label for="first-name">First Name</label>
                <input
                    id="first-name"
                    v-model="form.first_name"
                    data-testid="input-first-name"
                    type="text"
                    name="first_name"
                    autocomplete="given-name"
                />
            </div>

            <div>
                <label for="last-name">Last Name</label>
                <input
                    id="last-name"
                    v-model="form.last_name"
                    data-testid="input-last-name"
                    type="text"
                    name="last_name"
                    autocomplete="family-name"
                />
            </div>

            <div>
                <label for="email">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    data-testid="input-email"
                    type="email"
                    name="email"
                    autocomplete="email"
                />
            </div>

            <div>
                <label for="phone">Phone</label>
                <input
                    id="phone"
                    v-model="form.phone"
                    data-testid="input-phone"
                    type="tel"
                    name="phone"
                    autocomplete="tel"
                />
            </div>

            <div class="form-actions">
                <button type="submit">Save</button>
                <button data-testid="cancel-btn" type="button" @click="cancel">Cancel</button>
            </div>
        </form>
    </div>
</template>
