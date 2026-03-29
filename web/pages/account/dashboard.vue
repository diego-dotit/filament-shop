<template>
    <div>
        <h1>My Account</h1>

        <!-- Success message -->
        <Alert v-if="successMessage" data-testid="success-msg">
            <AlertDescription>{{ successMessage }}</AlertDescription>
        </Alert>

        <!-- Error message -->
        <Alert v-if="errorMessage" data-testid="error-msg" variant="destructive">
            <AlertDescription>{{ errorMessage }}</AlertDescription>
        </Alert>

        <!-- Profile card -->
        <Card>
            <CardHeader>
                <CardTitle>Profile</CardTitle>
            </CardHeader>
            <CardContent>
                <!-- Display mode -->
                <div v-if="!isEditing">
                    <p v-if="user" data-testid="greeting">
                        Welcome, {{ userRecord.first_name }} {{ userRecord.last_name }}
                    </p>
                    <p v-if="user">{{ user?.email }}</p>
                    <p v-if="user">{{ userRecord.phone }}</p>
                    <div>
                        <Button data-testid="edit-btn" variant="outline" @click="openEdit">
                            Edit Profile
                        </Button>
                    </div>
                </div>

                <!-- Edit form -->
                <form v-else data-testid="edit-form" @submit.prevent="submitEdit">
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
                        <Button type="submit" :disabled="submitting">Save</Button>
                        <Button
                            data-testid="cancel-btn"
                            type="button"
                            variant="outline"
                            @click="cancelEdit"
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Navigation card -->
        <Card>
            <CardHeader>
                <CardTitle>Quick Links</CardTitle>
            </CardHeader>
            <CardContent>
                <nav>
                    <Button as-child variant="ghost">
                        <NuxtLink to="/account/orders" data-testid="nav-orders">My Orders</NuxtLink>
                    </Button>
                    <Button as-child variant="ghost">
                        <NuxtLink to="/account/addresses" data-testid="nav-addresses"
                            >Addresses</NuxtLink
                        >
                    </Button>
                </nav>
            </CardContent>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

definePageMeta({ middleware: "auth" });

const { user, isAuthenticated } = useAuth();
const api = useApi();

// ── Auth guard ──────────────────────────────────────────────────────────────

if (!isAuthenticated.value) {
    navigateTo("/login");
}

watch(isAuthenticated, (authenticated) => {
    if (!authenticated) {
        navigateTo("/login");
    }
});

// ── Derived user record ─────────────────────────────────────────────────────

const userRecord = computed(() => user.value as Record<string, unknown>);

// ── Edit mode state ─────────────────────────────────────────────────────────

const isEditing = ref(false);
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);
const submitting = ref(false);

const form = reactive({
    first_name: (userRecord.value?.first_name as string) ?? "",
    last_name: (userRecord.value?.last_name as string) ?? "",
    email: (user.value?.email as string) ?? "",
    phone: (userRecord.value?.phone as string) ?? "",
});

function openEdit(): void {
    form.first_name = (userRecord.value?.first_name as string) ?? "";
    form.last_name = (userRecord.value?.last_name as string) ?? "";
    form.email = (user.value?.email as string) ?? "";
    form.phone = (userRecord.value?.phone as string) ?? "";
    successMessage.value = null;
    errorMessage.value = null;
    isEditing.value = true;
}

function cancelEdit(): void {
    isEditing.value = false;
    successMessage.value = null;
    errorMessage.value = null;
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

        if (user.value && response.data) {
            Object.assign(user.value, response.data);
        }

        isEditing.value = false;
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
