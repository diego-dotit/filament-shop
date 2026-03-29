<template>
    <div class="max-w-xl mx-auto px-4 py-8">
        <Card>
            <CardContent class="pt-6">
                <h1 class="text-2xl font-bold mb-6">Edit Address</h1>
                <form class="flex flex-col gap-4" data-testid="edit-address-form" @submit.prevent="handleSubmit">
                    <div class="flex flex-col gap-1.5">
                        <Label for="country">Country *</Label>
                        <Input id="country" v-model="form.country" data-testid="input-country" type="text" required />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="city">City *</Label>
                        <Input id="city" v-model="form.city" data-testid="input-city" type="text" required />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="address_line_1">Address Line 1 *</Label>
                        <Input id="address_line_1" v-model="form.address_line_1" data-testid="input-address-line-1" type="text" required />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="address_line_2">Address Line 2</Label>
                        <Input id="address_line_2" v-model="form.address_line_2" data-testid="input-address-line-2" type="text" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="postcode">Postcode *</Label>
                        <Input id="postcode" v-model="form.postcode" data-testid="input-postcode" type="text" required />
                    </div>
                    <Alert v-if="error" variant="destructive" data-testid="error-msg">
                        <AlertDescription>{{ error }}</AlertDescription>
                    </Alert>
                    <div class="flex items-center gap-4 mt-2">
                        <Button data-testid="submit-btn" type="submit" :disabled="submitting">
                            {{ submitting ? "Saving..." : "Save Address" }}
                        </Button>
                        <Button data-testid="cancel-btn" type="button" variant="outline" @click="cancel">
                            Cancel
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";

definePageMeta({ middleware: "auth", ssr: false });

const api = useApi();
const route = useRoute();

const addressId = route.params.id as string;

const form = reactive({
    country: "",
    city: "",
    address_line_1: "",
    address_line_2: "",
    postcode: "",
});

const submitting = ref(false);
const error = ref<string | null>(null);

onMounted(async () => {
    try {
        const response = await api<{ data: Record<string, string> }>(
            `/customers/me/addresses/${addressId}`,
            { method: "GET" }
        );
        if (response?.data) {
            Object.assign(form, {
                country: response.data.country ?? "",
                city: response.data.city ?? "",
                address_line_1: response.data.address_line_1 ?? "",
                address_line_2: response.data.address_line_2 ?? "",
                postcode: response.data.postcode ?? "",
            });
        }
    } catch (err: unknown) {
        const e = err as { data?: { message?: string }; message?: string } | null;
        error.value = e?.data?.message ?? e?.message ?? "Failed to load address.";
    }
});

function cancel(): void {
    navigateTo("/account/addresses");
}

async function handleSubmit(): Promise<void> {
    submitting.value = true;
    error.value = null;
    try {
        await api(`/customers/me/addresses/${addressId}`, {
            method: "PUT",
            body: form,
        });
        await navigateTo("/account/addresses");
    } catch (err: unknown) {
        const e = err as { data?: { message?: string }; message?: string } | null;
        error.value = e?.data?.message ?? e?.message ?? "Failed to save address.";
    } finally {
        submitting.value = false;
    }
}
</script>
