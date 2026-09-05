<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import AdminSideBar from './components/AdminSideBar.vue'
import '../assets/admin/StudentAttendanceHistory.css'

const records = ref([])
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
    hour: 'numeric', minute: '2-digit', hour12: true,
  }).format(date)
}

async function loadHistory() {
  isLoading.value = true
  try {
    const attendanceResponse = await api.get('/api/attendance')
    const attendance = Array.isArray(attendanceResponse.data?.data) ? attendanceResponse.data.data : []
    records.value = await Promise.all(attendance.map(async (record) => {
      const response = await api.get('/api/attendance-logs', {
        params: { attendance_id: record.attendance_id },
      })
      return {
        ...record,
        logs: Array.isArray(response.data?.data) ? response.data.data : [],
      }
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
  <div class="admin-layout">
    <AdminSideBar />
    <main class="history-page">
      <header class="page-header">
        <div><p class="eyebrow">Administration</p><h1>Student attendance history</h1><p>Review all student attendance logs by company and date.</p></div>
        <button type="button" :disabled="isLoading" @click="loadHistory">Refresh</button>
      </header>

      <section class="filters" aria-label="Attendance history filters">
        <label>Attendance date<select v-model="filters.attendance_date"><option value="">All dates</option><option v-for="date in availableDates" :key="date" :value="date">{{ date }}</option></select></label>
        <button type="button" :disabled="!filters.attendance_date" @click="filters.attendance_date = ''">Clear filter</button>
      </section>

      <p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p>
      <p v-if="isLoading">Loading attendance history...</p>
      <p v-else-if="!groupedRecords.length" class="empty-state">No attendance history matches the selected date.</p>

      <section v-for="company in groupedRecords" :key="company.company_id" class="company-container">
        <h2>{{ company.company_name }}</h2>
        <div v-for="record in company.dates" :key="record.attendance_id" class="date-container">
          <div class="date-heading">
            <div><span class="label">Attendance date</span><strong>{{ record.attendance_date }}</strong><span class="student-name">{{ record.firstname }} {{ record.middlename }} {{ record.lastname }} · {{ record.school_id || 'No school ID' }}</span></div>
            <div class="summary"><span>{{ record.total_hours }} hours</span><span>{{ record.status }}</span></div>
          </div>
          <table>
            <thead><tr><th>Attendance type</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="log in record.logs" :key="log.attendance_log_id"><td>{{ log.attendance_type }}</td><td>{{ formatTime12(log.attendance_time) }}</td><td>{{ log.status }}</td></tr>
              <tr v-if="!record.logs.length"><td colspan="3">No attendance logs recorded.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; color: #19313a; }
.history-page { flex: 1; max-width: 1200px; padding: 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 28px; }
.eyebrow { margin: 0 0 8px; color: #b04a32; font: 700 12px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
h1, h2 { margin: 0; }
.page-header p:last-child { color: #5b747b; }
button { border: 1px solid #b9cbc6; border-radius: 4px; padding: 8px 12px; background: #fffaf3; color: #19313a; cursor: pointer; font-family: 'Roboto', sans-serif; }
button:hover { border-color: #d96b45; }
button:disabled { cursor: wait; opacity: .6; }
.filters { display: flex; align-items: end; gap: 12px; margin-bottom: 24px; padding: 16px; background: #edf3f0; border: 1px solid #dce6e3; }
.filters label { display: grid; gap: 6px; min-width: 220px; font: 700 13px 'Roboto', sans-serif; }
select { box-sizing: border-box; width: 100%; padding: 10px; border: 1px solid #aebfbc; border-radius: 3px; background: #fffaf3; font: 14px 'Roboto', sans-serif; }
.company-container { margin-bottom: 28px; padding: 22px; border: 1px solid #cbd8d5; border-radius: 6px; background: #edf3f0; }
.company-container h2 { margin-bottom: 18px; font-size: 21px; }
.date-container { margin-top: 14px; padding: 18px; border: 1px solid #dce6e3; border-radius: 4px; background: #fffaf3; }
.date-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 14px; }
.date-heading div:first-child { display: grid; gap: 4px; }
.label { color: #b04a32; font: 700 11px 'Roboto', sans-serif; text-transform: uppercase; letter-spacing: .08em; }
.student-name { color: #5b747b; font-size: 13px; }
.summary { display: flex; gap: 8px; color: #5b747b; font-size: 13px; text-transform: capitalize; }
.summary span { padding: 5px 8px; border: 1px solid #cbd8d5; border-radius: 3px; }
table { width: 100%; border-collapse: collapse; font: 14px 'Roboto', sans-serif; }
th, td { padding: 10px; border-bottom: 1px solid #dce6e3; text-align: left; vertical-align: top; }
th { background: #edf3f0; }
.empty-state, .message { padding: 14px 16px; background: #edf3f0; border-left: 4px solid #b04a32; }
.error { border-color: #b83b3b; background: #fff0ed; color: #a33b2e; }
@media (max-width: 700px) { .admin-layout { display: block; } .history-page { padding: 24px 16px; } .page-header, .date-heading, .filters { align-items: start; flex-direction: column; } }
</style>
