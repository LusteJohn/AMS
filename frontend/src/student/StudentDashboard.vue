<script setup>
import Dashboard from '../components/Dashboard.vue'
import StudentSideBar from './components/StudentSideBar.vue'
import '../assets/student/StudentDashboard.css'
</script>

<template>
  <div class="student-layout">
    <StudentSideBar />
    <Dashboard title="Student Dashboard" />
  </div>
</template>

<style scoped>
.student-layout { display: flex; min-height: 100vh; }
</style>
