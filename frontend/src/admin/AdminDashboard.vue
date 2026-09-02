<script setup>
import Dashboard from '../components/Dashboard.vue'
import AdminSideBar from './components/AdminSideBar.vue'
</script>

<template>
  <div class="admin-layout">
    <AdminSideBar />
    <Dashboard title="Admin Dashboard" />
  </div>
</template>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; }
</style>
