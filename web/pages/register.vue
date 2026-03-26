<template>
    <div class="register-page">
        <h1 class="register-page__title">Create an Account</h1>

        <form class="register-page__form" @submit.prevent="handleSubmit">
            <!-- Name -->
            <div class="form-group">
                <label for="name">Name</label>
                <input
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
            <div class="form-group">
                <label for="email">Email</label>
                <input
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
            <div class="form-group">
                <label for="password">Password</label>
                <input
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
            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input
                    id="password_confirmation"
                    v-model="passwordConfirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Repeat your password"
                    required
                />
            </div>

            <!-- Inline validation errors -->
            <p
                v-if="passwordMismatchError"
                class="register-page__error register-page__error--inline"
            >
                Passwords do not match. Please try again.
            </p>
            <p v-if="apiError" class="register-page__error">
                {{ apiError }}
            </p>

            <!-- Submit -->
            <button type="submit" class="register-page__submit" :disabled="loading">
                <span v-if="loading">Registering…</span>
                <span v-else>Create Account</span>
            </button>
        </form>

        <p class="register-page__login-link">
            Already have an account?
            <NuxtLink to="/login">Login</NuxtLink>
        </p>
    </div>
</template>

<script setup lang="ts">
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

<style scoped>
.register-page {
    max-width: 420px;
    margin: 4rem auto;
    padding: 2rem;
}

.register-page__title {
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

.register-page__form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.form-group label {
    font-size: 0.875rem;
    font-weight: 600;
}

.form-group input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
}

.register-page__error {
    color: #c0392b;
    font-size: 0.875rem;
    margin: 0;
}

.register-page__submit {
    padding: 0.625rem 1.25rem;
    background-color: #2c3e50;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.register-page__submit:hover:not(:disabled) {
    background-color: #1a252f;
}

.register-page__submit:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.register-page__login-link {
    text-align: center;
    margin-top: 1.25rem;
    font-size: 0.9rem;
}

.register-page__login-link a {
    color: #2980b9;
    text-decoration: none;
}

.register-page__login-link a:hover {
    text-decoration: underline;
}
</style>
