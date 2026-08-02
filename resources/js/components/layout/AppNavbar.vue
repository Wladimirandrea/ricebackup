<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLocale } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notificationStore'

const emit = defineEmits(['toggle'])
const { t } = useI18n()
const { locale, setLocale } = useLocale()
const authStore = useAuthStore()
const notifStore = useNotificationStore()
const router = useRouter()

const langOpen = ref(false)
const profileOpen = ref(false)
const notifOpen = ref(false)

function toggleLocale(lang) { setLocale(lang); langOpen.value = false }

function toggleNotif() {
    notifOpen.value = !notifOpen.value
    if (notifOpen.value) notifStore.markAllRead()
}

function formatNotif(n) {
    const icons = { created: '📅', status_changed: '🔄', cancelled: '❌' }
    const statusColors = {
        pending: '#f59e0b',
        confirmed: '#3b82f6',
        completed: '#22c55e',
        cancelled: '#ef4444',
    }
    return { icon: icons[n.type] ?? '🔔', color: statusColors[n.status] ?? '#7a8aaa' }
}

function timeAgo(isoDate) {
    const diff = Math.floor((Date.now() - new Date(isoDate)) / 1000)
    if (diff < 60) return t('notifications.justNow')
    if (diff < 3600) return `${Math.floor(diff / 60)}${t('notifications.minsAgo')}`
    if (diff < 86400) return `${Math.floor(diff / 3600)}${t('notifications.hoursAgo')}`
    return `${Math.floor(diff / 86400)}${t('notifications.daysAgo')}`
}

function goToNotif(n) {
    notifOpen.value = false
    const role = authStore.user?.role
    const routeName = role === 'admin' ? 'admin.appointments.day' : 'manager.appointments.day'
    router.push({ name: routeName, params: { date: n.date } })
}


async function logout() {
    profileOpen.value = false
    await authStore.logout()
    router.push({ name: 'login' })
}

const initials = computed(() => {
    const name = authStore.user?.name || ''
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})
</script>

<template>
    <header class="navbar">
        <button class="toggle-btn" @click="emit('toggle')">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="nav-right">

            <!-- Language -->
            <div class="lang-selector">
                <button class="lang-btn" @click="langOpen = !langOpen">
                    <span>{{ locale === 'en' ? '🇺🇸' : '🇲🇽' }}</span>
                    <span class="lang-code">{{ locale.toUpperCase() }}</span>
                    <i class="fa fa-chevron-down lang-arrow" :class="{ rotated: langOpen }"></i>
                </button>
                <div class="lang-dropdown" :class="{ open: langOpen }">
                    <button class="lang-option" :class="{ active: locale === 'en' }" @click="toggleLocale('en')">
                        <span>English (EN)</span><span>🇺🇸</span>
                    </button>
                    <button class="lang-option" :class="{ active: locale === 'es' }" @click="toggleLocale('es')">
                        <span>Español (ES)</span><span>🇲🇽</span>
                    </button>
                </div>
                <div v-if="langOpen" class="profile-overlay" @click="langOpen = false" />
            </div>

            <div class="nav-divider"></div>

            <!-- Notifications -->
            <div class="notif-wrapper">
                <div class="nav-icon-btn" @click="toggleNotif">
                    <i class="fa fa-bell"></i>
                    <span v-if="notifStore.unreadCount > 0" class="nav-badge">
                        {{ notifStore.unreadCount > 9 ? '9+' : notifStore.unreadCount }}
                    </span>
                </div>

                <Transition name="dropdown-fade">
                    <div v-if="notifOpen" class="notif-dropdown">
                        <div class="notif-header">
                            <span class="notif-title">{{ $t('notifications.title') }}</span>
                            <button v-if="notifStore.notifications.length" class="notif-clear"
                                @click="notifStore.clear()">
                                {{ $t('notifications.clearAll') }}
                            </button>
                        </div>
                        <div class="notif-list">
                            <div v-if="notifStore.notifications.length === 0" class="notif-empty">
                                <i class="fa fa-bell-slash"></i>
                                <span>{{ $t('notifications.empty') }}</span>
                            </div>
                            <div v-for="n in notifStore.notifications" :key="n.id" class="notif-item"
                                :class="{ 'notif-item--unread': !n.read }" @click="goToNotif(n)">
                                <div class="notif-icon">{{ formatNotif(n).icon }}</div>
                                <div class="notif-content">
                                    <p class="notif-text">
                                        <strong>{{ n.clientName }}</strong>
                                        <span v-if="n.type === 'created'"> — {{ $t('notifications.newAppointment')
                                        }}</span>
                                        <span v-else-if="n.type === 'cancelled'"> — {{ $t('notifications.cancelled')
                                        }}</span>
                                        <span v-else> — {{ $t('notifications.status') }}:
                                            <span class="notif-status" :style="{ color: formatNotif(n).color }">{{
                                                $t(`appointments.${n.status}`) }}</span>
                                        </span>
                                    </p>
                                    <p class="notif-meta">{{ n.date }} · {{ n.time }} · {{ n.caseManagerName }} · {{
                                        timeAgo(n.createdAt) }}</p>
                                </div>
                                <div v-if="!n.read" class="notif-dot" />
                            </div>
                        </div>
                    </div>
                </Transition>
                <div v-if="notifOpen" class="profile-overlay" @click="notifOpen = false" />
            </div>

            <div class="nav-divider"></div>

            <!-- Profile -->
            <div class="profile-selector">
                <div class="nav-profile" @click="profileOpen = !profileOpen">
                    <div class="nav-avatar">{{ initials }}</div>
                    <div class="nav-profile-info">
                        <span class="nav-profile-name">{{ authStore.user?.name }}</span>
                        <span class="nav-profile-role">{{ authStore.user?.role ?? 'Admin' }}</span>
                    </div>
                    <i class="fa fa-chevron-down nav-chevron" :class="{ rotated: profileOpen }"></i>
                </div>
                <div class="profile-dropdown" :class="{ open: profileOpen }">
                    <div class="profile-dropdown__header">
                        <div class="profile-dropdown__avatar">{{ initials }}</div>
                        <div class="profile-dropdown__meta">
                            <span class="profile-dropdown__name">{{ authStore.user?.name }}</span>
                            <span class="profile-dropdown__email">{{ authStore.user?.email }}</span>
                        </div>
                    </div>
                    <div class="profile-dropdown__divider" />
                    <button class="profile-dropdown__logout" @click="logout">
                        <i class="fa-solid fa-right-from-bracket" />
                        <span>{{ $t('nav.logout') }}</span>
                    </button>
                </div>
                <div v-if="profileOpen" class="profile-overlay" @click="profileOpen = false" />
            </div>

        </div>
    </header>
</template>

<style scoped>
.navbar {
    grid-area: navbar;
    background: rgb(2 41 36);
    border-bottom: 1px solid #1e2a3a;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 8px;
    z-index: 100;
}

.toggle-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0 1rem;
    height: 100%;
    display: flex;
    align-items: center;
}

.toggle-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 2px;
    margin-left: auto;
}

.nav-icon-btn {
    position: relative;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.2s;
    color: #c9d4e8;
    font-size: 16px;
}

.nav-icon-btn:hover {
    background: #1e2a3a;
}

.nav-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #e24b4a;
    color: white;
    font-size: 9px;
    font-weight: 700;
    border-radius: 20px;
    min-width: 14px;
    height: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    border: 1.5px solid #131c2e;
}

.nav-divider {
    width: 1px;
    height: 24px;
    background: #2a3a55;
    margin: 0 4px;
}

/* Language */
.lang-selector {
    position: relative;
}

.lang-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #1e2a3a;
    border: 1px solid #2a3a55;
    border-radius: 8px;
    color: white;
    font-size: 13px;
    padding: 6px 10px;
    cursor: pointer;
    transition: background 0.2s;
}

.lang-btn:hover {
    background: #2a3a55;
}

.lang-code {
    font-size: 12px;
}

.lang-arrow {
    font-size: 10px;
    color: #7a8aaa;
    transition: transform 0.2s;
}

.lang-arrow.rotated {
    transform: rotate(180deg);
}

.lang-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #111827;
    border: 1px solid #1e2a3a;
    border-radius: 10px;
    overflow: hidden;
    min-width: 170px;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-6px);
    transition: opacity 0.2s ease, transform 0.2s ease;
    z-index: 1001;
}

.lang-dropdown.open {
    opacity: 1;
    pointer-events: all;
    transform: translateY(0);
}

.lang-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 10px 14px;
    color: #7a8aaa;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
    background: none;
    border: none;
    font-family: 'Segoe UI', sans-serif;
}

.lang-option:hover {
    background: #1e2a3a;
    color: white;
}

.lang-option.active {
    color: white;
}

/* Notifications */
.notif-wrapper {
    position: relative;
}

.notif-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #111827;
    border: 1px solid #1e2a3a;
    border-radius: 14px;
    width: 320px;
    max-height: 420px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 1001;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px 10px;
    border-bottom: 1px solid #1e2a3a;
    flex-shrink: 0;
}

.notif-title {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
}

.notif-clear {
    font-size: 11px;
    color: #7a8aaa;
    background: none;
    border: none;
    cursor: pointer;
    font-family: 'Segoe UI', sans-serif;
    transition: color 0.2s;
}

.notif-clear:hover {
    color: #f87171;
}

.notif-list {
    overflow-y: auto;
    flex: 1;
}

.notif-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 32px 16px;
    color: #7a8aaa;
    font-size: 13px;
}

.notif-empty i {
    font-size: 24px;
    opacity: 0.4;
}

.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #1a2235;
    transition: background 0.2s;
    position: relative;
    cursor: pointer; 
}

.notif-item:last-child {
    border-bottom: none;
}

.notif-item:hover {
    background: #1a2235;
}

.notif-item--unread {
    background: rgba(74, 144, 226, 0.05);
}

.notif-icon {
    font-size: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.notif-content {
    flex: 1;
    min-width: 0;
}

.notif-text {
    font-size: 12px;
    color: #c9d4e8;
    margin: 0 0 4px;
    line-height: 1.4;
}

.notif-text strong {
    color: #fff;
}

.notif-status {
    font-weight: 700;
    text-transform: capitalize;
}

.notif-meta {
    font-size: 11px;
    color: #7a8aaa;
    margin: 0;
}

.notif-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #4a90e2;
    flex-shrink: 0;
    margin-top: 6px;
}

/* Profile */
.profile-selector {
    position: relative;
}

.nav-profile {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 8px 0 4px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.nav-profile:hover {
    background: #1e2a3a;
}

.nav-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #4a90e2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.nav-profile-info {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.nav-profile-name {
    font-size: 13px;
    color: white;
    font-weight: 500;
    white-space: nowrap;
}

.nav-profile-role {
    font-size: 10px;
    color: #7a8aaa;
    text-transform: capitalize;
}

.nav-chevron {
    color: #7a8aaa;
    font-size: 11px;
    transition: transform 0.2s;
}

.nav-chevron.rotated {
    transform: rotate(180deg);
}

.profile-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #111827;
    border: 1px solid #1e2a3a;
    border-radius: 12px;
    min-width: 220px;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-6px);
    transition: opacity 0.2s ease, transform 0.2s ease;
    z-index: 1001;
    overflow: hidden;
}

.profile-dropdown.open {
    opacity: 1;
    pointer-events: all;
    transform: translateY(0);
}

.profile-dropdown__header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
}

.profile-dropdown__avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #4a90e2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.profile-dropdown__meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: hidden;
}

.profile-dropdown__name {
    font-size: 13px;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-dropdown__email {
    font-size: 11px;
    color: #7a8aaa;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-dropdown__divider {
    height: 1px;
    background: #1e2a3a;
}

.profile-dropdown__logout {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 16px;
    background: none;
    border: none;
    color: #f87171;
    font-size: 13px;
    font-family: 'Segoe UI', sans-serif;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
    text-align: left;
}

.profile-dropdown__logout:hover {
    background: rgba(248, 113, 113, 0.1);
    color: #fca5a5;
}

.profile-dropdown__logout i {
    font-size: 14px;
}

.profile-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
}

/* Transitions */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
    transition: opacity 0.2s, transform 0.2s;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>