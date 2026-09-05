<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import FacultySideBar from './components/FacultySideBar.vue'
import '../assets/faculty/FacultyStudentAttendanceHistory.css'

const records = ref([])
const facultyProfile = ref(null)
const filters = ref({ attendance_date: '' })
const isLoading = ref(false)
const errorMessage = ref('')

const availableDates = computed(() => [...new Set(records.value.map((record) => record.attendance_date))].sort().reverse())
const filteredRecords = computed(() => records.value.filter((record) => (
	!filters.value.attendance_date || record.attendance_date === filters.value.attendance_date
)))
const groupedRecords = computed(() => {
	const companies = new Map()
	for (const record of filteredRecords.value) {
		const companyKey = String(record.company_id)
		if (!companies.has(companyKey)) {
			companies.set(companyKey, { company_id: record.company_id, company_name: record.company_name, dates: [] })
		}
		companies.get(companyKey).dates.push(record)
	}
	return [...companies.values()]
})

function apiError(error) {
	return error.response?.data?.message || error.message || 'Request failed.'
}

function formatTime12(value) {
	if (!value) return '-'
	const [hours, minutes] = String(value).split(':').map(Number)
	if (Number.isNaN(hours) || Number.isNaN(minutes)) return value
	return new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
		.format(new Date(2000, 0, 1, hours, minutes))
}

async function loadHistory() {
	isLoading.value = true
	errorMessage.value = ''
	try {
		const profileResponse = await api.get('/api/faculty/profile')
		facultyProfile.value = profileResponse.data?.data
		const sectionId = facultyProfile.value?.section_id
		if (!sectionId) {
			records.value = []
			return
		}

		const attendanceResponse = await api.get('/api/attendance', { params: { section_id: sectionId } })
		const attendance = Array.isArray(attendanceResponse.data?.data) ? attendanceResponse.data.data : []
		records.value = await Promise.all(attendance.map(async (record) => {
			const response = await api.get('/api/attendance-logs', { params: { attendance_id: record.attendance_id } })
			return { ...record, logs: Array.isArray(response.data?.data) ? response.data.data : [] }
		}))
	} catch (error) {
		errorMessage.value = apiError(error)
	} finally {
		isLoading.value = false
	}
}

onMounted(loadHistory)
</script>

<template>
	<div class="faculty-layout">
		<FacultySideBar />
		<main class="faculty-history-page">
			<header class="page-header">
				<div>
					<p class="eyebrow">Faculty</p>
					<h1>Student attendance history</h1>
					<p>Review attendance logs for students assigned to your section.</p>
					<p v-if="facultyProfile" class="section-info">Assigned section: <strong>{{ facultyProfile.section_name }}</strong> ({{ facultyProfile.program_name }} - {{ facultyProfile.college_name }})</p>
				</div>
				<button type="button" :disabled="isLoading" @click="loadHistory">Refresh</button>
			</header>

			<section class="filters" aria-label="Attendance history filters">
				<label>Attendance date<select v-model="filters.attendance_date"><option value="">All dates</option><option v-for="date in availableDates" :key="date" :value="date">{{ date }}</option></select></label>
				<button type="button" :disabled="!filters.attendance_date" @click="filters.attendance_date = ''">Clear filter</button>
			</section>

			<p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
			<p v-if="isLoading">Loading section attendance history...</p>
			<p v-else-if="!groupedRecords.length" class="empty-state">No attendance history found for your assigned section.</p>

			<section v-for="company in groupedRecords" :key="company.company_id" class="company-container">
				<h2>{{ company.company_name }}</h2>
				<div v-for="record in company.dates" :key="record.attendance_id" class="date-container">
					<div class="date-heading">
						<div><span class="label">Attendance date</span><strong>{{ record.attendance_date }}</strong><span class="student-name">{{ record.firstname }} {{ record.middlename }} {{ record.lastname }} · {{ record.school_id || 'No school ID' }}</span></div>
						<div class="summary"><span>{{ record.total_hours }} hours</span><span>{{ record.status }}</span></div>
					</div>
					<table><thead><tr><th>Attendance type</th><th>Time</th><th>Status</th></tr></thead><tbody>
						<tr v-for="log in record.logs" :key="log.attendance_log_id"><td>{{ log.attendance_type }}</td><td>{{ formatTime12(log.attendance_time) }}</td><td>{{ log.status }}</td></tr>
						<tr v-if="!record.logs.length"><td colspan="3">No attendance logs recorded.</td></tr>
					</tbody></table>
				</div>
			</section>
		</main>
	</div>
</template>
