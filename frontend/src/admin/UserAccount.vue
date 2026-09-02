<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import AdminSideBar from './components/AdminSideBar.vue'

const users = ref([])
const errorMessage = ref('')
const successMessage = ref('')
const isLoading = ref(false)
const form = ref(null)
let messageTimer

const formTitle = computed(() => `${form.value?.mode === 'edit' ? 'Edit' : 'Register'} account`)

function showMessage(type, message) {
  errorMessage.value = type === 'error' ? message : ''
  successMessage.value = type === 'success' ? message : ''
  clearTimeout(messageTimer)
  messageTimer = setTimeout(() => {
    errorMessage.value = ''
    successMessage.value = ''
  }, 3000)
}

function apiError(error) {
  return error.response?.data?.message || error.message || 'Request failed.'
}

async function loadUsers() {
  isLoading.value = true
  try {
    const response = await api.get('/api/users')
    users.value = Array.isArray(response.data?.data) ? response.data.data : []
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  form.value = {
    mode: 'create',
    id: null,
    username: '',
    email: '',
    password: '',
    role: 'student',
  }
}

function openEdit(user) {
  form.value = {
    mode: 'edit',
    id: user.user_id,
    username: user.username,
    email: user.email,
    password: '',
    role: user.role,
  }
}

function closeForm() {
  form.value = null
}

async function saveForm() {
  const current = form.value
  if (!current) return

  errorMessage.value = ''
  successMessage.value = ''
  const payload = {
    username: current.username,
    email: current.email,
    role: current.role,
  }
  if (current.mode === 'create' || current.password) {
    payload.password = current.password
  }

  try {
    if (current.mode === 'edit') {
      await api.put(`/api/users/${current.id}`, payload)
    } else {
      await api.post('/api/users', payload)
    }
    closeForm()
    showMessage('success', `Account ${current.mode === 'edit' ? 'updated' : 'registered'} successfully.`)
    await loadUsers()
  } catch (error) {
    showMessage('error', apiError(error))
  }
}

async function deleteUser(user) {
  if (!window.confirm(`Delete the account for ${user.username}?`)) return

  try {
    await api.delete(`/api/users/${user.user_id}`)
    showMessage('success', 'Account deleted successfully.')
    await loadUsers()
  } catch (error) {
    showMessage('error', apiError(error))
  }
}

onMounted(loadUsers)
</script>

<template>
  <div class="admin-layout">
    <AdminSideBar />
    <main class="account-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Administration</p>
          <h1>User accounts</h1>
          <p>Register and manage faculty and student accounts.</p>
        </div>
        <div class="header-actions">
          <button type="button" :disabled="isLoading" @click="loadUsers">Refresh</button>
          <button type="button" @click="openCreate">Register account</button>
        </div>
      </header>

      <div class="message-container" aria-live="polite">
        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
      </div>

      <p v-if="isLoading">Loading accounts...</p>
      <table>
        <thead>
          <tr><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.user_id">
            <td>{{ user.username }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.role }}</td>
            <td class="actions">
              <button type="button" @click="openEdit(user)">Edit</button>
              <button type="button" @click="deleteUser(user)">Delete</button>
            </td>
          </tr>
          <tr v-if="!users.length"><td colspan="4">No user accounts found.</td></tr>
        </tbody>
      </table>
    </main>

    <div v-if="form" class="modal-backdrop" @click.self="closeForm">
      <form class="modal" @submit.prevent="saveForm">
        <h2>{{ formTitle }}</h2>
        <label>
          Username
          <input v-model.trim="form.username" required maxlength="100" autocomplete="username" />
        </label>
        <label>
          Email
          <input v-model.trim="form.email" type="email" required maxlength="100" autocomplete="email" />
        </label>
        <label>
          Password
          <input v-model="form.password" type="password" :required="form.mode === 'create'" minlength="8" autocomplete="new-password" />
          <small v-if="form.mode === 'edit'">Leave blank to keep the current password.</small>
        </label>
        <label>
          Role
          <select v-model="form.role" required>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
          </select>
        </label>
        <div class="modal-actions">
          <button type="button" @click="closeForm">Cancel</button>
          <button type="submit">Save account</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; color: #19313a; }
.account-page { flex: 1; max-width: 1100px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 32px; }
.header-actions, .actions { display: flex; gap: 8px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
th, td { padding: 12px 10px; border-bottom: 1px solid #dce6e3; text-align: left; }
th { background: #edf3f0; }
.message-container { position: fixed; top: 24px; right: 24px; z-index: 20; width: min(360px, calc(100vw - 48px)); }
.message { margin: 0; padding: 14px 16px; border-left: 4px solid; border-radius: 4px; box-shadow: 0 8px 24px rgb(25 49 58 / 14%); font: 14px/1.4 'Roboto', sans-serif; }
.error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
.success { border-color: #2b8a6e; background: #edf8f1; color: #21704f; }
.modal-backdrop { position: fixed; inset: 0; display: grid; place-items: center; padding: 20px; background: rgb(25 49 58 / 35%); }
.modal { width: min(420px, 100%); display: grid; gap: 18px; padding: 24px; background: #fffaf3; border: 1px solid #cbd8d5; border-radius: 6px; }
.modal label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, select { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
small { color: #607980; font-weight: 400; }
.modal-actions { display: flex; justify-content: end; gap: 8px; }
@media (max-width: 700px) { .admin-layout { display: block; } .admin-sidebar { border-right: 0; border-bottom: 1px solid #d9e2df; } .account-page { padding: 24px 16px; overflow-x: auto; } .page-header { align-items: start; flex-direction: column; } }
</style>
