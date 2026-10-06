<!-- resources/js/App.vue -->
<template>
  <RouterView />
</template>

<script setup>
import { RouterView } from 'vue-router'
import { watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'

const authStore = useAuthStore()
const notifStore = useNotificationStore()

// Suscribirse y desuscribirse automáticamente de Reverb según la sesión del usuario
watch(
    () => authStore.user,
    (user) => {
        if (user) {
            notifStore.subscribeReverb()
        } else {
            notifStore.unsubscribeReverb()
        }
    },
    { immediate: true }
)
</script>