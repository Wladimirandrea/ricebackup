<!-- resources/js/components/schedule/ScheduleCard.vue -->
<template>
    <div class="sc-card" :class="{ 'sc-card--working': localWorking }">

        <!-- Toggle input oculto -->
        <input :id="`toggle-${day.day_of_week}`" v-model="localWorking" type="checkbox" class="sc-toggle-input"
            @change="onToggle" />

        <div class="sc-content">
            <!-- Título del día -->
            <h2 class="sc-title">{{ dayName }}</h2>
            <div class="sc-divider" />

            <!-- Switch día/noche -->
            <div class="sc-switch-row">
                <span class="sc-label" :class="{ 'sc-label--active': !localWorking }">
                    {{ $t('schedule.dayOff') }}
                </span>

                <label :for="`toggle-${day.day_of_week}`" class="sc-switch">
                    <div class="sc-sun-moon">
                        <div class="sc-dots" />
                    </div>
                    <div class="sc-bg">
                        <div class="sc-stars1" />
                        <div class="sc-stars2" />
                    </div>
                </label>

                <span class="sc-label" :class="{ 'sc-label--active': localWorking }">
                    {{ $t('schedule.work') }}
                </span>
            </div>

            <!-- Time pickers -->
            <div class="sc-times" :class="{ 'sc-times--disabled': !localWorking }">
                <div class="sc-time-box">
                    <input v-model="localStart" type="time" :disabled="!localWorking" class="sc-time-input" />
                </div>
                <span class="sc-time-sep">—</span>
                <div class="sc-time-box">
                    <input v-model="localEnd" type="time" :disabled="!localWorking" class="sc-time-input" />
                </div>
            </div>

            <!-- Error -->
            <p v-if="errorMsg" class="sc-error">{{ errorMsg }}</p>

            <!-- Botón Save -->
            <button class="sc-save-btn" :disabled="saving" @click="save">
                <span v-if="saving" class="sc-save-spinner" />
                <span v-else>{{ $t('schedule.save') }}</span>
            </button>

            <!-- Success -->
            <Transition name="fade">
                <p v-if="saved" class="sc-success">✓ {{ $t('schedule.saved') }}</p>
            </Transition>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useScheduleStore } from '@/stores/scheduleStore'

const { t, locale } = useI18n()
const store = useScheduleStore()

const props = defineProps({
    day: { type: Object, required: true },
})

const DAY_NAMES_EN = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
const DAY_NAMES_ES = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']

const dayName = computed(() =>
    locale.value === 'es'
        ? DAY_NAMES_ES[props.day.day_of_week]
        : DAY_NAMES_EN[props.day.day_of_week]
)

const localWorking = ref(props.day.is_working)
const localStart = ref(props.day.start_time ?? '08:00')
const localEnd = ref(props.day.end_time ?? '17:00')
const saving = ref(false)
const saved = ref(false)
const errorMsg = ref('')

watch(() => props.day, (val) => {
    localWorking.value = val.is_working
    localStart.value = val.start_time ?? '08:00'
    localEnd.value = val.end_time ?? '17:00'
}, { deep: true })

function onToggle() {
    errorMsg.value = ''
}

async function save() {
    errorMsg.value = ''
    saving.value = true
    saved.value = false

    const result = await store.updateDay({
        id: props.day.id,
        is_working: localWorking.value,
        start_time: localWorking.value ? localStart.value : null,
        end_time: localWorking.value ? localEnd.value : null,
    })

    saving.value = false

    if (result.success) {
        saved.value = true
        setTimeout(() => saved.value = false, 2500)
    } else {
        errorMsg.value = result.message || t('schedule.error')
    }
}
</script>

<style scoped>
/* ── Card ── */
.sc-card {
    width: 200px;
    border-radius: 22px;
    padding: 25px 16px;
    position: relative;
    background: #666666;
    border: 2px solid #404040;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    transition: background 0.5s ease, border-color 0.5s ease;
    display: flex;
    flex-direction: column;
}

.sc-card--working {
    background: #b4c7e7;
    border-color: #2f5597;
}

.sc-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    gap: 0;
}

/* Toggle input oculto */
.sc-toggle-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

/* Título */
.sc-title {
    font-size: 1.3rem;
    font-weight: 400;
    margin: 0 0 8px;
    color: #fff;
    transition: color 0.5s;
    text-align: center;
}

.sc-card--working .sc-title {
    color: #000;
}

/* Divider */
.sc-divider {
    width: 90%;
    height: 8px;
    border-radius: 2px;
    border: 1px solid #000;
    margin-bottom: 14px;
    background: #b4c7e7;
    transition: background 0.5s;
}

.sc-card--working .sc-divider {
    background: #595959;
}

/* Switch row */
.sc-switch-row {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

/* Labels */
.sc-label {
    font-size: 0.82rem;
    color: #fff;
    opacity: 0.5;
    font-weight: 400;
    transition: color 0.5s, opacity 0.5s, font-weight 0.3s;
}

.sc-label--active {
    opacity: 1;
    font-weight: 700;
}

.sc-card--working .sc-label {
    color: #555;
}

.sc-card--working .sc-label--active {
    color: #222;
}

/* Switch animado */
.sc-switch {
    position: relative;
    display: block;
    width: 4.2rem;
    height: 2.3rem;
    border-radius: 2rem;
    overflow: hidden;
    cursor: pointer;
    flex-shrink: 0;
}

.sc-bg {
    position: absolute;
    inset: 0;
    border-radius: 2rem;
    border: 3px solid #202020;
    background: linear-gradient(to right, #484848, #202020);
    transition: all 0.3s;
}

.sc-card--working .sc-bg {
    border-color: #78C1D5;
    background: linear-gradient(to right, #78C1D5, #BBE7F5);
}

.sc-stars1,
.sc-stars2 {
    position: absolute;
    height: 5px;
    width: 5px;
    background: #fff;
    border-radius: 50%;
    transition: 0.3s all ease;
}

.sc-stars1 {
    top: 5px;
    right: 16px;
}

.sc-stars2 {
    top: 28px;
    right: 32px;
}

.sc-card--working .sc-stars1,
.sc-card--working .sc-stars2 {
    opacity: 0;
    transform: translateY(1.5rem);
}

.sc-sun-moon {
    position: absolute;
    left: 3px;
    top: 3px;
    width: 1.7rem;
    height: 1.7rem;
    background: #FFFDF2;
    border-radius: 50%;
    border: 3px solid #DEE2C6;
    z-index: 2;
    transition: all 0.5s ease;
}

.sc-card--working .sc-sun-moon {
    left: calc(100% - 2rem);
    background: #F5EC59;
    border-color: #E7C65C;
    transform: rotate(-25deg);
}

.sc-dots {
    position: absolute;
    top: 1px;
    left: 12px;
    height: 7px;
    width: 7px;
    background: #EFEEDB;
    border: 3px solid #DEE2C6;
    border-radius: 50%;
    transition: 0.4s all ease;
}

.sc-card--working .sc-dots {
    height: 10px;
    width: 10px;
    top: 0;
    left: -10px;
    background: #fff;
    border-color: #fff;
    transform: rotate(25deg);
}

/* Time inputs */
.sc-times {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 14px;
    transition: opacity 0.3s;
}

.sc-times--disabled {
    opacity: 0.35;
    pointer-events: none;
}

.sc-time-box {
    background: #fff;
    border: 1px solid #7f7f7f;
    padding: 2px 6px;
    border-radius: 6px;
    box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.15);
    height: 34px;
    display: flex;
    align-items: center;
}

.sc-time-input {
    border: none;
    outline: none;
    font-size: 0.75rem;
    font-family: monospace;
    width: 58px;
    height: 24px;
    background: transparent;
    cursor: pointer;
    padding: 0;
}

.sc-time-input::-webkit-calendar-picker-indicator {
    transform: scale(0.8);
    cursor: pointer;
}


.sc-time-sep {
    color: #fff;
    font-size: 1.2rem;
    font-weight: 700;
}

.sc-card--working .sc-time-sep {
    color: #333;
}

/* Save button */
.sc-save-btn {
    background: linear-gradient(to bottom, #4f81bd, #2f5597);
    border: 2px solid #1f3864;
    color: #fff;
    font-size: 0.88rem;
    padding: 6px 32px;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.4);
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    transition: filter 0.2s, transform 0.15s;
    min-width: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.sc-save-btn:hover:not(:disabled) {
    filter: brightness(1.1);
    transform: translateY(-1px);
}

.sc-save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.sc-save-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: sc-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes sc-spin {
    to {
        transform: rotate(360deg);
    }
}

/* Messages */
.sc-error {
    color: #e53935;
    font-size: 0.78rem;
    margin: 6px 0 0;
    text-align: center;
}

.sc-success {
    color: #2e7d32;
    font-size: 0.82rem;
    margin: 6px 0 0;
    text-align: center;
    font-weight: 600;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Mobile */
@media (max-width: 767px) {
    .sc-card {
        width: 100%;
        max-width: 260px;
    }

    .sc-time-box {
        padding: 2px 6px;
        height: 34px;
    }

    .sc-time-input {
        width: 58px;
        height: 24px;
        font-size: 0.75rem;
    }
}

@media (min-width: 1024px) {

    .sc-card {
        width: 240px;
        padding: 32px 22px;
    }

    .sc-title {
        font-size: 1.6rem;
    }

    .sc-switch-row {
        gap: 18px;
        margin-bottom: 28px;
    }

    .sc-label {
        font-size: 0.95rem;
    }

    .sc-times {
        gap: 10px;
        margin-bottom: 18px;
    }

    .sc-time-box {
        padding: 4px 8px;
    }

    .sc-time-input {
        width: 78px;
        height: 28px;
        font-size: 0.62rem;
    }

    .sc-save-btn {
        width: 100%;
        font-size: 0.95rem;
        padding: 10px 20px;
    }
}
</style>