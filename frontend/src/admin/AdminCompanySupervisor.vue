<script setup>
import { onMounted, ref } from 'vue'
import api from '../api/axios'
import AdminSideBar from './components/AdminSideBar.vue'
import '../assets/admin/AdminCompanySupervisor.css'

const supervisors = ref([])
const companies = ref([])
const form = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
let timer

function message(type, text) {
  errorMessage.value = type === 'error' ? text : ''
  successMessage.value = type === 'success' ? text : ''
  clearTimeout(timer)
  timer = setTimeout(() => { errorMessage.value = ''; successMessage.value = '' }, 3000)
}
function apiError(error) { return error.response?.data?.message || error.message || 'Request failed.' }
function emptyForm() { return { company_id: '', firstname: '', middlename: '', lastname: '', name_ext: '', gender: '', address: '' } }
function openCreate() { form.value = { mode: 'create', id: null, ...emptyForm() } }
function openEdit(item) { form.value = { mode: 'edit', id: item.supervisor_id, company_id: item.company_id, firstname: item.firstname || '', middlename: item.middlename || '', lastname: item.lastname || '', name_ext: item.name_ext || '', gender: item.gender || '', address: item.address || '' } }
function closeForm() { if (!isSaving.value) form.value = null }
async function loadData() {
  isLoading.value = true
  try {
    const [supervisorResponse, companyResponse] = await Promise.all([api.get('/api/company-supervisors'), api.get('/api/companies')])
    supervisors.value = supervisorResponse.data?.data || []
    companies.value = companyResponse.data?.data || []
  } catch (error) { message('error', apiError(error)) } finally { isLoading.value = false }
}
async function save() {
  const current = form.value
  const isUpdate = current.mode === 'edit'
  isSaving.value = true
  try {
    if (isUpdate) await api.put(`/api/company-supervisors/${current.id}`, current)
    else await api.post('/api/company-supervisors', current)
    form.value = null
    message('success', `Supervisor ${isUpdate ? 'updated' : 'created'} successfully.`)
    await loadData()
  } catch (error) { message('error', apiError(error)) } finally { isSaving.value = false }
}
async function remove(item) {
  if (!window.confirm(`Delete ${item.firstname} ${item.lastname}?`)) return
  try { await api.delete(`/api/company-supervisors/${item.supervisor_id}`); message('success', 'Supervisor deleted successfully.'); await loadData() }
  catch (error) { message('error', apiError(error)) }
}
onMounted(loadData)
</script>

<template>
  <div class="layout"><AdminSideBar /><main class="page">
    <header class="header"><div><p class="eyebrow">Administration</p><h1>Company supervisors</h1><p>Manage company supervisor records.</p></div><div class="actions"><button :disabled="isLoading" @click="loadData">Refresh</button><button @click="openCreate">Add supervisor</button></div></header>
    <div class="messages" aria-live="polite"><p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p><p v-if="successMessage" class="success" role="status">{{ successMessage }}</p></div>
    <p v-if="isLoading">Loading supervisors...</p>
    <table><thead><tr><th>Supervisor</th><th>Company</th><th>Gender</th><th>Address</th><th>Actions</th></tr></thead><tbody>
      <tr v-for="item in supervisors" :key="item.supervisor_id"><td>{{ item.firstname }} {{ item.middlename }} {{ item.lastname }} {{ item.name_ext }}</td><td>{{ item.company_name }}</td><td>{{ item.gender }}</td><td>{{ item.address }}</td><td class="actions"><button @click="openEdit(item)">Edit</button><button @click="remove(item)">Delete</button></td></tr>
      <tr v-if="!supervisors.length"><td colspan="5">No company supervisors found.</td></tr>
    </tbody></table>
  </main>
  <div v-if="form" class="backdrop" @click.self="closeForm"><form class="modal" @submit.prevent="save"><h2>{{ form.mode === 'edit' ? 'Edit' : 'Add' }} supervisor</h2>
    <label>Company<select v-model="form.company_id" required><option disabled value="">Select company</option><option v-for="company in companies" :key="company.company_id" :value="company.company_id">{{ company.company_name }}</option></select></label>
    <label>First name<input v-model.trim="form.firstname" required maxlength="50" /></label><label>Middle name<input v-model.trim="form.middlename" maxlength="50" /></label><label>Last name<input v-model.trim="form.lastname" required maxlength="50" /></label><label>Name extension<input v-model.trim="form.name_ext" maxlength="5" /></label>
    <label>Gender<select v-model="form.gender" required><option disabled value="">Select gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></label><label>Address<textarea v-model.trim="form.address" required maxlength="255" rows="3"></textarea></label>
    <div class="modal-actions"><button type="button" :disabled="isSaving" @click="closeForm">Cancel</button><button type="submit" :disabled="isSaving">{{ isSaving ? 'Saving...' : 'Save supervisor' }}</button></div>
  </form></div></div>
</template>

<style scoped>
.layout{display:flex;min-height:100vh;color:#19313a}.page{flex:1;max-width:1200px;padding:40px}.header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:32px}.actions,.modal-actions{display:flex;gap:8px}.eyebrow{margin:0 0 8px;color:#b04a32;font:700 12px 'Roboto',sans-serif;text-transform:uppercase;letter-spacing:.08em}h1,h2{margin:0}.header p:last-child{color:#5b747b}button{border:1px solid #b9cbc6;border-radius:4px;padding:8px 12px;background:#fffaf3;color:#19313a;cursor:pointer;font-family:'Roboto',sans-serif}button:hover{border-color:#d96b45}button:disabled{cursor:wait;opacity:.6}table{width:100%;border-collapse:collapse;font:14px 'Roboto',sans-serif}th,td{padding:12px 10px;border-bottom:1px solid #dce6e3;text-align:left;vertical-align:top}th{background:#edf3f0}.messages{position:fixed;top:24px;right:24px;z-index:20;width:min(360px,calc(100vw - 48px))}.messages p{margin:0;padding:14px 16px;border-left:4px solid;border-radius:4px;box-shadow:0 8px 24px rgb(25 49 58 / 14%);font:14px/1.4 'Roboto',sans-serif}.error{border-color:#b83b3b;background:#fff0ed;color:#a33b2e}.success{border-color:#2b8a6e;background:#edf8f1;color:#21704f}.backdrop{position:fixed;inset:0;display:grid;place-items:center;padding:20px;background:rgb(25 49 58 / 35%)}.modal{width:min(520px,100%);display:grid;gap:14px;padding:24px;background:#fffaf3;border:1px solid #cbd8d5;border-radius:6px}.modal label{display:grid;gap:6px;font:700 13px 'Roboto',sans-serif}input,select,textarea{box-sizing:border-box;width:100%;padding:10px;border:1px solid #aebfbc;border-radius:3px;font:14px 'Roboto',sans-serif}.modal-actions{justify-content:end}@media(max-width:700px){.layout{display:block}.page{padding:24px 16px;overflow-x:auto}.header{align-items:start;flex-direction:column}}
</style>
