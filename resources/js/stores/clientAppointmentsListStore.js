import { defineStore } from 'pinia'
import api from '@/plugins/axios'

export const useClientAppointmentsListStore = defineStore('clientAppointmentsList', {
    state: () => ({
        appointments: [],
        counts: { all: 0, pending: 0, confirmed: 0, completed: 0, cancelled: 0 },
        status: 'all',
        loading: false,
        error: null,
    }),
    actions: {
        async fetchAppointments() {
            this.loading = true
            this.error = null
            try {
                const { data } = await api.get('/client/appointments/list', {
                    params: { status: this.status },
                })
                this.appointments = data.appointments
                this.counts = data.counts
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al cargar las citas.'
            } finally {
                this.loading = false
            }
        },

        async cancelAppointment(id) {
            try {
                await api.patch(`/client/appointments/${id}/status`, { status: 'cancelled' })
                await this.fetchAppointments()
                return { success: true }
            } catch (e) {
                return { success: false, message: e.response?.data?.message || 'Error al cancelar la cita.' }
            }
        },

        setStatus(status) {
            this.status = status
            this.fetchAppointments()
        },
    },
})