<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const activeIndex = ref(0)
const menuRef = ref(null)
const menuBorderLeft = ref('0px')

const adminItems = computed(() => [
    { label: t('nav.home'), icon: 'fa-house', route: 'admin.dashboard', color: '#4a90e2' },
    { label: t('nav.users'), icon: 'fa-users', route: 'admin.users', color: '#2dd4bf' },
    { label: t('nav.caseManagers'), icon: 'fa-user-tie', route: 'admin.case-managers', color: '#a78bfa' },
    { label: t('nav.schedule'), icon: 'fa-calendar', route: 'admin.schedule', color: '#a78bfa' },
    { label: t('nav.appointments'), icon: 'fa-calendar-check', route: 'admin.appointments', color: '#fb923c' },

])

const managerItems = computed(() => [
    { label: t('nav.home'),         icon: 'fa-house',          route: 'manager.dashboard',    color: '#4a90e2' },
    { label: t('nav.clients'),      icon: 'fa-users',           route: 'manager.clients',      color: '#2dd4bf' },
    { label: t('nav.appointments'), icon: 'fa-calendar-check',  route: 'manager.appointments', color: '#a78bfa' },
    { label: t('nav.calendar'),     icon: 'fa-calendar',        route: 'manager.calendar',     color: '#fb923c' },
])

const clientItems = computed(() => [
    { label: t('nav.home'), icon: 'fa-house', route: 'client.dashboard', color: '#4a90e2' },
    { label: t('nav.schedule'), icon: 'fa-calendar-check', route: 'client.appointments', color: '#2dd4bf' },
    { label: t('nav.profile'), icon: 'fa-user-tie', route: 'client.dashboard', color: '#a78bfa' },
])

const items = computed(() =>
    auth.isAdmin ? adminItems.value : auth.isCaseManager ? managerItems.value : clientItems.value
)

function updateBorder(index) {
    nextTick(() => {
        if (!menuRef.value) return
        const menuItems = menuRef.value.querySelectorAll('.menu_item')
        if (!menuItems[index]) return
        const item = menuItems[index]
        const menu = menuRef.value
        const itemRect = item.getBoundingClientRect()
        const menuRect = menu.getBoundingClientRect()
        const fontSize = parseFloat(getComputedStyle(menu).fontSize)
        const borderWidth = 10.9 * fontSize
        const left = itemRect.left - menuRect.left + itemRect.width / 2 - borderWidth / 2
        menuBorderLeft.value = `${left}px`
    })
}

function clickItem(index) {
    activeIndex.value = index
    updateBorder(index)
    router.push({ name: items.value[index].route })
}

function onResize() {
    updateBorder(activeIndex.value)
}

onMounted(() => {
    updateBorder(activeIndex.value)
    window.addEventListener('resize', onResize)
})

onUnmounted(() => {
    window.removeEventListener('resize', onResize)
})
</script>

<template>
    <div class="bottom-nav">
        <menu class="menu" ref="menuRef">
            <button v-for="(item, index) in items" :key="index" class="menu_item"
                :class="{ active: activeIndex === index }"
                :style="activeIndex === index ? `--bgColorItem: ${item.color}` : ''" @click="clickItem(index)">
                <i :class="['fa-solid', item.icon]" style="color: white;"></i>
                <span class="menu_label">{{ item.label }}</span>
            </button>
            <div class="menu_border" :style="{ transform: `translate3d(${menuBorderLeft}, 0, 0)` }"></div>
        </menu>
        <div class="svg-container">
            <svg viewBox="0 0 202.9 45.5">
                <clipPath id="menu" clipPathUnits="objectBoundingBox"
                    transform="scale(0.0049285362247413 0.021978021978022)">
                    <path d="M6.7,45.5c5.7,0.1,14.1-0.4,23.3-4c5.7-2.3,9.9-5,18.1-10.5c10.7-7.1,11.8-9.2,20.6-14.3c5-2.9,9.2-5.2,15.2-7
          c7.1-2.1,13.3-2.3,17.6-2.1c4.2-0.2,10.5,0.1,17.6,2.1c6.1,1.8,10.2,4.1,15.2,7c8.8,5,9.9,7.1,20.6,14.3c8.3,5.5,12.4,8.2,18.1,10.5
          c9.2,3.6,17.6,4.2,23.3,4H6.7z" />
                </clipPath>
            </svg>
        </div>
    </div>
</template>

<style scoped>
.bottom-nav {
    grid-area: bottom-nav;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgb(19, 28, 46);
    border-top: 1px solid #1e2a3a;
    padding: 6px 0 10px;
}

.menu {
    margin: 0;
    display: flex;
    width: 100%;
    max-width: 500px;
    font-size: 1.2em;
    padding: 0 1em;
    position: relative;
    align-items: center;
    justify-content: space-between;
    background-color: #131c2e;
    border-radius: 10px;
    --bgColorMenu: #131c2e;
    --duration: 0.5s;
    --bgColorItem: #4a90e2;
}

.menu_item {
    all: unset;
    flex-grow: 1;
    flex-basis: 0;
    z-index: 100;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    position: relative;
    border-radius: 50%;
    align-items: center;
    will-change: transform;
    justify-content: center;
    padding: 1em 0 0.15em;
    transition: transform var(--duration);
}

.menu_item::before {
    content: "";
    z-index: -1;
    width: 4em;
    height: 4em;
    border-radius: 50%;
    position: absolute;
    transform: scale(0);
    transition: transform var(--duration);
    top: 0;
}

.menu_item.active {
    transform: translate3d(0, -0.8em, 0);
}

.menu_item.active::before {
    transform: scale(1);
    background-color: var(--bgColorItem);
}

.menu_label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.45);
    white-space: nowrap;
    margin-top: 6px;
    line-height: 1;
}

.menu_item.active .menu_label {
    color: rgba(255, 255, 255, 0.9);
}

.menu_border {
    left: 0;
    bottom: 99%;
    width: 10.9em;
    height: 2.4em;
    position: absolute;
    clip-path: url(#menu);
    will-change: transform;
    background-color: #131c2e;
    transition: transform var(--duration);
}

.svg-container {
    width: 0;
    height: 0;
}

@media (min-width: 768px) {
    .bottom-nav {
        display: none;
    }
}
</style>