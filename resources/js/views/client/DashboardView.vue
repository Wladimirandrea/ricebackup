<script setup>
import { onMounted, onUnmounted, computed, reactive, ref, watch } from 'vue'
import { useClientAppointmentStore } from '@/stores/clientAppointmentStore'
import { useAuthStore } from '@/stores/auth'
import Chart from 'chart.js/auto'
import { useI18n } from 'vue-i18n'

const store = useClientAppointmentStore()
const auth = useAuthStore()
const { t, locale } = useI18n()

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

// ── Reloj ──────────────────────────────────
const now = ref(new Date())
let clockInterval = null
const currentDateLabel = computed(() =>
    now.value.toLocaleDateString(locale.value, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)
const currentTimeLabel = computed(() =>
    now.value.toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)

// ── Stats del mes ──────────────────────────
const completedThisMonth = computed(() =>
    Object.values(store.calendar).reduce((acc, d) => acc + (d.completed ?? 0), 0)
)
const pendingThisMonth = computed(() =>
    Object.values(store.calendar).reduce((acc, d) => acc + (d.pending ?? 0), 0)
)
const confirmedThisMonth = computed(() =>
    Object.values(store.calendar).reduce((acc, d) => acc + (d.confirmed ?? 0), 0)
)

// ── Calendario semanal ─────────────────────
const dayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
const dayNames = computed(() => dayKeys.map(k => t(`managerDashboard.days.${k}`)))
const weekOffset = ref(0)
const weekAppointments = reactive({})
const loadingWeek = ref(false)

function pad(n) { return String(n).padStart(2, '0') }
function toKey(d) { return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` }

const weekDates = computed(() => {
    const base = new Date()
    const monday = new Date(base)
    const dayIdx = (base.getDay() + 6) % 7
    monday.setDate(base.getDate() - dayIdx + weekOffset.value * 7)
    monday.setHours(0, 0, 0, 0)

    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(monday)
        d.setDate(monday.getDate() + i)
        return { key: toKey(d), label: dayNames.value[i], dayNumber: d.getDate() }
    })
})

async function fetchWeek() {
    loadingWeek.value = true
    await Promise.all(
        weekDates.value.map(async ({ key }) => {
            try {
                const { data } = await store.$state ? null : null // placeholder no-op
            } catch { }
        })
    )
    // Reutilizamos fetchDay del store, guardando cada resultado localmente
    await Promise.all(
        weekDates.value.map(async ({ key }) => {
            const prevDay = store.dayAppointments
            await store.fetchDay(key)
            weekAppointments[key] = store.dayAppointments
        })
    )
    loadingWeek.value = false
}

function prevWeek() { weekOffset.value--; fetchWeek() }
function nextWeek() { weekOffset.value++; fetchWeek() }

// ── Donut de status ─────────────────────────
const statusCanvas = ref(null)
let statusChart = null

function buildStatusChart() {
    if (!statusCanvas.value) return
    if (statusChart) statusChart.destroy()
    statusChart = new Chart(statusCanvas.value, {
        type: 'doughnut',
        data: {
            labels: [
                t('managerDashboard.charts.closed'),
                t('managerDashboard.charts.waiting'),
                t('managerDashboard.charts.pending'),
            ],
            datasets: [{
                data: [completedThisMonth.value, confirmedThisMonth.value, pendingThisMonth.value],
                backgroundColor: ['#1d6fa5', '#e0932c', '#3f9142'],
                borderWidth: 0,
            }],
        },
        options: { cutout: '65%', plugins: { legend: { display: false } } },
    })
}

const caseStatusTotal = computed(() => completedThisMonth.value + confirmedThisMonth.value + pendingThisMonth.value)
function casePct(v) {
    return caseStatusTotal.value ? Math.round((v / caseStatusTotal.value) * 100) : 0
}

watch([completedThisMonth, confirmedThisMonth, pendingThisMonth], buildStatusChart)
watch(locale, buildStatusChart)

onMounted(async () => {
    const d = new Date()
    await Promise.all([
        store.fetchCalendar(d.getMonth() + 1, d.getFullYear()),
        store.fetchCaseManager(),
    ])
    await store.fetchDay(todayKey())
    fetchWeek()
    buildStatusChart()

    clockInterval = setInterval(() => { now.value = new Date() }, 1000)
})

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval)
    statusChart?.destroy()
})
</script>

<template>
    <div class="dashboard">
        <div class="dashboard__header">
            <h1 class="dashboard__title">{{ t('dashboard.welcome', { name: auth.user?.name ?? '' }) }}</h1>
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
                        <p class="stat-card__value">{{ completedThisMonth }}</p>
                        <p class="stat-card__label">{{ t('managerDashboard.stats.completed') }}</p>
                    </div>
                    <div class="stat-card__icon"><i class="fa-solid fa-clipboard-check"></i></div>
                </div>
                <p class="stat-card__footer">{{ t('managerDashboard.stats.completedSub') }}</p>
            </div>

            <div class="stat-card stat-card--yellow">
                <div class="stat-card__top">
                    <div>
                        <p class="stat-card__value">{{ pendingThisMonth }}</p>
                        <p class="stat-card__label">{{ t('managerDashboard.stats.pending') }}</p>
                    </div>
                    <div class="stat-card__icon"><i class="fa-regular fa-calendar-days"></i></div>
                </div>
                <p class="stat-card__footer">{{ t('managerDashboard.stats.pendingSub') }}</p>
            </div>

            <div class="stat-card stat-card--purple">
                <div class="stat-card__top">
                    <div>
                        <p class="stat-card__value">{{ confirmedThisMonth }}</p>
                        <p class="stat-card__label">{{ t('appointments.confirmed') }}</p>
                    </div>
                    <div class="stat-card__icon"><i class="fa-solid fa-check"></i></div>
                </div>
                <p class="stat-card__footer">{{ t('managerDashboard.charts.waiting') }}</p>
            </div>

            <div class="stat-card stat-card--red">
                <div class="stat-card__top">
                    <div>
                        <p class="stat-card__value">{{ store.dayAppointments.length }}</p>
                        <p class="stat-card__label">{{ t('managerDashboard.stats.critical') }}</p>
                    </div>
                    <div class="stat-card__icon"><i class="fa-solid fa-bullhorn"></i></div>
                </div>
                <p class="stat-card__footer">{{ t('managerDashboard.stats.criticalSub') }}</p>
            </div>
        </div>

        <div class="dashboard__row">
            <div class="week-card">
                <div class="week-card__header">
                    <h2 class="week-card__title">{{ t('managerDashboard.week.title') }}</h2>
                    <div class="week-card__nav">
                        <button class="week-card__nav-btn" @click="prevWeek">‹</button>
                        <span class="week-card__nav-label">{{ t('managerDashboard.week.current') }}</span>
                        <button class="week-card__nav-btn" @click="nextWeek">›</button>
                    </div>
                </div>

                <div v-if="loadingWeek" class="week-card__loading">{{ t('managerDashboard.week.loading') }}</div>

                <div v-else class="week-grid">
                    <div v-for="day in weekDates" :key="day.key" class="week-grid__col">
                        <div
                            :class="['week-grid__col-header', day.key === todayKey() && 'week-grid__col-header--today']">
                            <span>{{ day.label }}</span>
                            <span class="week-grid__col-date">{{ day.dayNumber }}</span>
                        </div>
                        <div class="week-grid__col-body">
                            <div v-for="appt in weekAppointments[day.key]" :key="appt.id"
                                :class="['week-chip', `week-chip--${appt.status}`]">
                                <span class="week-chip__time">{{ formatTime(appt.start_time) }}</span>
                                <span class="week-chip__name">{{ appt.case_manager?.name ?? '—' }}</span>
                            </div>
                            <p v-if="!weekAppointments[day.key]?.length" class="week-grid__empty">—</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="agenda-card">
                <h2 class="agenda-card__title">{{ t('managerDashboard.agenda.title') }}</h2>

                <div v-if="store.loadingDay" class="week-card__loading">{{ t('managerDashboard.agenda.loading') }}</div>

                <div v-else class="agenda-table-wrap">
                    <table class="agenda-table">
                        <thead>
                            <tr>
                                <th>{{ t('managerDashboard.agenda.hour') }}</th>
                                <th>{{ t('appointments.caseManager') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="appt in store.dayAppointments" :key="appt.id">
                                <td>{{ formatTime(appt.start_time) }}</td>
                                <td>{{ appt.case_manager?.name ?? '—' }}</td>
                            </tr>
                            <tr v-if="!store.dayAppointments.length">
                                <td colspan="2" class="agenda-table__empty">{{ t('managerDashboard.agenda.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card">
                <h2 class="chart-card__title">{{ t('managerDashboard.charts.caseStatusTitle') }}</h2>
                <div v-if="caseStatusTotal" class="chart-card__donut">
                    <div class="chart-card__canvas-wrap"><canvas ref="statusCanvas"></canvas></div>
                    <ul class="chart-legend">
                        <li class="chart-legend__item">
                            <span class="chart-legend__dot" style="background:#1d6fa5"></span>
                            {{ casePct(completedThisMonth) }}% {{ t('managerDashboard.charts.closed') }}
                        </li>
                        <li class="chart-legend__item">
                            <span class="chart-legend__dot" style="background:#e0932c"></span>
                            {{ casePct(confirmedThisMonth) }}% {{ t('managerDashboard.charts.waiting') }}
                        </li>
                        <li class="chart-legend__item">
                            <span class="chart-legend__dot" style="background:#3f9142"></span>
                            {{ casePct(pendingThisMonth) }}% {{ t('managerDashboard.charts.pending') }}
                        </li>
                    </ul>
                </div>
                <p v-else class="chart-card__empty">{{ t('managerDashboard.charts.emptyMonth') }}</p>
            </div>

            <div class="chart-card">
                <h2 class="chart-card__title">{{ t('appointments.caseManager') }}</h2>
                <div v-if="store.loadingCaseManager" class="chart-card__empty">{{ t('common.loading') }}</div>
                <div v-else-if="store.caseManager" class="cm-info">
                    <img :src="store.caseManager.profile_image_url" class="cm-info__avatar" />
                    <div class="cm-info__data">
                        <span class="cm-info__name">{{ store.caseManager.name }}</span>
                        <span class="cm-info__email">{{ store.caseManager.email }}</span>
                    </div>
                </div>
                <p v-else class="chart-card__empty">{{ t('caseManagers.noAssigned') }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dashboard {
    min-height: 100%;
    padding: 0px 20px;
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
    font-size: 30px;
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
    font-size: 30px;
    font-weight: 500;
    color: #cbd5e1;
    text-transform: capitalize;
}

.dashboard__clock-sep {
    color: #64748b;
}

.dashboard__clock-time {
    font-size: 30px;
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

.stat-card--clickable {
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.2s;
}

.stat-card--clickable:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 24px rgba(0, 0, 0, 0.3);
}

.stat-card--clickable:active {
    transform: translateY(-1px);
}

.stat-card--clickable:focus-visible {
    outline: 2px solid #ffffff;
    outline-offset: 3px;
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
    flex-shrink: 0;
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
    background: rgba(255, 255, 255, 0.10);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
}

.agenda-card__title {
    margin: 0 0 12px;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: #ffffff;
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
    background: rgba(255, 255, 255, 0.3);
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
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    color: #f1f5f9;
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
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    color: #e2e8f0;
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
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.12);
    color: #f1f5f9;
    font-size: 10px;
    line-height: 1;
    cursor: pointer;
}

.agenda-table__menu-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.agenda-table__empty {
    padding: 20px 10px;
    text-align: center;
    color: #cbd5e1;
}

.charts-row {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.chart-card {
    flex: 1 1 320px;
    padding: 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.10);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
}

.chart-card__title {
    margin: 0 0 10px;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.03em;
    color: #ffffff;
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
    color: #e2e8f0;
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
    color: #cbd5e1;
    font-size: 13px;
}

.task-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.task-card__header .chart-card__title {
    margin: 0;
}

.task-card__add-btn {
    width: 26px;
    height: 26px;
    border: none;
    border-radius: 50%;
    background: #10b981;
    color: #ffffff;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
}

.task-card__add-btn:hover {
    background: #059669;
}

.task-form {
    display: flex;
    gap: 6px;
    margin-bottom: 10px;
}

.task-form__input {
    flex: 1;
    min-width: 0;
    padding: 6px 8px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    font-size: 12px;
}

.task-form__input::placeholder {
    color: #cbd5e1;
}

.task-form__select {
    padding: 6px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    font-size: 12px;
}

.task-form__select option {
    color: #1e293b;
}

.task-form__submit {
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
    background: #10b981;
    color: #ffffff;
    font-size: 12px;
    cursor: pointer;
}

.task-form__submit:hover {
    background: #059669;
}

.task-list {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 160px;
    overflow-y: auto;
    scrollbar-width: thin;
}

.task-list::-webkit-scrollbar {
    width: 5px;
}

.task-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.task-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.task-item:last-child {
    border-bottom: none;
}

.task-item__checkbox {
    width: 15px;
    height: 15px;
    accent-color: #10b981;
    flex-shrink: 0;
}

.task-item__title {
    flex: 1;
    font-size: 13px;
    color: #e2e8f0;
}

.task-item__title--done {
    color: #94a3b8;
    text-decoration: line-through;
}

.task-item__priority {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    color: #ffffff;
    flex-shrink: 0;
}

.task-item__priority--baja {
    background: #3f9142;
}

.task-item__priority--media {
    background: #e0932c;
}

.task-item__priority--alta {
    background: #dc2626;
}

.task-item__delete {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: none;
    border-radius: 50%;
    background: #ef4444;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
}

.task-item__delete:hover {
    background: #b91c1c;
}

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
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    cursor: pointer;
}

.week-card__nav-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.week-card__nav-label {
    padding: 4px 12px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.12);
    color: #f1f5f9;
    font-size: 13px;
    font-weight: 500;
}

.week-card__loading {
    padding: 24px;
    text-align: center;
    color: #cbd5e1;
    font-size: 14px;
}

.week-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 8px;
}

.week-grid__col {
    min-height: 140px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    overflow: hidden;
}

.week-grid__col-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 4px;
    background: rgba(255, 255, 255, 0.10);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 12px;
    font-weight: 700;
    color: #f1f5f9;
}

.week-grid__col-date {
    font-size: 11px;
    font-weight: 500;
    color: #cbd5e1;
}

.week-grid__col-header--today {
    background: rgba(16, 185, 129, 0.35);
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
    background: rgba(255, 255, 255, 0.3);
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
    cursor: pointer;
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
    background: linear-gradient(to bottom right, #d97706, #f59e0b);
    color: #000000;
}

.week-chip--confirmed {
    background: #0369a1;
    color: #ffffff;
}

.week-chip--completed {
    background: linear-gradient(to bottom right, #059669, #10b981);
    color: #000000;
}

.week-chip--cancelled {
    background: linear-gradient(to bottom right, #b91c1c, #ef4444);
    color: #ffffff;
}

.week-grid__empty {
    margin: 4px 0 0;
    text-align: center;
    font-size: 12px;
    color: #cbd5e1;
}

.cm-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
}

.cm-info__avatar {
    width: 150px;
    height: 150px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}

.cm-info__data {
    display: flex;
    flex-direction: column;
}

.cm-info__name {
    font-weight: 700;
    color: #fff;
    font-size: 0.9rem;
}

.cm-info__email {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.78rem;
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
        flex-shrink: 0;
    }

    .week-card {
        padding: 14px;
    }

    .dashboard__row {
        flex-direction: column;
        margin-top: 10px;
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

    .cm-info {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 0;
    }

    .cm-info__avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .cm-info__data {
        display: flex;
        flex-direction: column;
    }

    .cm-info__name {
        font-weight: 700;
        color: #fff;
        font-size: 0.9rem;
    }

    .cm-info__email {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.78rem;
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

    .task-form {
        flex-wrap: wrap;
    }
}
</style>