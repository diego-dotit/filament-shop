<template>
    <NuxtLink :to="href" class="category-chip">
        {{ category.name }}
    </NuxtLink>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useSlug } from "~/composables/useSlug";
import type { CategoryResource } from "~/composables/useCategories";

// ---------------------------------------------------------------------------
// Props
// ---------------------------------------------------------------------------

const props = defineProps<{
    category: CategoryResource;
    parentSlug?: string;
}>();

// ---------------------------------------------------------------------------
// Language-aware slug resolution
// ---------------------------------------------------------------------------

const { language } = useLocalization();

/**
 * Resolves the category slug for the current UI language.
 * Resolution order:
 *  1. Slug for current language (from slugs array)
 *  2. Slug for default locale 'en' (from slugs array)
 *  3. category.slug (legacy flat slug — always present)
 */
const href = computed<string>(() => {
    const resolvedSlug = useSlug(props.category, language.value);
    const slug = resolvedSlug ?? props.category.slug;
    return props.parentSlug ? `/${props.parentSlug}/${slug}` : `/${slug}`;
});
</script>

<style scoped>
.category-chip {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 9999px;
    font-size: 0.875rem;
    color: #374151;
    text-decoration: none;
    background: #fff;
    transition:
        background 0.15s,
        border-color 0.15s,
        color 0.15s;
}

.category-chip:hover {
    background: #eff6ff;
    border-color: #2563eb;
    color: #2563eb;
}
</style>
