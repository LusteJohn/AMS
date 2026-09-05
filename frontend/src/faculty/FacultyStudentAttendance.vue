<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import FacultySideBar from './components/FacultySideBar.vue'
import '../assets/faculty/FacultyStudentAttendance.css'

const attendance = ref([])
const companies = ref([])
const facultyProfile = ref(null)
const filters = ref({ company_id: '', attendance_date: '' })
const expandedAttendanceId = ref(null)
const attendanceDetails = ref({})
const isLoading = ref(false)
const isDetailsLoading = ref(false)
const errorMessage = ref('')
let messageTimer

const availableDates = computed(() => [...new Set(
	attendance.value
		.filter((item) => String(item.company_id) === String(filters.value.company_id))
		.map((item) => item.attendance_date),
)].sort().reverse())

const filteredAttendance = computed(() => attendance.value.filter((item) => {
	const matchesCompany = !filters.value.company_id || String(item.company_id) === String(filters.value.company_id)
	const matchesDate = !filters.value.attendance_date || item.attendance_date === filters.value.attendance_date
	return matchesCompany && matchesDate
}))

function showError(message) {
	errorMessage.value = message
	clearTimeout(messageTimer)
	messageTimer = setTimeout(() => { errorMessage.value = '' }, 3500)
}

function apiError(error) {
	return error.response?.data?.message || error.message || 'Request failed.'
}

function formatTime12(value) {
	if (!value) return '-'
	const [hours, minutes] = String(value).split(':').map(Number)
	if (Number.isNaN(hours) || Number.isNaN(minutes)) return value
	const date = new Date(2000, 0, 1, hours, minutes)
	return new Intl.DateTimeFormat('en-US', {
		hour: 'numeric',
		minute: '2-digit',
		hour12: true,
	}).format(date)
}

function clearCompanyFilter() {
	filters.value.attendance_date = ''
}

async function toggleDetails(item) {
	if (expandedAttendanceId.value === item.attendance_id) {
		expandedAttendanceId.value = null
		return
	}
	expandedAttendanceId.value = item.attendance_id
	if (attendanceDetails.value[item.attendance_id]) return

	isDetailsLoading.value = true
	try {
		const response = await api.get('/api/attendance-logs', { params: { attendance_id: item.attendance_id } })
		const logs = Array.isArray(response.data?.data) ? response.data.data : []
		attendanceDetails.value[item.attendance_id] = { logs }
	} catch (error) {
		expandedAttendanceId.value = null
		showError(apiError(error))
	} finally {
		isDetailsLoading.value = false
	}
}

async function loadData() {
	isLoading.value = true
	try {
		const [profileResponse, companiesResponse] = await Promise.all([
			api.get('/api/faculty/profile'),
			api.get('/api/companies'),
		])
		facultyProfile.value = profileResponse.data?.data
		companies.value = Array.isArray(companiesResponse.data?.data) ? companiesResponse.data.data : []

		const sectionId = facultyProfile.value?.section_id
		const params = sectionId ? { section_id: sectionId } : {}
		const attendanceResponse = await api.get('/api/attendance', { params })
		attendance.value = Array.isArray(attendanceResponse.data?.data) ? attendanceResponse.data.data : []
	} catch (error) {
		showError(apiError(error))
	} finally {
		isLoading.value = false
	}
}

onMounted(loadData)
</script>

<template>
	<div class="faculty-layout">
		<FacultySideBar />
		<main class="attendance-page">
			<header class="page-header">
				<div>
					<p class="eyebrow">Faculty</p>
					<h1>Student attendance</h1>
					<p>Review attendance records and time logs for students in your section.</p>
					<p v-if="facultyProfile" class="section-info">
						Viewing section: <strong>{{ facultyProfile.section_name }}</strong>
						({{ facultyProfile.program_name }} - {{ facultyProfile.college_name }})
					</p>
				</div>
				<button type="button" :disabled="isLoading" @click="loadData">Refresh</button>
			</header>

			<div class="message-container" aria-live="polite"><p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p></div>

			<section class="filters" aria-label="Attendance filters">
				<label>Company<select v-model="filters.company_id" @change="clearCompanyFilter"><option value="">All companies</option><option v-for="company in companies" :key="company.company_id" :value="company.company_id">{{ company.company_name }}</option></select></label>
				<label>Date<select v-model="filters.attendance_date" :disabled="!filters.company_id"><option value="">All dates</option><option v-for="date in availableDates" :key="date" :value="date">{{ date }}</option></select></label>
				<button type="button" class="clear-button" :disabled="!filters.company_id && !filters.attendance_date" @click="filters = { company_id: '', attendance_date: '' }">Clear filters</button>
			</section>

			<p v-if="isLoading">Loading attendance...</p>
			<div v-else class="table-wrap">
				<table>
					<thead><tr><th>Date</th><th>Student</th><th>School ID</th><th>Company</th><th>Total hours</th><th>Status</th><th>Details</th></tr></thead>
					<tbody>
						<template v-for="item in filteredAttendance" :key="item.attendance_id">
							<tr>
								<td>{{ item.attendance_date }}</td>
								<td>{{ item.firstname }} {{ item.middlename }} {{ item.lastname }}</td>
								<td>{{ item.school_id || '-' }}</td>
								<td>{{ item.company_name }}</td>
								<td>{{ item.total_hours }}</td>
								<td>{{ item.status }}</td>
								<td><button type="button" class="expand-button" :aria-expanded="expandedAttendanceId === item.attendance_id" :aria-label="expandedAttendanceId === item.attendance_id ? 'Hide attendance details' : 'Show attendance details'" @click="toggleDetails(item)"><span class="arrow" :class="{ 'arrow-up': expandedAttendanceId === item.attendance_id }" aria-hidden="true"></span></button></td>
							</tr>
							<tr v-if="expandedAttendanceId === item.attendance_id" class="details-row">
								<td colspan="7">
									<p v-if="isDetailsLoading">Loading attendance logs...</p>
									<div v-else-if="attendanceDetails[item.attendance_id]?.logs.length" class="details-content">
										<h2>Attendance logs</h2>
										<table class="nested-table"><thead><tr><th>Attendance type</th><th>Time</th><th>Status</th></tr></thead><tbody><tr v-for="log in attendanceDetails[item.attendance_id].logs" :key="log.attendance_log_id"><td>{{ log.attendance_type }}</td><td>{{ formatTime12(log.attendance_time) }}</td><td>{{ log.status }}</td></tr></tbody></table>
									</div>
									<p v-else>No attendance logs found.</p>
								</td>
							</tr>
						</template>
						<tr v-if="!filteredAttendance.length"><td colspan="7">No attendance records match the selected filters.</td></tr>
					</tbody>
				</table>
			</div>
		</main>
	</div>
</template>

<style scoped>
.faculty-layout { display: flex; min-height: 100vh; color: #19313a; }
.attendance-page { flex: 1; max-width: 1400px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 28px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
.section-info { margin-top: 8px; color: #5b747b; font: 500 14px 'Roboto', sans-serif; }
.section-info strong { color: #19313a; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
.filters { display: flex; align-items: end; gap: 14px; flex-wrap: wrap; margin-bottom: 24px; padding: 16px; background: #edf3f0; border: 1px solid #dce6e3; }
.filters label { display: grid; gap: 6px; min-width: 220px; font: 700 13px 'Roboto', sans-serif; }
select { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; background: #fffaf3; font: 14px 'Roboto', sans-serif; }
.clear-button { margin-left: auto; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
th, td { padding: 12px 10px; border-bottom: 1px solid #dce6e3; text-align: left; vertical-align: top; }
th { background: #edf3f0; }
.expand-button { min-width: 34px; padding: 8px 10px; }
.arrow { display: inline-block; width: 8px; height: 8px; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: rotate(45deg) translateY(-2px); transition: transform .15s ease; }
.arrow-up { transform: rotate(225deg) translate(-1px, -1px); }
.details-row > td { padding: 18px; background: #f7faf8; }
.details-content h2 { margin-bottom: 12px; font-size: 16px; }
.nested-table { background: #fffaf3; }
.nested-table th { background: #dfeae6; }
.message-container { position: fixed; top: 24px; right: 24px; z-index: 20; width: min(360px, calc(100vw - 48px)); }
.message { margin: 0; padding: 14px 16px; border-left: 4px solid #b83b3b; background: #fff0ed; color: #a33b2e; }
a { color: #a8492f; font-weight: 700; }
@media (max-width: 700px) { .faculty-layout { display: block; } .attendance-page { padding: 24px 16px; } .page-header { align-items: start; flex-direction: column; } .clear-button { margin-left: 0; } }
</style>
