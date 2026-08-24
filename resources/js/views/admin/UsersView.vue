<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import UserFormModal from '@/components/admin/UserFormModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import api from '@/plugins/axios'

const { t } = useI18n()

const users = ref([])
const loading = ref(false)
const deleteLoading = ref(false)
const showForm = ref(false)
const showConfirm = ref(false)
const selectedUser = ref(null)
const selectedRole = ref('all')
const searchQuery = ref('')

const showMobileBookModal = ref(false)

let pollInterval = null

const availableRoles = computed(() => {
    const roles = users.value.map(u => u.role || 'Member')
    return ['all', ...new Set(roles)]
})

const filteredUsers = computed(() => {
    let result = users.value
    if (selectedRole.value !== 'all') {
        result = result.filter(u => (u.role || 'Member') === selectedRole.value)
    }
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(u =>
            (u.name && u.name.toLowerCase().includes(query)) ||
            (u.role && u.role.toLowerCase().includes(query))
        )
    }
    return result
})

const teamMembers = computed(() => {
    const targetList = filteredUsers.value
    if (targetList && targetList.length > 0) {
        return targetList.map(user => ({
            id: user.id,
            name: user.name,
            role: user.role || 'Member',
            img: user.profile_image_url || 'https://ik.imagekit.io/gopichakradhar/luffy/o1.jpeg?updatedAt=1754289569411',
            raw: user
        }))
    }
    return [
        { id: 'placeholder-1', name: t('users.no_user_found', 'No se encuentra ese usuario'), role: "---", img: "/images/nofound.jpeg", isPlaceholder: true }
    ]
})

const currentIndex = ref(0)
const activeMember = computed(() => teamMembers.value[currentIndex.value] || teamMembers.value[0])

let isAnimating = false
let autoPlayInterval = null
const autoplayDelay = 3500
let scrollCooldown = false
let touchStartX = 0
let touchEndX = 0

const isHovered = ref(false)

const crumbs = computed(() => [
    { label: t('users.crumbs.dashboard'), icon: 'fa-house', route: 'admin.dashboard' },
    { label: t('users.crumbs.users'), icon: 'fa-users', route: null },
])

const actions = computed(() => [
    { label: t('users.create'), icon: 'fa-plus', type: 'primary', emit: 'create' },
])

function roleIcon(role) {
    const roleMap = {
        admin: 'fa-user-tie',
        casemanager: 'fa-briefcase',
        cliente: 'fa-child-reaching',
        client: 'fa-child-reaching'
    }
    const key = (role || '')
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[\s_-]/g, '') // quita espacios, guiones y guiones bajos
    return roleMap[key] || 'fa-crown'
}

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

function filterByRole(role) {
    selectedRole.value = role
    currentIndex.value = 0
    nextTick(() => {
        updateCarousel(0)
        startAutoplay()
    })
}

function handleAction(event) {
    if (event === 'create') {
        selectedUser.value = null
        showForm.value = true
    }
}

function editUser(member) {
    if (member.id.toString().includes('placeholder')) return
    const targetUser = member.raw || users.value.find(u => u.id === member.id)
    if (targetUser) {
        selectedUser.value = targetUser
        showForm.value = true
    }
}

function confirmDelete(member) {
    if (member.id.toString().includes('placeholder')) return
    const targetUser = member.raw || users.value.find(u => u.id === member.id)
    if (targetUser) {
        selectedUser.value = targetUser
        showConfirm.value = true
    }
}

async function deleteUser() {
    if (!selectedUser.value) return
    deleteLoading.value = true
    try {
        await api.delete(`/admin/users/${selectedUser.value.id}`)
        users.value = users.value.filter(u => u.id !== selectedUser.value.id)
        showConfirm.value = false
        showMobileBookModal.value = false
        selectedUser.value = null
        if (currentIndex.value >= teamMembers.value.length) {
            currentIndex.value = Math.max(0, teamMembers.value.length - 1)
        }
        nextTick(() => updateCarousel(currentIndex.value))
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
    showMobileBookModal.value = false
}

function handleCardClick(index) {
    resetAutoplay(() => updateCarousel(index))
    if (window.innerWidth <= 768) {
        showMobileBookModal.value = true
    }
}

function updateCarousel(newIndex) {
    if (isAnimating) return
    isAnimating = true

    const cards = document.querySelectorAll(".card")
    if (!cards.length) {
        isAnimating = false
        return
    }

    currentIndex.value = (newIndex + cards.length) % cards.length

    cards.forEach((card, i) => {
        const offset = (i - currentIndex.value + cards.length) % cards.length

        card.classList.remove("center", "up-1", "up-2", "down-1", "down-2", "hidden")

        if (offset === 0) {
            card.classList.add("center")
        } else if (offset === 1) {
            card.classList.add("down-1")
        } else if (offset === 2) {
            card.classList.add("down-2")
        } else if (offset === cards.length - 1) {
            card.classList.add("up-1")
        } else if (offset === cards.length - 2) {
            card.classList.add("up-2")
        } else {
            card.classList.add("hidden")
        }
    })

    setTimeout(() => {
        isAnimating = false
    }, 800)
}

function startAutoplay() {
    stopAutoplay()
    if (isHovered.value || showMobileBookModal.value) return

    autoPlayInterval = setInterval(() => {
        updateCarousel(currentIndex.value + 1)
    }, autoplayDelay)
}

function stopAutoplay() {
    if (autoPlayInterval) {
        clearInterval(autoPlayInterval)
        autoPlayInterval = null
    }
}

function handleMouseEnter() {
    isHovered.value = true
    stopAutoplay()
}

function handleMouseLeave() {
    isHovered.value = false
    startAutoplay()
}

function resetAutoplay(actionFunction) {
    actionFunction()
    stopAutoplay()
    if (!isHovered.value && !showMobileBookModal.value) {
        startAutoplay()
    }
}

function handleKeydown(e) {
    if (e.key === "ArrowUp") {
        resetAutoplay(() => updateCarousel(currentIndex.value - 1))
    } else if (e.key === "ArrowDown") {
        resetAutoplay(() => updateCarousel(currentIndex.value + 1))
    }
}

function handleWheel(e) {
    if (scrollCooldown) return
    if (e.deltaY > 0) {
        resetAutoplay(() => updateCarousel(currentIndex.value + 1))
    } else {
        resetAutoplay(() => updateCarousel(currentIndex.value - 1))
    }
    scrollCooldown = true
    setTimeout(() => {
        scrollCooldown = false
    }, 800)
}

function handleTouchStart(e) {
    touchStartX = e.changedTouches[0].screenY
}

function handleTouchEnd(e) {
    touchEndX = e.changedTouches[0].screenY
    const swipeThreshold = 50
    const diff = touchStartX - touchEndX

    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            resetAutoplay(() => updateCarousel(currentIndex.value + 1))
        } else {
            resetAutoplay(() => updateCarousel(currentIndex.value - 1))
        }
    }
}

onMounted(async () => {
    await fetchUsers()

    pollInterval = setInterval(() => {
        if (!document.hidden) fetchUsers(true)
    }, 10000)

    nextTick(() => {
        updateCarousel(0)
        startAutoplay()
    })

    window.addEventListener("keydown", handleKeydown)
    window.addEventListener("wheel", handleWheel)
    document.addEventListener("touchstart", handleTouchStart)
    document.addEventListener("touchend", handleTouchEnd)
})

onUnmounted(() => {
    clearInterval(pollInterval)
    stopAutoplay()

    window.removeEventListener("keydown", handleKeydown)
    window.removeEventListener("wheel", handleWheel)
    document.removeEventListener("touchstart", handleTouchStart)
    document.removeEventListener("touchend", handleTouchEnd)
})

watch(searchQuery, () => {
    currentIndex.value = 0
    nextTick(() => {
        updateCarousel(0)
    })
})

watch(showMobileBookModal, (val) => {
    if (val) {
        stopAutoplay()
    } else {
        startAutoplay()
    }
})
</script>

<template>
    <div class="page">
        <AppTopbar :title="t('users.title')" :crumbs="crumbs" :actions="actions" @action="handleAction" />

        <div class="content">
            <div v-if="loading" class="loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>

            <div class="filters-search-bar">
                <div class="filters-container">
                    <button v-for="role in availableRoles" :key="role" class="filter-btn"
                        :class="{ active: selectedRole === role }" @click="filterByRole(role)">
                        {{ role === 'all' ? t('users.filters.all', 'Todos') : role.charAt(0).toUpperCase() +
                            role.slice(1) }}
                    </button>
                </div>

                <div class="search-container">
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" v-model="searchQuery"
                            :placeholder="t('users.search_placeholder', 'Buscar por nombre o rol...')"
                            class="search-input" />
                        <button v-if="searchQuery" @click="searchQuery = ''" class="clear-search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="main-container" @mouseenter="handleMouseEnter" @mouseleave="handleMouseLeave">
                <div class="carousel-section">
                    <div class="carousel-container">
                        <button class="nav-arrow up" @click="resetAutoplay(() => updateCarousel(currentIndex - 1))">
                            <img src="https://ik.imagekit.io/gopichakradhar/icons/top.png?updatedAt=1754290522765"
                                alt="Up">
                        </button>
                        <div class="carousel-track">
                            <div v-for="(member, index) in teamMembers" :key="member.id || index" class="card"
                                :class="{ 'card-placeholder': member.isPlaceholder }" :data-index="index"
                                @click="handleCardClick(index)">
                                <div class="card-image-container">
                                    <div class="crown-container" v-if="!member.isPlaceholder">
                                        <div class="role-icon-circle">
                                            <i :class="['fa-solid', roleIcon(member.role), 'crown-icon']"></i>
                                        </div>
                                    </div>
                                    <img :src="member.img" :alt="member.name" class="card-image">
                                    <h2 class="card-title">{{ member.name }}</h2>
                                </div>
                            </div>

                        </div>
                        <button class="nav-arrow down" @click="resetAutoplay(() => updateCarousel(currentIndex + 1))">
                            <img src="https://ik.imagekit.io/gopichakradhar/icons/down.png?updatedAt=1754290523249"
                                alt="Down">
                        </button>
                    </div>
                </div>

                <div class="controls-section desktop-book-section">
                    <div class="book-container">
                        <div class="book">
                            <div class="book_front" style="--angle: -150deg">
                                <div class="carpeta-contenido"></div>
                                <div class="carpeta-cuerpo"></div>

                                <div class="foto">
                                    <div class="foto-img-container">
                                        <img :src="activeMember.img" alt="Foto del Usuario">
                                    </div>
                                    <div class="foto-texto">
                                        <span class="nombre">{{ activeMember.name }}</span>
                                        <span class="rol" v-if="!activeMember.isPlaceholder">{{ t('users.role_label',
                                            'Rol:') }} {{ activeMember.role }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="book_front-back" style="--angle: -149.5deg"></div>

                            <div class="page-card" style="--angle: -140deg; --duration: 1.25s"></div>
                            <div class="page-card" style="--angle: -135deg; --duration: 1.5s"></div>
                            <div class="page-card" style="--angle: -130deg; --duration: 1.75s"></div>

                            <div class="page-card" style="--angle: -30deg; --duration: 1.25s">
                                <div>
                                    <div class="foto-interior">
                                        <div class="foto-img-container">
                                            <img :src="activeMember.img" alt="Foto del Usuario">
                                        </div>
                                    </div>

                                    <h2>{{ t('users.book.user_data_title', 'DATOS DEL USUARIO') }}</h2>
                                    <div class="info-usuario">
                                        <div class="info-item">
                                            <strong>{{ t('users.book.name_label', 'Nombre:') }}</strong>
                                            <span>{{ activeMember.name }}</span>
                                        </div>
                                        <div class="info-item" v-if="!activeMember.isPlaceholder">
                                            <strong>{{ t('users.book.role_label', 'Rol:') }}</strong>
                                            <span>{{ activeMember.role }}</span>
                                        </div>
                                        <div class="info-item" v-if="!activeMember.isPlaceholder">
                                            <strong>{{ t('users.book.status_label', 'Estado:') }}</strong>
                                            <span class="badge-activo">{{ t('users.book.active_status', 'Activo')
                                            }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="acciones-container"
                                    v-if="!activeMember.id.toString().includes('placeholder')">
                                    <button class="btn-accion btn-editar" :title="t('users.edit', 'Editar')"
                                        @click="editUser(activeMember)">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn-accion btn-eliminar" :title="t('users.delete', 'Eliminar')"
                                        @click="confirmDelete(activeMember)">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="page-card" style="--angle: -25deg; --duration: 1.5s"></div>
                            <div class="page-card ultima" style="--angle: -20deg; --duration: 1.75s"></div>

                            <div class="pestana"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showMobileBookModal" class="mobile-book-modal-overlay" @click.self="showMobileBookModal = false">
            <div class="mobile-book-modal-content">
                <button class="close-modal-btn" @click="showMobileBookModal = false">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="book-container">
                    <div class="book">
                        <div class="book_front" style="--angle: -150deg">
                            <div class="carpeta-contenido"></div>
                            <div class="carpeta-cuerpo"></div>

                            <div class="foto">
                                <div class="foto-img-container">
                                    <img :src="activeMember.img" alt="Foto del Usuario">
                                </div>
                                <div class="foto-texto">
                                    <span class="nombre">{{ activeMember.name }}</span>
                                    <span class="rol" v-if="!activeMember.isPlaceholder">{{ t('users.role_label',
                                        'Rol:') }} {{ activeMember.role }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="book_front-back" style="--angle: -149.5deg"></div>

                        <div class="page-card" style="--angle: -140deg; --duration: 1.25s"></div>
                        <div class="page-card" style="--angle: -135deg; --duration: 1.5s"></div>
                        <div class="page-card" style="--angle: -130deg; --duration: 1.75s"></div>

                        <div class="page-card" style="--angle: -30deg; --duration: 1.25s">
                            <div>
                                <div class="foto-interior">
                                    <div class="foto-img-container">
                                        <img :src="activeMember.img" alt="Foto del Usuario">
                                    </div>
                                </div>

                                <h2>{{ t('users.book.user_data_title', 'DATOS DEL USUARIO') }}</h2>
                                <div class="info-usuario">
                                    <div class="info-item">
                                        <strong>{{ t('users.book.name_label', 'Nombre:') }}</strong>
                                        <span>{{ activeMember.name }}</span>
                                    </div>
                                    <div class="info-item" v-if="!activeMember.isPlaceholder">
                                        <strong>{{ t('users.book.role_label', 'Rol:') }}</strong>
                                        <span>{{ activeMember.role }}</span>
                                    </div>
                                    <div class="info-item" v-if="!activeMember.isPlaceholder">
                                        <strong>{{ t('users.book.status_label', 'Estado:') }}</strong>
                                        <span class="badge-activo">{{ t('users.book.active_status', 'Activo') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="acciones-container" v-if="!activeMember.id.toString().includes('placeholder')">
                                <button class="btn-accion btn-editar" :title="t('users.edit', 'Editar')"
                                    @click="editUser(activeMember)">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-accion btn-eliminar" :title="t('users.delete', 'Eliminar')"
                                    @click="confirmDelete(activeMember)">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>

                        <div class="page-card" style="--angle: -25deg; --duration: 1.5s"></div>
                        <div class="page-card ultima" style="--angle: -20deg; --duration: 1.75s"></div>

                        <div class="pestana"></div>
                    </div>
                </div>
            </div>
        </div>

        <UserFormModal :show="showForm" :user="selectedUser" @close="showForm = false" @saved="onSaved" />

        <ConfirmModal :show="showConfirm" :title="t('users.delete')"
            :message="t('users.confirm_delete', { name: selectedUser?.name })" :loading="deleteLoading"
            :confirm-label="t('users.delete_confirm')" :loading-label="t('users.deleting')" @close="showConfirm = false"
            @confirm="deleteUser" />
    </div>
</template>

<style scoped>
.page {
    display: grid;
    grid-template-rows: auto 1fr auto;
    height: 100%;
    overflow: hidden;
}

.content {
    overflow-y: auto;
    padding: 20px;
}

.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
    color: #4a90e2;
    font-size: 2rem;
}

.filters-search-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.filters-container {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 5px;
    max-width: 100%;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.filters-container::-webkit-scrollbar {
    display: none;
}

.filter-btn {
    background-color: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
    white-space: nowrap;
}

.filter-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
}

.filter-btn.active {
    background-color: rgb(29, 194, 14);
    border-color: rgb(29, 194, 14);
    color: white;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(29, 194, 14, 0.4);
}

@media (max-width: 480px) {
    .filter-btn {
        padding: 5px 10px;
        font-size: 0.75rem;
    }
}

.search-container {
    display: flex;
    justify-content: flex-end;
}

.search-input-wrapper {
    position: relative;
    width: 100%;
    max-width: 300px;
}

.search-input-wrapper i.fa-magnifying-glass {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

.search-input {
    width: 100%;
    background-color: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 10px 40px 10px 42px;
    border-radius: 20px;
    font-size: 0.9rem;
    outline: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
    box-sizing: border-box;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.search-input:focus {
    background-color: rgba(255, 255, 255, 0.15);
    border-color: rgb(29, 194, 14);
    box-shadow: 0 0 10px rgba(29, 194, 14, 0.3);
}

.clear-search {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    font-size: 0.9rem;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
}

.clear-search:hover {
    color: #ffffff;
}

.main-container {
    display: flex;
    width: 100%;
    max-width: 1200px;
    height: auto;
    gap: 60px;
    align-items: center;
    justify-content: center;
    margin: 50px auto 0 auto;
    /* <- Aquí aumentamos el espacio superior en PC */
    box-sizing: border-box;
}

.main-container * {
    box-sizing: border-box;
}

.carousel-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 20px;
}

.controls-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 40px;
    padding-left: 40px;
    min-height: 400px;
}

.carousel-container {
    width: 100%;
    max-width: 540px;
    height: 380px;
    position: relative;
    perspective: 1000px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.carousel-container .nav-arrow {
    display: flex;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 75px;
    height: 75px;
    margin: 0;
    background: transparent;
    border: none;
    box-shadow: none;
    z-index: 20;
    cursor: pointer;
}

.carousel-container .nav-arrow.up {
    left: -15px;
}

.carousel-container .nav-arrow.down {
    right: -15px;
}

.carousel-container .nav-arrow.up:hover {
    transform: translateY(-50%) scale(1.2);
}

.carousel-container .nav-arrow.down:hover {
    transform: translateY(-50%) scale(1.2);
}

.carousel-container .nav-arrow img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    filter: none;
    transition: all 0.3s ease;
}

.carousel-container .nav-arrow:hover img {
    transform: scale(1.1);
}

.carousel-track {
    width: 450px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
    transform-style: preserve-3d;
    transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.card {
    position: absolute;
    width: 200px;
    height: 215px;
    background-color: #ffffff;
    border-radius: 20px;
    padding: 8px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    cursor: pointer;
}

.card-image-container {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 14px;
    overflow: hidden;
}

.card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.card.card-placeholder .card-image {
    object-fit: cover;
}

.crown-container {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    gap: 4px;
    align-items: center;
    z-index: 2;
}

.role-icon-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

.crown-icon {
    font-size: 1rem;
    color: #ffffff;
    filter: none;
}

.card-title {
    position: absolute;
    bottom: 10px;
    left: 0;
    right: 0;
    text-align: center;
    color: #ffffff;
    font-family: 'Great Vibes', cursive;
    font-size: 1.3rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    text-shadow:
        0px 2px 6px rgba(0, 0, 0, 0.9),
        0px 0px 10px rgba(0, 0, 0, 0.7);
    z-index: 2;
    padding: 0 6px;
    line-height: 1.1;
}

.placeholder-label {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 28%;
    background-color: rgba(0, 0, 0, 0.75);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    line-height: 1.1;
    box-sizing: border-box;
}

.card.center {
    z-index: 10;
    transform: scale(1.08) translateZ(0);
}

.card.center img {
    filter: none;
}

.card.up-2 {
    z-index: 1;
    transform: translateY(-190px) scale(0.75) translateZ(-300px);
    opacity: 0.5;
}

.card.up-2 img,
.card.up-1 img,
.card.down-1 img,
.card.down-2 img {
    filter: grayscale(100%);
}

.card.up-1 {
    z-index: 5;
    transform: translateY(-95px) scale(0.88) translateZ(-100px);
    opacity: 0.8;
}

.card.down-1 {
    z-index: 5;
    transform: translateY(95px) scale(0.88) translateZ(-100px);
    opacity: 0.8;
}

.card.down-2 {
    z-index: 1;
    transform: translateY(190px) scale(0.75) translateZ(-300px);
    opacity: 0.5;
}

.book-container {
    font-size: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.book {
    position: relative;
    width: 11em;
    height: 15.5em;
    perspective: 1500px;
    transform-style: preserve-3d;
    background: #fffbec;
    box-shadow: 3px 3px 0.6em rgba(0, 0, 0, 0.3);
    cursor: pointer;
}

.book::before {
    content: "";
    position: absolute;
    inset: 0;
    transition: box-shadow 500ms ease-in-out;
    z-index: 10;
}

.book_front {
    position: relative;
    height: 100%;
    z-index: 3;
    background: transparent;
}

.book_front-back {
    position: absolute;
    background: #fffbec;
    inset: 0;
    z-index: 2;
}

.carpeta-cuerpo {
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, #fbe87b, #d1a938);
    position: absolute;
    bottom: 0;
    left: 0;
    z-index: 2;
}

.carpeta-contenido {
    width: 70%;
    height: 92%;
    background-color: #ffffff;
    border-radius: 10px;
    position: absolute;
    top: 4%;
    right: 5px;
    z-index: 1;
}

.page-card {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #eeeae3, #fffbf6);
    box-shadow: inset 0px -1px 2px rgba(50, 50, 50, 0.1), inset -1px 0px 1px rgba(150, 150, 150, 0.2);
    padding: 0.8em;
    z-index: 1;
    transform-origin: 0 50%;
    transition: transform 1s ease-in-out, box-shadow 1s ease-in-out;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.book_front,
.book_front-back {
    transform-origin: 0 50%;
    transition: transform 1s ease-in-out, box-shadow 1s ease-in-out;
}

.book h2 {
    font-size: 0.75rem;
    text-align: center;
    margin-bottom: 0.4em;
    color: #333;
    border-bottom: 2px solid #d1a938;
    padding-bottom: 0.2em;
}

.info-usuario {
    display: flex;
    flex-direction: column;
    gap: 0.5em;
}

.info-item {
    font-size: 0.75rem;
    color: #000000;
    line-height: 1.2;
    display: flex;
    align-items: center;
    gap: 6px;
}

.info-item strong {
    color: #000000;
    font-size: 0.8rem;
    font-weight: 700;
}

.badge-activo {
    display: inline-block;
    background-color: #2ecc71;
    color: white;
    padding: 0.1em 0.4em;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.6rem;
}

.acciones-container {
    display: flex;
    justify-content: center;
    gap: 0.8em;
    margin-top: 0.2em;
}

.btn-accion {
    width: 2.1em;
    height: 2.1em;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    font-size: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s ease, background-color 0.2s ease;
}

.btn-editar {
    background-color: #3498db;
}

.btn-editar:hover {
    background-color: #2980b9;
    transform: scale(1.1);
}

.btn-eliminar {
    background-color: #e74c3c;
}

.btn-eliminar:hover {
    background-color: #c0392b;
    transform: scale(1.1);
}

.book:hover>* {
    transform: rotateY(var(--angle));
    transition-duration: var(--duration, 1s);
}

.pestana {
    position: absolute;
    top: 0px;
    right: -25px;
    width: 25px;
    height: 70px;
    border-radius: 0 10px 10px 0;
    background: linear-gradient(to bottom, #fbe87b, #d1a938);
    z-index: 0;
}

.foto {
    position: absolute;
    top: 25px;
    left: 50%;
    transform: translateX(-50%) rotate(3deg);
    width: 8em;
    height: 10em;
    background-color: #ffffff;
    padding: 0.5em 0.5em 2.5em 0.5em;
    box-shadow: 2px 3px 8px rgba(0, 0, 0, 0.25);
    z-index: 5;
    border-radius: 2px;
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.foto-interior {
    position: relative;
    width: 3.5em;
    height: 4.2em;
    background-color: #ffffff;
    padding: 0.2em;
    box-shadow: 2px 3px 5px rgba(0, 0, 0, 0.2);
    border-radius: 2px;
    margin: 0 auto 0.2em auto;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.foto:hover {
    transform: translateX(-50%) rotate(0deg) scale(1.05);
}

.foto-img-container {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.foto img,
.foto-interior img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
    filter: none;
}

.foto-texto {
    position: absolute;
    bottom: 5px;
    width: 100%;
    text-align: center;
    font-family: 'Caveat', cursive, sans-serif;
    color: #333;
    line-height: 1.1;
    transition: opacity 0.3s ease;
}

.foto-texto .nombre {
    font-family: 'Caveat', cursive, sans-serif;
    font-size: 1.2rem;
    font-weight: bold;
    display: block;
}

.foto-texto .rol {
    font-family: 'Caveat', cursive, sans-serif;
    font-size: 0.9rem;
    color: #555;
    display: block;
}

.mobile-book-modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    padding: 20px;
}

.mobile-book-modal-content {
    position: relative;
    background: transparent;
    display: flex;
    justify-content: center;
    align-items: center;
    animation: scaleUpModal 0.3s ease;
}

.close-modal-btn {
    position: absolute;
    top: -45px;
    right: 0;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.close-modal-btn:hover {
    background: rgba(255, 255, 255, 0.4);
}

@keyframes scaleUpModal {
    from {
        transform: scale(0.8);
        opacity: 0;
    }

    to {
        transform: scale(1);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .desktop-book-section {
        display: none !important;
    }

    .main-container {
        flex-direction: column;
        height: auto;
        gap: 20px;
        max-width: 100%;
        margin-top: 10px;
        /* Separación más compacta en móvil */
    }

    .carousel-section {
        flex: none;
        width: 100%;
    }

    .controls-section {
        flex: none;
        width: 100%;
        padding-left: 0;
        gap: 20px;
        min-height: auto;
    }

    .carousel-container {
        height: 320px;
        max-width: 360px;
    }

    .carousel-container .nav-arrow {
        width: 65px;
        height: 65px;
    }

    .carousel-container .nav-arrow.up {
        left: -5px;
    }

    .carousel-container .nav-arrow.down {
        right: -5px;
    }

    .carousel-container .nav-arrow img {
        width: 50px;
        height: 50px;
    }

    .card {
        width: 200px;
        height: 160px;
    }

    .carousel-track {
        width: 280px;
    }

    .card.up-2 {
        transform: translateY(-110px) scale(0.75) translateZ(-300px);
    }

    .card.up-1 {
        transform: translateY(-55px) scale(0.88) translateZ(-100px);
    }

    .card.down-1 {
        transform: translateY(55px) scale(0.88) translateZ(-100px);
    }

    .card.down-2 {
        transform: translateY(110px) scale(0.75) translateZ(-300px);
    }

    .book-container {
        font-size: 16px;
    }
}

@media (min-width: 424px) {
    .book {
        width: 13em;
        height: 18em;
    }

    .foto {
        width: 9em;
        height: 11em;
        padding-bottom: 2.8em;
    }

    .foto-interior {
        width: 4.2em;
        height: 5em;
    }

    .foto-texto .nombre {
        font-size: 1.4rem;
    }

    .foto-texto .rol {
        font-size: 1.0rem;
    }

    .info-item {
        font-size: 0.85rem;
    }

    .info-item strong {
        font-size: 0.9rem;
    }

    .book h2 {
        font-size: 0.85rem;
    }

    .btn-accion {
        width: 2.3em;
        height: 2.3em;
        font-size: 0.85rem;
    }
}
</style>