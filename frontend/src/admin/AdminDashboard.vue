<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'
import AdminSideBar from './components/AdminSideBar.vue'
import '../assets/admin/AdminDashboard.css'

const router = useRouter()
const user = ref(null)
const errorMessage = ref('')
const isLoggingOut = ref(false)
const greetingName = computed(() => user.value?.username || 'Administrator')

async function logout() {
  isLoggingOut.value = true
  errorMessage.value = ''
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
    sessionStorage.setItem('user', JSON.stringify(user.value))
  } catch (error) {
    user.value = JSON.parse(sessionStorage.getItem('user') || 'null')
    if (!user.value) await router.push('/login')
  }
})
</script>

<template>
  <div class="admin-layout">
    <AdminSideBar />
    <main class="dashboard-page">
      <header class="dashboard-topbar">
        <div>
          <span class="session-chip">System operational</span>
          <h1>Good morning, {{ greetingName }}</h1>
          <p>Here is the current pulse of your OJT administration workspace.</p>
        </div>
        <button class="logout-button" type="button" :disabled="isLoggingOut" @click="logout">
          {{ isLoggingOut ? 'Signing out...' : 'Sign out' }}
        </button>
      </header>

      <p v-if="errorMessage" class="message error dashboard-error" role="alert">{{ errorMessage }}</p>

      <section class="dashboard-grid" aria-label="Administration summary">
        <article class="metric-card"><span class="metric-label">Active students</span><strong class="metric-value">248</strong><span class="metric-note">+12 this semester</span></article>
        <article class="metric-card"><span class="metric-label">Hours rendered</span><strong class="metric-value">8,642</strong><span class="metric-note">78.5% of target</span></article>
        <article class="metric-card"><span class="metric-label">Partner companies</span><strong class="metric-value">36</strong><span class="metric-note">4 awaiting review</span></article>
        <article class="metric-card"><span class="metric-label">Pending approvals</span><strong class="metric-value">17</strong><span class="metric-note" style="color: var(--admin-amber-ink)">Needs attention</span></article>
      </section>

      <section class="dashboard-columns">
        <article class="dashboard-panel">
          <div class="panel-heading"><h2>Placement progress</h2><span>AY 2026-2027</span></div>
          <div class="progress-row"><div class="progress-label"><span>Students with active placements</span><span>86%</span></div><div class="progress-track"><div class="progress-fill" style="width: 86%"></div></div></div>
          <div class="progress-row"><div class="progress-label"><span>Signed training agreements</span><span>72%</span></div><div class="progress-track"><div class="progress-fill" style="width: 72%"></div></div></div>
          <div class="progress-row"><div class="progress-label"><span>Verified attendance this week</span><span>94%</span></div><div class="progress-track"><div class="progress-fill" style="width: 94%"></div></div></div>
        </article>

        <article class="dashboard-panel">
          <div class="panel-heading"><h2>Recent activity</h2><span>Today</span></div>
          <div class="activity-list">
            <div class="activity-item"><span class="activity-icon">✓</span><div><p><strong>12 attendance logs</strong> were verified.</p><small>18 minutes ago</small></div></div>
            <div class="activity-item"><span class="activity-icon">+</span><div><p><strong>Northstar Labs</strong> joined as a partner.</p><small>2 hours ago</small></div></div>
            <div class="activity-item"><span class="activity-icon">!</span><div><p><strong>5 agreements</strong> need signatures.</p><small>Yesterday</small></div></div>
          </div>
        </article>
      </section>
    </main>
  </div>
</template>
