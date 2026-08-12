<template>
    <div class="al-view">
        <AppTopbar :title="$t('appointments.title')" :crumbs="[
            { label: $t('nav.dashboard'), icon: 'fa-house', route: 'manager.dashboard' },
            { label: $t('appointments.title'), icon: 'fa-calendar-check' },
        ]" :search="true" :search-value="store.search"
            :search-placeholder="$t('managerAppointmentsList.searchPlaceholder')" @update:search-value="onSearch" />

        <div class="al-body">
            <!-- Tabs de status -->
            <div class="al-tabs">
                <button v-for="tab in tabs" :key="tab.key" class="al-tab"
                    :class="{ 'al-tab--active': store.status === tab.key }" @click="store.setStatus(tab.key)">
                    {{ $t(tab.label) }}
                    <span class="al-tab__count">{{ store.counts[tab.key] ?? 0 }}</span>
                </button>
            </div>

            <!-- Loading -->
            <div v-if="store.loading" class="al-loading">
                <div v-for="n in 5" :key="n" class="al-skeleton" />
            </div>

            <!-- Error -->
            <div v-else-if="store.error" class="al-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>{{ store.error }}</p>
            </div>

            <!-- Empty -->
            <div v-else-if="store.appointments.length === 0" class="al-empty">
                <div class="al-empty__icon">📅</div>
                <h3>{{ $t('appointments.noAppointments') }}</h3>
                <p>{{ $t('managerAppointmentsList.noAppointmentsDesc') }}</p>
            </div>

            <!-- Lista -->
            <TransitionGroup v-else name="row-fade" tag="div" class="al-list">
                <div v-for="a in store.appointments" :key="a.id" class="al-row" :class="`al-row--${a.status}`"
                    role="button" tabindex="0" @click="openAppointmentDetail(a)"
                    @keyup.enter="openAppointmentDetail(a)">
                    <img :src="a.client?.profile_image_url" class="al-row__avatar" :alt="a.client?.name" />
                    <div class="al-row__info">
                        <span class="al-row__name">{{ a.client?.name || '—' }}</span>
                        <span class="al-row__meta">
                            {{ a.date }} · {{ a.start_time }} - {{ a.end_time }}
                        </span>
                        <span v-if="a.notes" class="al-row__notes">{{ a.notes }}</span>
                    </div>
                    <span class="al-badge" :class="`al-badge--${a.status}`">
                        {{ $t(`appointments.${a.status}`) }}
                    </span>
                </div>
            </TransitionGroup>
        </div>
        <ManagerAppointmentDetailModal v-model="showDetailModal" :appointment="selectedAppointment"
            @status-changed="handleAppointmentChanged" @appointment-updated="handleAppointmentChanged" />
    </div>
</template>

<script setup>
import { onMounted, computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useManagerAppointmentsListStore } from '@/stores/managerAppointmentsListStore'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import ManagerAppointmentDetailModal from '@/components/appointments/ManagerAppointmentDetailModal.vue'
const route = useRoute()
const store = useManagerAppointmentsListStore()

const showDetailModal = ref(false)
const selectedAppointment = ref(null)

function openAppointmentDetail(appt) {
    selectedAppointment.value = appt
    showDetailModal.value = true
}

async function handleAppointmentChanged() {
    await store.fetchAppointments()
    showDetailModal.value = false
}

const tabs = computed(() => [
    { key: 'all', label: 'appointments.all' },
    { key: 'pending', label: 'appointments.pending' },
    { key: 'confirmed', label: 'appointments.confirmed' },
    { key: 'completed', label: 'appointments.completed' },
    { key: 'cancelled', label: 'appointments.cancelled' },
])

let searchTimer = null
function onSearch(val) {
    store.search = val
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => store.fetchAppointments(), 350)
}

onMounted(() => {
    if (route.query.status && ['pending', 'confirmed', 'completed', 'cancelled'].includes(route.query.status)) {
        store.status = route.query.status
    }
    store.fetchAppointments()
})
</script>

<style scoped>
.al-view {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.al-body {
    flex: 1;
    overflow-y: auto;
    padding: 28px 32px;
}

/* Tabs */
.al-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.al-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.al-tab:hover {
    background: rgba(255, 255, 255, 0.08);
}

.al-tab--active {
    background: rgba(99, 102, 241, 0.18);
    border-color: rgba(99, 102, 241, 0.4);
    color: #fff;
}

.al-tab__count {
    background: rgba(255, 255, 255, 0.1);
    padding: 1px 8px;
    border-radius: 999px;
    font-size: 0.75rem;
}

/* Lista */
.al-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.al-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
    cursor: pointer;
}

.al-row--pending {
    background: rgba(158, 123, 19, 0.562);
    border-color: rgba(234, 179, 8, 0.35);
}

.al-row--confirmed {
    background: rgba(6, 146, 57, 0.808);
    border-color: rgba(34, 197, 94, 0.35);
}

.al-row--completed {
    background: rgba(13, 83, 196, 0.87);
    border-color: rgba(59, 130, 246, 0.35);
}

.al-row--cancelled {
    background: rgba(239, 68, 68, 0.753);
    border-color: rgba(239, 68, 68, 0.35);
}

.al-row__avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    background: rgba(255, 255, 255, 0.1);
}

.al-row__info {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.al-row__name {
    font-weight: 700;
    color: #fff;
    font-size: 0.92rem;
}

.al-row__meta {
    color: rgba(255, 255, 255, 0.45);
    font-size: 0.8rem;
}

.al-row__notes {
    color: rgba(255, 255, 255, 0.35);
    font-size: 0.78rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Badges por status */
.al-badge {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
}

.al-badge--pending {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.al-badge--confirmed {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.al-badge--completed {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.al-badge--cancelled {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

/* Loading skeleton */
.al-loading {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.al-skeleton {
    height: 72px;
    border-radius: 14px;
    background: linear-gradient(90deg,
            rgba(255, 255, 255, 0.06) 25%,
            rgba(255, 255, 255, 0.12) 50%,
            rgba(255, 255, 255, 0.06) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

/* Error / Empty */
.al-error,
.al-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 64px 0;
    text-align: center;
}

.al-error {
    color: #f87171;
    font-size: 0.9rem;
}

.al-error i {
    font-size: 2.5rem;
    opacity: 0.7;
}

.al-empty__icon {
    font-size: 4rem;
}

.al-empty h3 {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}

.al-empty p {
    color: rgba(255, 255, 255, 0.4);
    font-size: 0.88rem;
    margin: 0;
}

.row-fade-enter-active {
    transition: opacity 0.3s, transform 0.3s;
}

.row-fade-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

@media (max-width: 767px) {
    .al-body {
        padding: 16px;
    }
}
</style>