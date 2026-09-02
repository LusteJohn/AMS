<script setup>
import { onMounted, ref } from 'vue'

import api from './api/axios'

const checks = ref([
  {
    name: 'API and database',
    path: '/api/health',
    status: 'pending',
    message: 'Not checked yet',
    duration: null,
  },
])

const isChecking = ref(false)
const lastChecked = ref(null)

function statusLabel(status) {
  return {
    pending: 'Pending',
    checking: 'Checking',
    success: 'Connected',
    error: 'Failed',
  }[status]
}

async function checkEndpoint(check) {
  check.status = 'checking'
  check.message = 'Request in progress'
  const startedAt = performance.now()

  try {
    const response = await api.get(check.path)
    check.status = response.data?.success ? 'success' : 'error'
    check.message = response.data?.message || `HTTP ${response.status}`
  } catch (error) {
    check.status = 'error'
    check.message = error.response?.data?.message || error.message || 'Request failed'
  } finally {
    check.duration = `${Math.round(performance.now() - startedAt)} ms`
  }
}

async function checkAll() {
  isChecking.value = true
  await Promise.all(checks.value.map(checkEndpoint))
  lastChecked.value = new Date().toLocaleString()
  isChecking.value = false
}

onMounted(checkAll)
</script>

<template>
  <main class="connection-page">
    <section class="connection-header">
      <div>
        <p class="eyebrow">System diagnostics</p>
        <h1>Frontend connection</h1>
        <p class="description">Check the API and database connection from the browser.</p>
      </div>
      <button type="button" class="check-button" :disabled="isChecking" @click="checkAll">
        {{ isChecking ? 'Checking...' : 'Check again' }}
      </button>
    </section>

    <section class="checks" aria-live="polite">
      <article v-for="check in checks" :key="check.path" class="check-row">
        <div class="status-dot" :class="check.status" aria-hidden="true"></div>
        <div class="check-details">
          <h2>{{ check.name }}</h2>
          <p>{{ check.path }} <span v-if="check.duration">- {{ check.duration }}</span></p>
        </div>
        <div class="check-result" :class="check.status">
          <strong>{{ statusLabel(check.status) }}</strong>
          <span>{{ check.message }}</span>
        </div>
      </article>
    </section>

    <p v-if="lastChecked" class="last-checked">Last checked {{ lastChecked }}</p>
  </main>
</template>

<style scoped>
.connection-page {
  max-width: 760px;
  margin: 0 auto;
  padding: 64px 24px;
  color: #19313a;
  font-family: Georgia, 'Times New Roman', serif;
}
.connection-header {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 24px;
  padding-bottom: 32px;
  border-bottom: 1px solid #cbd8d5;
}
.eyebrow {
  margin: 0 0 10px;
  color: #b04a32;
  font: 700 12px/1.2 Arial, sans-serif;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
h1, h2, p { margin-top: 0; }
h1 { margin-bottom: 10px; font-size: clamp(32px, 6vw, 52px); font-weight: 500; }
.description { margin-bottom: 0; color: #557078; font: 15px/1.5 Arial, sans-serif; }
.check-button {
  border: 0;
  border-radius: 4px;
  padding: 12px 16px;
  background: #d96b45;
  color: #fffaf3;
  cursor: pointer;
  font: 700 13px Arial, sans-serif;
  white-space: nowrap;
}
.check-button:disabled { cursor: wait; opacity: 0.65; }
.checks { padding-top: 20px; }
.check-row {
  display: grid;
  grid-template-columns: 12px minmax(0, 1fr) auto;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
  border-bottom: 1px solid #e0e8e5;
}
.status-dot { width: 10px; height: 10px; border-radius: 50%; background: #a9bbb8; }
.status-dot.checking { background: #d96b45; }
.status-dot.success { background: #2b8a6e; }
.status-dot.error { background: #b83b3b; }
h2 { margin-bottom: 5px; font-size: 19px; font-weight: 600; }
.check-details p, .check-result span, .last-checked { margin-bottom: 0; color: #668087; font: 12px/1.4 Arial, sans-serif; }
.check-result { text-align: right; }
.check-result strong { display: block; margin-bottom: 4px; font: 700 12px Arial, sans-serif; }
.check-result.success strong { color: #2b8a6e; }
.check-result.error strong { color: #b83b3b; }
.last-checked { margin-top: 20px; }
@media (max-width: 560px) {
  .connection-page { padding: 36px 18px; }
  .connection-header { align-items: start; flex-direction: column; }
  .check-row { grid-template-columns: 12px minmax(0, 1fr); }
  .check-result { grid-column: 2; text-align: left; }
}
</style>
