<!-- resources/js/views/manager/ManagerCalendarView.vue -->
<template>
    <div class="mc-view">
        <AppTopbar :title="$t('appointments.title')" :crumbs="[
            { label: $t('users.crumbs.dashboard'), icon: 'fa-house', route: 'manager.dashboard' },
            { label: $t('appointments.title'), icon: 'fa-calendar-check' },
        ]" :actions="[
                { label: $t('appointments.new'), icon: 'fa-plus', type: 'primary', emit: 'new' }
            ]" @action="onTopbarAction" />

        <div class="mc-view__body">
            <!-- Filtro clientes -->
            <div class="mc-client-filter">
                <div class="mc-client-item" :class="{ 'mc-client-item--active': store.selectedClientFilter === null }"
                    @click="store.setClientFilter(null)">
                    <div class="mc-client-avatar mc-client-avatar--all">
                        <i class="fa-solid fa-users" />
                    </div>
                    <span class="mc-client-name">{{ $t('appointments.all') }}</span>
                </div>

                <div v-for="client in store.myClients" :key="client.id" class="mc-client-item"
                    :class="{ 'mc-client-item--active': store.selectedClientFilter === client.id }"
                    @click="store.setClientFilter(client.id)">
                    <div class="mc-client-avatar">
                        <img :src="client.profile_image_url ?? defaultAvatar" />
                    </div>
                    <span class="mc-client-name">{{ client.name.split(' ')[0] }}</span>
                </div>
            </div>

            <!-- Calendario -->
            <ManagerAppointmentCalendar @day-click="onDayClick" />
        </div>

        <!-- Modal nueva cita -->
        <ManagerAppointmentFormModal v-model="showModal" :date="modalDate" @created="onAppointmentCreated" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useManagerAppointmentStore } from '@/stores/managerAppointmentStore'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import ManagerAppointmentCalendar from '@/components/appointments/ManagerAppointmentCalendar.vue'
import ManagerAppointmentFormModal from '@/components/appointments/ManagerAppointmentFormModal.vue'

const store = useManagerAppointmentStore()
const router = useRouter()
const defaultAvatar = 'https://ui-avatars.com/api/?name=C&background=1565c0&color=fff'

const showModal = ref(false)
const modalDate = ref('')

function onImgError(e) {
    e.target.src = defaultAvatar
}

function onTopbarAction(action) {
    if (action === 'new') {
        modalDate.value = ''
        showModal.value = true
    }
}

function onDayClick({ day, month, year }) {
    const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    router.push({ name: 'manager.appointments.day', params: { date } })
}

function onAppointmentCreated() {
    showModal.value = false
    store.fetchCalendar()
}

onMounted(async () => { await store.fetchClients(); await store.fetchCalendar(); store.subscribeRealtime() })
</script>

<style scoped>
.mc-view {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}

.mc-view__body {
    flex: 1;
    overflow-y: auto;
    padding: 12px 20px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 0;
}

/* Filtro clientes */
.mc-client-filter {
    display: flex;
    gap: 14px;
    align-items: center;
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    overflow-x: auto;
    flex-shrink: 0;
}

.mc-client-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s, transform 0.2s;
    flex-shrink: 0;
}

.mc-client-item--active {
    opacity: 1;
}

.mc-client-item:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.mc-client-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: border-color 0.2s;
}

.mc-client-item--active .mc-client-avatar {
    border-color: #3b82f6;
}

.mc-client-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mc-client-avatar--all {
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #fff;
}

.mc-client-name {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
}

@media (max-width: 767px) {
    .mc-view__body {
        padding: 10px;
    }
}
</style>