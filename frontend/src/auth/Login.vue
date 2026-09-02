<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'

const router = useRouter()
const login = ref('')
const password = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

function dashboardForRole(role) {
  const normalizedRole = String(role || '').trim().toLowerCase()

  return {
    admin: '/admin',
    faculty: '/faculty',
    student: '/student',
  }[normalizedRole]
}

async function submitLogin() {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    const response = await api.post('/api/auth/login', {
      login: login.value,
      password: password.value,
    })
    const user = response.data?.data || response.data?.user || response.data
    const dashboard = dashboardForRole(user?.role)

    if (!user || !dashboard) {
      throw new Error(`Your account has an unsupported role: ${user?.role || 'missing'}.`)
    }

    sessionStorage.setItem('user', JSON.stringify(user))
    await router.push(dashboard)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || error.message || 'Login failed.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <main>
    <h1>Login</h1>
    <form @submit.prevent="submitLogin">
      <div>
        <label for="login">Username or email</label>
        <input id="login" v-model="login" type="text" autocomplete="username" required />
      </div>
      <div>
        <label for="password">Password</label>
        <input id="password" v-model="password" type="password" autocomplete="current-password" required />
      </div>
      <button type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? 'Logging in...' : 'Login' }}
      </button>
      <p v-if="errorMessage" role="alert">{{ errorMessage }}</p>
    </form>
  </main>
</template>
