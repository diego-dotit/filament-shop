<template>
    <header class="sticky top-0 z-50 bg-slate-900 text-white shadow-md">
        <div class="flex items-center gap-6 max-w-7xl mx-auto px-6 py-3">
            <!-- Brand / Home -->
            <NuxtLink to="/" class="text-rose-500 font-bold text-xl mr-auto no-underline hover:text-rose-400 transition-colors">
                Filament Shop
            </NuxtLink>

            <!-- Primary navigation -->
            <nav class="flex gap-5">
                <NuxtLink to="/" class="text-slate-300 hover:text-white text-sm transition-colors no-underline">
                    Home
                </NuxtLink>
                <NuxtLink to="/categories" class="text-slate-300 hover:text-white text-sm transition-colors no-underline">
                    Categories
                </NuxtLink>
                <NuxtLink to="/cart" class="flex items-center gap-1.5 text-slate-300 hover:text-white text-sm transition-colors no-underline">
                    Cart
                    <Badge class="bg-rose-500 text-white hover:bg-rose-500 min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full text-xs font-semibold">
                        {{ itemCount }}
                    </Badge>
                </NuxtLink>
            </nav>

            <!-- Localization controls -->
            <div class="flex items-center gap-2">
                <Select :model-value="language" @update:model-value="(val) => setLanguage(val as 'en' | 'fr' | 'es')">
                    <SelectTrigger class="w-20 h-8 text-xs bg-slate-800 border-slate-600 text-slate-300 hover:border-slate-400 hover:text-white focus:ring-0">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent class="bg-slate-800 border-slate-600 text-slate-300">
                        <SelectItem
                            v-for="lang in availableLanguages"
                            :key="lang"
                            :value="lang"
                            class="text-xs hover:bg-slate-700 focus:bg-slate-700 cursor-pointer"
                        >
                            {{ lang.toUpperCase() }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select :model-value="currency" @update:model-value="(val) => setCurrency(val as 'USD' | 'EUR' | 'GBP')">
                    <SelectTrigger class="w-20 h-8 text-xs bg-slate-800 border-slate-600 text-slate-300 hover:border-slate-400 hover:text-white focus:ring-0">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent class="bg-slate-800 border-slate-600 text-slate-300">
                        <SelectItem
                            v-for="curr in availableCurrencies"
                            :key="curr"
                            :value="curr"
                            class="text-xs hover:bg-slate-700 focus:bg-slate-700 cursor-pointer"
                        >
                            {{ curr }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Auth controls -->
            <div class="flex items-center gap-4">
                <template v-if="isAuthenticated">
                    <NuxtLink to="/account" class="text-slate-300 hover:text-white text-sm transition-colors no-underline">
                        My Account
                    </NuxtLink>
                    <Button
                        variant="outline"
                        size="sm"
                        class="border-rose-500 text-rose-500 hover:bg-rose-500 hover:text-white transition-colors"
                        @click="logout"
                    >
                        Logout
                    </Button>
                </template>
                <template v-else>
                    <NuxtLink to="/login" class="text-slate-300 hover:text-white text-sm transition-colors no-underline">
                        Login
                    </NuxtLink>
                    <NuxtLink to="/register" class="text-slate-300 hover:text-white text-sm transition-colors no-underline">
                        Register
                    </NuxtLink>
                </template>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const { isAuthenticated, logout } = useAuth();
const { itemCount } = useCart();
const { language, currency, availableLanguages, availableCurrencies, setLanguage, setCurrency } =
    useLocalization();
</script>
