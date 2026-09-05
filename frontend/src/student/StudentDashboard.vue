<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'
import StudentSideBar from './components/StudentSideBar.vue'
import '../assets/student/StudentDashboard.css'

const router = useRouter()
const user = ref(null)
const errorMessage = ref('')
const isLoggingOut = ref(false)
const studentName = computed(() => user.value?.username || 'Student trainee')

const summaryCards = [
  { label: 'Hours rendered', value: '348', detail: 'of 500 required hours', progress: 69.6 },
  { label: 'Weekly hours', value: '37.5', detail: 'Target: 40 hours', progress: 93.75 },
  { label: 'Attendance rate', value: '96%', detail: 'Excellent standing', progress: 96 },
]

const activity = [
  { icon: '✓', title: 'Thursday attendance verified', detail: 'Approved by your supervisor' },
  { icon: '↗', title: 'Training agreement endorsed', detail: 'Nexus Technologies Inc.' },
  { icon: '!', title: 'Monthly DTR due soon', detail: 'Submit before September 10' },
]

async function logout() {
  isLoggingOut.value = true
  try {
    await api.post('/api/auth/logout')
    sessionStorage.removeItem('user')
    sessionStorage.removeItem('csrfToken')
    await router.push('/login')
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.message || 'Logout failed.'
  } finally {
    isLoggingOut.value = false
  }
}

onMounted(async () => {
  try {
    const response = await api.get('/api/auth/session')
    user.value = response.data?.data
  } catch (error) {
    user.value = JSON.parse(sessionStorage.getItem('user') || 'null')
    if (!user.value) await router.push('/login')
  }
})
</script>

<template>
  <div class="student-layout">
    <StudentSideBar />
    <main class="student-dashboard-page">
      <header class="student-dashboard-header">
        <div><span class="dashboard-status">Active OJT placement</span><h1>Good morning, {{ studentName }}</h1><p>Keep track of your internship progress and attendance in one place.</p></div>
        <button class="dashboard-logout" type="button" :disabled="isLoggingOut" @click="logout">{{ isLoggingOut ? 'Signing out...' : 'Sign out' }}</button>
      </header>

      <p v-if="errorMessage" class="dashboard-error" role="alert">{{ errorMessage }}</p>

      <section class="student-context"><div><span>Current placement</span><strong>Nexus Technologies Inc.</strong><small>BSIT 4-B · Onsite practicum</small></div><div><span>Supervisor</span><strong>Engr. Marcus Vance</strong><small>Lead Systems Architect</small></div><div><span>Expected completion</span><strong>December 12, 2026</strong><small>86 days remaining</small></div></section>

      <section class="summary-grid" aria-label="Student summary">
        <article v-for="card in summaryCards" :key="card.label" class="summary-card"><div><span>{{ card.label }}</span><strong>{{ card.value }}</strong><small>{{ card.detail }}</small></div><div class="mini-progress"><div :style="{ width: `${card.progress}%` }"></div></div></article>
      </section>

      <section class="dashboard-columns">
        <article class="dashboard-panel"><div class="panel-heading"><h2>This week's attendance</h2><span>Week 10</span></div><div class="week-list"><div><span class="day-complete">MON</span><strong>8.0 hrs</strong><small>Present · Verified</small></div><div><span class="day-complete">TUE</span><strong>9.25 hrs</strong><small>Present · Overtime</small></div><div><span class="day-complete">WED</span><strong>8.0 hrs</strong><small>Present · Verified</small></div><div><span class="day-complete">THU</span><strong>8.1 hrs</strong><small>Present · Verified</small></div><div><span class="day-current">FRI</span><strong>Active</strong><small>Clocked in at 8:00 AM</small></div></div></article>
        <article class="dashboard-panel"><div class="panel-heading"><h2>Recent activity</h2><span>Today</span></div><div class="activity-list"><div v-for="item in activity" :key="item.title" class="activity-item"><span class="activity-icon">{{ item.icon }}</span><div><strong>{{ item.title }}</strong><small>{{ item.detail }}</small></div></div></div></article>
      </section>

      <section class="dashboard-panel next-task"><div class="panel-heading"><h2>Next steps</h2><span>Keep your placement on track</span></div><div class="next-task-grid"><div><span class="task-icon">DTR</span><strong>Submit monthly DTR</strong><small>Due September 10 · Requires supervisor signature</small></div><div><span class="task-icon">EVAL</span><strong>Prepare midterm reflection</strong><small>Draft is 80% complete · Due September 18</small></div><div><span class="task-icon">CHAT</span><strong>Message your supervisor</strong><small>Check in about next week's deliverables</small></div></div></section>
    </main>
  </div>
</template>
