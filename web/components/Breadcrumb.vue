<template>
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb__list">
            <!-- Home item -->
            <li class="breadcrumb__item">
                <NuxtLink to="/" class="breadcrumb__link">Home</NuxtLink>
            </li>

            <!-- Dynamic items -->
            <template v-for="(item, index) in items" :key="item.id">
                <!-- Separator -->
                <li class="breadcrumb__separator" data-testid="breadcrumb-separator" aria-hidden="true">
                    →
                </li>

                <!-- Item with url → clickable link -->
                <li class="breadcrumb__item">
                    <NuxtLink v-if="item.url" :to="item.url" class="breadcrumb__link">
                        {{ item.name }}
                    </NuxtLink>
                    <!-- Item without url → current page (non-link) -->
                    <span v-else class="breadcrumb__current" aria-current="page">
                        {{ item.name }}
                    </span>
                </li>
            </template>
        </ol>
    </nav>
</template>

<script setup lang="ts">
import type { SlugRecord } from "~/composables/useSlug";

// ---------------------------------------------------------------------------
// Props
// ---------------------------------------------------------------------------

export interface BreadcrumbItem {
    id: number;
    name: string;
    slugs: SlugRecord[];
    url?: string;
}

defineProps<{
    items: BreadcrumbItem[];
}>();
</script>

<style scoped>
.breadcrumb {
    padding: 0.5rem 0;
}

.breadcrumb__list {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.25rem;
    list-style: none;
    margin: 0;
    padding: 0;
    font-size: 0.875rem;
}

.breadcrumb__item {
    display: flex;
    align-items: center;
}

.breadcrumb__separator {
    display: flex;
    align-items: center;
    color: #9ca3af;
    padding: 0 0.25rem;
}

.breadcrumb__link {
    color: #3b82f6;
    text-decoration: none;
}

.breadcrumb__link:hover {
    text-decoration: underline;
}

.breadcrumb__current {
    color: #374151;
    font-weight: 500;
}
</style>
