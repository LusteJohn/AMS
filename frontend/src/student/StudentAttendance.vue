<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import api from '../api/axios'
import StudentSideBar from './components/StudentSideBar.vue'

const assignments = ref([])
const companySchedules = ref([])
const attendance = ref([])
const attendanceForm = ref(null)
const logForm = ref(null)
const expandedAttendanceId = ref(null)
const attendanceDetails = ref({})
const isDetailsLoading = ref(false)
const isLoading = ref(false)
const isSaving = ref(false)
const isCameraLoading = ref(false)
const cameraActive = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const cameraError = ref('')
const selfiePreview = ref('')
const imageBlob = ref(null)
const video = ref(null)
const manilaNow = ref(new Date())
let cameraStream = null
let messageTimer
let clockTimer

const attendanceFormTitle = computed(() => attendanceForm.value?.id ? 'Edit attendance' : 'Add attendance')

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

function emptyAttendanceForm() {
	return { id: null, student_company_id: '', attendance_date: '', total_hours: 0, status: 'pending' }
}

function openAttendanceCreate() {
	attendanceForm.value = emptyAttendanceForm()
}

function openAttendanceEdit(item) {
	attendanceForm.value = {
		id: item.attendance_id,
		student_company_id: item.student_company_id,
		attendance_date: item.attendance_date,
		total_hours: item.total_hours,
		status: item.status,
	}
}

function closeAttendanceForm() {
	if (!isSaving.value) attendanceForm.value = null
}

function openLogForm(item) {
	stopCamera()
	logForm.value = {
		attendance: item,
		attendance_type: 'morning_in',
		attendance_time: new Date().toTimeString().slice(0, 5),
		status: 'on_time',
	}
	selfiePreview.value = ''
	imageBlob.value = null
	cameraError.value = ''
}

function scheduleForAttendance(item) {
	return companySchedules.value.find((schedule) => Number(schedule.company_id) === Number(item.company_id)) || null
}

function manilaClock() {
	return new Intl.DateTimeFormat('en-PH', {
		timeZone: 'Asia/Manila', dateStyle: 'medium', timeStyle: 'medium', hour12: true,
	}).format(manilaNow.value)
}

function earlyAttendanceMessage(item, attendanceType) {
	const schedule = scheduleForAttendance(item)
	if (!schedule) return 'No attendance schedule has been configured for this company.'
	const scheduleField = {
		morning_in: 'morning_in',
		morning_out: 'morning_out',
		afternoon_in: 'afternoon_in',
		afternoon_out: 'afternoon_out',
	}[attendanceType]
	const nowParts = new Intl.DateTimeFormat('en-CA', {
		timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
	}).formatToParts(new Date())
	const parts = Object.fromEntries(nowParts.map(({ type, value }) => [type, value]))
	const today = `${parts.year}-${parts.month}-${parts.day}`
	if (item.attendance_date !== today) return 'Attendance can only be recorded for today.'
	const currentTime = `${parts.hour}:${parts.minute}`
	const scheduledTime = String(schedule[scheduleField] || '').slice(0, 5)
	if (currentTime < scheduledTime) return `${attendanceType.replaceAll('_', ' ')} cannot be recorded before ${scheduledTime}.`
	return ''
}

function closeLogForm() {
	if (!isSaving.value) {
		stopCamera()
		logForm.value = null
	}
}

async function loadData() {
	isLoading.value = true
	try {
		const [attendanceResponse, assignmentResponse, scheduleResponse] = await Promise.all([
			api.get('/api/attendance'),
			api.get('/api/ojt-student-companies'),
			api.get('/api/company-schedules'),
		])
		attendance.value = Array.isArray(attendanceResponse.data?.data) ? attendanceResponse.data.data : []
		assignments.value = Array.isArray(assignmentResponse.data?.data) ? assignmentResponse.data.data : []
		companySchedules.value = Array.isArray(scheduleResponse.data?.data) ? scheduleResponse.data.data : []
	} catch (error) {
		showMessage('error', apiError(error))
	} finally {
		isLoading.value = false
	}
}

async function toggleAttendanceDetails(item) {
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
		const evidenceResponses = await Promise.all(logs.map((log) => api.get('/api/attendance-evidence', { params: { attendance_log_id: log.attendance_log_id } })))
		const evidence = evidenceResponses.flatMap((evidenceResponse) => Array.isArray(evidenceResponse.data?.data) ? evidenceResponse.data.data : [])
		attendanceDetails.value[item.attendance_id] = { logs, evidence }
	} catch (error) {
		showMessage('error', apiError(error))
		expandedAttendanceId.value = null
	} finally {
		isDetailsLoading.value = false
	}
}

function evidenceForLog(logId) {
		if (!expandedAttendanceId.value) return null
		return attendanceDetails.value[expandedAttendanceId.value]?.evidence.find((item) => item.attendance_log_id === logId) || null
}

function evidenceUrl(path) {
	return `${api.defaults.baseURL.replace(/\/$/, '')}/${path}`
}

async function saveAttendance() {
	const current = attendanceForm.value
	const isUpdate = Boolean(current.id)
	isSaving.value = true
	try {
		const payload = {
			student_company_id: Number(current.student_company_id),
			attendance_date: current.attendance_date,
			total_hours: Number(current.total_hours),
			status: current.status,
		}
		if (isUpdate) await api.put(`/api/attendance/${current.id}`, payload)
		else await api.post('/api/attendance', payload)
		attendanceForm.value = null
		showMessage('success', `Attendance ${isUpdate ? 'updated' : 'added'} successfully.`)
		await loadData()
	} catch (error) {
		showMessage('error', apiError(error))
	} finally {
		isSaving.value = false
	}
}

async function deleteAttendance(item) {
	if (!window.confirm(`Delete attendance for ${item.attendance_date}? Its logs and evidence will also be deleted.`)) return
	try {
		await api.delete(`/api/attendance/${item.attendance_id}`)
		showMessage('success', 'Attendance deleted successfully.')
		await loadData()
	} catch (error) {
		showMessage('error', apiError(error))
	}
}

async function startCamera() {
	cameraError.value = ''
	isCameraLoading.value = true
	try {
		if (!navigator.mediaDevices?.getUserMedia) throw new Error('Camera access is not supported by this browser.')
		if (selfiePreview.value) URL.revokeObjectURL(selfiePreview.value)
		selfiePreview.value = ''
		imageBlob.value = null
		stopCamera()
		cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
		video.value.srcObject = cameraStream
		cameraActive.value = true
		await video.value.play()
	} catch (error) {
		cameraActive.value = false
		cameraError.value = error.message || 'Unable to access the camera.'
	} finally {
		isCameraLoading.value = false
	}
}

function captureSelfie() {
	if (!video.value || !cameraStream) return
	const canvas = document.createElement('canvas')
	canvas.width = video.value.videoWidth
	canvas.height = video.value.videoHeight
	canvas.getContext('2d').drawImage(video.value, 0, 0, canvas.width, canvas.height)
	canvas.toBlob((blob) => {
		if (!blob) return
		imageBlob.value = blob
		selfiePreview.value = URL.createObjectURL(blob)
		stopCamera()
	}, 'image/jpeg', 0.9)
}

function stopCamera() {
	if (cameraStream) {
		cameraStream.getTracks().forEach((track) => track.stop())
		cameraStream = null
	}
	cameraActive.value = false
	if (video.value) video.value.srcObject = null
}

async function saveLogAndSelfie() {
	const timingError = earlyAttendanceMessage(logForm.value.attendance, logForm.value.attendance_type)
	if (timingError) {
		cameraError.value = timingError
		return
	}
	if (!imageBlob.value) {
		cameraError.value = 'Capture a selfie before saving the attendance log.'
		return
	}
	isSaving.value = true
	try {
		const logResponse = await api.post('/api/attendance-logs', {
			attendance_id: logForm.value.attendance.attendance_id,
			attendance_type: logForm.value.attendance_type,
		})
		const attendanceLogId = logResponse.data?.data?.attendance_log_id
		if (!attendanceLogId) throw new Error('Attendance log ID was not returned.')

		const formData = new FormData()
		formData.append('attendance_log_id', attendanceLogId)
		formData.append('image', imageBlob.value, `selfie-${attendanceLogId}.jpg`)
		await api.post('/api/attendance-evidence', formData)
		stopCamera()
		logForm.value = null
		showMessage('success', 'Attendance log and selfie saved successfully.')
		await loadData()
	} catch (error) {
		showMessage('error', apiError(error))
	} finally {
		isSaving.value = false
	}
}

onMounted(() => {
		loadData()
		clockTimer = window.setInterval(() => { manilaNow.value = new Date() }, 1000)
})
onBeforeUnmount(() => {
	stopCamera()
	window.clearInterval(clockTimer)
	if (selfiePreview.value) URL.revokeObjectURL(selfiePreview.value)
})
</script>

<template>
	<div class="student-layout">
		<StudentSideBar />
		<main class="attendance-page">
			<header class="page-header">
				<div><p class="eyebrow">Student placement</p><h1>Attendance</h1><p>Record your daily attendance and time logs.</p></div><div class="clock" aria-live="polite"><span>Asia/Manila</span><strong>{{ manilaClock() }}</strong></div>
				<div class="header-actions"><button type="button" :disabled="isLoading" @click="loadData">Refresh</button><button type="button" @click="openAttendanceCreate">Add attendance</button></div>
			</header>

			<div class="message-container" aria-live="polite"><p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p><p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p></div>
			<p v-if="isLoading">Loading attendance...</p>
			<div v-else class="table-wrap">
				<table>
					<thead><tr><th>Date</th><th>Company</th><th>Total hours</th><th>Status</th><th>Actions</th></tr></thead>
					<tbody>
						<template v-for="item in attendance" :key="item.attendance_id">
							<tr>
								<td>{{ item.attendance_date }}</td><td>{{ item.company_name }}</td><td>{{ item.total_hours }}</td><td>{{ item.status }}</td>
								<td class="row-actions"><button type="button" class="expand-button" :aria-expanded="expandedAttendanceId === item.attendance_id" :aria-label="expandedAttendanceId === item.attendance_id ? 'Hide attendance details' : 'Show attendance details'" @click="toggleAttendanceDetails(item)"><span class="arrow" :class="{ 'arrow-up': expandedAttendanceId === item.attendance_id }" aria-hidden="true"></span></button><button type="button" @click="openAttendanceEdit(item)">Edit</button><button type="button" @click="deleteAttendance(item)">Delete</button><button type="button" @click="openLogForm(item)">Add log + selfie</button></td>
							</tr>
							<tr v-if="expandedAttendanceId === item.attendance_id" class="details-row">
								<td colspan="5">
									<p v-if="isDetailsLoading">Loading attendance logs and evidence...</p>
									<div v-else-if="attendanceDetails[item.attendance_id]?.logs.length" class="details-content">
										<h3>Attendance logs and evidence</h3>
										<table class="nested-table"><thead><tr><th>Type</th><th>Time</th><th>Status</th><th>Evidence</th></tr></thead><tbody><tr v-for="log in attendanceDetails[item.attendance_id].logs" :key="log.attendance_log_id"><td>{{ log.attendance_type }}</td><td>{{ log.attendance_time }}</td><td>{{ log.status }}</td><td><a v-if="evidenceForLog(log.attendance_log_id)" :href="evidenceUrl(evidenceForLog(log.attendance_log_id).image_path)" target="_blank" rel="noopener">View selfie</a><span v-else>No selfie</span></td></tr></tbody></table>
									</div>
									<p v-else>No attendance logs or evidence found.</p>
								</td>
							</tr>
						</template>
						<tr v-if="!attendance.length"><td colspan="5">No attendance records found.</td></tr>
					</tbody>
				</table>
			</div>
		</main>

		<div v-if="attendanceForm" class="modal-backdrop" @click.self="closeAttendanceForm"><form class="modal" @submit.prevent="saveAttendance"><h2>{{ attendanceFormTitle }}</h2><label>OJT company<select v-model="attendanceForm.student_company_id" required><option disabled value="">Select company assignment</option><option v-for="assignment in assignments" :key="assignment.student_company_id" :value="assignment.student_company_id">{{ assignment.company_name }} ({{ assignment.ojt_start_date }} to {{ assignment.ojt_end_date }})</option></select></label><label>Attendance date<input v-model="attendanceForm.attendance_date" type="date" required /></label><label>Total hours<input v-model.number="attendanceForm.total_hours" type="number" min="0" max="999.99" step="0.01" required /></label><label>Status<select v-model="attendanceForm.status" required><option value="pending">Pending</option><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="leave">Leave</option></select></label><div class="modal-actions"><button type="button" :disabled="isSaving" @click="closeAttendanceForm">Cancel</button><button type="submit" :disabled="isSaving">{{ isSaving ? 'Saving...' : 'Save attendance' }}</button></div></form></div>

		<div v-if="logForm" class="modal-backdrop" @click.self="closeLogForm"><form class="modal log-modal" @submit.prevent="saveLogAndSelfie"><h2>Add attendance log</h2><p class="context">{{ logForm.attendance.company_name }} · {{ logForm.attendance.attendance_date }}</p><label>Attendance type<select v-model="logForm.attendance_type" required><option value="morning_in">Morning in</option><option value="morning_out">Morning out</option><option value="afternoon_in">Afternoon in</option><option value="afternoon_out">Afternoon out</option></select></label><div v-if="scheduleForAttendance(logForm.attendance)" class="schedule-context"><strong>Company schedule</strong><span>Morning: {{ scheduleForAttendance(logForm.attendance).morning_in }} - {{ scheduleForAttendance(logForm.attendance).morning_out }}</span><span>Afternoon: {{ scheduleForAttendance(logForm.attendance).afternoon_in }} - {{ scheduleForAttendance(logForm.attendance).afternoon_out }}</span><span>Grace period: {{ scheduleForAttendance(logForm.attendance).grace_period_minutes }} minutes</span></div><p class="current-time">Recorded time: <strong>{{ manilaClock() }}</strong><br /><small>Status is calculated from the company schedule.</small></p><div class="camera-box"><video ref="video" v-show="!selfiePreview" autoplay playsinline></video><img v-if="selfiePreview" :src="selfiePreview" alt="Captured attendance selfie" /><p v-if="cameraError" class="camera-error" role="alert">{{ cameraError }}</p><div class="camera-actions"><button type="button" :disabled="isCameraLoading || isSaving" @click="startCamera">{{ isCameraLoading ? 'Opening camera...' : 'Open camera' }}</button><button type="button" :disabled="!cameraActive || isSaving" @click="captureSelfie">Capture selfie</button></div></div><div class="modal-actions"><button type="button" :disabled="isSaving" @click="closeLogForm">Cancel</button><button type="submit" :disabled="isSaving || !imageBlob">{{ isSaving ? 'Saving...' : 'Save log and selfie' }}</button></div></form></div>
	</div>
</template>

<style scoped>
.student-layout { display: flex; min-height: 100vh; color: #19313a; }
.attendance-page { flex: 1; max-width: 1200px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 32px; }
.header-actions, .modal-actions, .row-actions, .camera-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.expand-button { min-width: 34px; padding: 8px 10px; }
.arrow { display: inline-block; width: 8px; height: 8px; border-right: 2px solid currentColor; border-bottom: 2px solid currentColor; transform: rotate(45deg) translateY(-2px); transition: transform .15s ease; }
.arrow-up { transform: rotate(225deg) translate(-1px, -1px); }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child, .context { color: #5b747b; }
.clock { display: grid; gap: 4px; min-width: 210px; padding: 12px 14px; border-left: 3px solid #d96b45; background: #edf3f0; }
.clock span { color: #b04a32; font: 700 11px 'Roboto', sans-serif; letter-spacing: .08em; text-transform: uppercase; }
.clock strong { font: 700 14px 'Roboto', sans-serif; }
.table-wrap { overflow-x: auto; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
th, td { padding: 12px 10px; border-bottom: 1px solid #dce6e3; text-align: left; vertical-align: top; }
th { background: #edf3f0; }
.details-row > td { padding: 18px; background: #f7faf8; }
.details-content h3 { margin: 0 0 12px; font-size: 16px; }
.nested-table { background: #fffaf3; }
.schedule-context { display: grid; gap: 4px; padding: 12px; border-left: 3px solid #2b8a6e; background: #edf8f1; font-size: 13px; }
.current-time { margin: 0; padding: 10px 12px; background: #edf3f0; line-height: 1.5; }
.message-container { position: fixed; top: 24px; right: 24px; z-index: 20; width: min(360px, calc(100vw - 48px)); }
.message { margin: 0; padding: 14px 16px; border-left: 4px solid; border-radius: 4px; box-shadow: 0 8px 24px rgb(25 49 58 / 14%); font: 14px/1.4 'Roboto', sans-serif; }
.error, .camera-error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
.success { border-color: #2b8a6e; background: #edf8f1; color: #21704f; }
.modal-backdrop { position: fixed; inset: 0; display: grid; place-items: center; padding: 20px; background: rgb(25 49 58 / 35%); z-index: 10; }
.modal { width: min(520px, 100%); max-height: calc(100vh - 40px); overflow-y: auto; display: grid; gap: 14px; padding: 24px; background: #fffaf3; border: 1px solid #cbd8d5; border-radius: 6px; }
.modal label { display: grid; gap: 6px; font: 700 13px 'Roboto', sans-serif; }
input, select { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; font: 14px 'Roboto', sans-serif; }
.modal-actions { justify-content: end; }
.camera-box { display: grid; gap: 10px; }
video, .camera-box img { width: 100%; max-height: 280px; object-fit: cover; background: #19313a; border-radius: 4px; }
.camera-error { margin: 0; padding: 10px; border-left: 3px solid; font-size: 13px; }
@media (max-width: 700px) { .student-layout { display: block; } .attendance-page { padding: 24px 16px; } .page-header { align-items: start; flex-direction: column; } }
</style>
