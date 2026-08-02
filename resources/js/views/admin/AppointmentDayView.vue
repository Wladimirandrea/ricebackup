<!-- resources/js/views/admin/AppointmentDayView.vue -->
<template>
    <div class="day-view">
        <AppTopbar :title="formattedDate" :crumbs="[
            { label: $t('users.crumbs.dashboard'), icon: 'fa-house', route: 'admin.dashboard' },
            { label: $t('appointments.title'), icon: 'fa-calendar-check', route: 'admin.appointments' },
            { label: formattedDate, icon: 'fa-calendar-day' },
        ]" :actions="[{ label: $t('appointments.new'), icon: 'fa-plus', type: 'primary', emit: 'new', disabled: !store.daySchedule?.is_working }]"
            @action="onAction" />

        <div class="day-view__body">

            <!-- Filtro case managers -->
            <div class="day-cm-filter">
                <div class="day-cm-item" :class="{ 'day-cm-item--active': store.selectedManager === null }"
                    @click="store.selectedManager = null">
                    <div class="day-cm-avatar day-cm-avatar--all">
                        <i class="fa-solid fa-users" />
                    </div>
                    <span class="day-cm-name">{{ $t('appointments.all') }}</span>
                </div>

                <div v-for="cm in store.dayCaseManagers" :key="cm.id" class="day-cm-item"
                    :class="{ 'day-cm-item--active': store.selectedManager === cm.id }"
                    @click="store.selectedManager = cm.id">
                    <div class="day-cm-avatar">
                        <img :src="cm.profile_image || defaultAvatar" :alt="cm.name" />
                    </div>
                    <span class="day-cm-name">{{ cm.name.split(' ')[0] }}</span>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="store.loadingDay" class="day-loading">
                <div class="day-spinner" />
            </div>

            <!-- Día no laborable -->
            <div v-else-if="!store.daySchedule?.is_working" class="day-day-off">
                <div class="day-day-off__icon-wrap">
                    <i class="fa-solid fa-moon" />
                </div>
                <p class="day-day-off__title">{{ $t('appointments.dayOff') }}</p>
                <p v-if="store.daySchedule?.reason" class="day-day-off__reason">
                    "{{ store.daySchedule.reason }}"
                </p>
            </div>

            <!-- Sin citas -->
            <div v-else-if="filteredAppointments.length === 0" class="day-empty">
                <span>📅</span>
                <p>{{ $t('appointments.noAppointments') }}</p>
            </div>

            <!-- Timeline (desktop/tablet) -->
            <div v-else class="day-timeline-wrapper">

                <!-- Headers clientes — FUERA del scroll -->
                <div class="day-timeline-header">
                    <div class="day-timeline-header__spacer" />
                    <div class="day-timeline-header__clients" ref="headerClients">
                        <div v-for="client in filteredClients" :key="client.id" class="day-col-header">
                            <img :src="client.profile_image || defaultAvatar" :alt="client.name"
                                class="day-col-avatar" />
                            <span class="day-col-name">{{ client.name }}</span>
                            <span class="day-col-count">{{ clientAppointments(client.id).length }} {{
                                $t('appointments.apptCount') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Body con scroll único -->
                <div class="day-timeline-body" ref="timelineBody" @scroll="syncScroll">
                    <!-- Columna horas -->
                    <div class="day-timeline__hours">
                        <div v-for="slot in timeSlots" :key="slot" class="day-timeline__hour">
                            {{ slot }}
                        </div>
                    </div>

                    <!-- Columnas clientes -->
                    <div class="day-timeline__clients">
                        <div v-for="client in filteredClients" :key="client.id" class="day-timeline__client-col">
                            <div class="day-col-slots">
                                <div v-for="slot in timeSlots" :key="slot" class="day-slot"
                                    :class="{ 'day-slot--available': isSlotAvailable(slot) }">
                                    <div v-for="appt in getSlotAppointment(client.id, slot)" :key="appt.id"
                                        class="day-appt" :class="`day-appt--${appt.status}`" @click="onApptClick(appt)">
                                        <div class="day-appt__top">
                                            <span class="day-appt__client">{{ appt.client.name }}</span>
                                            <span v-if="appt.notes" class="day-appt__notes">{{ appt.notes }}</span>
                                        </div>
                                        <span class="day-appt__time">{{ appt.start_time }} — {{ appt.end_time }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Lista de citas (mobile) -->
            <div v-if="!store.loadingDay && store.daySchedule?.is_working && filteredAppointments.length > 0"
                class="day-mobile-list">
                <div v-for="appt in sortedAppointments" :key="appt.id" class="day-mobile-card"
                    :class="`day-mobile-card--${appt.status}`" @click="onApptClick(appt)">
                    <img :src="appt.client.profile_image || defaultAvatar" :alt="appt.client.name"
                        class="day-mobile-card__avatar" />
                    <div class="day-mobile-card__info">
                        <span class="day-mobile-card__name">{{ appt.client.name }}</span>
                        <span class="day-mobile-card__time">{{ appt.start_time }} — {{ appt.end_time }}</span>
                    </div>
                    <button type="button" class="day-mobile-card__edit" @click.stop="onApptClick(appt)">
                        <i class="fa-solid fa-pen" />
                    </button>
                </div>
            </div>

        </div>

        <AppointmentFormModal v-model="showForm" :date="date" @created="onCreated" />
        <AppointmentDetailModal v-model="showDetail" :appointment="selectedAppt" @status-changed="onStatusChanged" @appointment-updated="onAppointmentUpdated" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAppointmentStore } from '@/stores/appointmentStore'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import AppointmentFormModal from '@/components/appointments/AppointmentFormModal.vue'
import AppointmentDetailModal from '@/components/appointments/AppointmentDetailModal.vue'

const { locale } = useI18n()
const route = useRoute()
const store = useAppointmentStore()

const defaultAvatar = 'https://ui-avatars.com/api/?name=C&background=1565c0&color=fff'
const showForm = ref(false)
const showDetail = ref(false)
const selectedAppt = ref(null)
const timelineBody = ref(null)
const headerClients = ref(null)

const date = computed(() => route.params.date)

const formattedDate = computed(() => {
    if (!date.value) return ''
    return new Date(date.value + 'T00:00:00').toLocaleDateString(
        locale.value === 'es' ? 'es-ES' : 'en-US',
        { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
    )
})

const timeSlots = computed(() => {
    const slots = []
    if (!store.daySchedule?.is_working) return slots

    const start = store.daySchedule.start_time ?? '08:00'
    const end = store.daySchedule.end_time ?? '17:00'

    const [startH, startM] = start.split(':').map(Number)
    const [endH, endM] = end.split(':').map(Number)

    let h = startH
    let m = startM

    while (h < endH || (h === endH && m < endM)) {
        slots.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`)
        m += 30
        if (m >= 60) { m = 0; h++ }
    }

    return slots
})

const filteredAppointments = computed(() => {
    if (!store.selectedManager) return store.dayAppointments
    return store.dayAppointments.filter(a => a.case_manager.id === store.selectedManager)
})

const sortedAppointments = computed(() => {
    return [...filteredAppointments.value].sort((a, b) => a.start_time.localeCompare(b.start_time))
})

const filteredClients = computed(() => {
    const map = new Map()
    filteredAppointments.value.forEach(a => {
        if (!map.has(a.client.id)) map.set(a.client.id, a.client)
    })
    return Array.from(map.values())
})

function clientAppointments(clientId) {
    return filteredAppointments.value.filter(a => a.client.id === clientId)
}

function getSlotAppointment(clientId, slot) {
    return filteredAppointments.value.filter(
        a => a.client.id === clientId && a.start_time === slot
    )
}

function onAction(emit) {
    if (emit === 'new') {
        if (!store.daySchedule?.is_working) return
        showForm.value = true
    }
}

function onApptClick(appt) {
    selectedAppt.value = appt
    showDetail.value = true
}

function onCreated() {
    showForm.value = false
}

function onStatusChanged() {
    showDetail.value = false
}

function isSlotAvailable(slot) {
    const found = store.availableSlots.find(s => s.time === slot)
    return found?.available === true
}

function syncScroll() {
    if (headerClients.value && timelineBody.value) {
        headerClients.value.scrollLeft = timelineBody.value.scrollLeft
    }
}
function onAppointmentUpdated(updated) {
    // Actualizar la cita seleccionada
    const idx = store.dayAppointments.findIndex(a => a.id === updated.id)
    if (idx !== -1) {
        store.dayAppointments[idx].start_time = updated.start_time
        store.dayAppointments[idx].end_time   = updated.end_time
        store.dayAppointments[idx].date       = updated.date
    }
    selectedAppt.value = { ...selectedAppt.value, ...updated }
    showDetail.value   = false
}

watch(date, (newDate) => {
    if (newDate) store.fetchDay(newDate)
})

onMounted(() => store.fetchDay(date.value))
</script>

<style scoped>
.day-view {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.day-view__body {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 16px 24px 24px;
    gap: 16px;
}

/* ── Filtro CM ── */
.day-cm-filter {
    display: flex;
    gap: 16px;
    align-items: center;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    overflow-x: auto;
    flex-shrink: 0;
}

.day-cm-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s, transform 0.2s;
    flex-shrink: 0;
}

.day-cm-item--active {
    opacity: 1;
}

.day-cm-item:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.day-cm-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: border-color 0.2s;
}

.day-cm-item--active .day-cm-avatar {
    border-color: #3b82f6;
}

.day-cm-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.day-cm-avatar--all {
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
}

.day-cm-name {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
}

/* ── Timeline wrapper ── */
.day-timeline-wrapper {
    flex: 1;
    overflow: hidden;
    border-radius: 18px;
    background: linear-gradient(135deg,
            rgba(180, 180, 160, 0.35) 0%,
            rgba(160, 180, 140, 0.25) 40%,
            rgba(200, 160, 140, 0.25) 70%,
            rgba(160, 140, 180, 0.25) 100%);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    flex-direction: column;
}

/* ── Header fijo clientes ── */
.day-timeline-header {
    display: flex;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(20, 30, 50, 0.7);
    backdrop-filter: blur(12px);
}

.day-timeline-header__spacer {
    width: 60px;
    flex-shrink: 0;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    left: 0;
    z-index: 3;
    background: rgba(20, 30, 50, 0.7);
}

.day-timeline-header__clients {
    display: flex;
    flex: 1;
    overflow-x: hidden;
}

.day-col-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 8px;
    width: 200px;
    flex-shrink: 0;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    gap: 4px;
}

.day-col-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.day-col-name {
    font-size: 0.8rem;
    font-weight: 700;
    color: #fff;
    text-align: center;
}

.day-col-count {
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.5);
}

/* ── Body scroll único ── */
.day-timeline-body {
    display: flex;
    flex: 1;
    overflow-y: auto;
    overflow-x: auto;
}

.day-timeline__hours {
    width: 60px;
    flex-shrink: 0;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    left: 0;
    z-index: 2;
    background: rgba(15, 25, 45, 0.85);
}

.day-timeline__hour {
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 600;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.day-timeline__clients {
    display: flex;
    flex: 1;
    min-width: max-content;
}

.day-timeline__client-col {
    width: 200px;
    flex-shrink: 0;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
}

.day-col-slots {
    flex: 1;
}

.day-slot {
    height: 56px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding: 2px 4px;
}

.day-slot--available {
    background: rgba(34, 197, 94, 0.08);
    border-left: 2px solid rgba(34, 197, 94, 0.3);
}

/* ── Cita ── */
.day-appt {
    border-radius: 8px;
    padding: 4px 8px;
    cursor: pointer;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: filter 0.15s, transform 0.15s;
}

.day-appt:hover {
    filter: brightness(1.1);
    transform: scale(1.01);
}

.day-appt--pending {
    background: rgba(245, 158, 11, 0.85);
    border-left: 3px solid #f59e0b;
}

.day-appt--confirmed {
    background: rgba(59, 130, 246, 0.85);
    border-left: 3px solid #3b82f6;
}

.day-appt--completed {
    background: rgba(34, 197, 94, 0.85);
    border-left: 3px solid #22c55e;
}

.day-appt--cancelled {
    background: rgba(239, 68, 68, 0.85);
    border-left: 3px solid #ef4444;
}

.day-appt__top {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow: hidden;
}

.day-appt__client {
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    flex-shrink: 0;
}

.day-appt__notes {
    font-size: 0.65rem;
    color: rgba(255, 255, 255, 0.85);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.day-appt__time {
    font-size: 0.62rem;
    color: rgba(255, 255, 255, 0.75);
    font-weight: 600;
}

/* ── Estados ── */
.day-loading {
    display: flex;
    justify-content: center;
    padding: 48px;
}

.day-spinner {
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

/* ── Day off ── */
.day-day-off {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    flex: 1;
    padding: 48px 24px;
}

.day-day-off__icon-wrap {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.12);
    border: 2px solid rgba(99, 102, 241, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: rgba(165, 180, 252, 0.8);
}

.day-day-off__title {
    font-size: 1.2rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

.day-day-off__reason {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.45);
    font-style: italic;
    margin: 0;
    text-align: center;
    max-width: 320px;
}

/* ── Empty ── */
.day-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 48px;
    color: rgba(255, 255, 255, 0.4);
}

.day-empty span {
    font-size: 3rem;
}

/* ── Lista mobile (tarjetas) ── */
.day-mobile-list {
    display: none;
    flex-direction: column;
    gap: 12px;
    overflow-y: auto;
    flex: 1;
    padding-bottom: 8px;
}

.day-mobile-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 14px;
    cursor: pointer;
    transition: filter 0.15s, transform 0.15s;
}

.day-mobile-card:active {
    transform: scale(0.98);
}

.day-mobile-card:hover {
    filter: brightness(1.05);
}

.day-mobile-card--pending {
    background: #d9962f;
}

.day-mobile-card--confirmed {
    background: #3b82f6;
}

.day-mobile-card--completed {
    background: #22c55e;
}

.day-mobile-card--cancelled {
    background: #e05c5c;
}

.day-mobile-card__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.35);
}

.day-mobile-card__info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
}

.day-mobile-card__name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.day-mobile-card__time {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 600;
}

.day-mobile-card__edit {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background 0.15s;
}

.day-mobile-card__edit:hover {
    background: rgba(255, 255, 255, 0.4);
}

/* ── Mobile ── */
@media (max-width: 767px) {
    .day-view__body {
        padding: 8px 12px 12px;
        gap: 8px;
    }

    .day-cm-filter {
        padding: 8px 12px;
        gap: 12px;
    }

    .day-cm-avatar {
        width: 40px;
        height: 40px;
    }

    .day-cm-name {
        font-size: 0.68rem;
    }

    /* En mobile ocultamos el timeline por horas y mostramos la lista de tarjetas */
    .day-timeline-wrapper {
        display: none;
    }

    .day-mobile-list {
        display: flex;
    }
}
</style>