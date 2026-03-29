<template>
    <div class="min-h-[60vh] flex items-center justify-center px-4 py-12">
        <Card class="w-full max-w-sm">
            <CardHeader>
                <CardTitle class="text-2xl text-center">Login</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="flex flex-col gap-4" @submit.prevent="handleSubmit">
                    <!-- Email field -->
                    <div class="flex flex-col gap-1.5">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                        />
                        <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
                    </div>

                    <!-- Password field -->
                    <div class="flex flex-col gap-1.5">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Min. 8 characters"
                        />
                        <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password }}</p>
                    </div>

                    <!-- API-level error (wrong credentials etc.) -->
                    <Alert v-if="apiError" variant="destructive">
                        <AlertDescription>{{ apiError }}</AlertDescription>
                    </Alert>

                    <!-- Submit -->
                    <Button type="submit" class="w-full" :disabled="loading">
                        {{ loading ? 'Logging in…' : 'Login' }}
                    </Button>
                </form>

                <p class="text-center text-sm text-muted-foreground mt-4">
                    Don't have an account?
                    <NuxtLink to="/register" class="underline hover:text-foreground">Register</NuxtLink>
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
// Login page
// Route: /login
// Public page. Redirects to homepage (or ?redirect param) when already auth.
// ---------------------------------------------------------------------------

const { isAuthenticated, login } = useAuth();
const route = useRoute();

// Redirect already-authenticated visitors immediately.
if (isAuthenticated.value) {
    const destination = (route.query.redirect as string) || "/";
    navigateTo(destination);
}

// ── Form state ─────────────────────────────────────────────────────────────

const email = ref("");
const password = ref("");
const loading = ref(false);
const apiError = ref<string | null>(null);
const errors = ref<{ email?: string; password?: string }>({});

// ── Validation ─────────────────────────────────────────────────────────────

function validate(): boolean {
    const newErrors: { email?: string; password?: string } = {};

    if (!email.value || !email.value.includes("@")) {
        newErrors.email = "A valid email address is required.";
    }

    if (!password.value || password.value.length < 8) {
        newErrors.password = "Password must be at least 8 characters.";
    }

    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
}

// ── Submit handler ─────────────────────────────────────────────────────────

async function handleSubmit(): Promise<void> {
    apiError.value = null;

    if (!validate()) return;

    loading.value = true;

    try {
        const [, error] = await login(email.value, password.value);

        if (error) {
            const err = error as { message?: string; data?: { message?: string } };
            apiError.value =
                err?.data?.message ?? err?.message ?? "Login failed. Please try again.";
            return;
        }

        // Success — hydrate cart before navigating so the header count is current.
        try {
            const { fetchCart } = useCart();
            await fetchCart();
        } catch {
            // Cart load failure must not block redirect
        }

        // Reset form and navigate.
        email.value = "";
        password.value = "";

        const destination = (route.query.redirect as string) || "/";
        navigateTo(destination);
    } finally {
        loading.value = false;
    }
}
</script>
