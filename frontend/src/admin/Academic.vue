<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import AdminSideBar from './components/AdminSideBar.vue'

const colleges = ref([])
const programs = ref([])
const sections = ref([])
const errorMessage = ref('')
const successMessage = ref('')
const isLoading = ref(false)
const form = ref(null)
let messageTimer

function showMessage(type, message) {
  errorMessage.value = type === 'error' ? message : ''
  successMessage.value = type === 'success' ? message : ''
  clearTimeout(messageTimer)
  messageTimer = setTimeout(() => {
    errorMessage.value = ''
    successMessage.value = ''
  }, 3000)
}

const formTitle = computed(() => {
  if (!form.value) return ''
  return `${form.value.mode === 'edit' ? 'Edit' : 'Add'} ${form.value.type}`
})

function rows(response) {
  return Array.isArray(response.data?.data) ? response.data.data : []
}

function apiError(error) {
  return error.response?.data?.message || error.message || 'Request failed.'
}

async function loadData() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const [collegeResponse, programResponse, sectionResponse] = await Promise.all([
      api.get('/api/colleges'),
      api.get('/api/programs'),
      api.get('/api/sections'),
    ])
    colleges.value = rows(collegeResponse)
    programs.value = rows(programResponse)
    sections.value = rows(sectionResponse)
  } catch (error) {
    errorMessage.value = apiError(error)
  } finally {
    isLoading.value = false
  }
}

function openCreate(type) {
  form.value = {
    type,
    mode: 'create',
    id: null,
    college_id: '',
    program_id: '',
    college_name: '',
    program: '',
    section: '',
  }
}

function openEdit(type, item) {
  form.value = {
    type,
    mode: 'edit',
    id: item.college_id || item.program_id || item.section_id,
    college_id: item.college_id || '',
    program_id: item.program_id || '',
    college_name: item.college_name || '',
    program: item.program || item.program_name || '',
    section: item.section || item.section_name || '',
  }
}

function closeForm() {
  form.value = null
}

function endpoint(type, id = null) {
  const path = { College: 'colleges', Program: 'programs', Section: 'sections' }[type]
  return `/api/${path}${id ? `/${id}` : ''}`
}

async function saveForm() {
  const current = form.value
  if (!current) return
  errorMessage.value = ''
  successMessage.value = ''

  const payload = current.type === 'College'
    ? { college_name: current.college_name }
    : current.type === 'Program'
      ? { college_id: Number(current.college_id), program: current.program }
      : { program_id: Number(current.program_id), section: current.section }

  try {
    if (current.mode === 'edit') {
      await api.put(endpoint(current.type, current.id), payload)
    } else {
      await api.post(endpoint(current.type), payload)
    }
    closeForm()
    showMessage('success', `${current.type} ${current.mode === 'edit' ? 'updated' : 'created'} successfully.`)
    await loadData()
  } catch (error) {
    showMessage('error', apiError(error))
  }
}

async function deleteItem(type, item) {
  const id = item.college_id || item.program_id || item.section_id
  if (!window.confirm(`Delete this ${type.toLowerCase()}?`)) return

  errorMessage.value = ''
  successMessage.value = ''
  try {
    await api.delete(endpoint(type, id))
    showMessage('success', `${type} deleted successfully.`)
    await loadData()
  } catch (error) {
    showMessage('error', apiError(error))
  }
}

onMounted(loadData)
</script>

<template>
  <div class="admin-layout">
    <AdminSideBar />
    <main class="academic-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Administration</p>
          <h1>Academic setup</h1>
          <p>Manage colleges, programs, and sections.</p>
        </div>
        <button type="button" :disabled="isLoading" @click="loadData">Refresh</button>
      </header>

      <div class="message-container" aria-live="polite">
        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
      </div>
      <p v-if="isLoading">Loading academic records...</p>

      <section class="table-section">
        <div class="section-header">
          <h2>Colleges</h2>
          <button type="button" @click="openCreate('College')">Add college</button>
        </div>
        <table>
          <thead><tr><th>Name</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="college in colleges" :key="college.college_id">
              <td>{{ college.college_name }}</td>
              <td class="actions">
                <button type="button" @click="openEdit('College', college)">Edit</button>
                <button type="button" @click="deleteItem('College', college)">Delete</button>
              </td>
            </tr>
            <tr v-if="!colleges.length"><td colspan="2">No colleges found.</td></tr>
          </tbody>
        </table>
      </section>

      <section class="table-section">
        <div class="section-header">
          <h2>Programs</h2>
          <button type="button" @click="openCreate('Program')">Add program</button>
        </div>
        <table>
          <thead><tr><th>Program</th><th>College</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="program in programs" :key="program.program_id">
              <td>{{ program.program || program.program_name }}</td>
              <td>{{ program.college_name || program.college_id }}</td>
              <td class="actions">
                <button type="button" @click="openEdit('Program', program)">Edit</button>
                <button type="button" @click="deleteItem('Program', program)">Delete</button>
              </td>
            </tr>
            <tr v-if="!programs.length"><td colspan="3">No programs found.</td></tr>
          </tbody>
        </table>
      </section>

      <section class="table-section">
        <div class="section-header">
          <h2>Sections</h2>
          <button type="button" @click="openCreate('Section')">Add section</button>
        </div>
        <table>
          <thead><tr><th>Section</th><th>Program</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="section in sections" :key="section.section_id">
              <td>{{ section.section || section.section_name }}</td>
              <td>{{ section.program_name || section.program_id }}</td>
              <td class="actions">
                <button type="button" @click="openEdit('Section', section)">Edit</button>
                <button type="button" @click="deleteItem('Section', section)">Delete</button>
              </td>
            </tr>
            <tr v-if="!sections.length"><td colspan="3">No sections found.</td></tr>
          </tbody>
        </table>
      </section>
    </main>

    <div v-if="form" class="modal-backdrop" @click.self="closeForm">
      <form class="modal" @submit.prevent="saveForm">
        <h2>{{ formTitle }}</h2>
        <label v-if="form.type === 'College'">
          College name
          <input v-model.trim="form.college_name" required maxlength="150" />
        </label>
        <template v-else-if="form.type === 'Program'">
          <label>
            College
            <select v-model="form.college_id" required>
              <option disabled value="">Select college</option>
              <option v-for="college in colleges" :key="college.college_id" :value="college.college_id">
                {{ college.college_name }}
              </option>
            </select>
          </label>
          <label>
            Program name
            <input v-model.trim="form.program" required maxlength="150" />
          </label>
        </template>
        <template v-else>
          <label>
            Program
            <select v-model="form.program_id" required>
              <option disabled value="">Select program</option>
              <option v-for="program in programs" :key="program.program_id" :value="program.program_id">
                {{ program.program || program.program_name }}
              </option>
            </select>
          </label>
          <label>
            Section name
            <input v-model.trim="form.section" required maxlength="100" />
          </label>
        </template>
        <div class="modal-actions">
          <button type="button" @click="closeForm">Cancel</button>
          <button type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; color: #19313a; }
.academic-page { flex: 1; max-width: 1100px; padding: 40px; }
.page-header, .section-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.page-header { margin-bottom: 32px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
.table-section { margin-bottom: 30px; }
.section-header { margin-bottom: 10px; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: #fffaf3; color: #19313a; cursor: pointer; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
th, td { padding: 12px 10px; border-bottom: 1px solid #dce6e3; text-align: left; }
th { background: #edf3f0; }
.actions { display: flex; gap: 8px; }
.message-container { position: fixed; top: 24px; right: 24px; z-index: 20; width: min(360px, calc(100vw - 48px)); }
.message { margin: 0; padding: 14px 16px; border-left: 4px solid; border-radius: 4px; box-shadow: 0 8px 24px rgb(25 49 58 / 14%); font: 14px/1.4 'Roboto', sans-serif; }
.error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
.success { border-color: #2b8a6e; background: #edf8f1; color: #21704f; }
.modal-backdrop { position: fixed; inset: 0; display: grid; place-items: center; padding: 20px; background: rgb(25 49 58 / 35%); }
.modal { width: min(420px, 100%); display: grid; gap: 18px; padding: 24px; background: #fffaf3; border: 1px solid #cbd8d5; border-radius: 6px; }
.modal label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, select { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
.modal-actions { display: flex; justify-content: end; gap: 8px; }
@media (max-width: 700px) { .admin-layout { display: block; } .admin-sidebar { border-right: 0; border-bottom: 1px solid #d9e2df; } .academic-page { padding: 24px 16px; overflow-x: auto; } .page-header { align-items: start; flex-direction: column; } }
</style>
