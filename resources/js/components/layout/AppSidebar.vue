<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { computed } from 'vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()

const logoUrl = '/images/raise-logo.png'

const menuItems = computed(() => {
    if (auth.isAdmin) {
        return [
            { label: t('nav.dashboard'), icon: 'fa-house', route: 'admin.dashboard' },
            { label: t('nav.users'), icon: 'fa-users', route: 'admin.users' },
            { label: t('nav.caseManagers'), icon: 'fa-user-tie', route: 'admin.case-managers' },
            { label: t('nav.schedule'), icon: 'fa-calendar', route: 'admin.schedule' },
            { label: t('nav.appointments'), icon: 'fa-calendar-check', route: 'admin.appointments' },

        ]
    }
    if (auth.isCaseManager) {
        return [
            { label: t('nav.dashboard'), icon: 'fa-house', route: 'manager.dashboard' },
            { label: t('nav.clients'), icon: 'fa-users', route: 'manager.clients' },
            { label: t('nav.appointments'), icon: 'fa-calendar-check', route: 'manager.appointments' },
            { label: t('nav.calendar'), icon: 'fa-calendar', route: 'manager.calendar' },
        ]
    }
    return [
        { label: t('nav.dashboard'), icon: 'fa-house', route: 'client.dashboard' },
        { label: t('nav.appointments'), icon: 'fa-calendar-check', route: 'client.appointments' },
    ]
})

const initials = computed(() => {
    const name = auth.user?.name || ''
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

function isActive(routeName) {
    return route.name === routeName
}

async function handleLogout() {
    await auth.logout()
}
</script>

<template>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img :src="logoUrl" alt="Logo" />
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <img v-if="auth.user?.profile_image_url" :src="auth.user.profile_image_url" alt="avatar" />
                <span v-else class="avatar-initials">{{ initials }}</span>
            </div>
            <div class="sidebar-profile-info">
                <span class="sidebar-profile-name">{{ auth.user?.name }}</span>
                <span class="sidebar-profile-email">{{ auth.user?.role?.replace('_', ' ') }}</span>
            </div>
        </div>
        <div class="sidebar-divider"></div>
        <nav class="sidebar-nav">
            <a v-for="item in menuItems" :key="item.route" class="sidebar-item"
                :class="{ active: isActive(item.route) }" :data-tooltip="item.label" href="#"
                @click.prevent="router.push({ name: item.route })">
                <i :class="['fa', item.icon]"></i>
                <span class="sidebar-text">{{ item.label }}</span>
                <span v-if="item.badge" class="sidebar-badge">{{ item.badge }}</span>
            </a>
        </nav>
        <div class="sidebar-divider"></div>
        <div class="sidebar-footer">
            <a class="sidebar-item" href="#" :data-tooltip="t('nav.settings')">
                <i class="fa fa-gear"></i>
                <span class="sidebar-text">{{ t('nav.settings') }}</span>
            </a>
            <button class="sidebar-item sidebar-logout" :data-tooltip="t('nav.logout')" @click="handleLogout">
                <i class="fa fa-right-from-bracket"></i>
                <span class="sidebar-text">{{ t('nav.logout') }}</span>
            </button>
        </div>
    </aside>
</template>

<!-- styles sin cambios -->

<style scoped>
.sidebar {
    display: none;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    background: rgb(19, 28, 46);
    border-right: 1px solid #1e2a3a;
    transition: width 0.3s ease;
}

.sidebar-logo {
    display: grid;
    place-items: center;
    width: 100%;
    padding: 8px 12px;
}

.sidebar-logo img {
    width: 100%;
    height: auto;
    max-width: 120px;
    object-fit: contain;
}

.sidebar-divider {
    width: calc(100% - 32px);
    height: 1px;
    background: #1e2a3a;
    margin: 6px 16px;
    flex-shrink: 0;
}

.sidebar-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    margin: 4px 10px;
    background: #1e2a3a;
    border-radius: 12px;
    width: calc(100% - 20px);
    overflow: hidden;
}

.sidebar-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    background: #4a90e2;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sidebar-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initials {
    color: white;
    font-size: 14px;
    font-weight: 700;
}

.sidebar-profile-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    white-space: nowrap;
    flex: 1;
}

.sidebar-profile-name {
    font-size: 13px;
    font-weight: 600;
    color: white;
}

.sidebar-profile-email {
    font-size: 11px;
    color: #7a8aaa;
    text-transform: capitalize;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    width: 100%;
    gap: 2px;
    padding: 0 15px;
    flex: 1;
}

.sidebar-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 15px;
    color: #7a8aaa;
    text-decoration: none;
    font-size: 13px;
    border-radius: 10px;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
    border: none;
    background: none;
    white-space: nowrap;
    position: relative;
    font-family: 'Segoe UI', sans-serif;
}

.sidebar-item:hover {
    background: #1e2a3a;
    color: #c9d4e8;
}

.sidebar-item.active {
    background: #1a3a6e;
    color: white;
}

.sidebar-item i {
    font-size: 16px;
    min-width: 20px;
    text-align: center;
    flex-shrink: 0;
}

.sidebar-badge {
    margin-left: auto;
    background: #4a90e2;
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 20px;
}

.sidebar-footer {
    display: flex;
    flex-direction: column;
    width: 100%;
    gap: 2px;
    padding: 0 10px 12px;
    margin-top: auto;
}

.sidebar-logout:hover {
    background: #2d1515;
    color: #ff6b6b;
}

@media (min-width: 768px) {
    .sidebar {
        display: flex;
        grid-area: sidebar;
    }
}
</style>
