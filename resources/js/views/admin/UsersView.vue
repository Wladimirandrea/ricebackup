<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import UserFormModal from '@/components/admin/UserFormModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import api from '@/plugins/axios'

const { t } = useI18n()

const users         = ref([])
const loading       = ref(false)
const deleteLoading = ref(false)
const showForm      = ref(false)
const showConfirm   = ref(false)
const selectedUser  = ref(null)
const currentPage   = ref(1)
const containerRef  = ref(null)
const columns       = ref(4)
const activeFilter  = ref('all')

const perPage = computed(() => columns.value * 2)

let pollInterval   = null
let resizeObserver = null

const crumbs = computed(() => [
    { label: t('users.crumbs.dashboard'), icon: 'fa-house', route: 'admin.dashboard' },
    { label: t('users.crumbs.users'),     icon: 'fa-users', route: null },
])

const actions = computed(() => [
    { label: t('users.create'), icon: 'fa-plus', type: 'primary', emit: 'create' },
])

const filters = computed(() => [
    { key: 'all',          label: t('users.roles.all'),          count: users.value.length },
    { key: 'admin',        label: t('users.roles.admin'),        count: users.value.filter(u => u.role === 'admin').length },
    { key: 'case_manager', label: t('users.roles.case_manager'), count: users.value.filter(u => u.role === 'case_manager').length },
    { key: 'client',       label: t('users.roles.client'),       count: users.value.filter(u => u.role === 'client').length },
])

const filteredUsers = computed(() => {
    if (activeFilter.value === 'all') return users.value
    return users.value.filter(u => u.role === activeFilter.value)
})

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredUsers.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value))

function setFilter(key) {
    activeFilter.value = key
    currentPage.value  = 1
}

function updateColumns() {
    if (!containerRef.value) return
    const containerWidth = containerRef.value.offsetWidth
    const cardWidth      = 260
    const gap            = 40
    columns.value = Math.max(1, Math.floor((containerWidth + gap) / (cardWidth + gap)))
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

watch(filteredUsers, () => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = Math.max(1, totalPages.value)
    }
})

watch(perPage, () => {
    currentPage.value = 1
})

async function fetchUsers(silent = false) {
    if (!silent) loading.value = true
    try {
        const { data } = await api.get('/admin/users')
        users.value = data.data
    } catch (err) {
        console.error(err)
    } finally {
        if (!silent) loading.value = false
    }
}

function handleAction(event) {
    if (event === 'create') {
        selectedUser.value = null
        showForm.value = true
    }
}

function editUser(user) {
    selectedUser.value = user
    showForm.value = true
}

function confirmDelete(user) {
    selectedUser.value = user
    showConfirm.value = true
}

async function deleteUser() {
    if (!selectedUser.value) return
    deleteLoading.value = true
    try {
        await api.delete(`/admin/users/${selectedUser.value.id}`)
        users.value = users.value.filter(u => u.id !== selectedUser.value.id)
        showConfirm.value = false
        selectedUser.value = null
    } catch (err) {
        console.error(err)
    } finally {
        deleteLoading.value = false
    }
}

function onSaved(updatedUser) {
    const index = users.value.findIndex(u => u.id === updatedUser.id)
    if (index !== -1) {
        const busted = {
            ...updatedUser,
            profile_image_url: updatedUser.profile_image_url
                ? `${updatedUser.profile_image_url}?t=${Date.now()}`
                : null
        }
        users.value.splice(index, 1, busted)
    } else {
        users.value.unshift(updatedUser)
    }
    showForm.value = false
}

function getRoleLabel(role) {
    return t(`users.roles.${role}`) || role
}

function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

onMounted(() => {
    fetchUsers()
    pollInterval = setInterval(() => {
        if (!document.hidden) fetchUsers(true)
    }, 10000)

    resizeObserver = new ResizeObserver(updateColumns)
    if (containerRef.value) resizeObserver.observe(containerRef.value)
    updateColumns()
})

onUnmounted(() => {
    clearInterval(pollInterval)
    resizeObserver?.disconnect()
})
</script>

<template>
    <div class="page">
        <AppTopbar :title="t('users.title')" :crumbs="crumbs" :actions="actions" @action="handleAction" />

        <div class="content">
            <div v-if="loading" class="loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>

            <template v-else>
                <!-- Filtros -->
                <div class="filters">
                    <button
                        v-for="f in filters"
                        :key="f.key"
                        class="filter-btn"
                        :class="{ active: activeFilter === f.key }"
                        @click="setFilter(f.key)"
                    >
                        {{ f.label }}
                        <span class="filter-count">{{ f.count }}</span>
                    </button>
                </div>

                <div class="container" ref="containerRef">
                    <div v-for="user in paginatedUsers" :key="user.id" class="profile-card">
                        <div class="action-buttons">
                            <button class="btn view" @click="editUser(user)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn edit" @click="editUser(user)">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="btn delete" @click="confirmDelete(user)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="border"></div>
                        <div class="glass-circle">
                            <div class="image-container">
                                <img
                                    v-if="user.profile_image_url && !user.profile_image_url.includes('ui-avatars')"
                                    :key="user.profile_image_url"
                                    :src="user.profile_image_url"
                                    :alt="user.name"
                                />
                                <div v-else class="avatar-initials">{{ getInitials(user.name) }}</div>
                                <div class="text-overlay">
                                    <div class="name-bg">
                                        <h2>{{ user.name }}</h2>
                                    </div>
                                    <p>{{ getRoleLabel(user.role) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredUsers.length === 0" class="empty">
                    <i class="fa-solid fa-users-slash"></i>
                    <p>{{ t('users.no_users') }}</p>
                </div>
            </template>
        </div>

        <div v-if="!loading && totalPages > 1" class="pagination-navbar">
            <div class="pagination">
                <a
                    href="#"
                    :class="{ disabled: currentPage === 1 }"
                    @click.prevent="goToPage(currentPage - 1)"
                ><i class="fa fa-chevron-left"></i></a>
                <a
                    v-for="page in totalPages"
                    :key="page"
                    href="#"
                    :class="{ active: page === currentPage }"
                    @click.prevent="goToPage(page)"
                >{{ page }}</a>
                <a
                    href="#"
                    :class="{ disabled: currentPage === totalPages }"
                    @click.prevent="goToPage(currentPage + 1)"
                ><i class="fa fa-chevron-right"></i></a>
            </div>
        </div>

        <UserFormModal
            :show="showForm"
            :user="selectedUser"
            @close="showForm = false"
            @saved="onSaved"
        />

        <ConfirmModal
            :show="showConfirm"
            :title="t('users.delete')"
            :message="t('users.confirm_delete', { name: selectedUser?.name })"
            :loading="deleteLoading"
            :confirm-label="t('users.delete_confirm')"
            :loading-label="t('users.deleting')"
            @close="showConfirm = false"
            @confirm="deleteUser"
        />
    </div>
</template>

<style scoped>
.page { display: grid; grid-template-rows: auto 1fr auto; height: 100%; overflow: hidden; }
.content { overflow-y: auto; padding: 20px; }

.loading { display: flex; justify-content: center; align-items: center; height: 200px; color: #4a90e2; font-size: 2rem; }
.empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; color: #7a8aaa; gap: 12px; }
.empty i { font-size: 2.5rem; }
.empty p { font-size: 14px; font-family: 'Segoe UI', sans-serif; }

/* ── Filtros ── */
.filters { display: flex; gap: 10px; padding: 16px 20px 0; flex-wrap: wrap; }
.filter-btn { display: flex; align-items: center; gap: 8px; padding: 8px 18px; border-radius: 20px; border: 1px solid #1e2a3a; background: #131c2e; color: #7a8aaa; font-size: 13px; font-family: 'Segoe UI', sans-serif; cursor: pointer; transition: background 0.2s, color 0.2s, border-color 0.2s; }
.filter-btn:hover { background: #1e2a3a; color: #c9d4e8; border-color: #2a3a54; }
.filter-btn.active { background: #1a3a6e; color: white; border-color: #2a5298; }
.filter-count { background: rgba(255,255,255,0.15); color: inherit; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; min-width: 22px; text-align: center; }
.filter-btn.active .filter-count { background: rgba(255,255,255,0.25); }

.container { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 60px 40px; padding: 40px 20px; justify-items: center; align-items: center; }
.profile-card { position: relative; width: 240px; height: 200px; display: flex; justify-content: center; align-items: center; }
.glass-circle { width: 240px; height: 240px; background: rgba(255,255,255,0.15); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-radius: 50%; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; position: relative; z-index: 2; }
.border { position: absolute; background: linear-gradient(to bottom, #00152b 0%, #84a0c0 35%, #1f3149 70%, #0862c2 100%); width: 240px; height: 240px; border-radius: 50%; transition: transform 1.10s ease-in-out; }
.image-container { width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; }
.image-container img { width: 85%; height: 85%; object-fit: cover; border-radius: 50%; transition: transform 1.10s ease; }
.avatar-initials { width: 85%; height: 85%; border-radius: 50%; background: #4a90e2; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; color: white; transition: transform 1.10s ease; }
.text-overlay { position: absolute; bottom: 0; width: 100%; padding-bottom: 25px; background: linear-gradient(transparent, rgba(0,0,0,0.4)); text-align: center; }
.text-overlay h2 { margin: 0; color: #fff; font-size: 1.1rem; text-shadow: 2px 2px 8px rgba(0,0,0,0.8); font-family: 'Segoe UI', sans-serif; }
.text-overlay p { margin: 0; color: #eee; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; text-shadow: 1px 1px 4px rgba(0,0,0,0.8); }

.action-buttons { position: absolute; right: 30px; top: 20%; transform: translateX(55%); display: flex; flex-direction: column; gap: 12px; z-index: 3; }
.btn { width: 32px; height: 32px; border-radius: 50%; border: none; background: white; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: flex; justify-content: center; align-items: center; font-size: 1rem; opacity: 0; transform: translateX(-60px); transition: opacity 0.4s ease, transform 0.5s cubic-bezier(0.68,-0.55,0.265,1.55), background 0.2s, color 0.2s; }
.btn.view   { color: #2563eb; transition-delay: 0.4s; }
.btn.edit   { color: #f59e0b; transition-delay: 0.2s; }
.btn.delete { color: #ef4444; transition-delay: 0s; }
.profile-card:hover .btn { opacity: 1; transform: translateX(0); }
.profile-card:hover .image-container img,
.profile-card:hover .avatar-initials { transform: scale(0.75); }
.profile-card:hover .border { transform: rotate(360deg); }
.profile-card:hover .btn.view   { transition-delay: 0s; }
.profile-card:hover .btn.edit   { transition-delay: 0.2s; }
.profile-card:hover .btn.delete { transition-delay: 0.4s; }
.btn:hover { background: #1a365d; color: white; transform: scale(1.15); }

/* ── Paginación ── */
.pagination-navbar { display: flex; justify-content: center; padding: 5px 0 20px; border-top: 1px solid #1e2a3a; }
.pagination { display: flex; align-items: center; gap: 10px; padding: 10px 25px; }
.pagination a { text-decoration: none; color: white; padding: 10px 15px; border-radius: 12px; transition: background 0.3s, transform 0.2s; background: rgba(255,255,255,0.15); cursor: pointer; user-select: none; }
.pagination a:hover   { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
.pagination a.active  { background: rgba(255,255,255,0.5); color: #333; font-weight: bold; }
.pagination a.disabled { opacity: 0.35; pointer-events: none; }
</style>