<script setup>
import { onMounted, ref } from 'vue'

import api from '../api/axios'
import FacultySideBar from './components/FacultySideBar.vue'

const students = ref([])
const facultyProfile = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)
const isFormOpen = ref(false)
const isManualFormOpen = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const csvFile = ref(null)
const isCsvHelpOpen = ref(false)
const manualForm = ref({
  username: '', email: '', password: '', school_id: '',
  firstname: '', middlename: '', lastname: '', name_ext: '', gender: '', address: '',
})
let messageTimer

const facultySectionId = ref('')

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
  csvFile.value = null
  isCsvHelpOpen.value = false
}

function openForm() {
  resetForm()
  isFormOpen.value = true
}

function openManualForm() {
  manualForm.value = {
    username: '', email: '', password: '', school_id: '',
    firstname: '', middlename: '', lastname: '', name_ext: '', gender: '', address: '',
  }
  isManualFormOpen.value = true
}

function selectCsv(event) {
  csvFile.value = event.target.files?.[0] || null
}

function closeForm() {
  if (!isSaving.value) isFormOpen.value = false
}

function closeManualForm() {
  if (!isSaving.value) isManualFormOpen.value = false
}

async function loadStudents() {
  isLoading.value = true
  try {
    const [studentsResponse, profileResponse] = await Promise.all([
      api.get('/api/students'),
      api.get('/api/faculty/profile'),
    ])
    students.value = Array.isArray(studentsResponse.data?.data) ? studentsResponse.data.data : []
    facultyProfile.value = profileResponse.data?.data
    facultySectionId.value = facultyProfile.value?.section_id || ''
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isLoading.value = false
  }
}

async function importStudents() {
  isSaving.value = true
  try {
    const payload = new FormData()
    payload.append('csv_file', csvFile.value)
    payload.append('section_id', String(facultySectionId.value))
    await api.post('/api/students/import', payload)
    isFormOpen.value = false
    showMessage('success', 'Student account registered successfully.')
    await loadStudents()
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isSaving.value = false
  }
}

async function registerStudent() {
  isSaving.value = true
  try {
    await api.post('/api/students', {
      ...manualForm.value,
      section_id: Number(facultySectionId.value),
      school_id: manualForm.value.school_id || null,
    })
    isManualFormOpen.value = false
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
  <div class="faculty-layout">
    <FacultySideBar />
    <main class="faculty-student-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Faculty</p>
          <h1>Students</h1>
          <p>View student profiles and register new student accounts in your section.</p>
          <p v-if="facultyProfile" class="section-info">
            Assigned section:
            <strong>{{ facultyProfile.section_name }}</strong>
            ({{ facultyProfile.program_name }} - {{ facultyProfile.college_name }})
          </p>
        </div>
        <div class="header-actions">
          <button type="button" :disabled="isLoading" @click="loadStudents">Refresh</button>
          <button type="button" @click="openManualForm">Register one student</button>
          <button type="button" @click="openForm">Import CSV</button>
        </div>
      </header>

      <div class="message-container" aria-live="polite">
        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
      </div>

      <p v-if="isLoading">Loading students...</p>
      <table v-else>
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
      <form class="modal" @submit.prevent="importStudents">
        <div class="modal-title">
          <h2>Import student accounts</h2>
          <button type="button" class="help-button" :aria-expanded="isCsvHelpOpen" aria-label="CSV format help" @click="isCsvHelpOpen = !isCsvHelpOpen">?</button>
        </div>
        <div v-if="isCsvHelpOpen" class="csv-help">
          <p>The CSV or XLSX file must include these column headers:</p>
          <code>school_id,firstname,middlename,lastname,gender</code>
          <p>Example:</p>
          <pre>school_id,firstname,middlename,lastname,gender
20260001,Juan,,Dela Cruz,male</pre>
          <ul>
            <li>The section is automatically set to your assigned section.</li>
            <li>Use a school ID with up to 10 characters.</li>
            <li>The school ID becomes the username and initial password.</li>
            <li>Leave middlename blank when it is not available.</li>
          </ul>
        </div>
        <p class="modal-help">Required columns: school_id, firstname, middlename, lastname, gender.</p>
        <p class="modal-help">Students will be registered to your assigned section: <strong>{{ facultyProfile?.section_name || '-' }}</strong></p>
        <label>CSV or XLSX file<input type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required @change="selectCsv" /></label>
        <div class="modal-actions">
          <button type="button" :disabled="isSaving" @click="closeForm">Cancel</button>
          <button type="submit" :disabled="isSaving || !csvFile">{{ isSaving ? 'Importing...' : 'Import students' }}</button>
        </div>
      </form>
    </div>

    <div v-if="isManualFormOpen" class="modal-backdrop" @click.self="closeManualForm">
      <form class="modal" @submit.prevent="registerStudent">
        <div class="modal-title">
          <h2>Register one student</h2>
        </div>
        <p class="modal-help">This student will be registered to your assigned section: <strong>{{ facultyProfile?.section_name || '-' }}</strong></p>
        <label>Username<input v-model.trim="manualForm.username" required maxlength="100" autocomplete="username" /></label>
        <label>Email<input v-model.trim="manualForm.email" type="email" required maxlength="100" autocomplete="email" /></label>
        <label>Password<input v-model="manualForm.password" type="password" required autocomplete="new-password" /></label>
        <label>School ID<input v-model.trim="manualForm.school_id" maxlength="10" /></label>
        <label>First name<input v-model.trim="manualForm.firstname" required maxlength="50" /></label>
        <label>Middle name<input v-model.trim="manualForm.middlename" maxlength="50" /></label>
        <label>Last name<input v-model.trim="manualForm.lastname" required maxlength="50" /></label>
        <label>Name extension<input v-model.trim="manualForm.name_ext" maxlength="5" /></label>
        <label>Gender
          <select v-model="manualForm.gender" required>
            <option disabled value="">Select gender</option>
            <option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>
          </select>
        </label>
        <label>Address<textarea v-model.trim="manualForm.address" required maxlength="255" rows="3"></textarea></label>
        <div class="modal-actions">
          <button type="button" :disabled="isSaving" @click="closeManualForm">Cancel</button>
          <button type="submit" :disabled="isSaving">{{ isSaving ? 'Registering...' : 'Register student' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.faculty-layout { display: flex; min-height: 100vh; color: #19313a; }
.faculty-student-page { flex: 1; max-width: 1200px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 32px; }
.header-actions { display: flex; gap: 8px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
.section-info { margin-top: 8px; color: #5b747b; font: 500 14px 'Roboto', sans-serif; }
.section-info strong { color: #19313a; }
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
.modal-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.help-button { width: 28px; height: 28px; padding: 0; border-radius: 50%; font-weight: 700; }
.csv-help { padding: 12px; border: 1px solid #cbd8d5; background: #edf3f0; font: 13px/1.4 'Roboto', sans-serif; }
.csv-help p { margin: 0 0 8px; }
.csv-help code, .csv-help pre { display: block; overflow-x: auto; margin: 0 0 10px; font: 12px/1.5 monospace; }
.csv-help ul { margin: 0; padding-left: 18px; }
.modal-help { margin: 0; color: #5b747b; font: 13px/1.4 'Roboto', sans-serif; }
.modal label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, select, textarea { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
.modal-actions { justify-content: end; }
@media (max-width: 700px) { .faculty-layout { display: block; } .faculty-student-page { padding: 24px 16px; } .page-header { align-items: start; flex-direction: column; } }
</style>
