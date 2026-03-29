<template>
    <NuxtLayout>
        <div>
            <Card>
                <CardContent>
                    <h1>
                        {{ error?.statusCode ?? "Error" }}
                    </h1>
                    <h2>{{ title }}</h2>
                    <p>{{ description }}</p>
                    <div>
                        <Button as-child>
                            <NuxtLink to="/">Go Home</NuxtLink>
                        </Button>
                        <Button variant="outline" @click="handleBack"> Go Back </Button>
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
