<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Breadcrumb navigation -->
        <Breadcrumb :items="breadcrumbItems" class="mb-6" />

        <!-- Error state -->
        <div v-if="notFound" data-testid="category-error" class="py-12 text-center">
            <p class="text-gray-600">
                Category not found. Please check the URL or go back to the
                <NuxtLink to="/" class="underline text-primary">homepage</NuxtLink>.
            </p>
        </div>

        <!-- Loading state -->
        <div v-else-if="loading" data-testid="category-loading" class="py-12 text-center">
            <p class="text-gray-500">Loading…</p>
        </div>

        <!-- Category content -->
        <template v-else-if="category">
            <!-- Category header -->
            <header class="mb-8">
                <img
                    v-if="category.image"
                    :src="category.image"
                    :alt="category.name"
                    class="w-full max-h-64 object-cover rounded-lg mb-4"
                />
                <h1 class="text-3xl font-bold">{{ category.name }}</h1>
            </header>

            <!-- Subcategories -->
            <section
                v-if="category.children && category.children.length > 0"
                class="mb-8"
                data-testid="subcategories"
            >
                <h2 class="text-lg font-semibold mb-3">Subcategories</h2>
                <div class="flex flex-wrap gap-2">
                    <CategoryChip
                        v-for="child in category.children"
                        :key="child.id"
                        :category="child"
                        :parent-slug="category.slug"
                        data-testid="subcategory-chip"
                    />
                </div>
            </section>

            <!-- Products grid -->
            <section class="mb-8" data-testid="products-section">
                <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-6">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>
            </section>

            <!-- Pagination -->
            <nav
                v-if="totalPages > 1"
                class="flex items-center justify-center gap-4"
                data-testid="pagination"
            >
                <Button
                    variant="outline"
                    :disabled="currentPage <= 1"
                    @click="goToPage(currentPage - 1)"
                >
                    Previous
                </Button>

                <span class="text-sm text-gray-500">Page {{ currentPage }} of {{ totalPages }}</span>

                <Button
                    variant="outline"
                    :disabled="currentPage >= totalPages"
                    @click="goToPage(currentPage + 1)"
                >
                    Next
                </Button>
            </nav>
        </template>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ ssr: false });
import { ref, computed, onMounted } from "vue";
import type { CategoryResource } from "~/composables/useCategories";
import Breadcrumb from "@/components/Breadcrumb.vue";
import { Button } from "@/components/ui/button";
import type { BreadcrumbItem } from "@/components/Breadcrumb.vue";

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
// Computed
// ---------------------------------------------------------------------------

const breadcrumbItems = computed<BreadcrumbItem[]>(() => {
    if (!category.value) return [];

    const items: BreadcrumbItem[] = [];

    if (category.value.parent) {
        items.push({
            id: category.value.parent.id,
            name: category.value.parent.name,
            slugs: [],
            url: `/${category.value.parent.slug}`,
        });
    }

    items.push({
        id: category.value.id,
        name: category.value.name,
        slugs: category.value.slugs ?? [],
        // No url — this is the current page (renders as non-link)
    });

    return items;
});

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
            notFound.value = true;
        } else {
            throw e;
        }
    } finally {
        loading.value = false;
    }
});
</script>
