<!-- resources/js/components/appointments/ManagerAppointmentCalendar.vue -->
<template>
    <div class="cal-wrapper">
        <div class="cal-header">
            <button class="cal-nav" @click="store.prevMonth()">
                <i class="fa-solid fa-chevron-left" />
            </button>
            <h2 class="cal-month">{{ monthName }}</h2>
            <button class="cal-nav" @click="store.nextMonth()">
                <i class="fa-solid fa-chevron-right" />
            </button>
        </div>

        <div class="cal-weekdays">
            <div v-for="day in weekDays" :key="day" class="cal-weekday">{{ day }}</div>
        </div>

        <div v-if="store.loading" class="cal-loading">
            <div class="cal-spinner" />
        </div>

        <div v-else class="cal-grid">
            <div v-for="n in firstDayOfMonth" :key="`e-${n}`" class="cal-cell cal-cell--empty" />

            <div v-for="day in daysInMonth" :key="day" class="cal-cell" :class="{
                'cal-cell--today': isToday(day),
                'cal-cell--day-off': isDayOff(day)
            }" @click="$emit('day-click', { day, month: store.currentMonth, year: store.currentYear })">
                <span class="cal-day-num">{{ day }}</span>

                <div v-if="isDayOff(day)" class="cal-day-off">
                    <span class="cal-day-off__icon">🌙</span>
                    <span v-if="getDayOff(day)?.reason" class="cal-day-off__reason">
                        {{ getDayOff(day).reason }}
                    </span>
                </div>

                <div v-else class="cal-badges">
                    <span v-for="(count, status) in getDayBadges(day)" :key="status" class="cal-badge"
                        :class="`cal-badge--${status}`">{{ count }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useManagerAppointmentStore } from '@/stores/managerAppointmentStore'

const { locale } = useI18n()
const store = useManagerAppointmentStore()
defineEmits(['day-click'])

const MONTHS_EN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const MONTHS_ES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const WDAYS_EN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const WDAYS_ES = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const monthName = computed(() => (locale.value === 'es' ? MONTHS_ES : MONTHS_EN)[store.currentMonth - 1])
const weekDays = computed(() => locale.value === 'es' ? WDAYS_ES : WDAYS_EN)
const daysInMonth = computed(() => new Date(store.currentYear, store.currentMonth, 0).getDate())
const firstDayOfMonth = computed(() => new Date(store.currentYear, store.currentMonth - 1, 1).getDay())

function isToday(day) {
    const t = new Date()
    return day === t.getDate() && store.currentMonth === t.getMonth() + 1 && store.currentYear === t.getFullYear()
}
function key(day) {
    return `${store.currentYear}-${String(store.currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}
function isDayOff(day) { return !!store.daysOff[key(day)] }
function getDayOff(day) { return store.daysOff[key(day)] ?? null }
function getDayBadges(day) {
    const data = store.calendar[key(day)]
    if (!data) return {}
    const b = {}
    if (data.pending > 0) b.pending = data.pending
    if (data.confirmed > 0) b.confirmed = data.confirmed
    if (data.completed > 0) b.completed = data.completed
    if (data.cancelled > 0) b.cancelled = data.cancelled
    return b
}
</script>

<style scoped>
.cal-wrapper {
    width: 100%; border-radius: 20px; overflow: hidden;
    flex-shrink: 0;
    background: linear-gradient(135deg,
        rgba(180,180,160,0.45) 0%, rgba(160,180,140,0.35) 30%,
        rgba(200,160,140,0.35) 60%, rgba(160,140,180,0.35) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.2);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    padding: 18px;
}
.cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding: 0 6px; }
.cal-month  { font-size: 2.2rem; font-weight: 900; color: #fff; text-transform: uppercase; letter-spacing: 0.06em; margin: 0; text-shadow: 0 2px 12px rgba(0,0,0,0.3); }
.cal-nav {
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
    color: #fff; width: 38px; height: 38px; border-radius: 10px;
    font-size: 0.9rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, transform 0.15s; backdrop-filter: blur(8px);
}
.cal-nav:hover { background: rgba(255,255,255,0.28); transform: scale(1.05); }
.cal-weekdays { display: grid; grid-template-columns: repeat(7,1fr); margin-bottom: 6px; }
.cal-weekday  { text-align: center; font-size: 0.85rem; font-weight: 600; color: rgba(255,255,255,0.85); padding: 5px 0; }
.cal-grid     { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; }
.cal-cell {
    min-height: 64px; padding: 5px 4px; border-radius: 9px;
    cursor: pointer; display: flex; flex-direction: column;
    align-items: center; gap: 4px; transition: background 0.15s;
}
.cal-cell:hover        { background: rgba(255,255,255,0.12); }
.cal-cell--empty       { cursor: default; pointer-events: none; }
.cal-cell--today .cal-day-num { background: rgba(255,255,255,0.9); color: #1a2a4a; font-weight: 800; }
.cal-cell--day-off     { background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4); }
.cal-cell--day-off:hover { background: rgba(239,68,68,0.3); }
.cal-day-num { font-size: 0.88rem; color: rgba(255,255,255,0.9); width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 500; }
.cal-badges  { display: flex; flex-wrap: wrap; gap: 3px; justify-content: center; }
.cal-badge   { width: 21px; height: 21px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.64rem; font-weight: 800; color: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
.cal-badge--pending   { background: #f59e0b; }
.cal-badge--confirmed { background: #3b82f6; }
.cal-badge--completed { background: #22c55e; }
.cal-badge--cancelled { background: #ef4444; }
.cal-day-off { display: flex; flex-direction: column; align-items: center; gap: 2px; width: 100%; }
.cal-day-off__icon   { font-size: 0.9rem; }
.cal-day-off__reason { font-size: 0.58rem; color: rgba(255,255,255,0.7); text-align: center; line-height: 1.2; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; max-width: 100%; }
.cal-loading { display: flex; justify-content: center; padding: 40px 0; }
.cal-spinner { width: 38px; height: 38px; border: 3px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 767px) {
    .cal-wrapper { padding: 14px 9px; }
    .cal-month   { font-size: 1.6rem; }
    .cal-cell    { min-height: 50px; padding: 4px 2px; }
    .cal-day-num { font-size: 0.75rem; width: 20px; height: 20px; }
    .cal-badge   { width: 18px; height: 18px; font-size: 0.56rem; }
    .cal-weekday { font-size: 0.7rem; }
}
</style>