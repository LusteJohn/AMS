<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import StudentSideBar from './components/StudentSideBar.vue'
import '../assets/student/StudentProfile.css'

const profile = ref(null)
const sections = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
let messageTimer

const formTitle = computed(() => profile.value ? 'Update profile' : 'Create profile')

const form = ref({
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

function applyProfile(data) {
  if (!data) return
  profile.value = data
  Object.assign(form.value, {
    section_id: data.section_id || '',
    school_id: data.school_id || '',
    firstname: data.firstname || '',
    middlename: data.middlename || '',
    lastname: data.lastname || '',
    name_ext: data.name_ext || '',
    gender: data.gender || '',
    address: data.address || '',
  })
}

async function loadProfile() {
  isLoading.value = true
  try {
    const [profileResponse, sectionsResponse] = await Promise.all([
      api.get('/api/student/profile'),
      api.get('/api/student/sections'),
    ])
    applyProfile(profileResponse.data?.data)
    sections.value = Array.isArray(sectionsResponse.data?.data) ? sectionsResponse.data.data : []
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isLoading.value = false
  }
}

async function saveProfile() {
  isSaving.value = true
  const isUpdate = Boolean(profile.value)
  try {
    const payload = {
      ...form.value,
      section_id: Number(form.value.section_id),
      school_id: form.value.school_id || null,
    }
    const response = isUpdate
      ? await api.put('/api/student/profile', payload)
      : await api.post('/api/student/profile', payload)

    applyProfile(response.data?.data)
    showMessage('success', `Profile ${isUpdate ? 'updated' : 'created'} successfully.`)
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isSaving.value = false
  }
}

onMounted(loadProfile)
</script>

<template>
  <div class="student-layout">
    <StudentSideBar />
    <main class="profile-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Student account</p>
          <h1>{{ formTitle }}</h1>
          <p>Keep your student information up to date.</p>
        </div>
      </header>

      <div class="message-container" aria-live="polite">
        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
      </div>

      <p v-if="isLoading">Loading profile...</p>
      <form v-else class="profile-form" @submit.prevent="saveProfile">
        <label>
          Section
          <select v-model="form.section_id" required>
            <option disabled value="">Select section</option>
            <option v-for="section in sections" :key="section.section_id" :value="section.section_id">
              {{ section.college_name }} - {{ section.program_name }} - {{ section.section_name || section.section }}
            </option>
          </select>
        </label>
        <label>
          School ID
          <input v-model.trim="form.school_id" type="text" maxlength="10" />
        </label>
        <label>
          First name
          <input v-model.trim="form.firstname" required maxlength="50" />
        </label>
        <label>
          Middle name
          <input v-model.trim="form.middlename" maxlength="50" />
        </label>
        <label>
          Last name
          <input v-model.trim="form.lastname" required maxlength="50" />
        </label>
        <label>
          Name extension
          <input v-model.trim="form.name_ext" maxlength="5" placeholder="Jr." />
        </label>
        <label>
          Gender
          <select v-model="form.gender" required>
            <option disabled value="">Select gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </label>
        <label class="full-width">
          Address
          <textarea v-model.trim="form.address" required maxlength="255" rows="4"></textarea>
        </label>
        <button type="submit" :disabled="isSaving">
          {{ isSaving ? 'Saving...' : profile ? 'Update profile' : 'Create profile' }}
        </button>
      </form>
    </main>
  </div>
</template>

<style scoped>
.student-layout { display: flex; min-height: 100vh; color: #19313a; }
.profile-page { flex: 1; max-width: 900px; padding: 40px; }
.page-header { margin-bottom: 32px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
.profile-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; max-width: 700px; }
.profile-form label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, select, textarea { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
.full-width { grid-column: 1 / -1; }
button { width: fit-content; border: 1px solid #b9cbc6; border-radius: 4px; padding: 10px 14px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
.message-container { position: fixed; top: 24px; right: 24px; z-index: 20; width: min(360px, calc(100vw - 48px)); }
.message { margin: 0; padding: 14px 16px; border-left: 4px solid; border-radius: 4px; box-shadow: 0 8px 24px rgb(25 49 58 / 14%); font: 14px/1.4 'Roboto', sans-serif; }
.error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
.success { border-color: #2b8a6e; background: #edf8f1; color: #21704f; }
@media (max-width: 700px) { .student-layout { display: block; } .student-sidebar { border-right: 0; border-bottom: 1px solid #d9e2df; } .profile-page { padding: 24px 16px; } .profile-form { grid-template-columns: 1fr; } .full-width { grid-column: auto; } }
</style>
