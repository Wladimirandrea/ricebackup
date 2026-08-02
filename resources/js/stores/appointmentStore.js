// resources/js/stores/appointmentStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/plugins/axios'
import echo from '@/plugins/echo'
import { useAuthStore } from '@/stores/auth'


export const useAppointmentStore = defineStore('appointment', () => {
    // ── Calendar ──────────────────────────────────────────
    const calendar = ref({})
    const loading = ref(false)
    const error = ref(null)
    const currentMonth = ref(new Date().getMonth() + 1)
    const currentYear = ref(new Date().getFullYear())
    const daysOff = ref({})
    const availableSlots = ref([])

    const formSlots = ref([])
    const loadingSlots = ref(false)

    // ── Day view ──────────────────────────────────────────
    const dayAppointments = ref([])
    const dayCaseManagers = ref([])
    const selectedDate = ref(null)
    const loadingDay = ref(false)
    const selectedManager = ref(null)
    const daySchedule = ref({ is_working: false, start_time: null, end_time: null })

    // ── Realtime ────────────────────────────────────────────
    let subscribedRealtime = false

    function subscribeRealtime() {
        if (subscribedRealtime) return
        subscribedRealtime = true

        const auth = useAuthStore()
        const user = auth.user
        if (!user) return

        echo
            .channel('appointments')
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

        if (selectedDate.value && dateKey === selectedDate.value) {
            const exists = dayAppointments.value.some(a => a.id === appt.id)
            if (!exists) {
                dayAppointments.value.push(appt)
                dayAppointments.value.sort((a, b) => a.start_time.localeCompare(b.start_time))
                const slotIdx = availableSlots.value.findIndex(s => s.time === appt.start_time)
                if (slotIdx !== -1) availableSlots.value[slotIdx].available = false

                const newCM = appt.case_manager
                if (newCM) {
                    const alreadyExists = dayCaseManagers.value.some(cm => cm.id === newCM.id)
                    if (!alreadyExists) dayCaseManagers.value.push(newCM)
                }
            }
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

    // ── Calendar actions ──────────────────────────────────
    async function fetchCalendar(month = null, year = null) {
        loading.value = true
        error.value = null
        const m = month ?? currentMonth.value
        const y = year ?? currentYear.value
        try {
            const { data } = await api.get('/admin/appointments/calendar', {
                params: { month: m, year: y }
            })
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
        if (currentMonth.value === 1) {
            currentMonth.value = 12
            currentYear.value--
        } else {
            currentMonth.value--
        }
        fetchCalendar()
    }

    function nextMonth() {
        if (currentMonth.value === 12) {
            currentMonth.value = 1
            currentYear.value++
        } else {
            currentMonth.value++
        }
        fetchCalendar()
    }

    // ── Day actions ───────────────────────────────────────
    async function fetchDay(date) {
        loadingDay.value = true
        selectedDate.value = date
        selectedManager.value = null
        try {
            const { data } = await api.get('/admin/appointments/day', { params: { date } })
            dayAppointments.value = data.appointments
            dayCaseManagers.value = data.case_managers
            daySchedule.value = data.schedule
            availableSlots.value = data.available_slots ?? []
        } catch (e) {
            dayAppointments.value = []
            dayCaseManagers.value = []
            availableSlots.value = []
            daySchedule.value = { is_working: false, start_time: null, end_time: null }

        } finally {
            loadingDay.value = false
        }
    }

    async function createAppointment(payload) {
        try {
            const { data } = await api.post('/admin/appointments', payload)
            dayAppointments.value.push(data.appointment)
            dayAppointments.value.sort((a, b) => a.start_time.localeCompare(b.start_time))

            const key = payload.date
            if (!calendar.value[key]) {
                calendar.value[key] = { pending: 0, confirmed: 0, completed: 0, cancelled: 0, total: 0 }
            }
            calendar.value[key][payload.status]++
            calendar.value[key].total++

            const slotIdx = availableSlots.value.findIndex(s => s.time === payload.start_time)
            if (slotIdx !== -1) availableSlots.value[slotIdx].available = false

            const newCM = data.appointment.case_manager
            const alreadyExists = dayCaseManagers.value.some(cm => cm.id === newCM.id)
            if (!alreadyExists) dayCaseManagers.value.push(newCM)



            return { success: true, appointment: data.appointment }
        } catch (e) {
            return {
                success: false,
                message: e.response?.data?.message ?? 'Error creating appointment',
            }
        }
    }

    async function updateStatus(id, status) {
        try {
            await api.patch(`/admin/appointments/${id}/status`, { status })
            const idx = dayAppointments.value.findIndex(a => a.id === id)
            if (idx !== -1) {
                const appt = dayAppointments.value[idx]
                const prevStatus = appt.status
                appt.status = status

                // actualizar contadores del calendario mensual
                const dateKey = appt.date ?? selectedDate.value
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
            const { data } = await api.put(`/admin/appointments/${id}`, payload)
            const idx = dayAppointments.value.findIndex(a => a.id === id)
            if (idx !== -1) {
                dayAppointments.value[idx].start_time = data.appointment.start_time
                dayAppointments.value[idx].end_time = data.appointment.end_time
                dayAppointments.value[idx].date = data.appointment.date
            }
            return { success: true, appointment: data.appointment }
        } catch (e) {
            return {
                success: false,
                message: e.response?.data?.message ?? 'Error updating appointment',
            }
        }
    }

    async function fetchSlots(date, caseManagerId) {
        if (!date || !caseManagerId) {
            formSlots.value = []
            return
        }
        loadingSlots.value = true
        try {
            const { data } = await api.get('/admin/appointments/slots', {
                params: { date, case_manager_id: caseManagerId }
            })
            formSlots.value = data.slots ?? []
        } catch {
            formSlots.value = []
        } finally {
            loadingSlots.value = false
        }
    }


    return {
        calendar, loading, error,
        currentMonth, currentYear,
        fetchCalendar, prevMonth, nextMonth,
        dayAppointments, dayCaseManagers, selectedDate,
        loadingDay, selectedManager, daySchedule,
        fetchDay, createAppointment, updateStatus, updateAppointment,
        daysOff, availableSlots, formSlots, loadingSlots, fetchSlots,
        subscribeRealtime,
    }
})