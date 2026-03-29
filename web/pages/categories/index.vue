<template>
    <div class="max-w-5xl mx-auto px-6 py-8">
        <h1 class="text-3xl font-bold mb-6">All Categories</h1>
        <div v-if="error" class="text-red-600 py-4">
            <p>{{ error }}</p>
        </div>
        <div v-else-if="loading" class="text-gray-500 py-4">Loading categories...</div>
        <div v-else-if="categories.length === 0" class="py-4">
            <p>No categories found.</p>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="cat in categories"
                :key="cat.id"
                data-testid="category-card"
            >
                <NuxtLink :to="`/${cat.slug}`" class="block no-underline text-inherit">
                    <Card class="hover:shadow-lg transition-shadow">
                        <CardContent class="pt-4">
                            <h2 class="text-lg font-semibold mb-2">{{ cat.name }}</h2>
                            <p v-if="cat.description" class="text-sm text-gray-500 leading-relaxed">
                                {{ cat.description }}
                            </p>
                        </CardContent>
                    </Card>
                </NuxtLink>
                <div
                    v-if="cat.children && cat.children.length > 0"
                    data-testid="subcategories"
                    class="flex flex-wrap gap-2 bg-gray-50 border border-t-0 p-3 rounded-b-lg"
                >
                    <CategoryChip
                        v-for="child in cat.children"
                        :key="child.id"
                        :category="child"
                        :parent-slug="cat.slug"
                        data-testid="subcategory-chip"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
definePageMeta({ ssr: false });
import type { CategoryResource } from "~/composables/useCategories";
import { Card, CardContent } from "@/components/ui/card";

const api = useApi();
const loading = ref(true);
const categories = ref<CategoryResource[]>([]);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        const response = await api<{ data: CategoryResource[] }>("/categories");
        categories.value = response.data;
    } catch {
        error.value = "Failed to load categories. Please try again.";
    } finally {
        loading.value = false;
    }
});
</script>
