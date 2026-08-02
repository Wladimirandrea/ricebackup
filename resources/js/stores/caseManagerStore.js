// resources/js/stores/caseManagerStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/plugins/axios'

export const useCaseManagerStore = defineStore('caseManager', () => {
  const managers = ref([])
  const meta = ref({})
  const loading = ref(false)
  const error = ref(null)
  const currentManagerId = ref(null)

  // Modal principal
  const modalOpen = ref(false)
  const modalManager = ref(null)
  const modalClients = ref([])
  const modalUnassigned = ref([])
  const modalLoading = ref(false)
  const activeTab = ref('assigned')

  // Modal cliente detalle
  const clientModalOpen = ref(false)
  const selectedClient = ref(null)
  const allManagers = ref([])
  const showReassignList = ref(false)
  const actionLoading = ref(false)

  // ── Fetch managers ──────────────────────────────────────
  async function fetchManagers(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/admin/case-managers', { params })
      managers.value = data.data
      meta.value = data.meta
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Error loading case managers'
    } finally {
      loading.value = false
    }
  }

  // ── Abrir modal principal ────────────────────────────────
  async function openClientsModal(manager) {
    modalManager.value = manager
    modalClients.value = []
    modalUnassigned.value = []
    modalOpen.value = true
    modalLoading.value = true
    activeTab.value = 'assigned'

    try {
      const [assigned, unassigned] = await Promise.all([
        api.get(`/admin/case-managers/${manager.id}/clients`),
        api.get('/admin/case-managers/unassigned-clients'),
      ])
      modalClients.value = assigned.data.clients
      modalUnassigned.value = unassigned.data.clients
    } catch (e) {
      modalClients.value = []
      modalUnassigned.value = []
    } finally {
      modalLoading.value = false
    }
  }

  function closeModal() {
    modalOpen.value = false
    modalManager.value = null
    modalClients.value = []
    modalUnassigned.value = []
    activeTab.value = 'assigned'
  }

  // ── Abrir modal cliente ──────────────────────────────────
  async function openClientDetail(client) {
    selectedClient.value = client
    showReassignList.value = false
    clientModalOpen.value = true

    try {
      const { data } = await api.get('/admin/case-managers/all')
      allManagers.value = data.managers

      // ← buscar el manager actual del cliente
      const assignedRes = await api.get(`/admin/case-managers/client/${client.id}/manager`)
      currentManagerId.value = assignedRes.data.case_manager_id ?? null
    } catch {
      allManagers.value = []
      currentManagerId.value = null
    }
  }

  function closeClientModal() {
    clientModalOpen.value = false
    selectedClient.value = null
    showReassignList.value = false
  }

  // ── Reasignar cliente ────────────────────────────────────
  async function reassignClient(newManagerId) {
    if (!selectedClient.value) return
    actionLoading.value = true
    try {
      await api.post('/admin/case-managers/reassign', {
        client_id: selectedClient.value.id,
        case_manager_id: newManagerId,
      })

      const { data } = await api.get('/admin/case-managers')
      managers.value = data.data
      meta.value = data.meta

      modalClients.value = modalClients.value.filter(c => c.id !== selectedClient.value.id)
      modalUnassigned.value = modalUnassigned.value.filter(c => c.id !== selectedClient.value.id)
      if (newManagerId === modalManager.value?.id) {
        modalClients.value.push(selectedClient.value)
      }
      currentManagerId.value = newManagerId
      closeClientModal()
    } catch (e) {
      console.error('Error reassigning:', e)
    } finally {
      actionLoading.value = false
    }
  }

  async function releaseClient() {
    if (!selectedClient.value) return
    actionLoading.value = true
    try {
      await api.delete(`/admin/case-managers/release/${selectedClient.value.id}`)

      modalClients.value = modalClients.value.filter(
        c => c.id !== selectedClient.value.id
      )
      modalUnassigned.value.push(selectedClient.value)

      currentManagerId.value = null

      // Recargar managers SIN afectar el loading global
      const { data } = await api.get('/admin/case-managers')
      managers.value = data.data
      meta.value = data.meta

      closeClientModal()
    } catch (e) {
      console.error('Error releasing:', e)
    } finally {
      actionLoading.value = false
    }
  }


  async function fetchAllManagers() {
    try {
      const { data } = await api.get('/admin/case-managers/all')
      allManagers.value = data.managers
    } catch (e) {
      allManagers.value = []
    }
  }

  return {
    managers, meta, loading, error,
    modalOpen, modalManager, modalClients, modalUnassigned, modalLoading, activeTab,
    clientModalOpen, selectedClient, allManagers, showReassignList, actionLoading,
    fetchManagers, openClientsModal, closeModal,
    openClientDetail, closeClientModal, reassignClient, releaseClient,
    currentManagerId,fetchAllManagers,
  }
})