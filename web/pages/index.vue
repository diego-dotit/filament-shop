<template>
    <div>
        <h1>Filament Shop</h1>

        <!-- Category filter -->
        <div>
            <Button
                :variant="selectedCategorySlug === null ? 'default' : 'outline'"
                @click="clearCategory"
            >
                All Products
            </Button>
            <Button
                v-for="category in categories"
                :key="category.id"
                :variant="selectedCategorySlug === category.slug ? 'default' : 'outline'"
                @click="selectCategory(category.slug)"
            >
                {{ category.name }}
            </Button>
        </div>

        <div>
            <ProductCard v-for="product in products" :key="product.id" :product="product" />
        </div>

        <div>
            <Button
                variant="outline"
                :disabled="currentPage <= 1"
                @click="goToPage(currentPage - 1)"
            >
                Previous
            </Button>

            <span> Page {{ currentPage }} of {{ totalPages }} </span>

            <Button
                variant="outline"
                :disabled="currentPage >= totalPages"
                @click="goToPage(currentPage + 1)"
            >
                Next
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ ssr: false });
import { ref } from "vue";

const { products, currentPage, pageSize, totalPages, fetchProducts } = useProducts();
const { categories, fetchCategories } = useCategories();

const selectedCategorySlug = ref<string | null>(null);

onMounted(async () => {
    await fetchCategories().catch(() => {
        // Errors are stored in useCategories error state; page still renders
    });
    fetchProducts(1, pageSize.value).catch(() => {
        // Errors are stored in useProducts error state; page still renders
    });
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
