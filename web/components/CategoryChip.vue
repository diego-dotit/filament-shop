<template>
    <NuxtLink :to="href">
        <Badge variant="outline">
            {{ category.name }}
        </Badge>
    </NuxtLink>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useSlug } from "~/composables/useSlug";
import type { CategoryResource } from "~/composables/useCategories";
import { Badge } from "@/components/ui/badge";

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
