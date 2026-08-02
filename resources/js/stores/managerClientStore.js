// resources/js/stores/managerClientStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/plugins/axios'

export const useManagerClientStore = defineStore('managerClient', () => {
    const clients      = ref([])
    const loading      = ref(false)
    const error        = ref(null)
    const search       = ref('')

    // Password modal
    const passwordModal = ref({ open: false, client: null, loading: false, error: '' })

    async function fetchClients() {
        loading.value = true
        error.value   = null
        try {
            const { data } = await api.get('/manager/clients', {
                params: search.value ? { search: search.value } : {},
            })
            clients.value = data.data
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Error loading clients'
        } finally {
            loading.value = false
        }
    }

    function openPasswordModal(client) {
        passwordModal.value = { open: true, client, loading: false, error: '' }
    }

    function closePasswordModal() {
        passwordModal.value = { open: false, client: null, loading: false, error: '' }
    }

    async function changePassword(clientId, password, password_confirmation) {
        passwordModal.value.loading = true
        passwordModal.value.error   = ''
        try {
            const { data } = await api.patch(
                `/manager/clients/${clientId}/password`,
                { password, password_confirmation }
            )
            closePasswordModal()
            return { success: true, message: data.message }
        } catch (e) {
            const msg = e.response?.data?.errors?.password?.[0]
                     ?? e.response?.data?.message
                     ?? 'Error updating password'
            passwordModal.value.error = msg
            return { success: false }
        } finally {
            passwordModal.value.loading = false
        }
    }

    return {
        clients, loading, error, search,
        passwordModal,
        fetchClients, openPasswordModal, closePasswordModal, changePassword,
    }
})