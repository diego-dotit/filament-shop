<template>
  <footer class="site-footer">
    <div class="site-footer__inner">
      <nav class="site-footer__nav" aria-label="Footer navigation">
        <NuxtLink to="/about">About</NuxtLink>
        <NuxtLink to="/contact">Contact</NuxtLink>
        <NuxtLink to="/terms">Terms</NuxtLink>
        <NuxtLink to="/privacy">Privacy</NuxtLink>
      </nav>
      <p class="site-footer__copy">&copy; {{ year }} Filament Shop. All rights reserved.</p>
    </div>
  </footer>
</template>

<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<style scoped>
.site-footer {
  background: #1a1a2e;
  color: #888;
  padding: 2rem 1.5rem;
  margin-top: auto;
}

.site-footer__inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.site-footer__nav {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  justify-content: center;
}

.site-footer__nav a {
  color: #aaa;
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.15s;
}

.site-footer__nav a:hover {
  color: #fff;
}

.site-footer__copy {
  font-size: 0.8rem;
  margin: 0;
}
</style>
