// resources/js/stores/scheduleStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/plugins/axios'

export const useScheduleStore = defineStore('schedule', () => {
    const schedules = ref([])
    const loading = ref(false)
    const error = ref(null)

    const daysOff = ref([])
    const loadingDays = ref(false)

    async function fetchSchedule() {
        loading.value = true
        error.value = null
        try {
            const { data } = await api.get('/admin/schedule')
            schedules.value = data.data
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Error loading schedule'
        } finally {
            loading.value = false
        }
    }

    async function updateDay(schedule) {
        try {
            const { data } = await api.put(`/admin/schedule/${schedule.id}`, {
                is_working: schedule.is_working,
                start_time: schedule.start_time,
                end_time: schedule.end_time,
            })
            // Actualizar en el array local
            const idx = schedules.value.findIndex(s => s.id === schedule.id)
            if (idx !== -1) schedules.value[idx] = data.data
            return { success: true }
        } catch (e) {
            return {
                success: false,
                errors: e.response?.data?.errors ?? {},
                message: e.response?.data?.message ?? 'Error saving',
            }
        }
    }

    async function fetchDaysOff(userId) {
        loadingDays.value = true
        try {
            const { data } = await api.get(`/api/admin/days-off/${userId}`)
            daysOff.value = data.days_off
        } finally {
            loadingDays.value = false
        }
    }

    async function createDayOff(userId, payload) {
        try {
            const { data } = await api.post(`/api/admin/days-off/${userId}`, payload)
            daysOff.value.push(data.day_off)
            daysOff.value.sort((a, b) => a.date.localeCompare(b.date))
            return { success: true }
        } catch (e) {
            return { success: false, errors: e.response?.data?.errors ?? {} }
        }
    }

    async function updateDayOff(userId, dayOffId, payload) {
        try {
            const { data } = await api.put(`/api/admin/days-off/${userId}/${dayOffId}`, payload)
            const idx = daysOff.value.findIndex(d => d.id === dayOffId)
            if (idx !== -1) daysOff.value[idx] = data.day_off
            return { success: true }
        } catch (e) {
            return { success: false, errors: e.response?.data?.errors ?? {} }
        }
    }

    async function deleteDayOff(userId, dayOffId) {
        try {
            await api.delete(`/api/admin/days-off/${userId}/${dayOffId}`)
            daysOff.value = daysOff.value.filter(d => d.id !== dayOffId)
            return { success: true }
        } catch (e) {
            return { success: false }
        }
    }

    return { schedules, loading, error, fetchSchedule, updateDay }
})