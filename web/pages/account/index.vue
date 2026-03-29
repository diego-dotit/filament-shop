<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
definePageMeta({ middleware: "auth" });
await navigateTo("/account/dashboard", { replace: true });
</script>
<template><div /></template>
