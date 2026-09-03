<script setup>
import Dashboard from '../components/Dashboard.vue'
import FacultySideBar from './components/FacultySideBar.vue'
</script>

<template>
  <div class="faculty-layout">
    <FacultySideBar />
    <Dashboard title="Faculty Dashboard" />
  </div>
</template>

<style scoped>
.faculty-layout { display: flex; min-height: calc(100vh - 61px); }
@media (max-width: 700px) { .faculty-layout { display: block; } }
</style>
