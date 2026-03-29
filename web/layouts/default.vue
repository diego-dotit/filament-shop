<template>
    <div class="flex flex-col min-h-screen font-sans">
        <Header />
        <main class="flex-1 max-w-7xl w-full mx-auto px-6 py-8">
            <slot />
        </main>
        <Footer />
    </div>
</template>

<script setup lang="ts"></script>
