<!-- resources/js/views/manager/ManagerAppointmentDayView.vue -->
<template>
    <div class="day-view">
        <AppTopbar
            :title="formattedDate"
            :crumbs="[
                { label: $t('users.crumbs.dashboard'),  icon: 'fa-house',         route: 'manager.dashboard' },
                { label: $t('appointments.title'),       icon: 'fa-calendar-check',route: 'manager.appointments' },
                { label: formattedDate,                  icon: 'fa-calendar-day' },
            ]"
            :actions="store.isDayOff ? [] : [{ label: $t('appointments.new'), icon: 'fa-plus', type: 'primary', emit: 'new' }]"
            @action="onAction"
        />

        <div class="day-view__body">
            <!-- Loading -->
            <div v-if="store.loadingDay" class="day-loading">
                <div class="day-spinner" />
            </div>

            <!-- Día no laborable -->
            <div v-else-if="!store.daySchedule?.is_working" class="day-day-off">
                <span>🌙</span>
                <p>{{ $t('appointments.dayOff') }}</p>
            </div>

            <!-- Day off registrado -->
            <div v-else-if="store.isDayOff" class="day-day-off-card">
                <div class="day-day-off-card__icon">🌙</div>
                <div class="day-day-off-card__info">
                    <h3 class="day-day-off-card__title">{{ $t('appointments.dayOffTitle') }}</h3>
                    <p v-if="store.dayOffInfo?.reason" class="day-day-off-card__reason">{{ store.dayOffInfo.reason }}</p>
                    <p class="day-day-off-card__time">{{ store.dayOffInfo?.start_time }} — {{ store.dayOffInfo?.end_time }}</p>
                </div>
            </div>

            <!-- Sin citas -->
            <div v-else-if="store.dayAppointments.length === 0" class="day-empty">
                <span>📅</span>
                <p>{{ $t('appointments.noAppointments') }}</p>
            </div>

            <!-- Timeline (desktop/tablet) -->
            <div v-else class="day-timeline-wrapper">
                <div class="day-timeline-header">
                    <div class="day-timeline-header__spacer" />
                    <div class="day-timeline-header__clients">
                        <div v-for="client in uniqueClients" :key="client.id" class="day-col-header">
                            <img :src="client.profile_image || defaultAvatar" :alt="client.name" class="day-col-avatar" />
                            <span class="day-col-name">{{ client.name }}</span>
                            <span class="day-col-count">{{ clientAppts(client.id).length }} {{ $t('appointments.apptCount') }}</span>
                        </div>
                    </div>
                </div>

                <div class="day-timeline-body">
                    <div class="day-timeline__hours">
                        <div v-for="slot in timeSlots" :key="slot" class="day-timeline__hour">{{ slot }}</div>
                    </div>
                    <div class="day-timeline__clients">
                        <div v-for="client in uniqueClients" :key="client.id" class="day-timeline__client-col">
                            <div class="day-col-slots">
                                <div
                                    v-for="slot in timeSlots"
                                    :key="slot"
                                    class="day-slot"
                                    :class="{ 'day-slot--available': isSlotAvailable(slot) }"
                                >
                                    <div
                                        v-for="appt in getSlotAppt(client.id, slot)"
                                        :key="appt.id"
                                        class="day-appt"
                                        :class="`day-appt--${appt.status}`"
                                        @click="onApptClick(appt)"
                                    >
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
            <div
                v-if="!store.loadingDay && store.daySchedule?.is_working && !store.isDayOff && store.dayAppointments.length > 0"
                class="day-mobile-list"
            >
                <div
                    v-for="appt in sortedAppointments"
                    :key="appt.id"
                    class="day-mobile-card"
                    :class="`day-mobile-card--${appt.status}`"
                    @click="onApptClick(appt)"
                >
                    <img :src="appt.client.profile_image || defaultAvatar" :alt="appt.client.name" class="day-mobile-card__avatar" />
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

        <!-- Modal nueva cita -->
        <ManagerAppointmentFormModal v-model="showForm" :date="date" @created="showForm = false" />

        <!-- Modal detalle -->
        <ManagerAppointmentDetailModal
            v-model="showDetail"
            :appointment="selectedAppt"
            @status-changed="showDetail = false"
            @appointment-updated="onApptUpdated"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useManagerAppointmentStore } from '@/stores/managerAppointmentStore'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import ManagerAppointmentFormModal   from '@/components/appointments/ManagerAppointmentFormModal.vue'
import ManagerAppointmentDetailModal from '@/components/appointments/ManagerAppointmentDetailModal.vue'

const { locale } = useI18n()
const route = useRoute()
const store = useManagerAppointmentStore()

const defaultAvatar = 'https://ui-avatars.com/api/?name=C&background=1565c0&color=fff'
const showForm    = ref(false)
const showDetail  = ref(false)
const selectedAppt = ref(null)

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
    const end   = store.daySchedule.end_time   ?? '17:00'
    const [sh, sm] = start.split(':').map(Number)
    const [eh, em] = end.split(':').map(Number)
    let h = sh, m = sm
    while (h < eh || (h === eh && m < em)) {
        slots.push(`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`)
        m += 30
        if (m >= 60) { m = 0; h++ }
    }
    return slots
})

const uniqueClients = computed(() => {
    const map = new Map()
    store.dayAppointments.forEach(a => { if (!map.has(a.client.id)) map.set(a.client.id, a.client) })
    return Array.from(map.values())
})

const sortedAppointments = computed(() => {
    return [...store.dayAppointments].sort((a, b) => a.start_time.localeCompare(b.start_time))
})

function clientAppts(clientId) {
    return store.dayAppointments.filter(a => a.client.id === clientId)
}
function getSlotAppt(clientId, slot) {
    return store.dayAppointments.filter(a => a.client.id === clientId && a.start_time === slot)
}
function isSlotAvailable(slot) {
    const found = store.availableSlots.find(s => s.time === slot)
    return found?.available === true
}
function onAction(emit) {
    if (emit === 'new') showForm.value = true
}
function onApptClick(appt) {
    selectedAppt.value = appt
    showDetail.value   = true
}
function onApptUpdated(updated) {
    const idx = store.dayAppointments.findIndex(a => a.id === updated.id)
    if (idx !== -1) {
        store.dayAppointments[idx].start_time = updated.start_time
        store.dayAppointments[idx].end_time   = updated.end_time
    }
    showDetail.value = false
}

onMounted(() => { store.fetchDay(date.value); store.subscribeRealtime() })
</script>

<style scoped>
/* Mismos estilos que AppointmentDayView.vue */
.day-view { display: flex; flex-direction: column; height: 100%; }
.day-view__body { flex: 1; overflow: hidden; display: flex; flex-direction: column; padding: 16px 24px 24px; gap: 16px; }
.day-timeline-wrapper { flex: 1; overflow: hidden; border-radius: 18px; background: linear-gradient(135deg, rgba(180,180,160,0.35) 0%, rgba(160,180,140,0.25) 40%, rgba(200,160,140,0.25) 70%, rgba(160,140,180,0.25) 100%); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); display: flex; flex-direction: column; }
.day-timeline-header { display: flex; flex-shrink: 0; border-bottom: 1px solid rgba(255,255,255,0.15); background: rgba(20,30,50,0.7); backdrop-filter: blur(12px); }
.day-timeline-header__spacer { width: 60px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); }
.day-timeline-header__clients { display: flex; flex: 1; overflow-x: hidden; }
.day-col-header { display: flex; flex-direction: column; align-items: center; padding: 12px 8px; min-width: 200px; flex: 1; border-right: 1px solid rgba(255,255,255,0.08); gap: 4px; }
.day-col-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
.day-col-name  { font-size: 0.8rem; font-weight: 700; color: #fff; text-align: center; }
.day-col-count { font-size: 0.68rem; color: rgba(255,255,255,0.5); }
.day-timeline-body { display: flex; flex: 1; overflow-y: auto; overflow-x: auto; }
.day-timeline__hours { width: 60px; flex-shrink: 0; border-right: 1px solid rgba(255,255,255,0.1); }
.day-timeline__hour { height: 56px; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; color: rgba(255,255,255,0.6); font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.06); }
.day-timeline__clients { display: flex; flex: 1; }
.day-timeline__client-col { min-width: 200px; flex: 1; border-right: 1px solid rgba(255,255,255,0.08); }
.day-col-slots { flex: 1; }
.day-slot { height: 56px; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 2px 4px; }
.day-slot--available { background: rgba(34,197,94,0.08); border-left: 2px solid rgba(34,197,94,0.3); }
.day-appt { border-radius: 8px; padding: 4px 8px; cursor: pointer; height: 100%; display: flex; flex-direction: column; justify-content: space-between; transition: filter 0.15s, transform 0.15s; }
.day-appt:hover { filter: brightness(1.1); transform: scale(1.01); }
.day-appt--pending   { background: rgba(245,158,11,0.85); border-left: 3px solid #f59e0b; }
.day-appt--confirmed { background: rgba(59,130,246,0.85); border-left: 3px solid #3b82f6; }
.day-appt--completed { background: rgba(34,197,94,0.85);  border-left: 3px solid #22c55e; }
.day-appt--cancelled { background: rgba(239,68,68,0.85);  border-left: 3px solid #ef4444; }
.day-appt__top { display: flex; align-items: center; gap: 6px; overflow: hidden; }
.day-appt__client { font-size: 0.75rem; font-weight: 700; color: #fff; white-space: nowrap; flex-shrink: 0; }
.day-appt__notes  { font-size: 0.65rem; color: rgba(255,255,255,0.85); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.day-appt__time   { font-size: 0.62rem; color: rgba(255,255,255,0.75); font-weight: 600; }
.day-loading { display: flex; justify-content: center; padding: 48px; }
.day-spinner { width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.day-day-off, .day-empty { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 64px; color: rgba(255,255,255,0.4); font-size: 1rem; }
.day-day-off span, .day-empty span { font-size: 3rem; }
.day-day-off-card { display: flex; align-items: center; gap: 20px; padding: 32px; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-radius: 18px; }
.day-day-off-card__icon { font-size: 3rem; }
.day-day-off-card__info { display: flex; flex-direction: column; gap: 6px; }
.day-day-off-card__title  { font-size: 1.1rem; font-weight: 700; color: #fff; margin: 0; }
.day-day-off-card__reason { font-size: 0.9rem; color: rgba(255,255,255,0.7); margin: 0; }
.day-day-off-card__time   { font-size: 0.82rem; color: rgba(239,68,68,0.9); font-weight: 600; margin: 0; }

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

.day-mobile-card:active { transform: scale(0.98); }
.day-mobile-card:hover  { filter: brightness(1.05); }

.day-mobile-card--pending   { background: #d9962f; }
.day-mobile-card--confirmed { background: #3b82f6; }
.day-mobile-card--completed { background: #22c55e; }
.day-mobile-card--cancelled { background: #e05c5c; }

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

.day-mobile-card__edit:hover { background: rgba(255, 255, 255, 0.4); }

@media (max-width: 767px) {
    .day-view__body { padding: 8px 12px 12px; gap: 8px; }
    .day-col-header { flex-direction: row; padding: 8px 10px; gap: 8px; min-width: 160px; align-items: center; justify-content: flex-start; }
    .day-col-avatar { width: 32px; height: 32px; flex-shrink: 0; }
    .day-col-name   { font-size: 0.75rem; text-align: left; }
    .day-col-count  { font-size: 0.62rem; margin-left: auto; }
    .day-timeline-header__spacer { width: 50px; }
    .day-timeline__hours { width: 50px; }
    .day-timeline__hour  { font-size: 0.65rem; }
    .day-slot { height: 48px; }

    /* En mobile ocultamos el timeline por horas y mostramos la lista de tarjetas */
    .day-timeline-wrapper { display: none; }
    .day-mobile-list { display: flex; }
}
</style>