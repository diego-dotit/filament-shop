<template>
    <footer>
        <div>
            <nav aria-label="Footer navigation">
                <NuxtLink to="/about">About</NuxtLink>
                <NuxtLink to="/contact">Contact</NuxtLink>
                <NuxtLink to="/terms">Terms</NuxtLink>
                <NuxtLink to="/privacy">Privacy</NuxtLink>
            </nav>
            <p>&copy; {{ year }} Filament Shop. All rights reserved.</p>
        </div>
    </footer>
</template>

<script setup lang="ts">
const year = new Date().getFullYear();
</script>
