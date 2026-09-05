<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'

const router = useRouter()
const user = ref(null)
const errorMessage = ref('')
const isLoggingOut = ref(false)
const showLogoutConfirmation = ref(false)
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
  showLogoutConfirmation.value = false
  isLoggingOut.value = true
  errorMessage.value = ''

  try {
    await api.post('/api/auth/logout')
    sessionStorage.removeItem('user')
    sessionStorage.removeItem('csrfToken')
    sessionStorage.setItem('authFlash', JSON.stringify({ type: 'success', message: 'You have been logged out successfully.' }))
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
    <div class="header-identity">
      <strong>{{ user?.username || 'Loading session...' }}</strong>
      <span v-if="user">{{ user.email }}</span>
    </div>
    <div class="header-actions">
      <div class="clock" aria-live="polite">
        <span>Asia/Manila</span>
        <strong>{{ manilaClock() }}</strong>
      </div>
      <span v-if="user" class="role">{{ user.role }}</span>
      <button class="logout-button" type="button" :disabled="isLoggingOut" @click="showLogoutConfirmation = true">
        {{ isLoggingOut ? 'Logging out...' : 'Logout' }}
      </button>
    </div>
    <div v-if="showLogoutConfirmation" class="logout-confirmation" role="alertdialog" aria-label="Confirm logout">
      <span>Are you sure you want to log out?</span>
      <button type="button" class="confirm-logout" @click="logout">Confirm</button>
      <button type="button" class="cancel-logout" @click="showLogoutConfirmation = false">Cancel</button>
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
  min-height: 76px;
  box-sizing: border-box;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 14px 24px;
  border-bottom: 1px solid #d9e2df;
  background: #fffaf3;
  color: #19313a;
  font: 14px 'Roboto', sans-serif;
}
.header-identity { min-width: 0; }
.header-identity span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.global-header strong, .global-header span { display: block; }
.global-header span { margin-top: 3px; color: #607980; font-size: 12px; }
.header-actions { display: flex; align-items: center; gap: 14px; }
.clock { display: grid !important; gap: 2px; min-width: 190px; margin-right: 2px; }
.clock span { margin-top: 0 !important; color: #b04a32 !important; font-size: 10px !important; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.clock strong { font-size: 13px; }
.role { color: #b04a32 !important; font-weight: 700; text-transform: capitalize; }
.logout-button { border: 1px solid #83d1a7; border-radius: 8px; padding: 9px 14px; background: #83d1a7; color: #123126; cursor: pointer; font-weight: 700; }
.logout-button:hover { background: #5fb889; border-color: #5fb889; }
.logout-confirmation { position: absolute; top: calc(100% + 10px); right: 24px; z-index: 2; display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid #cfe7da; border-radius: 10px; background: #fff; box-shadow: 0 10px 30px rgb(35 55 86 / 14%); color: #19313a; font-size: 13px; }
.logout-confirmation button { border-radius: 7px; padding: 7px 10px; font: 700 12px 'Roboto', sans-serif; cursor: pointer; }
.confirm-logout { border: 1px solid #83d1a7; background: #83d1a7; color: #123126; }
.cancel-logout { border: 1px solid #cfe7da; background: #dff8ee; color: #087653; }
button:disabled { cursor: wait; opacity: .6; }
.error { position: absolute; top: 100%; right: 24px; margin: 0; padding: 8px 12px; background: #fff0ed; color: #a33b2e; }
@media (max-width: 700px) {
  .global-header { align-items: stretch; flex-direction: column; gap: 10px; padding: 12px 16px; }
  .header-identity { padding-right: 44px; }
  .header-actions { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center; gap: 8px; }
  .clock { min-width: 0; margin-right: 0; }
  .clock strong { overflow: hidden; font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
  .role { margin-top: 0 !important; white-space: nowrap; }
  .logout-button { padding: 9px 11px; white-space: nowrap; }
  .logout-confirmation { right: 16px; left: 16px; flex-wrap: wrap; }
  .error { right: 16px; left: 16px; }
}
</style>
