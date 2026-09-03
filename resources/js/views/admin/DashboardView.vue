<script setup>
import { ref, computed, onMounted, onUnmounted, onActivated, onDeactivated } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/plugins/axios'
import { useAuthStore } from '@/stores/auth'

const { t, locale } = useI18n()

// ─────────────────────────────────────────────────────────
// Estado
// ─────────────────────────────────────────────────────────
const authStore = useAuthStore()

const isLoading = ref(true)
const loadError = ref(null)

const kpis = ref([])
const days = ref([])
const topManagers = ref([])
const donutChart = ref({ primary: 0, legend: [] })
const barsChart = ref({ total: 0, items: [] })

const userName = ref(authStore.user?.name || '')

const kpiDictionary = {
   // KPIs de Casos y Clientes
    'nuevos clientes': 'dashboard.kpis.new_clients',
    'citas para hoy': 'dashboard.kpis.appointments_today',
    'total de casos completados': 'dashboard.kpis.completed_cases',
    'total casos completados': 'dashboard.kpis.completed_cases',
    'casos completados': 'dashboard.kpis.completed_cases',
    'total de casos pendientes': 'dashboard.kpis.pending_cases',
    'total casos pendientes': 'dashboard.kpis.pending_cases',
    'casos pendientes': 'dashboard.kpis.pending_cases',
    'total pendientes': 'dashboard.kpis.pending_cases',

    // Estados
    'confirmados': 'dashboard.status.confirmed',
    'confirmadas': 'dashboard.status.confirmed',
    'confirmado': 'dashboard.status.confirmed',
    'en progreso': 'dashboard.status.in_progress',
    'programadas': 'dashboard.status.scheduled',
    'programados': 'dashboard.status.scheduled',

    // Periodos de tiempo / Footers
    'este mes': 'dashboard.time.this_month'
}

// ─────────────────────────────────────────────────────────
// Reloj en vivo (reactivo al idioma seleccionado)
// ─────────────────────────────────────────────────────────
const now = ref(new Date())
let clockInterval = null

const weekdayFormatter = computed(() => new Intl.DateTimeFormat(locale.value, { weekday: 'long' }))
const dateFormatter = computed(() => new Intl.DateTimeFormat(locale.value, { month: 'long', day: 'numeric', year: 'numeric' }))

function formatClockDate(d) {
    return `${weekdayFormatter.value.format(d)}, ${dateFormatter.value.format(d)}`
}

function formatClockTime(d) {
    return d.toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true })
}

// Helper corregido para traducir claves o textos de las KPI
function translateKpiText(textOrKey) {
    if (!textOrKey) return ''
    
    // Si viene una clave explícita de i18n
    if (t(textOrKey) !== textOrKey) return t(textOrKey)

    // Si viene texto en español desde Laravel, se busca en el diccionario
    const normalized = String(textOrKey).trim().toLowerCase()
    const translationKey = kpiDictionary[normalized]

    return translationKey ? t(translationKey) : textOrKey
}

// ─────────────────────────────────────────────────────────
// Fetch de datos
// ─────────────────────────────────────────────────────────
async function fetchDashboardStats({ silent = false } = {}) {
    if (!silent) isLoading.value = true
    loadError.value = null

    try {
        const { data } = await api.get('/admin/dashboard/stats')

        kpis.value = data.kpis ?? []
        days.value = data.week ?? []
        topManagers.value = data.top_managers ?? []
        donutChart.value = data.charts?.donut ?? { primary: 0, legend: [] }
        barsChart.value = data.charts?.bars ?? { total: 0, items: [] }
    } catch (err) {
        console.error('Error cargando el dashboard:', err)
        if (!silent) loadError.value = t('dashboard.error_loading')
    } finally {
        if (!silent) isLoading.value = false
    }
}

const POLL_INTERVAL_MS = 15_000
let pollInterval = null

function startPolling() {
    stopPolling()
    pollInterval = setInterval(() => {
        fetchDashboardStats({ silent: true })
    }, POLL_INTERVAL_MS)
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
    }
}

function handleVisibilityChange() {
    if (document.visibilityState === 'visible') {
        fetchDashboardStats({ silent: true })
    }
}

onMounted(() => {
    if (!authStore.user) {
        authStore.fetchMe().then(() => {
            userName.value = authStore.user?.name || ''
        })
    }

    fetchDashboardStats()
    startPolling()
    document.addEventListener('visibilitychange', handleVisibilityChange)

    now.value = new Date()
    clockInterval = setInterval(() => {
        now.value = new Date()
    }, 1000)
})

onActivated(() => {
    fetchDashboardStats({ silent: true })
    startPolling()
})

onDeactivated(() => {
    stopPolling()
})

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval)
    stopPolling()
    document.removeEventListener('visibilitychange', handleVisibilityChange)
})
</script>

<template>
    <div class="page">
        <div class="content dashboard">
            <!-- Header con Reloj / Saludo -->
            <div class="dashboard__header">
                <h1 class="dashboard__title">
                    {{ t('dashboard.greeting', { name: userName ? `, ${userName}` : '' }) }}
                </h1>
                <div class="dashboard__clock">
                    <span class="dashboard__clock-date">{{ formatClockDate(now) }}</span>
                    <span class="dashboard__clock-sep">-</span>
                    <span class="dashboard__clock-time">{{ formatClockTime(now) }}</span>
                </div>
            </div>

            <div v-if="loadError" class="dashboard__error">
                {{ loadError }}
            </div>

            <!-- Fila Superior de Tarjetas KPI -->
            <div class="stats-row">
                <template v-if="isLoading">
                    <div v-for="n in 4" :key="n" class="stat-card stat-card--skeleton"></div>
                </template>
                <div
                    v-else
                    v-for="(kpi, index) in kpis"
                    :key="index"
                    :class="['stat-card', 'stat-card--clickable', kpi.class]"
                >
                    <div class="stat-card__top">
                        <div>
                            <p class="stat-card__value">{{ kpi.value }}</p>
                            <h3 class="stat-card__label">
                                {{ translateKpiText(kpi.title_key || kpi.title) }}
                            </h3>
                        </div>
                        <div class="stat-card__icon">
                            <i :class="['fa-solid', kpi.icon]"></i>
                        </div>
                    </div>
                    <div class="stat-card__footer">
                        {{ translateKpiText(kpi.footer_key || kpi.footer) }}
                    </div>
                </div>
            </div>

            <!-- Fila Media: Calendario Semanal + Top 3 Case Managers -->
            <div class="dashboard__row">
                <!-- Calendario Semanal -->
                <div class="week-card">
                    <div class="week-card__header">
                        <h2 class="week-card__title">{{ t('dashboard.weekly_calendar') }}</h2>
                        <div class="week-card__nav">
                            <span class="week-card__nav-label">{{ t('dashboard.current_week') }}</span>
                        </div>
                    </div>
                    <div class="week-grid">
                        <div v-for="day in days" :key="day.date" class="week-grid__col">
                            <div :class="['week-grid__col-header', { 'week-grid__col-header--today': day.isToday }]">
                                <span>{{ day.name }}</span>
                                <span class="week-grid__col-date">{{ day.date }}</span>
                            </div>
                            <div class="week-grid__col-body">
                                <div
                                    v-for="(evt, idx) in day.events"
                                    :key="idx"
                                    :class="['week-chip', evt.type]"
                                >
                                    <span class="week-chip__time">{{ evt.time }}</span>
                                    <span class="week-chip__name">{{ evt.name }}</span>
                                </div>
                                <div v-if="!isLoading && (!day.events || day.events.length === 0)" class="week-grid__empty">
                                    —
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Lateral Top 3 Case Managers -->
                <div class="agenda-card">
                    <div class="top-managers__header">
                        <h2 class="agenda-card__title">{{ t('dashboard.top_managers') }}</h2>
                    </div>
                    <div class="managers-list">
                        <div v-if="!isLoading && topManagers.length === 0" class="managers-list__empty">
                            {{ t('dashboard.no_data') }}
                        </div>
                        <div v-for="manager in topManagers" :key="manager.rank" class="manager-item">
                            <div class="manager-item__info">
                                <span :class="['manager-item__rank', manager.badgeClass]">{{ manager.rank }}</span>
                                <img :src="manager.photo" class="manager-item__avatar" />
                                <span class="manager-item__name">{{ manager.name }}</span>
                            </div>
                            <div class="manager-item__stat">
                                <span class="manager-item__stat-value">{{ manager.completed }}</span>
                                <span class="manager-item__stat-label">{{ t('dashboard.cases') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila Inferior: Gráficos de Resumen -->
            <div class="charts-row">
                <!-- Total Casos Completados -->
                <div class="chart-card">
                    <h3 class="chart-card__title">{{ t('dashboard.completed_cases_title') }}</h3>
                    <div class="chart-card__donut">
                        <div class="donut-display">
                            <span class="donut-display__number">{{ donutChart.primary ?? 0 }}</span>
                            <span class="donut-display__label">{{ t('dashboard.completed_cases') }}</span>
                        </div>
                        <ul class="chart-legend">
                            <li
                                v-for="(item, idx) in donutChart.legend"
                                :key="idx"
                                class="chart-legend__item"
                            >
                                <span class="chart-legend__dot" :style="{ background: item.color }"></span>
                                {{ item.value }} {{ translateKpiText(item.label) }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Total Casos Pendientes -->
                <div class="chart-card">
                    <h3 class="chart-card__title">{{ t('dashboard.pending_cases_title') }}</h3>
                    <div class="bar-chart-preview">
                        <span class="bar-chart-preview__value">{{ barsChart.total ?? 0 }}</span>
                        <span class="bar-chart-preview__label">{{ t('dashboard.pending_cases') }}</span>
                        <div class="bar-chart-bars">
                            <div
                                v-for="(bar, idx) in barsChart.items"
                                :key="idx"
                                class="bar-col"
                                :style="{ height: bar.heightPct + '%', background: bar.color }"
                                :title="`${translateKpiText(bar.label)}: ${bar.value}`"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Top Case Manager del Mes -->
                <div class="chart-card top-manager-highlight">
                    <h3 class="chart-card__title">{{ t('dashboard.top_manager') }}</h3>
                    <div v-if="topManagers.length" class="top-manager-profile">
                        <img :src="topManagers[0].photo" class="top-manager-profile__img" />
                        <h4 class="top-manager-profile__name">{{ topManagers[0].name }}</h4>
                        <div class="top-manager-profile__award">
                            <i class="fa-solid fa-award"></i>
                        </div>
                    </div>
                    <div v-else class="top-manager-profile__empty">
                        {{ t('dashboard.no_data') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page {
    display: grid;
    grid-template-rows: auto 1fr;
    height: 100%;
    overflow: hidden;
}

.content {
    overflow-y: auto;
}

.dashboard {
  min-height: 100%;
  padding: 16px 20px;
  background: linear-gradient(to bottom, #0c1f3d, #0a1830);
}

.dashboard__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}

.dashboard__title {
  margin: 0;
  font-size: 26px;
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
  font-size: 18px;
  font-weight: 500;
  color: #cbd5e1;
}

.dashboard__clock-sep {
  color: #64748b;
}

.dashboard__clock-time {
  font-size: 18px;
  font-weight: 700;
  color: #ffffff;
  font-variant-numeric: tabular-nums;
}

.dashboard__error {
  margin-bottom: 16px;
  padding: 10px 14px;
  border-radius: 8px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fecaca;
  font-size: 13px;
}

.stats-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.stat-card {
  flex: 1 1 200px;
  min-width: 200px;
  padding: 18px;
  border-radius: 12px;
  background: linear-gradient(to bottom right, #0284c7, #0369a1);
  color: #ffffff;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
}

.stat-card--skeleton {
  min-height: 108px;
  background: rgba(255, 255, 255, 0.08);
  animation: pulse 1.4s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}

.stat-card--clickable {
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.2s;
}

.stat-card--clickable:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 24px rgba(0, 0, 0, 0.3);
}

.stat-card--yellow {
  background: linear-gradient(to bottom right, #d97706, #f59e0b);
}

.stat-card--purple {
  background: linear-gradient(to bottom right, #ea580c, #f97316);
}

.stat-card--red {
  background: linear-gradient(to bottom right, #b91c1c, #ef4444);
}

.stat-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.stat-card__value {
  margin: 0;
  font-size: 38px;
  font-weight: 800;
  line-height: 1;
}

.stat-card__label {
  margin: 6px 0 0;
  font-size: 13px;
  font-weight: 900;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.stat-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.18);
  font-size: 22px;
  flex-shrink: 0;
}

.stat-card__footer {
  margin: 14px 0 0;
  padding: 4px 8px;
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.15);
  font-size: 11px;
  font-weight: 500;
  text-align: center;
}

.dashboard__row {
  margin-top: 20px;
  display: flex;
  align-items: stretch;
  gap: 16px;
}

.dashboard__row .week-card {
  flex: 3 1 500px;
}

.agenda-card {
  flex: 1 1 240px;
  max-width: 320px;
  padding: 14px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.10);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
}

.top-managers__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.agenda-card__title {
  margin: 0;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #ffffff;
}

.managers-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}

.managers-list__empty,
.week-grid__empty,
.top-manager-profile__empty {
  color: #94a3b8;
  font-size: 12px;
  text-align: center;
}

.manager-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.manager-item__info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.manager-item__avatar {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  object-fit: cover;
}

.manager-item__name {
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.manager-item__rank {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: #ffffff;
}

.manager-item__stat {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  line-height: 1.1;
}

.manager-item__stat-value {
  font-size: 15px;
  font-weight: 800;
  color: #ffffff;
}

.manager-item__stat-label {
  font-size: 9px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #94a3b8;
}

.rank-badge--1 { background: #f59e0b; }
.rank-badge--2 { background: #64748b; }
.rank-badge--3 { background: #b45309; }

.week-card {
  padding: 14px;
  background: rgba(255, 255, 255, 0.10);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
}

.week-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.week-card__title {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #ffffff;
}

.week-card__nav-label {
  padding: 4px 10px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.12);
  color: #f1f5f9;
  font-size: 12px;
}

.week-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 8px;
}

.week-grid__col {
  min-height: 252px;
  display: flex;
  flex-direction: column;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  overflow: hidden;
  background: rgba(0, 0, 0, 0.15);
}

.week-grid__col-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-height: 52px;
  padding: 10px 4px;
  background: rgba(255, 255, 255, 0.10);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 11px;
  font-weight: 700;
  color: #f1f5f9;
}

.week-grid__col-header--today {
  background: rgba(16, 185, 129, 0.35);
}

.week-grid__col-body {
  padding: 6px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 190px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.35) transparent;
}

.week-grid__col-body::-webkit-scrollbar {
  width: 4px;
}

.week-grid__col-body::-webkit-scrollbar-track {
  background: transparent;
}

.week-grid__col-body::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.35);
  border-radius: 4px;
}

.week-chip {
  padding: 5px 6px;
  border-radius: 6px;
  font-size: 11px;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.week-chip__time {
  font-size: 9px;
  font-weight: 800;
  opacity: 0.9;
}

.week-chip__name {
  font-weight: 600;
}

.week-chip--pending {
  background: linear-gradient(to bottom right, #d97706, #f59e0b);
  color: #000000;
}

.week-chip--confirmed {
  background: #0369a1;
  color: #ffffff;
}

.week-chip--completed {
  background: #10b981;
  color: #ffffff;
}

.charts-row {
  margin-top: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.chart-card {
  flex: 1 1 240px;
  padding: 14px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.10);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
}

.chart-card__title {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 800;
  color: #ffffff;
}

.chart-card__donut {
  display: flex;
  align-items: center;
  gap: 16px;
}

.donut-display {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  border: 8px solid #0284c7;
  border-top-color: #f59e0b;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.donut-display__number {
  font-size: 20px;
  font-weight: 800;
  color: #ffffff;
  line-height: 1;
}

.donut-display__label {
  font-size: 8px;
  color: #94a3b8;
  text-align: center;
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
  font-size: 11px;
  font-weight: 600;
  color: #e2e8f0;
}

.chart-legend__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.bar-chart-preview {
  text-align: center;
}

.bar-chart-preview__value {
  font-size: 24px;
  font-weight: 800;
  color: #ffffff;
  display: block;
  line-height: 1;
}

.bar-chart-preview__label {
  font-size: 11px;
  color: #cbd5e1;
}

.bar-chart-bars {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 8px;
  height: 60px;
  margin-top: 10px;
}

.bar-col {
  width: 14px;
  border-radius: 3px 3px 0 0;
}

.top-manager-highlight {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.top-manager-profile {
  text-align: center;
  margin-top: 4px;
}

.top-manager-profile__img {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  object-fit: cover;
  border: 2px solid #f59e0b;
}

.top-manager-profile__name {
  margin: 6px 0 2px;
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
}

.top-manager-profile__award {
  color: #f59e0b;
  font-size: 18px;
}

@media (max-width: 768px) {
  .dashboard__row, .charts-row {
    flex-direction: column;
  }

  .agenda-card, .dashboard__row .week-card {
    max-width: 100%;
    width: 100%;
  }

  .week-grid {
    grid-template-columns: 1fr;
  }
}
</style>