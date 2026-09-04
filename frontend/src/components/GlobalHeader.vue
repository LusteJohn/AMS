<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'

const router = useRouter()
const user = ref(null)
const errorMessage = ref('')
const isLoggingOut = ref(false)
const currentTime = ref(new Date())
let clockTimer

function manilaClock() {
  return new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    dateStyle: 'medium',
    timeStyle: 'medium',
    hour12: true,
  }).format(currentTime.value)
}

async function loadSession() {
  try {
    const response = await api.get('/api/auth/session')
    user.value = response.data?.data
    sessionStorage.setItem('user', JSON.stringify(user.value))
  } catch (error) {
    sessionStorage.removeItem('user')
    errorMessage.value = error.response?.data?.message || 'Session expired.'
    await router.push('/login')
  }
}

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

onMounted(loadSession)
onMounted(() => {
  clockTimer = window.setInterval(() => { currentTime.value = new Date() }, 1000)
})
onBeforeUnmount(() => window.clearInterval(clockTimer))
</script>

<template>
  <header class="global-header">
    <div>
      <strong>{{ user?.username || 'Loading session...' }}</strong>
      <span v-if="user">{{ user.email }}</span>
    </div>
    <div class="header-actions">
      <div class="clock" aria-live="polite">
        <span>Asia/Manila</span>
        <strong>{{ manilaClock() }}</strong>
      </div>
      <span v-if="user" class="role">{{ user.role }}</span>
      <button type="button" :disabled="isLoggingOut" @click="logout">
        {{ isLoggingOut ? 'Logging out...' : 'Logout' }}
      </button>
    </div>
    <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>
  </header>
</template>

<style scoped>
.global-header {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 14px 24px;
  border-bottom: 1px solid #d9e2df;
  background: #fffaf3;
  color: #19313a;
  font: 14px 'Roboto', sans-serif;
}
.global-header strong, .global-header span { display: block; }
.global-header span { margin-top: 3px; color: #607980; font-size: 12px; }
.header-actions { display: flex; align-items: center; gap: 14px; }
.clock { display: grid !important; gap: 2px; min-width: 190px; margin-right: 2px; }
.clock span { margin-top: 0 !important; color: #b04a32 !important; font-size: 10px !important; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.clock strong { font-size: 13px; }
.role { color: #b04a32 !important; font-weight: 700; text-transform: capitalize; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: transparent; color: inherit; cursor: pointer; }
button:disabled { cursor: wait; opacity: .6; }
.error { position: absolute; top: 100%; right: 24px; margin: 0; padding: 8px 12px; background: #fff0ed; color: #a33b2e; }
@media (max-width: 700px) { .global-header { padding: 12px 16px; } .header-actions { gap: 8px; } .clock { min-width: 0; } .clock strong { font-size: 11px; } }
</style>
