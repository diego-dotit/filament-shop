<template>
    <div class="not-found">
        <div class="not-found__content">
            <h1 class="not-found__title">404</h1>
            <h2 class="not-found__subtitle">Page Not Found</h2>
            <p class="not-found__message">Sorry, the page you are looking for does not exist.</p>
            <div class="not-found__actions">
                <NuxtLink to="/" class="not-found__home-link">Go Home</NuxtLink>
                <button class="not-found__back-btn" @click="$router.back()">Go Back</button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const router = useRouter();
</script>

<style scoped>
.not-found {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 50vh;
    text-align: center;
    padding: 2rem;
}

.not-found__content {
    max-width: 480px;
}

.not-found__title {
    font-size: 6rem;
    font-weight: 700;
    color: #6b7280;
    margin: 0;
    line-height: 1;
}

.not-found__subtitle {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    margin: 0.5rem 0 1rem;
}

.not-found__message {
    color: #6b7280;
    margin-bottom: 2rem;
}

.not-found__actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.not-found__home-link {
    display: inline-block;
    background-color: #111827;
    color: #fff;
    padding: 0.625rem 1.5rem;
    border-radius: 0.375rem;
    text-decoration: none;
    font-weight: 500;
}

.not-found__home-link:hover {
    background-color: #374151;
}

.not-found__back-btn {
    background: none;
    border: 2px solid #d1d5db;
    color: #374151;
    padding: 0.625rem 1.5rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-weight: 500;
    font-size: 1rem;
}

.not-found__back-btn:hover {
    border-color: #9ca3af;
}
</style>
