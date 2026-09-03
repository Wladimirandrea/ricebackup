<!--resources/js/views/client/AppointmentCreateView.vue -->
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from '@/plugins/axios'
import AppTopbar from '@/components/layout/AppTopbar.vue'

const { t, locale } = useI18n()
const router = useRouter()

// --- Case manager asignado ---
const caseManager = ref(null)
const loadingManager = ref(true)

// --- Paso del wizard: 'calendar' | 'form' ---
const step = ref('calendar')

// --- Calendario ---
const today = new Date()
today.setHours(0, 0, 0, 0)
const todayStr = today.toISOString().split('T')[0]

const viewMonth = ref(today.getMonth())     // 0-11
const viewYear = ref(today.getFullYear())
const monthCounts = ref({})                 // { 'YYYY-MM-DD': { total, is_day_off, ... } }
const loadingCalendar = ref(false)

const selectedDate = ref('')

const weekDayLabels = computed(() => {
    const base = locale.value?.startsWith('es')
        ? ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa']
        : ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
    return base
})

const monthLabel = computed(() => {
    const d = new Date(viewYear.value, viewMonth.value, 1)
    return d.toLocaleDateString(locale.value?.startsWith('es') ? 'es-ES' : 'en-US', {
        month: 'long',
        year: 'numeric',
    })
})

const calendarDays = computed(() => {
    const firstOfMonth = new Date(viewYear.value, viewMonth.value, 1)
    const startOffset = firstOfMonth.getDay() // 0=Sun
    const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate()

    const cells = []

    // Días de relleno del mes anterior
    for (let i = 0; i < startOffset; i++) {
        cells.push({ inMonth: false })
    }

    // Días del mes actual
    for (let d = 1; d <= daysInMonth; d++) {
        const dateObj = new Date(viewYear.value, viewMonth.value, d)
        const dateStr = dateObj.toISOString().split('T')[0]
        cells.push({
            inMonth: true,
            day: d,
            dateStr,
            isPast: dateStr < todayStr,
            isToday: dateStr === todayStr,
            hasAppointment: !!monthCounts.value[dateStr]?.total,
            isDayOff: !!monthCounts.value[dateStr]?.is_day_off,
        })
    }

    return cells
})

function prevMonth() {
    if (viewMonth.value === 0) {
        viewMonth.value = 11
        viewYear.value -= 1
    } else {
        viewMonth.value -= 1
    }
}

function nextMonth() {
    if (viewMonth.value === 11) {
        viewMonth.value = 0
        viewYear.value += 1
    } else {
        viewMonth.value += 1
    }
}

async function fetchMonthCalendar() {
    loadingCalendar.value = true
    try {
        const { data } = await axios.get('/client/appointments/calendar', {
            params: { month: viewMonth.value + 1, year: viewYear.value },
        })
        monthCounts.value = data.calendar || {}
    } catch (err) {
        console.error('Error al cargar calendario:', err)
        monthCounts.value = {}
    } finally {
        loadingCalendar.value = false
    }
}

watch([viewMonth, viewYear], fetchMonthCalendar)

function pickDay(cell) {
    if (!cell.inMonth || cell.isPast || cell.isDayOff || !caseManager.value) return
    selectedDate.value = cell.dateStr
    step.value = 'form'
}

function backToCalendar() {
    step.value = 'calendar'
    form.value.start_time = ''
}

// --- Case manager ---
async function fetchCaseManager() {
    loadingManager.value = true
    try {
        const { data } = await axios.get('/client/case-manager')
        caseManager.value = data.case_manager
        if (!caseManager.value) {
            errorMessage.value = t('caseManagers.noAssigned') || 'Aún no tienes un gestor de caso asignado. Contacta al administrador.'
        }
    } catch (err) {
        console.error('Error al cargar case manager:', err)
        errorMessage.value = t('appointments.error') || 'No se pudo cargar tu gestor de caso.'
    } finally {
        loadingManager.value = false
    }
}

// --- Horarios disponibles (paso 2) ---
const slots = ref([])
const loadingSlots = ref(false)
const isWorkingDay = ref(true)

const form = ref({
    start_time: '',
    notes: '',
})

const submitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref({})

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return ''
    const [y, m, d] = selectedDate.value.split('-').map(Number)
    const dateObj = new Date(y, m - 1, d)
    return dateObj.toLocaleDateString(locale.value?.startsWith('es') ? 'es-ES' : 'en-US', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    })
})

async function fetchSlots() {
    if (!selectedDate.value) return
    loadingSlots.value = true
    form.value.start_time = ''
    try {
        const { data } = await axios.get('/client/appointments/slots', {
            params: { date: selectedDate.value },
        })
        slots.value = data.slots || []
        isWorkingDay.value = data.is_working
    } catch (err) {
        console.error('Error al cargar horarios:', err)
        slots.value = []
    } finally {
        loadingSlots.value = false
    }
}

watch(selectedDate, (val) => {
    if (val) fetchSlots()
})

function selectSlot(slot) {
    if (!slot.available) return
    form.value.start_time = slot.time
}

async function submitAppointment() {
    errorMessage.value = ''
    fieldErrors.value = {}

    if (!selectedDate.value || !form.value.start_time) {
        errorMessage.value = t('appointments.requiredFields')
        return
    }

    submitting.value = true
    try {
        await axios.post('/client/appointments', {
            date: selectedDate.value,
            start_time: form.value.start_time,
            notes: form.value.notes,
        })

        router.push({ name: 'client.appointments.list' })
    } catch (err) {
        if (err.response?.status === 422) {
            fieldErrors.value = err.response.data.errors || {}
            errorMessage.value = err.response.data.message || t('appointments.error')
        } else {
            errorMessage.value = t('appointments.error')
        }
        console.error('Error al crear la cita:', err)
    } finally {
        submitting.value = false
    }
}

function cancel() {
    router.push({ name: 'client.appointments.list' })
}

onMounted(async () => {
    await fetchCaseManager()
    fetchMonthCalendar()
})
</script>

<template>
    <AppTopbar :title="$t('appointments.title')" :crumbs="[
            { label: $t('nav.dashboard'), icon: 'fa-house', route: 'client.dashboard' },
            { label: $t('appointments.title'), icon: 'fa-calendar-check' },
        ]" />
    <div class="appointment-create">
        

        <div v-if="!loadingManager && caseManager" class="case-manager-card">
            <div class="cm-avatar">
                <img v-if="caseManager.profile_image_url" :src="caseManager.profile_image_url" alt="" />
                <i v-else class="fa fa-user-tie"></i>
            </div>
            <div class="cm-info">
                <span class="cm-label">{{ t('caseManagers.assignedManager') }}</span>
                <span class="cm-name">{{ caseManager.name }}</span>
            </div>
        </div>

        <div v-if="errorMessage && !caseManager" class="alert-error">
            <i class="fa fa-circle-exclamation"></i>
            <span>{{ errorMessage }}</span>
        </div>

        <!-- PASO 1: Calendario -->
        <div v-if="step === 'calendar' && caseManager" class="calendar-card">
            <div class="calendar-header">
                <button type="button" class="cal-nav-btn" @click="prevMonth">
                    <i class="fa fa-chevron-left"></i>
                </button>
                <span class="calendar-month">{{ monthLabel }}</span>
                <button type="button" class="cal-nav-btn" @click="nextMonth">
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>

            <div class="calendar-weekdays">
                <span v-for="wd in weekDayLabels" :key="wd">{{ wd }}</span>
            </div>

            <div class="calendar-grid" :class="{ 'is-loading': loadingCalendar }">
                <div
                    v-for="(cell, idx) in calendarDays"
                    :key="idx"
                    class="calendar-cell"
                    :class="{
                        'is-empty': !cell.inMonth,
                        'is-past': cell.inMonth && cell.isPast,
                        'is-today': cell.inMonth && cell.isToday,
                        'is-selected': cell.inMonth && cell.dateStr === selectedDate,
                        'is-dayoff': cell.inMonth && cell.isDayOff,
                        'is-clickable': cell.inMonth && !cell.isPast && !cell.isDayOff,
                    }"
                    :title="cell.inMonth && cell.isDayOff ? t('appointments.dayOff') : null"
                    @click="pickDay(cell)"
                >
                    <span v-if="cell.inMonth">{{ cell.day }}</span>
                    <span v-if="cell.hasAppointment && !cell.isDayOff" class="cell-dot"></span>
                    <i v-if="cell.isDayOff" class="fa fa-moon cell-dayoff-icon"></i>
                </div>
            </div>

            <p class="calendar-hint">
                <span class="hint-dot"></span>
                {{ t('appointments.hasAppointmentHint') }}
            </p>
            <p class="calendar-hint">
                <i class="fa fa-moon hint-dayoff-icon"></i>
                {{ t('appointments.dayOff') }}
            </p>
        </div>

        <!-- PASO 2: Horarios + formulario -->
        <form v-else-if="step === 'form'" class="appointment-form" @submit.prevent="submitAppointment">
            <button type="button" class="back-link" @click="backToCalendar">
                <i class="fa fa-chevron-left"></i>
                {{ t('appointments.changeDate') }}
            </button>

            <div class="selected-date-chip">
                <i class="fa fa-calendar-day"></i>
                <span>{{ selectedDateLabel }}</span>
            </div>

            <div v-if="errorMessage" class="alert-error">
                <i class="fa fa-circle-exclamation"></i>
                <span>{{ errorMessage }}</span>
            </div>

            <div class="form-group">
                <label>{{ t('appointments.startTime') }} *</label>

                <div v-if="loadingSlots" class="slots-loading">
                    <i class="fa fa-spinner fa-spin"></i>
                    {{ t('common.loading') }}
                </div>

                <div v-else-if="!isWorkingDay" class="slots-empty">
                    {{ t('appointments.dayOff') }}
                    <button type="button" class="link-btn" @click="backToCalendar">
                        {{ t('appointments.pickAnotherDay') }}
                    </button>
                </div>

                <div v-else-if="slots.length === 0" class="slots-empty">
                    {{ t('appointments.noSlots') }}
                    <button type="button" class="link-btn" @click="backToCalendar">
                        {{ t('appointments.pickAnotherDay') }}
                    </button>
                </div>

                <div v-else class="slots-grid">
                    <button
                        v-for="slot in slots"
                        :key="slot.time"
                        type="button"
                        class="slot-btn"
                        :class="{
                            selected: form.start_time === slot.time,
                            disabled: !slot.available,
                        }"
                        :disabled="!slot.available"
                        @click="selectSlot(slot)"
                    >
                        {{ slot.time }}
                    </button>
                </div>
                <span v-if="fieldErrors.start_time" class="field-error">{{ fieldErrors.start_time[0] }}</span>
            </div>

            <div class="form-group">
                <label for="notes">{{ t('appointments.notes') }}</label>
                <textarea
                    id="notes"
                    v-model="form.notes"
                    class="form-input form-textarea"
                    :class="{ 'input-error': fieldErrors.notes }"
                    rows="4"
                    :placeholder="t('appointments.notesPlaceholder')"
                ></textarea>
                <span v-if="fieldErrors.notes" class="field-error">{{ fieldErrors.notes[0] }}</span>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="cancel">
                    {{ t('common.cancel') }}
                </button>
                <button type="submit" class="btn btn-primary" :disabled="submitting || !form.start_time">
                    <i v-if="submitting" class="fa fa-spinner fa-spin"></i>
                    <span>{{ submitting ? t('appointments.submitting') : t('appointments.submit') }}</span>
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.appointment-create {
    max-width: 640px;
    margin: 0 auto;
    padding: 24px 16px 40px;
}

.page-header {
    margin-bottom: 20px;
}

.page-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #131c2e;
    margin: 0 0 4px;
}

.subtitle {
    font-size: 14px;
    color: #7a8aaa;
    margin: 0;
}

/* Case manager card */
.case-manager-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #131c2e;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 20px;
}

.cm-avatar {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #4a90e2;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.cm-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cm-avatar i {
    color: white;
    font-size: 18px;
}

.cm-info {
    display: flex;
    flex-direction: column;
}

.cm-label {
    font-size: 11px;
    color: #7a8aaa;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.cm-name {
    font-size: 15px;
    font-weight: 600;
    color: white;
}

/* Calendario — glassmorphism compacto */
.calendar-card {
    background: linear-gradient(
        135deg,
        rgba(180, 180, 160, 0.45) 0%,
        rgba(160, 180, 140, 0.35) 30%,
        rgba(200, 160, 140, 0.35) 60%,
        rgba(160, 140, 180, 0.35) 100%
    );
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border-radius: 14px;
    padding: 14px;
    color: #ffffff;
}

.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.calendar-month {
    font-size: 15px;
    font-weight: 700;
    color: #f3f3f3;
    text-transform: capitalize;
}

.cal-nav-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e5e9f2;
    background: #fff;
    color: #131c2e;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
}

.cal-nav-btn:hover {
    background: #f1f4f9;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    margin-bottom: 4px;
}

.calendar-weekdays span {
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    color: #d5d8de;
    text-transform: uppercase;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    transition: opacity 0.15s;
}

.calendar-grid.is-loading {
    opacity: 0.5;
}

.calendar-cell {
    position: relative;
    aspect-ratio: 1.4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    color: #ffffff;
    border-radius: 8px;
}

.calendar-cell.is-empty {
    visibility: hidden;
}

.calendar-cell.is-past {
    color: rgba(255, 255, 255, 0.35);
}

.calendar-cell.is-clickable {
    cursor: pointer;
}

.calendar-cell.is-clickable:hover {
    background: rgba(255, 255, 255, 0.15);
}

.calendar-cell.is-today {
    border: 1.5px solid #4a90e2;
    color: #4a90e2;
}

.calendar-cell.is-selected {
    background: #4a90e2;
    color: white;
}

.calendar-cell.is-dayoff {
    background: rgba(254, 243, 236, 0.85);
    color: #d97706;
    cursor: not-allowed;
}

.calendar-cell.is-dayoff.is-today {
    border: 1.5px solid #d97706;
}

.cell-dot {
    position: absolute;
    bottom: 5px;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #fb923c;
}

.calendar-cell.is-selected .cell-dot {
    background: white;
}

.cell-dayoff-icon {
    position: absolute;
    bottom: 4px;
    font-size: 8px;
    color: #d97706;
}

.calendar-hint {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #d5d8de;
    margin: 8px 0 0;
}

.hint-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #fb923c;
}

.hint-dayoff-icon {
    font-size: 11px;
    color: #d97706;
}

/* Formulario (paso 2) */
.appointment-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
    background: #ffffff;
    border: 1px solid #e5e9f2;
    border-radius: 14px;
    padding: 24px;
}

.back-link {
    display: flex;
    align-items: center;
    gap: 6px;
    align-self: flex-start;
    background: none;
    border: none;
    color: #4a90e2;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    font-family: inherit;
}

.back-link:hover {
    text-decoration: underline;
}

.selected-date-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #eef4fd;
    color: #1d5aa8;
    font-size: 14px;
    font-weight: 600;
    padding: 10px 14px;
    border-radius: 10px;
    text-transform: capitalize;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

label {
    font-size: 13px;
    font-weight: 600;
    color: #131c2e;
}

.form-input {
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #d7deea;
    font-size: 14px;
    color: #131c2e;
    background: #fff;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.15);
}

.form-textarea {
    resize: vertical;
    min-height: 90px;
}

.input-error {
    border-color: #ef4444;
}

.field-error {
    font-size: 12px;
    color: #ef4444;
}

.alert-error {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
}

.slots-loading,
.slots-empty {
    font-size: 13px;
    color: #7a8aaa;
    padding: 12px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.link-btn {
    background: none;
    border: none;
    color: #4a90e2;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    padding: 0;
    font-family: inherit;
}

.link-btn:hover {
    text-decoration: underline;
}

.slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
    gap: 8px;
}

.slot-btn {
    padding: 8px 6px;
    border-radius: 8px;
    border: 1px solid #d7deea;
    background: #fff;
    color: #131c2e;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.slot-btn:hover:not(.disabled) {
    border-color: #4a90e2;
    color: #4a90e2;
}

.slot-btn.selected {
    background: #4a90e2;
    border-color: #4a90e2;
    color: white;
}

.slot-btn.disabled {
    background: #f1f4f9;
    color: #b7c0d1;
    cursor: not-allowed;
    text-decoration: line-through;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 8px;
}

.btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: background 0.2s, opacity 0.2s;
    font-family: inherit;
}

.btn-primary {
    background: #4a90e2;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #3a7bc8;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f1f4f9;
    color: #131c2e;
}

.btn-secondary:hover {
    background: #e5e9f2;
}
</style>