<template>
    <div class="category-page">
        <!-- Back link to homepage -->
        <nav class="category-page__breadcrumb">
            <NuxtLink to="/">← Home</NuxtLink>
        </nav>

        <!-- Error state -->
        <div v-if="notFound" class="category-page__error">
            <p>
                Category not found. Please check the URL or go back to the
                <NuxtLink to="/">homepage</NuxtLink>.
            </p>
        </div>

        <!-- Loading state -->
        <div v-else-if="loading" class="category-page__loading">
            <p>Loading…</p>
        </div>

        <!-- Category content -->
        <template v-else-if="category">
            <!-- Category header -->
            <header class="category-page__header">
                <img
                    v-if="category.image"
                    :src="category.image"
                    :alt="category.name"
                    class="category-page__image"
                />
                <h1 class="category-page__title">{{ category.name }}</h1>
            </header>

            <!-- Subcategories -->
            <section
                v-if="category.children && category.children.length > 0"
                class="category-page__subcategories"
                data-testid="subcategories"
            >
                <h2 class="category-page__subcategories-title">Subcategories</h2>
                <div class="category-page__subcategories-list">
                    <NuxtLink
                        v-for="child in category.children"
                        :key="child.id"
                        :to="`/categories/${child.slug}`"
                        class="category-page__subcategory-chip"
                        data-testid="subcategory-chip"
                    >
                        {{ child.name }}
                    </NuxtLink>
                </div>
            </section>

            <!-- Products grid -->
            <section class="category-page__products">
                <div class="product-grid">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>
            </section>

            <!-- Pagination -->
            <nav v-if="totalPages > 1" class="category-page__pagination" data-testid="pagination">
                <button :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                    Previous
                </button>

                <span>Page {{ currentPage }} of {{ totalPages }}</span>

                <button :disabled="currentPage >= totalPages" @click="goToPage(currentPage + 1)">
                    Next
                </button>
            </nav>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface CategoryResource {
    id: number;
    name: string;
    slug: string;
    image?: string | null;
    children: CategoryResource[];
}

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const route = useRoute();
const api = useApi();

const category = ref<CategoryResource | null>(null);
const products = ref<
    Array<{ id: number; name: string; slug: string; price: string; images: string[] }>
>([]);
const currentPage = ref(1);
const totalPages = ref(1);
const loading = ref(true);
const notFound = ref(false);

// ---------------------------------------------------------------------------
// Data fetching
// ---------------------------------------------------------------------------

async function fetchCategory(slug: string): Promise<void> {
    const response = await api<{ data: CategoryResource }>(`/categories/${slug}`, {});
    category.value = response.data;
}

async function fetchProducts(slug: string, page = 1): Promise<void> {
    const response = await api<{
        data: Array<{ id: number; name: string; slug: string; price: string; images: string[] }>;
        meta: { current_page: number; last_page: number; total: number; per_page: number };
    }>("/products", {
        query: { category_slug: slug, page, per_page: 15 },
    });

    products.value = response.data;
    currentPage.value = response.meta.current_page;
    totalPages.value = response.meta.last_page;
}

async function goToPage(page: number): Promise<void> {
    const slug = route.params.slug as string;
    await fetchProducts(slug, page);
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

onMounted(async () => {
    const slug = route.params.slug as string;

    try {
        await fetchCategory(slug);
        await fetchProducts(slug);
    } catch (e: unknown) {
        const status =
            (e as { statusCode?: number })?.statusCode ??
            (e as { response?: { status?: number } })?.response?.status;

        if (status === 404) {
            throw createError({ statusCode: 404, statusMessage: "Category not found" });
        } else {
            throw e;
        }
    } finally {
        loading.value = false;
    }
});
</script>
