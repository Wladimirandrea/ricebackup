<!-- resources/js/components/appointments/AppointmentCalendar.vue -->
<template>
    <div class="cal-wrapper">
        <!-- Header mes -->
        <div class="cal-header">
            <button class="cal-nav" @click="store.prevMonth()">
                <i class="fa-solid fa-chevron-left" />
            </button>
            <h2 class="cal-month">{{ monthName }}</h2>
            <button class="cal-nav" @click="store.nextMonth()">
                <i class="fa-solid fa-chevron-right" />
            </button>
        </div>

        <!-- Días de la semana -->
        <div class="cal-weekdays">
            <div v-for="day in weekDays" :key="day" class="cal-weekday">
                {{ day }}
            </div>
        </div>

        <!-- Loading -->
        <div v-if="store.loading" class="cal-loading">
            <div class="cal-spinner" />
        </div>

        <!-- Grid días -->
        <div v-else class="cal-grid">
            <!-- Espacios vacíos inicio -->
            <div v-for="n in firstDayOfMonth" :key="`empty-${n}`" class="cal-cell cal-cell--empty" />

            <!-- Días del mes -->
            <div v-for="day in daysInMonth" :key="day" class="cal-cell" :class="{
                'cal-cell--today': isToday(day),
                'cal-cell--day-off': isDayOff(day)
            }" @click="$emit('day-click', { day, month: store.currentMonth, year: store.currentYear })">
                <span class="cal-day-num">{{ day }}</span>

                <!-- Day off -->
                <div v-if="isDayOff(day)" class="cal-day-off">
                    <span class="cal-day-off__icon">🌙</span>
                    <span v-if="getDayOff(day)?.reason" class="cal-day-off__reason">
                        {{ getDayOff(day).reason }}
                    </span>
                </div>

                <!-- Badges normales -->
                <div v-else class="cal-badges">
                    <span v-for="(count, status) in getDayBadges(day)" :key="status" class="cal-badge"
                        :class="`cal-badge--${status}`">
                        {{ count }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppointmentStore } from '@/stores/appointmentStore'

const { locale } = useI18n()
const store = useAppointmentStore()

defineEmits(['day-click'])

const MONTHS_EN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const MONTHS_ES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']

const WEEKDAYS_EN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const WEEKDAYS_ES = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

const monthName = computed(() => {
    const months = locale.value === 'es' ? MONTHS_ES : MONTHS_EN
    return months[store.currentMonth - 1]
})

const weekDays = computed(() =>
    locale.value === 'es' ? WEEKDAYS_ES : WEEKDAYS_EN
)

const daysInMonth = computed(() =>
    new Date(store.currentYear, store.currentMonth, 0).getDate()
)

const firstDayOfMonth = computed(() =>
    new Date(store.currentYear, store.currentMonth - 1, 1).getDay()
)

function isToday(day) {
    const today = new Date()
    return (
        day === today.getDate() &&
        store.currentMonth === today.getMonth() + 1 &&
        store.currentYear === today.getFullYear()
    )
}

function getDayBadges(day) {
    const key = `${store.currentYear}-${String(store.currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    const data = store.calendar[key]
    if (!data) return {}

    const badges = {}
    if (data.pending > 0) badges.pending = data.pending
    if (data.confirmed > 0) badges.confirmed = data.confirmed
    if (data.completed > 0) badges.completed = data.completed
    if (data.cancelled > 0) badges.cancelled = data.cancelled
    return badges
}

function isDayOff(day) {
    const key = `${store.currentYear}-${String(store.currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    return !!store.daysOff[key]
}

function getDayOff(day) {
    const key = `${store.currentYear}-${String(store.currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    return store.daysOff[key] ?? null
}
</script>

<style scoped>
.cal-wrapper {
    width: 100%;
    border-radius: 24px;
    overflow: hidden;
    background: linear-gradient(135deg,
            rgba(180, 180, 160, 0.45) 0%,
            rgba(160, 180, 140, 0.35) 30%,
            rgba(200, 160, 140, 0.35) 60%,
            rgba(160, 140, 180, 0.35) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    padding: 24px;
}

/* Header */
.cal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    padding: 0 8px;
}

.cal-month {
    font-size: 3rem;
    font-weight: 900;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
}

.cal-nav {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #fff;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, transform 0.15s;
    backdrop-filter: blur(8px);
}

.cal-nav:hover {
    background: rgba(255, 255, 255, 0.28);
    transform: scale(1.05);
}

/* Weekdays */
.cal-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    margin-bottom: 8px;
}

.cal-weekday {
    text-align: center;
    font-size: 1rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.85);
    padding: 8px 0;
    letter-spacing: 0.04em;
}

/* Grid */
.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}

.cal-cell {
    min-height: 80px;
    padding: 8px 6px;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    transition: background 0.15s;
    position: relative;
}

.cal-cell:hover {
    background: rgba(255, 255, 255, 0.12);
}

.cal-cell--empty {
    cursor: default;
    pointer-events: none;
}

.cal-cell--today .cal-day-num {
    background: rgba(255, 255, 255, 0.9);
    color: #1a2a4a;
    font-weight: 800;
}

.cal-day-num {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    transition: background 0.15s;
}

/* Badges */
.cal-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center;
}

.cal-badge {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 800;
    color: #fff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

.cal-badge--pending {
    background: #f59e0b;
}

.cal-badge--confirmed {
    background: #3b82f6;
}

.cal-badge--completed {
    background: #22c55e;
}

.cal-badge--cancelled {
    background: #ef4444;
}

/* Loading */
.cal-loading {
    display: flex;
    justify-content: center;
    padding: 48px 0;
}

.cal-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255, 255, 255, 0.2);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Mobile */
@media (max-width: 767px) {
    .cal-wrapper {
        padding: 16px 10px;
    }

    .cal-month {
        font-size: 2rem;
    }

    .cal-cell {
        min-height: 56px;
        padding: 4px 2px;
    }

    .cal-day-num {
        font-size: 0.82rem;
        width: 24px;
        height: 24px;
    }

    .cal-badge {
        width: 20px;
        height: 20px;
        font-size: 0.62rem;
    }

    .cal-weekday {
        font-size: 0.75rem;
    }
}

.cal-cell--day-off {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.4);
}
.cal-cell--day-off:hover {
    background: rgba(239, 68, 68, 0.3);
}
.cal-day-off {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    width: 100%;
}
.cal-day-off__icon {
    font-size: 1rem;
}
.cal-day-off__reason {
    font-size: 0.6rem;
    color: rgba(255,255,255,0.7);
    text-align: center;
    line-height: 1.2;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    max-width: 100%;
}
</style>