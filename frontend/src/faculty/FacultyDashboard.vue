<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'
import FacultySideBar from './components/FacultySideBar.vue'
import '../assets/faculty/FacultyDashboard.css'

const router = useRouter()
const user = ref(null)
const errorMessage = ref('')
const isLoggingOut = ref(false)
const facultyName = computed(() => user.value?.username || 'Faculty coordinator')

const summaryCards = [
  { label: 'Assigned students', value: '42', note: '+3 this semester', tone: 'mint' },
  { label: 'Active placements', value: '38', note: '90.5% placed', tone: 'blue' },
  { label: 'Attendance verified', value: '94%', note: 'This week', tone: 'mint' },
  { label: 'Pending reviews', value: '7', note: 'Needs attention', tone: 'amber' },
]

const placementProgress = [
  { label: 'Training agreements signed', value: 86 },
  { label: 'Students with active OJT companies', value: 90 },
  { label: 'Weekly attendance submissions', value: 94 },
]

const activity = [
  { icon: '✓', title: '12 attendance logs verified', detail: 'Today at 10:42 AM' },
  { icon: '+', title: 'New company supervisor added', detail: 'Yesterday at 3:15 PM' },
  { icon: '!', title: '5 student records need review', detail: 'Yesterday at 1:08 PM' },
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
  <div class="faculty-layout">
    <FacultySideBar />
    <main class="faculty-dashboard-page">
      <header class="faculty-dashboard-header">
        <div>
          <span class="dashboard-status">Faculty workspace</span>
          <h1>Good morning, {{ facultyName }}</h1>
          <p>Monitor your section's OJT placements, attendance, and student progress.</p>
        </div>
        <button class="dashboard-logout" type="button" :disabled="isLoggingOut" @click="logout">
          {{ isLoggingOut ? 'Signing out...' : 'Sign out' }}
        </button>
      </header>

      <p v-if="errorMessage" class="dashboard-error" role="alert">{{ errorMessage }}</p>

      <section class="summary-grid" aria-label="Faculty summary">
        <article v-for="card in summaryCards" :key="card.label" class="summary-card" :class="`summary-card--${card.tone}`">
          <span>{{ card.label }}</span>
          <strong>{{ card.value }}</strong>
          <small>{{ card.note }}</small>
        </article>
      </section>

      <section class="dashboard-columns">
        <article class="dashboard-panel">
          <div class="panel-heading"><h2>Section progress</h2><span>BSIT 4-B</span></div>
          <div v-for="item in placementProgress" :key="item.label" class="progress-row">
            <div class="progress-label"><span>{{ item.label }}</span><strong>{{ item.value }}%</strong></div>
            <div class="progress-track"><div class="progress-fill" :style="{ width: `${item.value}%` }"></div></div>
          </div>
        </article>

        <article class="dashboard-panel">
          <div class="panel-heading"><h2>Recent activity</h2><span>This week</span></div>
          <div class="activity-list">
            <div v-for="item in activity" :key="item.title" class="activity-item">
              <span class="activity-icon">{{ item.icon }}</span>
              <div><strong>{{ item.title }}</strong><small>{{ item.detail }}</small></div>
            </div>
          </div>
        </article>
      </section>

      <section class="dashboard-panel schedule-panel">
        <div class="panel-heading"><h2>Upcoming faculty tasks</h2><span>Next 14 days</span></div>
        <div class="task-list">
          <div><span class="task-date">SEP 09</span><strong>Review attendance exceptions</strong><small>3 students have incomplete time logs</small></div>
          <div><span class="task-date">SEP 12</span><strong>Company supervisor check-in</strong><small>Northstar Labs and Nexus Technologies</small></div>
          <div><span class="task-date">SEP 18</span><strong>Midterm evaluation endorsement</strong><small>Submit section summary to the dean's office</small></div>
        </div>
      </section>
    </main>
  </div>
</template>
