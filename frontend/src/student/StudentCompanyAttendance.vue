<script setup>
import { onMounted, ref } from 'vue'

import api from '../api/axios'
import StudentSideBar from './components/StudentSideBar.vue'

const assignments = ref([])
const schedules = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const isSubmitted = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
let messageTimer

const form = ref({
  company_id: '',
  morning_in: '',
  morning_out: '',
  afternoon_in: '',
  afternoon_out: '',
  grace_period_minutes: 15,
})

function showMessage(type, message) {
  errorMessage.value = type === 'error' ? message : ''
  successMessage.value = type === 'success' ? message : ''
  clearTimeout(messageTimer)
  messageTimer = setTimeout(() => {
    errorMessage.value = ''
    successMessage.value = ''
  }, 3500)
}

function apiError(error) {
  const errors = error.response?.data?.errors
  if (errors && typeof errors === 'object') return Object.values(errors).join(' ')
  return error.response?.data?.message || error.message || 'Request failed.'
}

async function loadAssignments() {
  isLoading.value = true
  try {
    const [assignmentResponse, scheduleResponse] = await Promise.all([
      api.get('/api/ojt-student-companies'),
      api.get('/api/company-schedules'),
    ])
    assignments.value = Array.isArray(assignmentResponse.data?.data) ? assignmentResponse.data.data : []
    schedules.value = Array.isArray(scheduleResponse.data?.data) ? scheduleResponse.data.data : []
    isSubmitted.value = schedules.value.length > 0
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isLoading.value = false
  }
}

function validateTimes() {
  if (form.value.morning_out <= form.value.morning_in) return 'Morning out must be after morning in.'
  if (form.value.afternoon_in <= form.value.morning_out) return 'Afternoon in must be after morning out.'
  if (form.value.afternoon_out <= form.value.afternoon_in) return 'Afternoon out must be after afternoon in.'
  return ''
}

async function saveSchedule() {
  const timeError = validateTimes()
  if (timeError) {
    showMessage('error', timeError)
    return
  }

  isSaving.value = true
  try {
    await api.post('/api/company-schedules', {
      company_id: Number(form.value.company_id),
      morning_in: form.value.morning_in,
      morning_out: form.value.morning_out,
      afternoon_in: form.value.afternoon_in,
      afternoon_out: form.value.afternoon_out,
      grace_period_minutes: Number(form.value.grace_period_minutes),
    })
    isSubmitted.value = true
    showMessage('success', 'Company attendance schedule submitted successfully.')
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isSaving.value = false
  }
}

onMounted(loadAssignments)
</script>

<template>
  <div class="student-layout">
    <StudentSideBar />
    <main class="schedule-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Student placement</p>
          <h1>Company attendance schedule</h1>
          <p>Submit the attendance schedule for your selected OJT company.</p>
        </div>
      </header>

      <div class="message-container" aria-live="polite">
        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
      </div>

      <p v-if="isLoading">Loading your OJT companies...</p>
      <p v-else-if="!assignments.length" class="notice">You must have an OJT company assignment before submitting a schedule.</p>
      <form v-else class="schedule-form" @submit.prevent="saveSchedule">
        <fieldset :disabled="isSubmitted || isSaving">
          <legend>{{ isSubmitted ? 'Submitted attendance schedule' : 'Attendance schedule' }}</legend>
          <label>
            Selected company
            <select v-model="form.company_id" required>
              <option disabled value="">Select your OJT company</option>
              <option v-for="assignment in assignments" :key="assignment.student_company_id" :value="assignment.company_id">
                {{ assignment.company_name }}
              </option>
            </select>
          </label>
          <div class="time-grid">
            <label>Morning in<input v-model="form.morning_in" type="time" required /></label>
            <label>Morning out<input v-model="form.morning_out" type="time" required /></label>
            <label>Afternoon in<input v-model="form.afternoon_in" type="time" required /></label>
            <label>Afternoon out<input v-model="form.afternoon_out" type="time" required /></label>
          </div>
          <label>
            Grace period in minutes
            <input v-model.number="form.grace_period_minutes" type="number" min="0" max="1440" required />
          </label>
        </fieldset>
        <button v-if="!isSubmitted" type="submit" :disabled="isSaving">
          {{ isSaving ? 'Submitting...' : 'Submit schedule' }}
        </button>
      </form>

    <section class="records-section">
      <h2>Submitted schedules</h2>
      <table>
        <thead><tr><th>Company</th><th>Morning</th><th>Afternoon</th><th>Grace period</th></tr></thead>
        <tbody>
          <tr v-for="schedule in schedules" :key="schedule.schedule_id">
            <td>{{ schedule.company_name }}</td>
            <td>{{ schedule.morning_in }} - {{ schedule.morning_out }}</td>
            <td>{{ schedule.afternoon_in }} - {{ schedule.afternoon_out }}</td>
            <td>{{ schedule.grace_period_minutes }} minutes</td>
          </tr>
          <tr v-if="!schedules.length"><td colspan="4">No attendance schedule submitted yet.</td></tr>
        </tbody>
      </table>
    </section>
    </main>
  </div>
</template>

<style scoped>
.student-layout { display: flex; min-height: 100vh; color: #19313a; }
.schedule-page { flex: 1; max-width: 900px; padding: 40px; }
.page-header { margin-bottom: 32px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
.schedule-form { display: grid; gap: 18px; max-width: 700px; }
fieldset { display: grid; gap: 18px; margin: 0; padding: 24px; border: 1px solid #cbd8d5; border-radius: 6px; background: #fffaf3; }
legend { padding: 0 8px; font: 700 16px 'Roboto', sans-serif; }
.schedule-form label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
.time-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
input, select { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; background: #fff; font: 14px 'Roboto', sans-serif; }
button { width: fit-content; border: 1px solid #b9cbc6; border-radius: 4px; padding: 10px 14px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
.notice { max-width: 700px; padding: 16px; background: #edf3f0; border-left: 4px solid #b04a32; color: #19313a; }
.records-section { max-width: 900px; margin-top: 36px; }
.records-section h2 { margin: 0 0 14px; }
.records-section table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
.records-section th, .records-section td { padding: 12px 10px; border-bottom: 1px solid #dce6e3; text-align: left; vertical-align: top; }
.records-section th { background: #edf3f0; }
.message-container { position: fixed; top: 24px; right: 24px; z-index: 20; width: min(360px, calc(100vw - 48px)); }
.message { margin: 0; padding: 14px 16px; border-left: 4px solid; border-radius: 4px; box-shadow: 0 8px 24px rgb(25 49 58 / 14%); font: 14px/1.4 'Roboto', sans-serif; }
.error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
.success { border-color: #2b8a6e; background: #edf8f1; color: #21704f; }
@media (max-width: 700px) { .student-layout { display: block; } .schedule-page { padding: 24px 16px; } .time-grid { grid-template-columns: 1fr; } }
</style>
