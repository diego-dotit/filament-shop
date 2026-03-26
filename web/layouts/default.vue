<template>
  <div class="layout">
    <Header />
    <main class="layout__content">
      <slot />
    </main>
    <Footer />
  </div>
</template>

<script setup lang="ts">
</script>

<style>
/* Global reset to ensure full-height flex layout */
html,
body,
#__nuxt {
  height: 100%;
  margin: 0;
  padding: 0;
}
</style>

<style scoped>
.layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  font-family: system-ui, -apple-system, sans-serif;
}

.layout__content {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}
</style>
