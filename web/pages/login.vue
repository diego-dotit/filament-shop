<template>
    <div class="login-page">
        <h1>Login</h1>

        <form @submit.prevent="handleSubmit">
            <!-- Email field -->
            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    v-model="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    placeholder="you@example.com"
                />
                <span v-if="errors.email" class="field-error">{{ errors.email }}</span>
            </div>

            <!-- Password field -->
            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    v-model="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    placeholder="Min. 8 characters"
                />
                <span v-if="errors.password" class="field-error">{{ errors.password }}</span>
            </div>

            <!-- API-level error (wrong credentials etc.) -->
            <p v-if="apiError" class="api-error">{{ apiError }}</p>

            <!-- Submit -->
            <button type="submit" :disabled="loading">
                <span v-if="loading">Logging in…</span>
                <span v-else>Login</span>
            </button>
        </form>

        <p>
            Don't have an account?
            <NuxtLink to="/register">Register</NuxtLink>
        </p>
    </div>
</template>

<script setup lang="ts">
// ---------------------------------------------------------------------------
// Login page
// Route: /login
// Public page. Redirects to homepage (or ?redirect param) when already auth.
// ---------------------------------------------------------------------------

const { isAuthenticated, login } = useAuth();
const router = useRouter();
const route = useRoute();

// Redirect already-authenticated visitors immediately.
if (isAuthenticated.value) {
    const destination = (route.query.redirect as string) || "/";
    router.push(destination);
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
        router.push(destination);
    } finally {
        loading.value = false;
    }
}
</script>
