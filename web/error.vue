<template>
    <NuxtLayout>
        <div class="min-h-screen flex items-center justify-center px-4">
            <Card class="w-full max-w-md">
                <CardContent class="pt-6 text-center">
                    <h1 class="text-6xl font-bold text-gray-500 mb-2">
                        {{ error?.statusCode ?? "Error" }}
                    </h1>
                    <h2 class="text-2xl font-semibold mb-4">{{ title }}</h2>
                    <p class="text-gray-600 mb-6">{{ description }}</p>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button class="flex-1" as-child>
                            <NuxtLink to="/">Go Home</NuxtLink>
                        </Button>
                        <Button class="flex-1" variant="outline" @click="handleBack">
                            Go Back
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </NuxtLayout>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

const error = useError();
const router = useRouter();

const is404 = computed(() => error.value?.statusCode === 404);

const title = computed(() => (is404.value ? "Page Not Found" : "Something Went Wrong"));

const description = computed(() =>
    is404.value
        ? "Sorry, the page you are looking for does not exist or has been moved."
        : "An unexpected error occurred. Please try again later or return home."
);

function handleBack() {
    router.back();
}
</script>
