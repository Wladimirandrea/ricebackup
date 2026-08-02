<!-- resources/js/components/schedule/DaysOffPanel.vue -->
<template>
    <div class="dop-wrapper">

        <!-- ── Select Days Off ── -->
        <div class="dop-card">
            <h3 class="dop-title">{{ $t('schedule.selectDaysOff') }}</h3>

            <div class="dop-form">
                <!-- Fecha -->
                <div class="dop-field">
                    <div class="dop-field__icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <input v-model="form.date" type="date" class="dop-input" :min="today" />
                </div>

                <!-- Hora inicio -->
                <div class="dop-field">
                    <div class="dop-field__icon dop-field__icon--blue">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <input v-model="form.start_time" type="time" class="dop-input" />
                </div>

                <!-- Hora fin -->
                <div class="dop-field">
                    <div class="dop-field__icon dop-field__icon--red">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <input v-model="form.end_time" type="time" class="dop-input" />
                </div>

                <!-- Razón -->
                <div class="dop-reason">
                    <span class="dop-reason__label">{{ $t('schedule.reason') }}</span>
                    <input v-model="form.reason" type="text" class="dop-reason__input"
                        :placeholder="$t('schedule.reasonPlaceholder')" />
                </div>

                <!-- Error -->
                <p v-if="errorMsg" class="dop-error">{{ errorMsg }}</p>

                <!-- Save -->
                <button class="dop-save-btn" :disabled="store.saving" @click="save">
                    <span v-if="store.saving" class="dop-spinner" />
                    <span v-else>{{ $t('schedule.save') }}</span>
                </button>
            </div>
        </div>

        <!-- ── Days Off List ── -->
        <div class="dop-card dop-card--list">
            <h3 class="dop-title">{{ $t('schedule.daysOff') }}</h3>

            <div v-if="store.loading" class="dop-loading">
                <div class="dop-spinner dop-spinner--dark" />
            </div>

            <div v-else-if="store.daysOff.length === 0" class="dop-empty">
                {{ $t('schedule.noDaysOff') }}
            </div>

            <ul v-else class="dop-list">
                <li v-for="day in store.daysOff" :key="day.id" class="dop-item">
                    <div class="dop-item__dot" />
                    <div class="dop-item__info">
                        <span class="dop-item__date">{{ formatDate(day.date) }}</span>
                        <span class="dop-item__time">
                            {{ formatTime(day.start_time) }} - {{ formatTime(day.end_time) }}
                        </span>
                        <span v-if="day.reason" class="dop-item__reason">{{ day.reason }}</span>
                    </div>
                    <button class="dop-item__delete" @click="remove(day.id)" :title="$t('schedule.delete')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </li>
            </ul>
        </div>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDayOffStore } from '@/stores/dayOffStore'

const { t } = useI18n()
const store = useDayOffStore()

const today = new Date().toISOString().split('T')[0]

const form = ref({
    date: '',
    start_time: '08:00',
    end_time: '17:00',
    reason: '',
})

const errorMsg = ref('')

function formatDate(date) {
    return new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
        month: '2-digit', day: '2-digit', year: 'numeric'
    })
}

function formatTime(time) {
    if (!time) return ''
    const [h, m] = time.split(':')
    const hour = parseInt(h)
    const ampm = hour >= 12 ? 'PM' : 'AM'
    const hour12 = hour % 12 || 12
    return `${hour12}:${m} ${ampm}`
}

async function save() {
    errorMsg.value = ''

    if (!form.value.date) {
        errorMsg.value = t('schedule.dateRequired')
        return
    }

    const result = await store.createDayOff({
        date: form.value.date,
        start_time: form.value.start_time,
        end_time: form.value.end_time,
        reason: form.value.reason || null,
    })

    if (result.success) {
        form.value = { date: '', start_time: '08:00', end_time: '17:00', reason: '' }
    } else {
        const firstError = Object.values(result.errors ?? {})[0]
        errorMsg.value = firstError?.[0] ?? result.message ?? t('schedule.error')
    }
}

async function remove(id) {
    await store.deleteDayOff(id)
}
</script>

<style scoped>
.dop-wrapper {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 300px;
    min-width: 280px;
    height: 100%;
    overflow: hidden;
}

/* Card */
.dop-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 20px 18px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
}

.dop-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin: 0 0 16px;
    letter-spacing: 0.03em;
}

/* Form */
.dop-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.dop-field {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    padding: 8px 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dop-field__icon {
    width: 32px;
    height: 32px;
    background: #e53935;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dop-field__icon i {
    color: #fff;
    font-size: 0.9rem;
}

.dop-field__icon--blue {
    background: #1565c0;
}

.dop-field__icon--red {
    background: #e53935;
}

.dop-input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: #fff;
    font-size: 0.9rem;
    font-family: 'Segoe UI', sans-serif;
}

.dop-input::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

/* Reason */
.dop-reason {
    display: flex;
    align-items: center;
    gap: 0;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dop-reason__label {
    background: #43a047;
    color: #fff;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 9px 12px;
    white-space: nowrap;
    flex-shrink: 0;
}

.dop-reason__input {
    flex: 1;
    background: rgba(255, 255, 255, 0.12);
    border: none;
    outline: none;
    color: #fff;
    font-size: 0.85rem;
    padding: 9px 12px;
    font-family: 'Segoe UI', sans-serif;
}

.dop-reason__input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

/* Error */
.dop-error {
    color: #ff8a80;
    font-size: 0.78rem;
    text-align: center;
    margin: 0;
}

/* Save btn */
.dop-save-btn {
    background: #1565c0;
    border: none;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    padding: 10px;
    border-radius: 10px;
    cursor: pointer;
    transition: filter 0.2s, transform 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.dop-save-btn:hover:not(:disabled) {
    filter: brightness(1.15);
    transform: translateY(-1px);
}

.dop-save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* List */
.dop-card--list {
    display: flex;
    flex-direction: column;
    flex: 1;
    /* ← reemplazar max-height: 320px por flex: 1 */
    overflow: hidden;
    /* ← agregar */
    min-height: 0;
    /* ← agregar, importante para flex */
}

.dop-loading {
    display: flex;
    justify-content: center;
    padding: 20px 0;
}

.dop-empty {
    text-align: center;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.85rem;
    padding: 20px 0;
}

.dop-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow-y: auto;
    
    flex: 1;
    min-height: 0;
    
}

.dop-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.dop-item__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    flex-shrink: 0;
}

.dop-item__info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.dop-item__date {
    font-size: 0.85rem;
    font-weight: 700;
    color: #fff;
}

.dop-item__time {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

.dop-item__reason {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

.dop-item__delete {
    background: rgba(229, 57, 53, 0.2);
    border: 1px solid rgba(229, 57, 53, 0.4);
    color: #ff8a80;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: background 0.2s;
    flex-shrink: 0;
}

.dop-item__delete:hover {
    background: rgba(229, 57, 53, 0.4);
}

/* Spinner */
.dop-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: dop-spin 0.7s linear infinite;
    display: inline-block;
}

.dop-spinner--dark {
    border-color: rgba(0, 0, 0, 0.15);
    border-top-color: #1565c0;
    width: 28px;
    height: 28px;
}

@keyframes dop-spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1024px) {
    .dop-wrapper {
        width: 100%;
        min-width: unset;
    }
}
</style>