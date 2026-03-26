<template>
  <NuxtLayout>
    <div class="error-page">
      <div class="error-page__content">
        <h1 class="error-page__code">{{ error?.statusCode ?? 'Error' }}</h1>
        <h2 class="error-page__title">{{ title }}</h2>
        <p class="error-page__message">{{ description }}</p>
        <div class="error-page__actions">
          <NuxtLink to="/" class="error-page__home-link">Go Home</NuxtLink>
          <button class="error-page__back-btn" @click="handleBack">Go Back</button>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const error = useError()
const router = useRouter()

const is404 = computed(() => error.value?.statusCode === 404)

const title = computed(() =>
  is404.value ? 'Page Not Found' : 'Something Went Wrong'
)

const description = computed(() =>
  is404.value
    ? 'Sorry, the page you are looking for does not exist or has been moved.'
    : 'An unexpected error occurred. Please try again later or return home.'
)

function handleBack() {
  router.back()
}
</script>

<style scoped>
.error-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 50vh;
  text-align: center;
  padding: 2rem;
}

.error-page__content {
  max-width: 480px;
}

.error-page__code {
  font-size: 6rem;
  font-weight: 700;
  color: #6b7280;
  margin: 0;
  line-height: 1;
}

.error-page__title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #111827;
  margin: 0.5rem 0 1rem;
}

.error-page__message {
  color: #6b7280;
  margin-bottom: 2rem;
}

.error-page__actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.error-page__home-link {
  display: inline-block;
  background-color: #111827;
  color: #fff;
  padding: 0.625rem 1.5rem;
  border-radius: 0.375rem;
  text-decoration: none;
  font-weight: 500;
}

.error-page__home-link:hover {
  background-color: #374151;
}

.error-page__back-btn {
  background: none;
  border: 2px solid #d1d5db;
  color: #374151;
  padding: 0.625rem 1.5rem;
  border-radius: 0.375rem;
  cursor: pointer;
  font-weight: 500;
  font-size: 1rem;
}

.error-page__back-btn:hover {
  border-color: #9ca3af;
}
</style>
