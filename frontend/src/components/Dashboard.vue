<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
})

const router = useRouter()
const user = ref(null)
const errorMessage = ref('')
const isLoggingOut = ref(false)

async function logout() {
  errorMessage.value = ''
  isLoggingOut.value = true

  try {
    await api.post('/api/auth/logout')
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.message || 'Logout failed.'
    return
  } finally {
    isLoggingOut.value = false
  }

  sessionStorage.removeItem('user')
  sessionStorage.removeItem('csrfToken')
  await router.push('/login')
}

onMounted(async () => {
  try {
    const response = await api.get('/api/auth/session')
    user.value = response.data?.data
    sessionStorage.setItem('user', JSON.stringify(user.value))
  } catch (error) {
    user.value = JSON.parse(sessionStorage.getItem('user') || 'null')
    if (!user.value) {
      errorMessage.value = error.response?.data?.message || 'Session expired.'
      await router.push('/login')
    }
  }
})
</script>

<template>
  <main>
    <h1>{{ props.title }}</h1>
    <p v-if="user">Welcome, {{ user.username }}</p>
    <p v-if="user">Username: {{ user.username }}</p>
    <p v-if="user">Email: {{ user.email }}</p>
    <p v-if="user">Role: {{ user.role }}</p>
    <button type="button" :disabled="isLoggingOut" @click="logout">
      {{ isLoggingOut ? 'Logging out...' : 'Logout' }}
    </button>
    <p v-if="errorMessage" role="alert">{{ errorMessage }}</p>
  </main>
</template>
