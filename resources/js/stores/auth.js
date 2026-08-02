import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/plugins/axios'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  // ── State ────────────────────────────────────────────────
  const token = ref(localStorage.getItem('token') || null)
  const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  // ── Getters ──────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value)
  const userRole        = computed(() => user.value?.role || null)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isCaseManager   = computed(() => user.value?.role === 'case_manager')
  const isClient        = computed(() => user.value?.role === 'client')

  // ── Actions ──────────────────────────────────────────────
  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials)

    token.value = data.access_token
    user.value  = data.user

    localStorage.setItem('token', data.access_token)
    localStorage.setItem('user',  JSON.stringify(data.user))

    // Redirigir según rol
    redirectByRole(data.user.role)
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
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
    token, user,
    isAuthenticated, userRole, isAdmin, isCaseManager, isClient,
    login, logout, fetchMe, clearSession,
  }
}, {
  // Hydration automática sin necesidad de localStorage manual
  // (usando pinia-plugin-persistedstate si lo prefieres)
})