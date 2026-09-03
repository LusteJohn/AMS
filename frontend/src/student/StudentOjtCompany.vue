<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import StudentSideBar from './components/StudentSideBar.vue'

const companies = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const isFormOpen = ref(false)
const editingId = ref(null)
const errorMessage = ref('')
const successMessage = ref('')
let messageTimer

const form = ref({
  company_name: '',
  description: '',
  contact_number: '',
  email_address: '',
  address: '',
})

const formTitle = computed(() => editingId.value ? 'Edit OJT company' : 'Add OJT company')

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
  editingId.value = null
  form.value = {
    company_name: '',
    description: '',
    contact_number: '',
    email_address: '',
    address: '',
  }
}

function openCreate() {
  resetForm()
  isFormOpen.value = true
}

function openEdit(company) {
  editingId.value = company.company_id
  form.value = {
    company_name: company.company_name || '',
    description: company.description || '',
    contact_number: company.contact_number || '',
    email_address: company.email_address || '',
    address: company.address || '',
  }
  isFormOpen.value = true
}

function closeForm() {
  if (!isSaving.value) isFormOpen.value = false
}

async function loadCompanies() {
  isLoading.value = true
  try {
    const response = await api.get('/api/companies')
    companies.value = Array.isArray(response.data?.data) ? response.data.data : []
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isLoading.value = false
  }
}

async function saveCompany() {
  const isUpdate = Boolean(editingId.value)
  isSaving.value = true
  try {
    if (isUpdate) {
      await api.put(`/api/companies/${editingId.value}`, form.value)
    } else {
      await api.post('/api/companies', form.value)
    }
    isFormOpen.value = false
    showMessage('success', `OJT company ${isUpdate ? 'updated' : 'added'} successfully.`)
    await loadCompanies()
  } catch (error) {
    showMessage('error', apiError(error))
  } finally {
    isSaving.value = false
  }
}

onMounted(loadCompanies)
</script>

<template>
  <div class="student-layout">
    <StudentSideBar />
    <main class="company-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Student placement</p>
          <h1>OJT companies</h1>
          <p>View available companies and submit company details.</p>
        </div>
        <div class="header-actions">
          <button type="button" :disabled="isLoading" @click="loadCompanies">Refresh</button>
          <button type="button" @click="openCreate">Add company</button>
        </div>
      </header>

      <div class="message-container" aria-live="polite">
        <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
        <p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
      </div>

      <p v-if="isLoading">Loading companies...</p>
      <table>
        <thead><tr><th>Company</th><th>Description</th><th>Contact</th><th>Email</th><th>Address</th><th>Action</th></tr></thead>
        <tbody>
          <tr v-for="company in companies" :key="company.company_id">
            <td>{{ company.company_name }}</td>
            <td>{{ company.description || '-' }}</td>
            <td>{{ company.contact_number || '-' }}</td>
            <td>{{ company.email_address || '-' }}</td>
            <td>{{ company.address }}</td>
            <td><button type="button" @click="openEdit(company)">Edit</button></td>
          </tr>
          <tr v-if="!companies.length"><td colspan="6">No OJT companies found.</td></tr>
        </tbody>
      </table>
    </main>

    <div v-if="isFormOpen" class="modal-backdrop" @click.self="closeForm">
      <form class="modal" @submit.prevent="saveCompany">
        <h2>{{ formTitle }}</h2>
        <label>
          Company name
          <input v-model.trim="form.company_name" required maxlength="150" />
        </label>
        <label>
          Contact number
          <input v-model.trim="form.contact_number" maxlength="30" />
        </label>
        <label>
          Email address
          <input v-model.trim="form.email_address" type="email" maxlength="100" />
        </label>
        <label>
          Address
          <input v-model.trim="form.address" required maxlength="255" />
        </label>
        <label>
          Description
          <textarea v-model.trim="form.description" rows="5"></textarea>
        </label>
        <div class="modal-actions">
          <button type="button" :disabled="isSaving" @click="closeForm">Cancel</button>
          <button type="submit" :disabled="isSaving">{{ isSaving ? 'Saving...' : 'Save company' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.student-layout { display: flex; min-height: 100vh; color: #19313a; }
.company-page { flex: 1; max-width: 1200px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 32px; }
.header-actions, .actions, .modal-actions { display: flex; gap: 8px; }
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
.modal { width: min(520px, 100%); display: grid; gap: 14px; padding: 24px; background: #fffaf3; border: 1px solid #cbd8d5; border-radius: 6px; }
.modal label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, textarea { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
.modal-actions { justify-content: end; }
@media (max-width: 700px) { .student-layout { display: block; } .company-page { padding: 24px 16px; overflow-x: auto; } .page-header { align-items: start; flex-direction: column; } }
</style>
