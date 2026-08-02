// resources/js/stores/notificationStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import echo from '@/plugins/echo'
import { useAuthStore } from '@/stores/auth'

export const useNotificationStore = defineStore('notifications', () => {
    const stored = localStorage.getItem('app_notifications')
    const notifications = ref(stored ? JSON.parse(stored) : [])

    const unreadCount = computed(() =>
        notifications.value.filter(n => !n.read).length
    )

    function save() {
        localStorage.setItem(
            'app_notifications',
            JSON.stringify(notifications.value.slice(0, 50))
        )
    }

    // ── Sonido ──────────────────────────────────────────────
    function playSound() {
        try {
            const audio = new Audio('/sounds/notification.mp3')
            audio.volume = 0.6
            audio.play().catch(() => {})
        } catch (_) {}
    }

    // ── Agregar notificación ────────────────────────────────
    function add({ type, clientName, caseManagerName, date, time, status }) {
        notifications.value.unshift({
            id:              Date.now(),
            type,
            clientName,
            caseManagerName,
            date,
            time,
            status,
            read:            false,
            createdAt:       new Date().toISOString(),
        })
        save()
        playSound()
    }

    function markAllRead() {
        notifications.value.forEach(n => (n.read = true))
        save()
    }

    function markRead(id) {
        const n = notifications.value.find(n => n.id === id)
        if (n) { n.read = true; save() }
    }

    function clear() {
        notifications.value = []
        save()
    }

    // ── Handler: cita creada ─────────────────────────────────
    function handleAppointmentCreated(data) {
        const appt = data.appointment ?? data
        add({
            type:            'created',
            clientName:      appt.client?.name        ?? '—',
            caseManagerName: appt.case_manager?.name  ?? '—',
            date:            appt.date                ?? '—',
            time:            appt.start_time          ?? '—',
            status:          appt.status               ?? 'pending',
        })
    }

    // ── Handler: status actualizado ──────────────────────────
    function handleAppointmentStatusUpdated(data) {
        add({
            type:            data.status === 'cancelled' ? 'cancelled' : 'status_changed',
            clientName:      data.client?.name        ?? '—',
            caseManagerName: data.case_manager?.name  ?? '—',
            date:            data.date                ?? '—',
            time:            data.start_time          ?? '—',
            status:          data.status               ?? 'pending',
        })
    }

    // ── Suscripción Reverb por rol ──────────────────────────
    let subscribed = false

    function subscribeReverb() {
        if (subscribed) return
        subscribed = true

        const auth = useAuthStore()
        const user = auth.user

        if (!user) return

        if (user.role === 'admin') {
            echo
                .channel('appointments')
                .listen('.appointment.created', handleAppointmentCreated)
                .listen('.appointment.status-updated', handleAppointmentStatusUpdated)

        } else if (user.role === 'case_manager') {
            echo
                .private(`manager.${user.id}`)
                .listen('.appointment.created', handleAppointmentCreated)
                .listen('.appointment.status-updated', handleAppointmentStatusUpdated)

        } else if (user.role === 'client') {
            echo
                .private(`client.${user.id}`)
                .listen('.appointment.created', handleAppointmentCreated)
                .listen('.appointment.status-updated', handleAppointmentStatusUpdated)
        }
    }

    function unsubscribeReverb() {
        const auth = useAuthStore()
        const user = auth.user

        if (!user) return

        if (user.role === 'admin') {
            echo.leaveChannel('appointments')
        } else if (user.role === 'case_manager') {
            echo.leaveChannel(`private-manager.${user.id}`)
        } else if (user.role === 'client') {
            echo.leaveChannel(`private-client.${user.id}`)
        }

        subscribed = false
    }

    return {
        notifications,
        unreadCount,
        add,
        markAllRead,
        markRead,
        clear,
        subscribeReverb,
        unsubscribeReverb,
    }
})