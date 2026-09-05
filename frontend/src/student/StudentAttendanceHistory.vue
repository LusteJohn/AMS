<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import StudentSideBar from './components/StudentSideBar.vue'
import '../assets/student/StudentAttendanceHistory.css'

const records = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

const groupedRecords = computed(() => {
  const companies = new Map()
  for (const record of records.value) {
    const companyKey = String(record.company_id)
    if (!companies.has(companyKey)) {
      companies.set(companyKey, {
        company_id: record.company_id,
        company_name: record.company_name,
        dates: [],
      })
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
  const date = new Date(2000, 0, 1, hours, minutes)
  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  }).format(date)
}

async function loadHistory() {
  isLoading.value = true
  try {
    const attendanceResponse = await api.get('/api/attendance')
    const attendance = Array.isArray(attendanceResponse.data?.data) ? attendanceResponse.data.data : []
    const recordsWithLogs = await Promise.all(attendance.map(async (record) => {
      const response = await api.get('/api/attendance-logs', {
        params: { attendance_id: record.attendance_id },
      })
      return {
        ...record,
        logs: Array.isArray(response.data?.data) ? response.data.data : [],
      }
    }))
    records.value = recordsWithLogs
  } catch (error) {
    errorMessage.value = apiError(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(loadHistory)
</script>

<template>
  <div class="student-layout">
    <StudentSideBar />
    <main class="history-page">
      <header class="page-header">
        <div>
          <p class="eyebrow">Student placement</p>
          <h1>Attendance history</h1>
          <p>Review your attendance logs by company and attendance date.</p>
        </div>
        <button type="button" :disabled="isLoading" @click="loadHistory">Refresh</button>
      </header>

      <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
      <p v-if="isLoading">Loading attendance history...</p>
      <p v-else-if="!groupedRecords.length" class="empty-state">No attendance history found.</p>

      <section v-for="company in groupedRecords" :key="company.company_id" class="company-container">
        <h2>{{ company.company_name }}</h2>
        <div v-for="record in company.dates" :key="record.attendance_id" class="date-container">
          <div class="date-heading">
            <div><span class="label">Attendance date</span><strong>{{ record.attendance_date }}</strong></div>
            <div class="summary"><span>{{ record.total_hours }} hours</span><span>{{ record.status }}</span></div>
          </div>
          <table>
            <thead><tr><th>Attendance type</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="log in record.logs" :key="log.attendance_log_id">
                <td>{{ log.attendance_type }}</td>
                <td>{{ formatTime12(log.attendance_time) }}</td>
                <td>{{ log.status }}</td>
              </tr>
              <tr v-if="!record.logs.length"><td colspan="3">No attendance logs recorded.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.student-layout { display: flex; min-height: 100vh; color: #19313a; }
.history-page { flex: 1; max-width: 1100px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 32px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
.company-container { margin-bottom: 28px; padding: 22px; border: 1px solid #cbd8d5; border-radius: 6px; background: #edf3f0; }
.company-container h2 { margin-bottom: 18px; font-size: 21px; }
.date-container { margin-top: 14px; padding: 18px; border: 1px solid #dce6e3; border-radius: 4px; background: #fffaf3; }
.date-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 14px; }
.date-heading div:first-child { display: grid; gap: 4px; }
.label { color: #b04a32; font: 700 11px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
.summary { display: flex; gap: 8px; color: #5b747b; font-size: 13px; text-transform: capitalize; }
.summary span { padding: 5px 8px; border: 1px solid #cbd8d5; border-radius: 3px; }
table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
th, td { padding: 10px; border-bottom: 1px solid #dce6e3; text-align: left; vertical-align: top; }
th { background: #edf3f0; }
.empty-state, .message { padding: 14px 16px; background: #edf3f0; border-left: 4px solid #b04a32; }
.error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
@media (max-width: 700px) { .student-layout { display: block; } .history-page { padding: 24px 16px; } .page-header, .date-heading { align-items: start; flex-direction: column; } }
</style>
