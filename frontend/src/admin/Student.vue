<script setup>
import { onMounted, ref } from 'vue'

import api from '../api/axios'
import AdminSideBar from './components/AdminSideBar.vue'

const students = ref([])
const sections = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const isFormOpen = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
let messageTimer

const form = ref({
  username: '',
  email: '',
  password: '',
  section_id: '',
  school_id: '',
  firstname: '',
  middlename: '',
  lastname: '',
  name_ext: '',
  gender: '',
  address: '',
})

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

function resetForm() {
  form.value = {
    username: '', email: '', password: '', section_id: '', school_id: '',
    firstname: '', middlename: '', lastname: '', name_ext: '', gender: '', address: '',
  }
}

function openForm() {
  resetForm()
  isFormOpen.value = true
}

function closeForm() {
  if (!isSaving.value) isFormOpen.value = false
}

async function loadStudents() {
  isLoading.value = true
  try {
    const [studentsResponse, sectionsResponse] = await Promise.all([
      api.get('/api/students'),
      api.get('/api/sections'),
    ])
    students.value = Array.isArray(studentsResponse.data?.data) ? studentsResponse.data.data : []
    sections.value = Array.isArray(sectionsResponse.data?.data) ? sectionsResponse.data.data : []
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isLoading.value = false
  }
}

async function registerStudent() {
  isSaving.value = true
  try {
    await api.post('/api/students', {
      ...form.value,
      section_id: Number(form.value.section_id),
      school_id: form.value.school_id || null,
    })
    isFormOpen.value = false
    showMessage('success', 'Student account registered successfully.')
    await loadStudents()
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isSaving.value = false
  }
}

onMounted(loadStudents)
</script>

<template>
  <div class="admin-layout">
    <AdminSideBar />
    <main class="student-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Administration</p>
          <h1>Students</h1>
          <p>Register student accounts and view student profiles.</p>
        </div>
        <div class="header-actions">
          <button type="button" :disabled="isLoading" @click="loadStudents">Refresh</button>
          <button type="button" @click="openForm">Register student</button>
        </div>
      </header>

      <div class="message-container" aria-live="polite">
        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
      </div>

      <p v-if="isLoading">Loading students...</p>
      <table>
        <thead>
          <tr><th>Student</th><th>Account</th><th>School ID</th><th>Section</th><th>Program</th><th>College</th></tr>
        </thead>
        <tbody>
          <tr v-for="student in students" :key="student.student_id">
            <td>{{ student.firstname }} {{ student.middlename }} {{ student.lastname }} {{ student.name_ext }}</td>
            <td>{{ student.username }}<br />{{ student.email }}</td>
            <td>{{ student.school_id || '-' }}</td>
            <td>{{ student.section_name || '-' }}</td>
            <td>{{ student.program_name || '-' }}</td>
            <td>{{ student.college_name || '-' }}</td>
          </tr>
          <tr v-if="!students.length"><td colspan="6">No students found.</td></tr>
        </tbody>
      </table>
    </main>

    <div v-if="isFormOpen" class="modal-backdrop" @click.self="closeForm">
      <form class="modal" @submit.prevent="registerStudent">
        <h2>Register student account</h2>
        <label>Username<input v-model.trim="form.username" required maxlength="100" autocomplete="username" /></label>
        <label>Email<input v-model.trim="form.email" type="email" required maxlength="100" autocomplete="email" /></label>
        <label>Password<input v-model="form.password" type="password" required minlength="8" autocomplete="new-password" /></label>
        <label>Section
          <select v-model="form.section_id" required>
            <option disabled value="">Select section</option>
            <option v-for="section in sections" :key="section.section_id" :value="section.section_id">
              {{ section.college_name }} - {{ section.program_name }} - {{ section.section_name || section.section }}
            </option>
          </select>
        </label>
        <label>School ID<input v-model.trim="form.school_id" maxlength="10" /></label>
        <label>First name<input v-model.trim="form.firstname" required maxlength="50" /></label>
        <label>Middle name<input v-model.trim="form.middlename" maxlength="50" /></label>
        <label>Last name<input v-model.trim="form.lastname" required maxlength="50" /></label>
        <label>Name extension<input v-model.trim="form.name_ext" maxlength="5" /></label>
        <label>Gender
          <select v-model="form.gender" required>
            <option disabled value="">Select gender</option>
            <option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>
          </select>
        </label>
        <label>Address<textarea v-model.trim="form.address" required maxlength="255" rows="3"></textarea></label>
        <div class="modal-actions">
          <button type="button" :disabled="isSaving" @click="closeForm">Cancel</button>
          <button type="submit" :disabled="isSaving">{{ isSaving ? 'Registering...' : 'Register student' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; color: #19313a; }
.student-page { flex: 1; max-width: 1200px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 32px; }
.header-actions, .modal-actions { display: flex; gap: 8px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
th, td { padding: 12px 10px; border-bottom: 1px solid #dce6e3; text-align: left; vertical-align: top; }
th { background: #edf3f0; }
.message-container { position: fixed; top: 24px; right: 24px; z-index: 20; width: min(360px, calc(100vw - 48px)); }
.message { margin: 0; padding: 14px 16px; border-left: 4px solid; border-radius: 4px; box-shadow: 0 8px 24px rgb(25 49 58 / 14%); font: 14px/1.4 'Roboto', sans-serif; }
.error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
.success { border-color: #2b8a6e; background: #edf8f1; color: #21704f; }
.modal-backdrop { position: fixed; inset: 0; display: grid; place-items: center; padding: 20px; background: rgb(25 49 58 / 35%); }
.modal { width: min(520px, 100%); max-height: 90vh; overflow-y: auto; display: grid; gap: 14px; padding: 24px; background: #fffaf3; border: 1px solid #cbd8d5; border-radius: 6px; }
.modal label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, select, textarea { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
.modal-actions { justify-content: end; }
@media (max-width: 700px) { .admin-layout { display: block; } .student-page { padding: 24px 16px; overflow-x: auto; } .page-header { align-items: start; flex-direction: column; } }
</style>
