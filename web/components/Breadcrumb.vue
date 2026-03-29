<template>
    <Breadcrumb>
        <BreadcrumbList>
            <!-- Home item -->
            <BreadcrumbItem>
                <BreadcrumbLink as-child>
                    <NuxtLink to="/">Home</NuxtLink>
                </BreadcrumbLink>
            </BreadcrumbItem>

            <!-- Dynamic items -->
            <template v-for="item in items" :key="item.id">
                <!-- Separator -->
                <BreadcrumbSeparator data-testid="breadcrumb-separator" />

                <!-- Item with url → clickable link -->
                <BreadcrumbItem v-if="item.url">
                    <BreadcrumbLink as-child>
                        <NuxtLink :to="item.url">{{ item.name }}</NuxtLink>
                    </BreadcrumbLink>
                </BreadcrumbItem>

                <!-- Item without url → current page -->
                <BreadcrumbItem v-else>
                    <BreadcrumbPage>{{ item.name }}</BreadcrumbPage>
                </BreadcrumbItem>
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>

<script setup lang="ts">
import type { SlugRecord } from "~/composables/useSlug";
import {
    Breadcrumb,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";

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
