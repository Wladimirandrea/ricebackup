// resources/js/stores/dayOffStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/plugins/axios'

export const useDayOffStore = defineStore('dayOff', () => {
    const daysOff = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const error = ref(null)

    async function fetchDaysOff() {
        loading.value = true
        error.value = null
        try {
            const { data } = await api.get('/admin/days-off')
            daysOff.value = data.days_off ?? []  // ← agregar ?? []
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Error loading days off'
            daysOff.value = []  // ← agregar fallback
        } finally {
            loading.value = false
        }
    }

    async function createDayOff(payload) {
        saving.value = true
        try {
            const { data } = await api.post('/admin/days-off', payload)
            daysOff.value.push(data.day_off)
            daysOff.value.sort((a, b) => a.date.localeCompare(b.date))
            return { success: true }
        } catch (e) {
            return {
                success: false,
                errors: e.response?.data?.errors ?? {},
                message: e.response?.data?.message ?? 'Error saving',
            }
        } finally {
            saving.value = false
        }
    }

    async function deleteDayOff(id) {
        try {
            await api.delete(`/admin/days-off/${id}`)
            daysOff.value = daysOff.value.filter(d => d.id !== id)
            return { success: true }
        } catch (e) {
            return { success: false }
        }
    }

    return { daysOff, loading, saving, error, fetchDaysOff, createDayOff, deleteDayOff }
})