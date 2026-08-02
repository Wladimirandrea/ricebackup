// resources/js/stores/managerAppointmentStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/plugins/axios'
import { useNotificationStore } from '@/stores/notificationStore'
import echo from '@/plugins/echo'
import { useAuthStore } from '@/stores/auth'

export const useManagerAppointmentStore = defineStore('managerAppointment', () => {
    const calendar = ref({})
    const daysOff = ref({})
    const loading = ref(false)
    const error = ref(null)
    const currentMonth = ref(new Date().getMonth() + 1)
    const currentYear = ref(new Date().getFullYear())
    const selectedClientFilter = ref(null)

    const dayAppointments = ref([])
    const loadingDay = ref(false)
    const daySchedule = ref({ is_working: false, start_time: null, end_time: null })
    const isDayOff = ref(false)
    const dayOffInfo = ref(null)
    const availableSlots = ref([])

    const formSlots = ref([])
    const loadingSlots = ref(false)

    const myClients = ref([])

    let subscribedRealtime = false

    function subscribeRealtime() {
        if (subscribedRealtime) return
        subscribedRealtime = true

        const auth = useAuthStore()
        const user = auth.user
        if (!user) return

        echo
            .private(`manager.${user.id}`)
            .listen('.appointment.created', handleRealtimeCreated)
            .listen('.appointment.status-updated', handleRealtimeStatusUpdate)
    }

    function handleRealtimeCreated(data) {
        const appt = data.appointment ?? data
        const dateKey = appt.date
        if (dateKey) {
            if (!calendar.value[dateKey]) {
                calendar.value[dateKey] = { pending: 0, confirmed: 0, completed: 0, cancelled: 0, total: 0 }
            }
            if (calendar.value[dateKey][appt.status] !== undefined) calendar.value[dateKey][appt.status]++
            calendar.value[dateKey].total++
        }

        const idx = dayAppointments.value.findIndex(a => a.id === appt.id)
        if (idx === -1 && dateKey && dayAppointments.value[0]?.date === dateKey) {
            dayAppointments.value.push(appt)
            dayAppointments.value.sort((a, b) => a.start_time.localeCompare(b.start_time))
            const slotIdx = availableSlots.value.findIndex(s => s.time === appt.start_time)
            if (slotIdx !== -1) availableSlots.value[slotIdx].available = false
        }
    }

    function handleRealtimeStatusUpdate(data) {
        const dateKey = data.date
        if (calendar.value[dateKey]) {
            const prev = data.previous_status
            if (calendar.value[dateKey][prev] !== undefined) calendar.value[dateKey][prev]--
            if (calendar.value[dateKey][data.status] !== undefined) calendar.value[dateKey][data.status]++
        }

        const idx = dayAppointments.value.findIndex(a => a.id === data.id)
        if (idx === -1) return
        const appt = dayAppointments.value[idx]
        const prevStatus = appt.status
        appt.status = data.status

        if (data.status === 'cancelled') {
            const slotIdx = availableSlots.value.findIndex(s => s.time === appt.start_time)
            if (slotIdx !== -1) availableSlots.value[slotIdx].available = true
        }
        if (prevStatus === 'cancelled' && data.status !== 'cancelled') {
            const slotIdx = availableSlots.value.findIndex(s => s.time === appt.start_time)
            if (slotIdx !== -1) availableSlots.value[slotIdx].available = false
        }
    }

    async function fetchClients() {
        try {
            const { data } = await api.get('/manager/clients')
            myClients.value = data.data
        } catch { myClients.value = [] }
    }

    async function fetchCalendar(month = null, year = null) {
        loading.value = true
        error.value = null
        const m = month ?? currentMonth.value
        const y = year ?? currentYear.value
        try {
            const params = { month: m, year: y }
            if (selectedClientFilter.value) params.client_id = selectedClientFilter.value
            const { data } = await api.get('/manager/appointments/calendar', { params })
            calendar.value = data.calendar
            daysOff.value = data.days_off ?? {}
            currentMonth.value = data.month
            currentYear.value = data.year
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Error loading calendar'
        } finally {
            loading.value = false
        }
    }

    function prevMonth() {
        if (currentMonth.value === 1) { currentMonth.value = 12; currentYear.value-- }
        else currentMonth.value--
        fetchCalendar()
    }

    function nextMonth() {
        if (currentMonth.value === 12) { currentMonth.value = 1; currentYear.value++ }
        else currentMonth.value++
        fetchCalendar()
    }

    function setClientFilter(clientId) {
        selectedClientFilter.value = clientId
        fetchCalendar()
    }

    async function fetchDay(date) {
        loadingDay.value = true
        try {
            const params = { date }
            if (selectedClientFilter.value) params.client_id = selectedClientFilter.value
            const { data } = await api.get('/manager/appointments/day', { params })
            dayAppointments.value = data.appointments
            daySchedule.value = data.schedule
            isDayOff.value = data.is_day_off
            dayOffInfo.value = data.day_off_info
            availableSlots.value = data.available_slots ?? []
        } catch {
            dayAppointments.value = []
            daySchedule.value = { is_working: false, start_time: null, end_time: null }
            isDayOff.value = false
            dayOffInfo.value = null
        } finally {
            loadingDay.value = false
        }
    }

    async function fetchSlots(date) {
        loadingSlots.value = true
        try {
            const { data } = await api.get('/manager/appointments/slots', { params: { date } })
            formSlots.value = data.slots ?? []
        } catch {
            formSlots.value = []
        } finally {
            loadingSlots.value = false
        }
    }

    async function createAppointment(payload) {
        try {
            const { data } = await api.post('/manager/appointments', payload)
            dayAppointments.value.push(data.appointment)
            dayAppointments.value.sort((a, b) => a.start_time.localeCompare(b.start_time))

            const key = payload.date
            if (!calendar.value[key]) calendar.value[key] = { pending: 0, confirmed: 0, completed: 0, cancelled: 0, total: 0 }
            calendar.value[key][payload.status]++
            calendar.value[key].total++

            const slotIdx = availableSlots.value.findIndex(s => s.time === payload.start_time)
            if (slotIdx !== -1) availableSlots.value[slotIdx].available = false

            return { success: true, appointment: data.appointment }
        } catch (e) {
            return { success: false, message: e.response?.data?.message ?? 'Error creating appointment' }
        }
    }

    async function updateStatus(id, status) {
        try {
            await api.patch(`/manager/appointments/${id}/status`, { status })
            const idx = dayAppointments.value.findIndex(a => a.id === id)
            if (idx !== -1) {
                const appt = dayAppointments.value[idx]
                const prevStatus = appt.status
                appt.status = status

                // actualizar contadores del calendario mensual
                const dateKey = appt.date
                if (dateKey && calendar.value[dateKey]) {
                    if (calendar.value[dateKey][prevStatus] !== undefined) calendar.value[dateKey][prevStatus]--
                    if (calendar.value[dateKey][status] !== undefined) calendar.value[dateKey][status]++
                }

                if (status === 'cancelled') {
                    const slotIdx = availableSlots.value.findIndex(s => s.time === appt.start_time)
                    if (slotIdx !== -1) availableSlots.value[slotIdx].available = true
                }
                if (prevStatus === 'cancelled' && status !== 'cancelled') {
                    const slotIdx = availableSlots.value.findIndex(s => s.time === appt.start_time)
                    if (slotIdx !== -1) availableSlots.value[slotIdx].available = false
                }
            }
            return { success: true }
        } catch {
            return { success: false }
        }
    }

    async function updateAppointment(id, payload) {
        try {
            const { data } = await api.put(`/manager/appointments/${id}`, payload)
            const idx = dayAppointments.value.findIndex(a => a.id === id)
            if (idx !== -1) {
                dayAppointments.value[idx].start_time = data.appointment.start_time
                dayAppointments.value[idx].end_time = data.appointment.end_time
                dayAppointments.value[idx].date = data.appointment.date
            }
            return { success: true, appointment: data.appointment }
        } catch (e) {
            return { success: false, message: e.response?.data?.message ?? 'Error updating' }
        }
    }

    return {
        calendar, daysOff, loading, error, currentMonth, currentYear,
        selectedClientFilter, myClients,
        dayAppointments, loadingDay, daySchedule, isDayOff, dayOffInfo, availableSlots,
        formSlots, loadingSlots,
        fetchClients, fetchCalendar, prevMonth, nextMonth, setClientFilter,
        fetchDay, fetchSlots, createAppointment, updateStatus, updateAppointment,
        subscribeRealtime,
    }
})