<template>
  <div class="categories-page">
    <h1>All Categories</h1>
    <div v-if="loading">Loading categories...</div>
    <div v-else-if="categories.length === 0"><p>No categories found.</p></div>
    <div v-else class="categories-grid">
      <NuxtLink
        v-for="cat in categories"
        :key="cat.id"
        :to="`/categories/${cat.slug}`"
        class="category-card"
      >
        <h2>{{ cat.name }}</h2>
        <p v-if="cat.description">{{ cat.description }}</p>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Category {
  id: number
  name: string
  slug: string
  description?: string
}

const api = useApi()
const loading = ref(true)
const categories = ref<Category[]>([])

onMounted(async () => {
  try {
    const response = await api<{ data: Category[] }>('/categories')
    categories.value = response.data
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.categories-page {
  max-width: 1100px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}

.categories-page h1 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .categories-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .categories-grid {
    grid-template-columns: 1fr;
  }
}

.category-card {
  display: block;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1.5rem;
  text-decoration: none;
  color: inherit;
  background: #fff;
  transition: box-shadow 0.2s, border-color 0.2s;
}

.category-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #2563eb;
}

.category-card h2 {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
  color: #111827;
}

.category-card p {
  font-size: 0.9rem;
  color: #6b7280;
  margin: 0;
  line-height: 1.5;
}
</style>
