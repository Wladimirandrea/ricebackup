<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppBottomNav from '@/components/layout/AppBottomNav.vue'
import ToastContainer from '@/components/layout/ToastContainer.vue'

const auth       = useAuthStore()
const notifStore = useNotificationStore()

const collapsed = ref(false)
const navHidden = ref(false)

const roleClass = computed(() => {
    if (auth.isAdmin)       return 'theme-admin'
    if (auth.isCaseManager) return 'theme-manager'
    if (auth.isClient)      return 'theme-client'
    return ''
})

function handleToggle() {
    if (window.innerWidth < 768) {
        navHidden.value = !navHidden.value
    } else {
        collapsed.value = !collapsed.value
    }
}

onMounted(() => {
    // Admin y case manager reciben notificaciones de citas en tiempo real
    if (auth.isAdmin || auth.isCaseManager) {
        notifStore.subscribeReverb()
    }
})

onUnmounted(() => {
    notifStore.unsubscribeReverb()
})
</script>

<template>
    <div class="layout" :class="[{ collapsed, 'nav-hidden': navHidden }, roleClass]">
        <AppNavbar @toggle="handleToggle" />
        <AppSidebar :collapsed="collapsed" />
        <main class="main-content">
            <RouterView />
        </main>
        <AppBottomNav />
        <ToastContainer />
    </div>
</template>

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.layout {
    display: grid;
    height: 100dvh;
    overflow: hidden;
    grid-template-columns: 1fr;
    grid-template-rows: 60px 1fr auto;
    grid-template-areas:
        "navbar"
        "main-content"
        "bottom-nav";
}

.main-content {
    grid-area: main-content;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    background: linear-gradient(to bottom,
        #00152b 0%, #054894 35%, #10448b 70%, #00152b 100%);
}

/* ── Tema Admin (default azul) ── */
.theme-admin .navbar,
.theme-admin .sidebar,
.theme-admin .bottom-nav {
    background: rgb(19, 28, 46);
}

/* ── Tema Case Manager (verde) ── */
.theme-manager .navbar,
.theme-manager .sidebar,
.theme-manager .bottom-nav {
    background: rgb(2, 41, 36);
}

.theme-manager .sidebar {
    border-right-color: rgba(255,255,255,0.08);
}

.theme-manager .navbar {
    border-bottom-color: rgba(255,255,255,0.08);
}

.theme-manager .bottom-nav {
    border-top-color: rgba(255,255,255,0.08);
}

.theme-manager .sidebar-item.active {
    background: rgba(2, 100, 80, 0.6);
}

.theme-manager .sidebar-item:hover {
    background: rgba(2, 100, 80, 0.3);
}

.theme-manager .sidebar-profile {
    background: rgba(2, 100, 80, 0.3);
}

.theme-manager .nav-icon-btn:hover,
.theme-manager .nav-profile:hover,
.theme-manager .lang-btn {
    background: rgba(2, 100, 80, 0.4);
}

.theme-manager .menu {
    background-color: rgb(2, 41, 36);
}

.theme-manager .menu_border {
    background-color: rgb(2, 41, 36);
}

/* ── Tema Cliente ── */
.theme-client .navbar,
.theme-client .sidebar,
.theme-client .bottom-nav {
    background: rgb(7, 36, 95);
}

.theme-client .sidebar {
    border-right-color: rgba(255,255,255,0.08);
}

.theme-client .navbar {
    border-bottom-color: rgba(255,255,255,0.08);
}

.theme-client .bottom-nav {
    border-top-color: rgba(255,255,255,0.08);
}

.theme-client .sidebar-item.active {
    background: rgba(7, 60, 160, 0.6);
}

.theme-client .sidebar-item:hover {
    background: rgba(7, 60, 160, 0.3);
}

.theme-client .sidebar-profile {
    background: rgba(7, 60, 160, 0.3);
}

.theme-client .nav-icon-btn:hover,
.theme-client .nav-profile:hover,
.theme-client .lang-btn {
    background: rgba(7, 60, 160, 0.4);
}

.theme-client .menu {
    background-color: rgb(7, 36, 95);
}

.theme-client .menu_border {
    background-color: rgb(7, 36, 95);
}

.layout.nav-hidden {
    grid-template-rows: 60px 1fr 0px;
}

.layout.nav-hidden .bottom-nav {
    transform: translateY(100%);
    opacity: 0;
    pointer-events: none;
    overflow: hidden;
}

@media (min-width: 768px) {
    .layout {
        grid-template-columns: 260px 1fr !important;
        grid-template-rows: 60px 1fr !important;
        grid-template-areas:
            "sidebar navbar"
            "sidebar main-content" !important;
        transition: grid-template-columns 0.3s ease;
    }

    .layout.collapsed {
        grid-template-columns: 80px 1fr !important;
    }

    .layout.nav-hidden {
        grid-template-rows: 60px 1fr !important;
    }

    .bottom-nav {
        display: none !important;
    }

    .layout.collapsed .sidebar-profile-info,
    .layout.collapsed .sidebar-text,
    .layout.collapsed .sidebar-badge {
        opacity: 0;
        width: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .layout.collapsed .sidebar-item:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        left: calc(100% + 10px);
        top: 50%;
        transform: translateY(-50%);
        background: #4a90e2;
        color: white;
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 6px;
        white-space: nowrap;
        pointer-events: none;
        z-index: 9999;
    }
}
</style>