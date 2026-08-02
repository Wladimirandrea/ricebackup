<!-- resources/js/components/layout/ToastContainer.vue -->
<template>
    <Teleport to="body">
        <div class="toast-stack">
            <TransitionGroup name="toast">
                <div v-for="toast in toasts" :key="toast.id" class="toast" :class="`toast--${toast.type}`">
                    <div class="toast__icon">{{ toastIcon(toast) }}</div>
                    <div class="toast__body">
                        <p class="toast__title">{{ toastTitle(toast) }}</p>
                        <p class="toast__meta">{{ toast.date }} · {{ toast.time }}</p>
                    </div>
                    <button class="toast__close" @click="dismiss(toast.id)">
                        <i class="fa fa-xmark"></i>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/stores/notificationStore'

const { t } = useI18n()
const notifStore = useNotificationStore()

const toasts = ref([])
const DURATION = 5000

function toastIcon(n) {
    const icons = { created: '📅', status_changed: '🔄', cancelled: '❌' }
    return icons[n.type] ?? '🔔'
}

function toastTitle(n) {
    if (n.type === 'created')   return t('notifications.newAppointment') + ' — ' + n.clientName
    if (n.type === 'cancelled') return n.clientName + ' — ' + t('notifications.cancelled')
    return n.clientName + ' — ' + t('notifications.status') + ': ' + t(`appointments.${n.status}`)
}

function dismiss(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
}

// Cada vez que el store recibe una notificación nueva (se agrega al frente del array),
// la mostramos como toast y la quitamos sola después de DURATION ms.
watch(
    () => notifStore.notifications[0],
    (latest) => {
        if (!latest) return
        toasts.value.push(latest)
        setTimeout(() => dismiss(latest.id), DURATION)
    }
)
</script>

<style scoped>
.toast-stack {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 340px;
    pointer-events: none;
}

.toast {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #111827;
    border: 1px solid #1e2a3a;
    border-left: 4px solid #4a90e2;
    border-radius: 12px;
    padding: 14px 14px 14px 16px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
    pointer-events: all;
}

.toast--created   { border-left-color: #4a90e2; }
.toast--cancelled { border-left-color: #ef4444; }
.toast--status_changed { border-left-color: #f59e0b; }

.toast__icon {
    font-size: 20px;
    line-height: 1;
    margin-top: 1px;
    flex-shrink: 0;
}

.toast__body {
    flex: 1;
    min-width: 0;
}

.toast__title {
    margin: 0 0 4px;
    font-size: 13px;
    font-weight: 600;
    color: #fff;
    line-height: 1.35;
}

.toast__meta {
    margin: 0;
    font-size: 11px;
    color: #7a8aaa;
}

.toast__close {
    background: none;
    border: none;
    color: #7a8aaa;
    cursor: pointer;
    font-size: 12px;
    padding: 2px;
    flex-shrink: 0;
    transition: color 0.2s;
}

.toast__close:hover {
    color: #fff;
}

/* Transiciones */
.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(40px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(40px);
}

.toast-move {
    transition: transform 0.25s ease;
}

@media (max-width: 480px) {
    .toast-stack {
        left: 12px;
        right: 12px;
        top: 12px;
        max-width: none;
    }
}
</style>