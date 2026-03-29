<template>
    <div>
        <Card>
            <CardContent>
                <h1>Add New Address</h1>
                <form @submit.prevent="handleSubmit">
                    <div>
                        <Label for="country">Country *</Label>
                        <Input id="country" v-model="form.country" type="text" required />
                    </div>
                    <div>
                        <Label for="city">City *</Label>
                        <Input id="city" v-model="form.city" type="text" required />
                    </div>
                    <div>
                        <Label for="address_line_1">Address Line 1 *</Label>
                        <Input
                            id="address_line_1"
                            v-model="form.address_line_1"
                            type="text"
                            required
                        />
                    </div>
                    <div>
                        <Label for="address_line_2">Address Line 2</Label>
                        <Input id="address_line_2" v-model="form.address_line_2" type="text" />
                    </div>
                    <div>
                        <Label for="postcode">Postcode *</Label>
                        <Input id="postcode" v-model="form.postcode" type="text" required />
                    </div>
                    <Alert v-if="error" variant="destructive">
                        <AlertDescription>{{ error }}</AlertDescription>
                    </Alert>
                    <div>
                        <Button type="submit" :disabled="submitting">
                            {{ submitting ? "Saving..." : "Save Address" }}
                        </Button>
                        <Button as-child variant="outline">
                            <NuxtLink to="/account/addresses">Cancel</NuxtLink>
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

definePageMeta({ middleware: "auth" });

const api = useApi();
const route = useRoute();

const form = reactive({
    country: "",
    city: "",
    address_line_1: "",
    address_line_2: "",
    postcode: "",
});

const submitting = ref(false);
const error = ref<string | null>(null);

async function handleSubmit() {
    submitting.value = true;
    error.value = null;
    try {
        await api("/customers/me/addresses", {
            method: "POST",
            body: form,
        });
        const redirect = route.query.redirect === "/checkout" ? "/checkout" : "/account/addresses";
        await navigateTo(redirect);
    } catch (err: unknown) {
        const e = err as { data?: { message?: string }; message?: string } | null;
        error.value = e?.data?.message ?? e?.message ?? "Failed to save address.";
    } finally {
        submitting.value = false;
    }
}
</script>
