<script setup>
import { onMounted, onUnmounted, computed, reactive, ref, watch } from 'vue'
import { useManagerClientStore } from '@/stores/managerClientStore'
import { useManagerAppointmentStore } from '@/stores/managerAppointmentStore'
import { useAuthStore } from '@/stores/auth'
import api from '@/plugins/axios'
import Chart from 'chart.js/auto'

const clientStore = useManagerClientStore()
const apptStore = useManagerAppointmentStore()
const auth = useAuthStore()

const completedThisMonth = computed(() =>
  Object.values(apptStore.calendar).reduce((acc, d) => acc + (d.completed ?? 0), 0)
)
const pendingThisMonth = computed(() =>
  Object.values(apptStore.calendar).reduce((acc, d) => acc + (d.pending ?? 0), 0)
)

function formatTime(time) {
  if (!time) return '—'
  const [h, m] = time.split(':')
  const hour = parseInt(h, 10)
  const suffix = hour >= 12 ? 'PM' : 'AM'
  const hour12 = ((hour + 11) % 12) + 1
  return `${String(hour12).padStart(2, '0')}:${m} ${suffix}`
}

function todayKey() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// ── Reloj en tiempo real ─────────────────────────────────
const now = ref(new Date())
let clockInterval = null

const currentDateLabel = computed(() =>
  now.value.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)
const currentTimeLabel = computed(() =>
  now.value.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)

// ── Calendario semanal ──────────────────────────────────
const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']
const weekOffset = ref(0) // 0 = semana actual, -1 = anterior, 1 = siguiente
const weekAppointments = reactive({}) // { 'YYYY-MM-DD': [{ id, client:{name}, ... }] }
const loadingWeek = ref(false)

function pad(n) { return String(n).padStart(2, '0') }
function toKey(d) { return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` }

const weekDates = computed(() => {
  const now = new Date()
  const monday = new Date(now)
  const dayIdx = (now.getDay() + 6) % 7 // lunes = 0
  monday.setDate(now.getDate() - dayIdx + weekOffset.value * 7)
  monday.setHours(0, 0, 0, 0)

  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + i)
    return { key: toKey(d), label: dayNames[i], dayNumber: d.getDate() }
  })
})

async function fetchWeek() {
  loadingWeek.value = true
  await Promise.all(
    weekDates.value.map(async ({ key }) => {
      try {
        const { data } = await api.get('/manager/appointments/day', { params: { date: key } })
        weekAppointments[key] = data.appointments ?? []
      } catch {
        weekAppointments[key] = []
      }
    })
  )
  loadingWeek.value = false
}

function prevWeek() {
  weekOffset.value--
  fetchWeek()
}
function nextWeek() {
  weekOffset.value++
  fetchWeek()
}

// ── Estatus casos (dona) ─────────────────────────────────
const caseStatusTotals = computed(() => {
  const t = { completed: 0, confirmed: 0, pending: 0 }
  Object.values(apptStore.calendar).forEach(d => {
    t.completed += d.completed ?? 0
    t.confirmed += d.confirmed ?? 0
    t.pending += d.pending ?? 0
  })
  return t
})
const caseStatusTotal = computed(() =>
  caseStatusTotals.value.completed + caseStatusTotals.value.confirmed + caseStatusTotals.value.pending
)
function casePct(v) {
  return caseStatusTotal.value ? Math.round((v / caseStatusTotal.value) * 100) : 0
}

const statusCanvas = ref(null)
let statusChart = null

function buildStatusChart() {
  if (!statusCanvas.value) return
  if (statusChart) statusChart.destroy()
  statusChart = new Chart(statusCanvas.value, {
    type: 'doughnut',
    data: {
      labels: ['Cerrados', 'En Espera', 'Pendientes'],
      datasets: [{
        data: [caseStatusTotals.value.completed, caseStatusTotals.value.confirmed, caseStatusTotals.value.pending],
        backgroundColor: ['#1d6fa5', '#e0932c', '#3f9142'],
        borderWidth: 0,
      }],
    },
    options: {
      cutout: '65%',
      plugins: { legend: { display: false } },
    },
  })
}

// ── Nuevos clientes (línea) ───────────────────────────────
const newClientsCanvas = ref(null)
let newClientsChart = null

const newClientsByDay = computed(() => {
  const days = []
  for (let i = 5; i >= 0; i--) {
    const d = new Date()
    d.setDate(d.getDate() - i)
    days.push({ key: toKey(d), label: dayNames[(d.getDay() + 6) % 7].slice(0, 3) })
  }
  return days.map(({ key, label }) => ({
    label,
    count: clientStore.clients.filter(c => (c.created_at ?? '').slice(0, 10) === key).length,
  }))
})

function buildNewClientsChart() {
  if (!newClientsCanvas.value) return
  if (newClientsChart) newClientsChart.destroy()
  newClientsChart = new Chart(newClientsCanvas.value, {
    type: 'line',
    data: {
      labels: newClientsByDay.value.map(d => d.label),
      datasets: [{
        data: newClientsByDay.value.map(d => d.count),
        borderColor: '#3454a5',
        backgroundColor: 'rgba(52,84,165,0.12)',
        tension: 0.35,
        fill: true,
        pointRadius: 3,
      }],
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  })
}

watch(caseStatusTotals, buildStatusChart, { deep: true })
watch(newClientsByDay, buildNewClientsChart, { deep: true })

onMounted(async () => {
  await Promise.all([
    clientStore.fetchClients(),
    apptStore.fetchCalendar(),
  ])
  apptStore.fetchDay(todayKey())
  fetchWeek()

  buildStatusChart()
  buildNewClientsChart()

  clockInterval = setInterval(() => {
    now.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (clockInterval) clearInterval(clockInterval)
  statusChart?.destroy()
  newClientsChart?.destroy()
})
</script>

<template>
  <div class="dashboard">
    <div class="dashboard__header">
      <h1 class="dashboard__title">
        Buenos días, {{ auth.user?.name ?? 'Usuario' }}
      </h1>
      <div class="dashboard__clock">
        <span class="dashboard__clock-date">{{ currentDateLabel }}</span>
        <span class="dashboard__clock-sep">·</span>
        <span class="dashboard__clock-time">{{ currentTimeLabel }}</span>
      </div>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card__top">
          <div>
            <p class="stat-card__value">{{ clientStore.clients.length }}</p>
            <p class="stat-card__label">CLIENTES</p>
          </div>
          <div class="stat-card__icon">
            <i class="fa-solid fa-users"></i>
          </div>
        </div>
        <p class="stat-card__footer">Asignados</p>
      </div>

      <div class="stat-card stat-card--purple">
        <div class="stat-card__top">
          <div>
            <p class="stat-card__value">{{ completedThisMonth }}</p>
            <p class="stat-card__label">COMPLETADAS</p>
          </div>
          <div class="stat-card__icon">
            <i class="fa-solid fa-clipboard-check"></i>
          </div>
        </div>
        <p class="stat-card__footer">Este mes</p>
      </div>

      <div class="stat-card stat-card--yellow">
        <div class="stat-card__top">
          <div>
            <p class="stat-card__value">{{ pendingThisMonth }}</p>
            <p class="stat-card__label">PENDIENTES</p>
          </div>
          <div class="stat-card__icon">
            <i class="fa-regular fa-calendar-days"></i>
          </div>
        </div>
        <p class="stat-card__footer">Este mes</p>
      </div>

      <div class="stat-card stat-card--red">
        <div class="stat-card__top">
          <div>
            <p class="stat-card__value">{{ apptStore.dayAppointments.length }}</p>
            <p class="stat-card__label">CRÍTICAS</p>
          </div>
          <div class="stat-card__icon">
            <i class="fa-solid fa-bullhorn"></i>
          </div>
        </div>
        <p class="stat-card__footer">Vencimiento Hoy</p>
      </div>
    </div>

    <div class="dashboard__row">
      <div class="week-card">
        <div class="week-card__header">
          <h2 class="week-card__title">CALENDARIO SEMANAL</h2>
          <div class="week-card__nav">
            <button class="week-card__nav-btn" @click="prevWeek">‹</button>
            <span class="week-card__nav-label">Semana actual</span>
            <button class="week-card__nav-btn" @click="nextWeek">›</button>
          </div>
        </div>

        <div v-if="loadingWeek" class="week-card__loading">Cargando…</div>

        <div v-else class="week-grid">
          <div v-for="day in weekDates" :key="day.key" class="week-grid__col">
            <div class="week-grid__col-header">
              <span>{{ day.label }}</span>
              <span class="week-grid__col-date">{{ day.dayNumber }}</span>
            </div>
            <div class="week-grid__col-body">
              <div
                v-for="appt in weekAppointments[day.key]"
                :key="appt.id"
                :class="['week-chip', `week-chip--${appt.status}`]"
              >
                <span class="week-chip__time">{{ formatTime(appt.start_time) }}</span>
                <span class="week-chip__name">{{ appt.client?.name ?? '—' }}</span>
              </div>
              <p v-if="!weekAppointments[day.key]?.length" class="week-grid__empty">—</p>
            </div>
          </div>
        </div>
      </div>

      <div class="agenda-card">
        <h2 class="agenda-card__title">AGENDA HOY</h2>

        <div v-if="apptStore.loadingDay" class="week-card__loading">Cargando…</div>

        <div v-else class="agenda-table-wrap">
          <table class="agenda-table">
            <thead>
              <tr>
                <th>Hora</th>
                <th>Cliente</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="appt in apptStore.dayAppointments" :key="appt.id">
                <td>{{ formatTime(appt.start_time) }}</td>
                <td>{{ appt.client?.name ?? '—' }}</td>
                <td class="agenda-table__actions">
                  <button class="agenda-table__menu-btn" type="button">⋯</button>
                </td>
              </tr>
              <tr v-if="!apptStore.dayAppointments.length">
                <td colspan="3" class="agenda-table__empty">Sin citas para hoy</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="charts-row">
      <div class="chart-card">
        <h2 class="chart-card__title">ESTATUS CASOS</h2>
        <div v-if="caseStatusTotal" class="chart-card__donut">
          <div class="chart-card__canvas-wrap">
            <canvas ref="statusCanvas"></canvas>
          </div>
          <ul class="chart-legend">
            <li class="chart-legend__item">
              <span class="chart-legend__dot" style="background:#1d6fa5"></span>
              {{ casePct(caseStatusTotals.completed) }}% Cerrados
            </li>
            <li class="chart-legend__item">
              <span class="chart-legend__dot" style="background:#e0932c"></span>
              {{ casePct(caseStatusTotals.confirmed) }}% En Espera
            </li>
            <li class="chart-legend__item">
              <span class="chart-legend__dot" style="background:#3f9142"></span>
              {{ casePct(caseStatusTotals.pending) }}% Pendientes
            </li>
          </ul>
        </div>
        <p v-else class="chart-card__empty">Sin citas este mes</p>
      </div>

      <div class="chart-card">
        <h2 class="chart-card__title">NUEVOS CLIENTES</h2>
        <div class="chart-card__line">
          <canvas ref="newClientsCanvas"></canvas>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard {
  min-height: 100%;
  padding: 18px;
  background: linear-gradient(to bottom, #0c1f3d, #0a1830);
}

.dashboard__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 24px;
}

.dashboard__title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  font-style: italic;
  color: #ffffff;
}

.dashboard__clock {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.dashboard__clock-date {
  font-size: 14px;
  font-weight: 500;
  color: #cbd5e1;
  text-transform: capitalize;
}

.dashboard__clock-sep {
  color: #64748b;
}

.dashboard__clock-time {
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  font-variant-numeric: tabular-nums;
}

.stats-row {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.stat-card {
  flex: 1 1 220px;
  min-width: 220px;
  padding: 24px;
  border-radius: 12px;
  background: linear-gradient(to bottom right, #059669, #10b981);
  color: #ffffff;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
}

.stat-card--purple {
  background: linear-gradient(to bottom right, #7c3aed, #8b5cf6);
}

.stat-card--yellow {
  background: linear-gradient(to bottom right, #d97706, #f59e0b);
}

.stat-card--red {
  background: linear-gradient(to bottom right, #b91c1c, #ef4444);
}

.stat-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 32px;
}

.stat-card__value {
  margin: 0;
  font-size: 50px;
  font-weight: 800;
  line-height: 1;
}

.stat-card__label {
  margin: 4px 0 0;
  font-size: 30px;
  font-weight: 900;
  letter-spacing: 0.05em;
}

.stat-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  font-size: 40px;
}

.stat-card__footer {
  margin: 16px 0 0;
  padding: 4px 8px;
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.15);
  font-size: 12px;
  font-weight: 500;
  text-align: center;
}

.dashboard__row {
  margin-top: 24px;
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.dashboard__row .week-card {
  flex: 3 1 500px;
  margin-top: 0;
}

.agenda-card {
  flex: 1 1 220px;
  max-width: 320px;
  padding: 14px;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
}

.agenda-card__title {
  margin: 0 0 12px;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #1e293b;
}

.agenda-table-wrap {
  max-height: 248px;
  overflow-y: auto;
  scrollbar-width: thin;
}

.agenda-table-wrap::-webkit-scrollbar {
  width: 5px;
}

.agenda-table-wrap::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.agenda-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 11px;
}

.agenda-table thead th {
  position: sticky;
  top: 0;
  padding: 6px 8px;
  background: #eef2ff;
  color: #475569;
  font-weight: 700;
  text-align: left;
}

.agenda-table thead th:first-child {
  border-radius: 6px 0 0 6px;
}

.agenda-table thead th:last-child {
  border-radius: 0 6px 6px 0;
}

.agenda-table tbody td {
  padding: 7px 8px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.agenda-table tbody tr:last-child td {
  border-bottom: none;
}

.agenda-table__actions {
  width: 26px;
  text-align: right;
}

.agenda-table__menu-btn {
  width: 20px;
  height: 20px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #ffffff;
  color: #64748b;
  font-size: 10px;
  line-height: 1;
  cursor: pointer;
}

.agenda-table__menu-btn:hover {
  background: #f8fafc;
}

.agenda-table__empty {
  padding: 20px 10px;
  text-align: center;
  color: #94a3b8;
}

.charts-row {
  margin-top: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.chart-card {
  flex: 1 1 320px;
  padding: 10px 10px 0px 20px;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
}

.chart-card__title {
  margin: 0 0 10px;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.03em;
  color: #1e293b;
}

.chart-card__donut {
  display: flex;
  align-items: center;
  gap: 16px;
}

.chart-card__canvas-wrap {
  position: relative;
  width: 110px;
  height: 110px;
  flex-shrink: 0;
}

.chart-legend {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.chart-legend__item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}

.chart-legend__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.chart-card__line {
  position: relative;
  height: 140px;
}

.chart-card__empty {
  padding: 20px 0;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}

.week-card {
  padding: 14px;
  background: linear-gradient(to bottom, #0c1f3d, #0a1830);
  border: 2px solid #e7e7e7;
  border-radius: 12px;
}

.week-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.week-card__title {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #ffffff;
}

.week-card__nav {
  display: flex;
  align-items: center;
  gap: 8px;
}

.week-card__nav-btn {
  width: 28px;
  height: 28px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #ffffff;
  color: #000000;
  cursor: pointer;
}

.week-card__nav-btn:hover {
  background: #f8fafc;
}

.week-card__nav-label {
  padding: 4px 12px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #000000;
  font-size: 13px;
  font-weight: 500;
}

.week-card__loading {
  padding: 24px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
}

.week-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 8px;
}

.week-grid__col {
  min-height: 140px;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  overflow: hidden;
}

.week-grid__col-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 4px;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.week-grid__col-date {
  font-size: 11px;
  font-weight: 500;
  color: #000000;
}

.week-grid__col-body {
  padding: 6px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-sizing: border-box;
  max-height: 165px;
  overflow-y: auto;
  scrollbar-width: thin;
}

.week-grid__col-body::-webkit-scrollbar {
  width: 5px;
}

.week-grid__col-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.week-chip {
  padding: 6px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.3;
  word-break: break-word;
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex-shrink: 0;
}

.week-chip__time {
  font-size: 10px;
  font-weight: 700;
  opacity: 0.85;
}

.week-chip__name {
  font-size: 12px;
  font-weight: 600;
}

.week-chip--pending {
  background: #fef3c7;
  color: #92400e;
}

.week-chip--confirmed {
  background: #e0f2fe;
  color: #0369a1;
}

.week-chip--completed {
  background: #d1fae5;
  color: #065f46;
}

.week-chip--cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.week-grid__empty {
  margin: 4px 0 0;
  text-align: center;
  font-size: 12px;
  color: #cbd5e1;
}

@media (max-width: 600px) {
  .dashboard {
    padding: 16px;
  }

  .dashboard__header {
    flex-direction: column;
    margin-bottom: 16px;
  }

  .dashboard__title {
    font-size: 20px;
  }

  .stats-row {
    flex-direction: column;
    gap: 12px;
  }

  .stat-card {
    flex: 1 1 auto;
    min-width: 0;
    width: 100%;
    padding: 18px;
  }

  .stat-card__top {
    gap: 16px;
  }

  .stat-card__value {
    font-size: 36px;
  }

  .stat-card__label {
    font-size: 20px;
  }

  .stat-card__icon {
    width: 56px;
    height: 56px;
    font-size: 26px;
  }

  .week-card {
    padding: 14px;
    background: #0c3661;
  }

  .dashboard__row {
    flex-direction: column;
    margin-top: 16px;
    gap: 16px;
  }

  .dashboard__row .week-card,
  .agenda-card {
    flex: 1 1 auto;
    width: 100%;
  }

  .agenda-card {
    padding: 14px;
  }

  .week-grid {
    grid-template-columns: 1fr;
  }

  .week-grid__col {
    min-height: 0;
  }

  .week-grid__col-header {
    flex-direction: row;
    justify-content: space-between;
    padding: 8px 12px;
  }

  .charts-row {
    flex-direction: column;
    margin-top: 16px;
  }

  .chart-card {
    padding: 14px;
  }

  .chart-card__donut {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
}
</style>