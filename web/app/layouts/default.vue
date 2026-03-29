<script setup lang="ts">
import Footer from '@/components/common/Footer.vue';
import Header from '@/components/common/Header.vue';
</script>

<template>
    <div class="flex flex-col items-stretch min-h-screen">
        <Header/>
        <div class="grow">
            <slot/>
        </div>
        <Footer/>
    </div>
</template>