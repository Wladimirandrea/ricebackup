<template>
    <div class="sc-view">
        <AppTopbar :title="$t('schedule.title')" :crumbs="[
            { label: $t('users.crumbs.dashboard'), icon: 'fa-house', route: 'admin.dashboard' },
            { label: $t('schedule.title'), icon: 'fa-calendar' },
        ]" :actions="[]" />

        <div class="sc-view__body">
            <div class="sc-view__layout">

                <!-- Grid de cards -->
                <div class="sc-view__left">
                    <div v-if="scheduleStore.loading" class="sc-view__loading">
                        <div class="sc-spinner" />
                        <span>{{ $t('common.loading') }}</span>
                    </div>
                    <div v-else-if="scheduleStore.error" class="sc-view__error">
                        {{ scheduleStore.error }}
                    </div>
                    <div v-else class="sc-view__grid">
                        <ScheduleCard v-for="day in scheduleStore.schedules" :key="day.day_of_week" :day="day" />
                    </div>
                </div>

                <!-- Panel Days Off -->
                <div class="sc-view__right">
                    <DaysOffPanel />
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useScheduleStore } from '@/stores/scheduleStore'
import { useDayOffStore } from '@/stores/dayOffStore'
import ScheduleCard from '@/components/schedule/ScheduleCard.vue'
import DaysOffPanel from '@/components/schedule/DaysOffPanel.vue'
import AppTopbar from '@/components/layout/AppTopbar.vue'

const scheduleStore = useScheduleStore()
const dayOffStore = useDayOffStore()

onMounted(() => {
    scheduleStore.fetchSchedule()
    dayOffStore.fetchDaysOff()
})
</script>

<style scoped>
.sc-view {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.sc-view__body {
    padding: 24px;
    flex: 1;
    overflow-y: hidden;
}

.sc-view__layout {
    display: flex;
    gap: 24px;
    align-items: flex-start;
    height: 100%;
}

.sc-view__left {
    flex: 1;
    min-width: 0;
    overflow-y: auto;
    height: 100%;
}

.sc-view__right {
    flex-shrink: 0;
    height: 100%;     
    overflow: hidden;
}

.sc-view__loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 48px 0;
    color: #90a4ae;
}

.sc-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e3eaf4;
    border-top-color: #1565c0;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.sc-view__error {
    text-align: center;
    color: #e53935;
    padding: 48px 0;
}

.sc-view__grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: flex-start;
}

@media (max-width: 1024px) {
    .sc-view__layout {
        flex-direction: column;
        height: auto; 
    }
     .sc-view__left {
        overflow-y: visible; /* ← quitar scroll interno */
        height: auto;
    }
    .sc-view__right {
        width: 100%;
        height: auto;        
        overflow: visible;
    }
    .sc-view__body {
        overflow-y: auto;    /* ← restaurar scroll en el body */
    }
}

@media (max-width: 767px) {
    .sc-view {
        overflow: visible;   /* ← quitar overflow hidden */
    }
    .sc-view__body {
        padding: 16px;
        overflow-y: auto;
    }

    .sc-view__grid {
        justify-content: center;
    }
}
</style>