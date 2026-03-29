<template>
    <div>
        <h1>All Categories</h1>
        <div v-if="error">
            <p>{{ error }}</p>
        </div>
        <div v-else-if="loading">Loading categories...</div>
        <div v-else-if="categories.length === 0">
            <p>No categories found.</p>
        </div>
        <div v-else>
            <div v-for="cat in categories" :key="cat.id" data-testid="category-card">
                <NuxtLink :to="`/${cat.slug}`">
                    <Card>
                        <CardContent>
                            <h2>{{ cat.name }}</h2>
                            <p v-if="cat.description">
                                {{ cat.description }}
                            </p>
                        </CardContent>
                    </Card>
                </NuxtLink>
                <div v-if="cat.children && cat.children.length > 0" data-testid="subcategories">
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
