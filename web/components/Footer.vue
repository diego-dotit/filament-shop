<template>
    <footer class="bg-slate-900 text-gray-400 py-8 px-6">
        <div class="max-w-7xl mx-auto flex flex-col items-center gap-4">
            <nav class="flex gap-6 flex-wrap justify-center" aria-label="Footer navigation">
                <NuxtLink to="/about" class="text-gray-500 text-sm hover:text-white transition-colors no-underline">About</NuxtLink>
                <NuxtLink to="/contact" class="text-gray-500 text-sm hover:text-white transition-colors no-underline">Contact</NuxtLink>
                <NuxtLink to="/terms" class="text-gray-500 text-sm hover:text-white transition-colors no-underline">Terms</NuxtLink>
                <NuxtLink to="/privacy" class="text-gray-500 text-sm hover:text-white transition-colors no-underline">Privacy</NuxtLink>
            </nav>
            <p class="text-xs m-0">&copy; {{ year }} Filament Shop. All rights reserved.</p>
        </div>
    </footer>
</template>

<script setup lang="ts">
const year = new Date().getFullYear();
</script>
