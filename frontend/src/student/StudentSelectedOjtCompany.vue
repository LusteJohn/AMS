<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import StudentSideBar from './components/StudentSideBar.vue'
import '../assets/student/StudentSelectedOjtCompany.css'

const companies = ref([])
const assignments = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const isFormOpen = ref(false)
const editingId = ref(null)
const errorMessage = ref('')
const successMessage = ref('')
let messageTimer

const form = ref({
	company_id: '',
	ojt_start_date: '',
	ojt_end_date: '',
	required_hours: 0,
})

const formTitle = computed(() => editingId.value ? 'Edit OJT company assignment' : 'Add OJT company')

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
	const validationErrors = error.response?.data?.errors
	if (validationErrors && typeof validationErrors === 'object') {
		return Object.values(validationErrors).join(' ')
	}
	return error.response?.data?.message || error.message || 'Request failed.'
}

function resetForm() {
	editingId.value = null
	form.value = {
		company_id: '',
		ojt_start_date: '',
		ojt_end_date: '',
		required_hours: 0,
	}
}

function openCreate() {
	resetForm()
	isFormOpen.value = true
}

function openEdit(assignment) {
	editingId.value = assignment.student_company_id
	form.value = {
		company_id: assignment.company_id,
		ojt_start_date: assignment.ojt_start_date,
		ojt_end_date: assignment.ojt_end_date,
		required_hours: assignment.required_hours,
	}
	isFormOpen.value = true
}

function closeForm() {
	if (!isSaving.value) isFormOpen.value = false
}

async function loadData() {
	isLoading.value = true
	try {
		const [assignmentResponse, companyResponse] = await Promise.all([
			api.get('/api/ojt-student-companies'),
			api.get('/api/companies'),
		])
		assignments.value = Array.isArray(assignmentResponse.data?.data) ? assignmentResponse.data.data : []
		companies.value = Array.isArray(companyResponse.data?.data) ? companyResponse.data.data : []
	} catch (error) {
		showMessage('error', apiError(error))
	} finally {
		isLoading.value = false
	}
}

async function saveAssignment() {
	if (form.value.ojt_end_date < form.value.ojt_start_date) {
		showMessage('error', 'End date must not be before start date.')
		return
	}

	const isUpdate = Boolean(editingId.value)
	isSaving.value = true
	try {
		const payload = {
			company_id: Number(form.value.company_id),
			ojt_start_date: form.value.ojt_start_date,
			ojt_end_date: form.value.ojt_end_date,
			required_hours: Number(form.value.required_hours),
		}
		if (isUpdate) {
			await api.put(`/api/ojt-student-companies/${editingId.value}`, payload)
		} else {
			await api.post('/api/ojt-student-companies', payload)
		}
		isFormOpen.value = false
		showMessage('success', `OJT company ${isUpdate ? 'updated' : 'added'} successfully.`)
		await loadData()
	} catch (error) {
		showMessage('error', apiError(error))
	} finally {
		isSaving.value = false
	}
}

onMounted(loadData)
</script>

<template>
	<div class="student-layout">
		<StudentSideBar />
		<main class="assignment-page">
			<header class="page-header">
				<div>
					<p class="eyebrow">Student placement</p>
					<h1>Selected OJT company</h1>
					<p>Submit the company where you will complete your OJT.</p>
				</div>
				<div class="header-actions">
					<button type="button" :disabled="isLoading" @click="loadData">Refresh</button>
					<button type="button" @click="openCreate">Add OJT company</button>
				</div>
			</header>

			<div class="message-container" aria-live="polite">
				<p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
				<p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p>
			</div>

			<p v-if="isLoading">Loading assignments...</p>
			<table v-else>
				<thead><tr><th>Company</th><th>OJT dates</th><th>Required hours</th><th>Status</th><th>Action</th></tr></thead>
				<tbody>
					<tr v-for="assignment in assignments" :key="assignment.student_company_id">
						<td>{{ assignment.company_name }}</td>
						<td>{{ assignment.ojt_start_date }} to {{ assignment.ojt_end_date }}</td>
						<td>{{ assignment.required_hours }}</td>
						<td>{{ assignment.status }}</td>
						<td><button type="button" @click="openEdit(assignment)">Edit</button></td>
					</tr>
					<tr v-if="!assignments.length"><td colspan="5">No OJT company assignments found.</td></tr>
				</tbody>
			</table>
		</main>

		<div v-if="isFormOpen" class="modal-backdrop" @click.self="closeForm">
			<form class="modal" @submit.prevent="saveAssignment">
				<h2>{{ formTitle }}</h2>
				<label>
					Company
					<select v-model="form.company_id" required>
						<option disabled value="">Select company</option>
						<option v-for="company in companies" :key="company.company_id" :value="company.company_id">
							{{ company.company_name }}
						</option>
					</select>
				</label>
				<label>OJT start date<input v-model="form.ojt_start_date" type="date" required /></label>
				<label>OJT end date<input v-model="form.ojt_end_date" type="date" required /></label>
				<label>Required hours<input v-model.number="form.required_hours" type="number" min="0" max="99999.99" step="0.01" required /></label>
				<div class="modal-actions">
					<button type="button" :disabled="isSaving" @click="closeForm">Cancel</button>
					<button type="submit" :disabled="isSaving">{{ isSaving ? 'Saving...' : 'Save assignment' }}</button>
				</div>
			</form>
		</div>
	</div>
</template>

<style scoped>
.student-layout { display: flex; min-height: 100vh; color: #19313a; }
.assignment-page { flex: 1; max-width: 1200px; padding: 40px; }
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
.modal { width: min(520px, 100%); display: grid; gap: 14px; padding: 24px; background: #fffaf3; border: 1px solid #cbd8d5; border-radius: 6px; }
.modal label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, select { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
.modal-actions { justify-content: end; }
@media (max-width: 700px) { .student-layout { display: block; } .assignment-page { padding: 24px 16px; overflow-x: auto; } .page-header { align-items: start; flex-direction: column; } }
</style>
