<!-- resources/js/views/admin/AppointmentsView.vue -->
<template>
    <div class="appt-view">
        <AppTopbar
            :title="$t('appointments.title')"
            :crumbs="[
                { label: $t('users.crumbs.dashboard'), icon: 'fa-house',     route: 'admin.dashboard' },
                { label: $t('appointments.title'),      icon: 'fa-calendar-check' },
            ]"
            :actions="[]"
        />

        <div class="appt-view__body">
            <AppointmentCalendar @day-click="onDayClick" />
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAppointmentStore } from '@/stores/appointmentStore'
import AppointmentCalendar from '@/components/appointments/AppointmentCalendar.vue'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import { useRouter } from 'vue-router'
const router = useRouter()

const store = useAppointmentStore()



function onDayClick({ day, month, year }) {
    const date = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`
    router.push({ name: 'admin.appointments.day', params: { date } })
}

onMounted(() => { store.fetchCalendar(); store.subscribeRealtime() })
</script>

<style scoped>
.appt-view {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.appt-view__body {
    padding: 24px;
    flex: 1;
    overflow-y: auto;
}

@media (max-width: 767px) {
    .appt-view__body { padding: 12px; }
}
</style>