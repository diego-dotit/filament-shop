<script setup lang="ts">
import { ref, reactive, watch } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";

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
const submitting = ref(false);

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
    submitting.value = true;
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
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div>
        <h1>Edit Profile</h1>

        <!-- Success message -->
        <Alert v-if="successMessage" data-testid="success-msg">
            <AlertDescription>{{ successMessage }}</AlertDescription>
        </Alert>

        <!-- Error message -->
        <Alert v-if="errorMessage" data-testid="error-msg" variant="destructive">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
        </Alert>

        <!-- Edit form -->
        <form data-testid="edit-form" @submit.prevent="submitEdit">
            <div>
                <Label for="first-name">First Name</Label>
                <Input
                    id="first-name"
                    v-model="form.first_name"
                    data-testid="input-first-name"
                    type="text"
                    name="first_name"
                    autocomplete="given-name"
                />
            </div>

            <div>
                <Label for="last-name">Last Name</Label>
                <Input
                    id="last-name"
                    v-model="form.last_name"
                    data-testid="input-last-name"
                    type="text"
                    name="last_name"
                    autocomplete="family-name"
                />
            </div>

            <div>
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    data-testid="input-email"
                    type="email"
                    name="email"
                    autocomplete="email"
                />
            </div>

            <div>
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    v-model="form.phone"
                    data-testid="input-phone"
                    type="tel"
                    name="phone"
                    autocomplete="tel"
                />
            </div>

            <div>
                <Button data-testid="submit-btn" type="submit" :disabled="submitting">Save</Button>
                <Button data-testid="cancel-btn" type="button" variant="outline" @click="cancel">
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>
