<template>
    <header>
        <div>
            <!-- Brand / Home -->
            <NuxtLink to="/"> Filament Shop </NuxtLink>

            <!-- Primary navigation -->
            <nav>
                <NuxtLink to="/"> Home </NuxtLink>
                <NuxtLink to="/categories"> Categories </NuxtLink>
                <NuxtLink to="/cart">
                    Cart
                    <Badge>
                        {{ itemCount }}
                    </Badge>
                </NuxtLink>
            </nav>

            <!-- Localization controls -->
            <div>
                <Select
                    :model-value="language"
                    @update:model-value="(val) => setLanguage(val as 'en' | 'fr' | 'es')"
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="lang in availableLanguages" :key="lang" :value="lang">
                            {{ lang.toUpperCase() }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="currency"
                    @update:model-value="(val) => setCurrency(val as 'USD' | 'EUR' | 'GBP')"
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="curr in availableCurrencies" :key="curr" :value="curr">
                            {{ curr }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Auth controls -->
            <div>
                <template v-if="isAuthenticated">
                    <NuxtLink to="/account"> My Account </NuxtLink>
                    <Button variant="outline" size="sm" @click="logout"> Logout </Button>
                </template>
                <template v-else>
                    <NuxtLink to="/login"> Login </NuxtLink>
                    <NuxtLink to="/register"> Register </NuxtLink>
                </template>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

const { isAuthenticated, logout } = useAuth();
const { itemCount } = useCart();
const { language, currency, availableLanguages, availableCurrencies, setLanguage, setCurrency } =
    useLocalization();
</script>
