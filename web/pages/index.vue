<template>
    <div class="homepage">
        <h1 class="homepage__title">Filament Shop</h1>

        <!-- Category filter -->
        <div class="homepage__filters">
            <button
                class="filter-btn"
                :class="{ active: selectedCategorySlug === null }"
                @click="clearCategory"
            >
                All Products
            </button>
            <button
                v-for="category in categories"
                :key="category.id"
                class="filter-btn"
                :class="{ active: selectedCategorySlug === category.slug }"
                @click="selectCategory(category.slug)"
            >
                {{ category.name }}
            </button>
        </div>

        <div class="homepage__grid">
            <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>

        <div class="pagination">
            <button
                class="pagination__btn"
                :disabled="currentPage <= 1"
                @click="goToPage(currentPage - 1)"
            >
                Previous
            </button>

            <span class="pagination__info"> Page {{ currentPage }} of {{ totalPages }} </span>

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
import { ref } from "vue";

const { products, currentPage, pageSize, totalPages, fetchProducts } = useProducts();
const { categories, fetchCategories } = useCategories();

const selectedCategorySlug = ref<string | null>(null);

onMounted(async () => {
    await fetchCategories().catch(() => {
        // Errors are stored in useCategories error state; page still renders
    });
    fetchProducts(1, pageSize.value);
});

async function selectCategory(slug: string) {
    selectedCategorySlug.value = slug;
    await fetchProducts(1, pageSize.value, { category_slug: slug });
}

async function clearCategory() {
    selectedCategorySlug.value = null;
    await fetchProducts(1, pageSize.value);
}

async function goToPage(page: number) {
    if (selectedCategorySlug.value) {
        await fetchProducts(page, pageSize.value, { category_slug: selectedCategorySlug.value });
    } else {
        await fetchProducts(page, pageSize.value);
    }
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
.homepage__filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.filter-btn {
    padding: 0.375rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 999px;
    background: #fff;
    cursor: pointer;
    font-size: 0.875rem;
    transition:
        background 0.15s,
        border-color 0.15s,
        color 0.15s;
}
.filter-btn:hover {
    background: #f3f4f6;
}
.filter-btn.active {
    background: #111827;
    border-color: #111827;
    color: #fff;
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
