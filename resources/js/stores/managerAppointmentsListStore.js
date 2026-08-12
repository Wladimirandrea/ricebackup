import { defineStore } from 'pinia'
import api from '@/plugins/axios'   // ← antes: import axios from 'axios'

export const useManagerAppointmentsListStore = defineStore('managerAppointmentsList', {
    state: () => ({
        appointments: [],
        counts: { all: 0, pending: 0, confirmed: 0, completed: 0, cancelled: 0 },
        status: 'all',
        search: '',
        loading: false,
        error: null,
    }),
    actions: {
        async fetchAppointments() {
            this.loading = true
            this.error = null
            try {
                const { data } = await api.get('/manager/appointments/list', {   // ← sin /api
                    params: {
                        status: this.status,
                        search: this.search || undefined,
                    },
                })
                this.appointments = data.appointments
                this.counts = data.counts
            } catch (e) {
                this.error = e.response?.data?.message || 'Error al cargar las citas.'
            } finally {
                this.loading = false
            }
        },
        setStatus(status) {
            this.status = status
            this.fetchAppointments()
        },
    },
})