<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../api/axios'
import '../assets/auth/login.css'
import loginLogo from '../assets/login_logo.jpeg'

const router = useRouter()
const login = ref('')
const password = ref('')
const isPasswordVisible = ref(false)
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
    sessionStorage.removeItem('csrfToken')
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
  <div class="login-page">
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <div class="login-logo">
            <img :src="loginLogo" alt="OJT Portal Logo" class="login-logo-img" />
          </div>
          <h1 class="login-title">Sign In to OJTrack</h1>
          <p class="login-subtitle">Enter your institutional credentials to access the portal.</p>
        </div>

        <form class="login-form" @submit.prevent="submitLogin">
          <div class="form-group">
            <label class="form-label" for="institutional-id">Institutional ID or University Email</label>
            <span class="form-helper">Format: 23-1082 or example@gmail.com</span>
            <div class="input-wrapper">
              <span class="form-input-icon material-symbols-outlined">account_circle</span>
              <input
                id="institutional-id"
                v-model="login"
                class="form-input"
                type="text"
                autocomplete="username"
                placeholder="e.g. 2021-10482 or trainee@stateuniv.edu.ph"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="portal-password">Security Password</label>
            <div class="input-wrapper">
              <span class="form-input-icon material-symbols-outlined">lock</span>
              <input
                id="portal-password"
                v-model="password"
                class="form-input form-input--with-toggle"
                :type="isPasswordVisible ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="Enter your confidential password"
                required
              />
              <button
                type="button"
                class="password-toggle"
                @click="isPasswordVisible = !isPasswordVisible"
                :aria-label="isPasswordVisible ? 'Hide password' : 'Show password'"
              >
                <span class="material-symbols-outlined">
                  {{ isPasswordVisible ? 'visibility_off' : 'visibility' }}
                </span>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-primary" :disabled="isSubmitting">
            <span class="material-symbols-outlined">{{ isSubmitting ? 'hourglass_empty' : 'login' }}</span>
            <span>{{ isSubmitting ? 'Signing in...' : 'Sign In to OJT Portal' }}</span>
          </button>

          <p v-if="errorMessage" class="error-message" role="alert">{{ errorMessage }}</p>
        </form>
      </div>
    </div>
  </div>
</template>
