import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/plugins/axios'
import router from '@/router'
import { useNotificationStore } from '@/stores/notificationStore'

export const useAuthStore = defineStore('auth', () => {
  // Parsing seguro para evitar que datos corruptos en localStorage tumben la app
  const token = ref(localStorage.getItem('token') || null)
  const user  = ref(getInitialUser())

  function getInitialUser() {
    try {
      return JSON.parse(localStorage.getItem('user') || 'null')
    } catch {
      localStorage.removeItem('user')
      return null
    }
  }

  // Getters computados
  const isAuthenticated = computed(() => !!token.value)
  const userRole        = computed(() => user.value?.role || null)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isCaseManager   = computed(() => user.value?.role === 'case_manager')
  const isClient        = computed(() => user.value?.role === 'client')

  async function login(credentials) {
    try {
      const { data } = await api.post('/auth/login', credentials)

      token.value = data.access_token
      user.value  = data.user

      localStorage.setItem('token', data.access_token)
      localStorage.setItem('user',  JSON.stringify(data.user))

      redirectByRole(data.user.role)
      return data
    } catch (error) {
      clearSession()
      throw error // Re-lanzar para que la vista/formulario capture los errores de validación
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (e) {
      // Registrar en consola si la API falla al cerrar sesión, pero continuar con la limpieza
      console.warn('Error invocado durante logout en backend:', e)
    } finally {
      clearSession()
      router.push({ name: 'login' })
    }
  }

  async function fetchMe() {
    try {
      const { data } = await api.get('/auth/me')
      user.value = data.user
      localStorage.setItem('user', JSON.stringify(data.user))
    } catch {
      clearSession()
    }
  }

  function clearSession() {
    // Invocar el store de notificaciones en runtime para evitar dependencias circulares
    try {
      const notifStore = useNotificationStore()
      notifStore.unsubscribeReverb?.()
      notifStore.clear?.()
    } catch (e) {
      console.warn('No se pudo desuscribir Reverb al limpiar sesión:', e)
    }

    token.value = null
    user.value  = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  function redirectByRole(role) {
    const routes = {
      admin:        { name: 'admin.dashboard' },
      case_manager: { name: 'manager.dashboard' },
      client:       { name: 'client.dashboard' },
    }
    router.push(routes[role] || { name: 'login' })
  }

  return {
    token,
    user,
    isAuthenticated,
    userRole,
    isAdmin,
    isCaseManager,
    isClient,
    login,
    logout,
    fetchMe,
    clearSession,
  }
})