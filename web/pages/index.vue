<template>
  <div class="homepage">
    <h1 class="homepage__title">Filament Shop</h1>

    <div class="homepage__grid">
      <ProductCard
        v-for="product in products"
        :key="product.id"
        :product="product"
      />
    </div>

    <div class="pagination">
      <button
        class="pagination__btn"
        :disabled="currentPage <= 1"
        @click="goToPage(currentPage - 1)"
      >
        Previous
      </button>

      <span class="pagination__info">
        Page {{ currentPage }} of {{ totalPages }}
      </span>

      <button
        class="pagination__btn"
        :disabled="currentPage >= totalPages"
        @click="goToPage(currentPage + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const { products, currentPage, pageSize, totalPages, fetchProducts } = useProducts()

onMounted(() => {
  fetchProducts(1, pageSize.value)
})

async function goToPage(page: number) {
  await fetchProducts(page, pageSize.value)
}
</script>

<style scoped>
.homepage {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}
.homepage__title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}
.homepage__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
}
.pagination__btn {
  padding: 0.5rem 1.25rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: #fff;
  cursor: pointer;
  font-size: 0.875rem;
}
.pagination__btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.pagination__btn:not(:disabled):hover {
  background: #f3f4f6;
}
.pagination__info {
  font-size: 0.875rem;
  color: #6b7280;
}
</style>
