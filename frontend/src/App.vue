<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import GlobalHeader from './components/GlobalHeader.vue'

const route = useRoute()
const requiresAuth = computed(() => route.meta.requiresAuth === true)
</script>

<template>
  <GlobalHeader v-if="requiresAuth" />
  <RouterView />
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

:root {
  font-family: 'Roboto', sans-serif;
}

body {
  margin: 0;
  font-family: 'Roboto', sans-serif;
}

@media (max-width: 700px) {
  body:has(.admin-sidebar),
  body:has(.faculty-sidebar),
  body:has(.student-sidebar) {
    padding-bottom: calc(76px + env(safe-area-inset-bottom));
  }
}
</style>
