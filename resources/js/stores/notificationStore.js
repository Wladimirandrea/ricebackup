// resources/js/stores/notificationStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import echo from '@/plugins/echo'
import { useAuthStore } from '@/stores/auth'

export const useNotificationStore = defineStore('notifications', () => {
    // ── Estado ──────────────────────────────────────────────
    function loadStored() {
        try {
            const stored = localStorage.getItem('app_notifications')
            return stored ? JSON.parse(stored) : []
        } catch (_) {
            return []
        }
    }

    const notifications = ref(loadStored())

    const unreadCount = computed(() =>
        notifications.value.filter(n => !n.read).length
    )

    function save() {
        try {
            localStorage.setItem(
                'app_notifications',
                JSON.stringify(notifications.value.slice(0, 50))
            )
        } catch (_) {}
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
    let idCounter = 0

    function add({ type, clientName, caseManagerName, date, time, status }) {
        notifications.value.unshift({
            id:              `${Date.now()}-${idCounter++}`, // evita ids repetidos
            type,
            clientName,
            caseManagerName,
            date,
            time,
            status,
            read:            false,
            createdAt:       new Date().toISOString(),
        })
        // Mantener solo las 50 más recientes también en memoria
        if (notifications.value.length > 50) {
            notifications.value.length = 50
        }
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
            status:          appt.status              ?? 'pending',
        })
    }

    // ── Handler: status actualizado ──────────────────────────
    function handleAppointmentStatusUpdated(data) {
        const appt = data.appointment ?? data
        add({
            type:            appt.status === 'cancelled' ? 'cancelled' : 'status_changed',
            clientName:      appt.client?.name        ?? '—',
            caseManagerName: appt.case_manager?.name  ?? '—',
            date:            appt.date                ?? '—',
            time:            appt.start_time          ?? '—',
            status:          appt.status              ?? 'pending',
        })
    }

    // ── Suscripción Reverb por rol ──────────────────────────
    // Guardamos el canal activo para poder salir de él aunque
    // el usuario ya sea null (caso del logout).
    let currentChannel = null

    function channelNameFor(user) {
        switch (user.role) {
            case 'admin':        return { name: 'appointments',            isPrivate: false }
            case 'case_manager': return { name: `manager.${user.id}`,     isPrivate: true  }
            case 'client':       return { name: `client.${user.id}`,      isPrivate: true  }
            default:             return null
        }
    }

    function subscribeReverb() {
        const user = useAuthStore().user
        if (!user) return

        const target = channelNameFor(user)
        if (!target) return

        // Ya estamos suscritos a este mismo canal
        if (currentChannel === target.name) return

        // Si había otro canal (cambio de usuario sin pasar por null), salir primero
        unsubscribeReverb()

        const channel = target.isPrivate
            ? echo.private(target.name)
            : echo.channel(target.name)

        channel
            .listen('.appointment.created',        handleAppointmentCreated)
            .listen('.appointment.status-updated', handleAppointmentStatusUpdated)

        currentChannel = target.name
    }

    function unsubscribeReverb() {
        if (!currentChannel) return
        // leave() añade el prefijo private-/presence- correctamente
        echo.leave(currentChannel)
        currentChannel = null
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