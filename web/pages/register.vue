<template>
    <div>
        <Card>
            <CardHeader>
                <CardTitle>Create an Account</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="handleSubmit">
                    <!-- Name -->
                    <div>
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="name"
                            type="text"
                            name="name"
                            autocomplete="name"
                            placeholder="Your full name"
                            required
                            minlength="3"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            required
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            placeholder="Min. 8 characters"
                            required
                            minlength="8"
                        />
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input
                            id="password_confirmation"
                            v-model="passwordConfirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Repeat your password"
                            required
                        />
                    </div>

                    <!-- Inline password mismatch error -->
                    <p v-if="passwordMismatchError">Passwords do not match. Please try again.</p>

                    <!-- API error -->
                    <Alert v-if="apiError" variant="destructive">
                        <AlertDescription>{{ apiError }}</AlertDescription>
                    </Alert>

                    <!-- Submit -->
                    <Button type="submit" :disabled="loading">
                        {{ loading ? "Registering…" : "Create Account" }}
                    </Button>
                </form>

                <p>
                    Already have an account?
                    <NuxtLink to="/login">Login</NuxtLink>
                </p>
            </CardContent>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";

// ---------------------------------------------------------------------------
// Guard: redirect authenticated users away from this page
// ---------------------------------------------------------------------------

const { isAuthenticated, register } = useAuth();

if (isAuthenticated.value) {
    navigateTo("/");
}

// ---------------------------------------------------------------------------
// Form state
// ---------------------------------------------------------------------------

const name = ref("");
const email = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const loading = ref(false);
const apiError = ref<string | null>(null);
const passwordMismatchError = ref(false);

// ---------------------------------------------------------------------------
// Submit handler
// ---------------------------------------------------------------------------

async function handleSubmit(): Promise<void> {
    // Reset previous errors
    apiError.value = null;
    passwordMismatchError.value = false;

    // Client-side: passwords must match before any API call
    if (password.value !== passwordConfirmation.value) {
        passwordMismatchError.value = true;
        return;
    }

    loading.value = true;

    try {
        const [result, error] = await register(
            name.value,
            email.value,
            password.value,
            passwordConfirmation.value
        );

        if (error !== null || result === null) {
            // Extract a human-readable message from the error
            const err = error as { message?: string; data?: { message?: string } } | null;
            apiError.value =
                err?.data?.message ?? err?.message ?? "Registration failed. Please try again.";
            return;
        }

        // Success: reset the form and redirect
        name.value = "";
        email.value = "";
        password.value = "";
        passwordConfirmation.value = "";

        await navigateTo("/");
    } finally {
        loading.value = false;
    }
}
</script>
