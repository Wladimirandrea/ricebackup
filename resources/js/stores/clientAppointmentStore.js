import { defineStore } from 'pinia'
import api from '@/plugins/axios'

export const useClientAppointmentStore = defineStore('clientAppointment', {
    state: () => ({
        calendar: {},
        dayAppointments: [],
        caseManager: null,
        loadingCalendar: false,
        loadingDay: false,
        loadingCaseManager: false,
    }),
    actions: {
        async fetchCalendar(month, year) {
            this.loadingCalendar = true
            try {
                const { data } = await api.get('/client/appointments/calendar', { params: { month, year } })
                this.calendar = data.calendar
            } finally {
                this.loadingCalendar = false
            }
        },
        async fetchDay(date) {
            this.loadingDay = true
            try {
                const { data } = await api.get('/client/appointments/day', { params: { date } })
                this.dayAppointments = data.appointments
            } finally {
                this.loadingDay = false
            }
        },
        async fetchCaseManager() {
            this.loadingCaseManager = true
            try {
                const { data } = await api.get('/client/case-manager')
                this.caseManager = data.case_manager
            } finally {
                this.loadingCaseManager = false
            }
        },
    },
})