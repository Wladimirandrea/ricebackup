<!-- resources/js/components/appointments/AppointmentFormModal.vue -->
<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="modelValue" class="af-backdrop" @click.self="$emit('update:modelValue', false)">
                <div class="af-modal">
                    <!-- Header -->
                    <div class="af-header">
                        <h3 class="af-title">{{ $t('appointments.new') }}</h3>
                        <button class="af-close" @click="$emit('update:modelValue', false)">X</button>
                    </div>

                    <div class="af-body">

                        <!-- Cliente -->
                        <div class="af-section">
                            <label class="af-section__label">{{ $t('appointments.client') }}</label>
                            <div class="af-avatars">
                                <div v-for="c in clients" :key="c.id" class="af-avatar-item"
                                    :class="{ 'af-avatar-item--selected': form.client_id === c.id }"
                                    @click="form.client_id = c.id">
                                    <div class="af-avatar-ring">
                                        <img :src="c.profile_image_url || defaultAvatar" :alt="c.name" />
                                    </div>
                                    <span class="af-avatar-name">{{ c.name.split(' ')[0] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Case Manager -->
                        <div class="af-section">
                            <label class="af-section__label">{{ $t('appointments.caseManager') }}</label>
                            <div class="af-avatars">
                                <div v-for="cm in caseManagers" :key="cm.id" class="af-avatar-item"
                                    :class="{ 'af-avatar-item--selected': form.case_manager_id === cm.id }"
                                    @click="form.case_manager_id = cm.id">
                                    <div class="af-avatar-ring">
                                        <img :src="cm.profile_image || defaultAvatar" :alt="cm.name" />
                                    </div>
                                    <span class="af-avatar-name">{{ cm.name.split(' ')[0] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fecha -->
                        <div class="af-section">
                            <label class="af-section__label">{{ $t('appointments.date') }}</label>
                            <input v-model="form.date" type="date" class="af-input" />
                        </div>

                        <!-- Hora inicio -->
                        <div class="af-section">
                            <label class="af-section__label">{{ $t('appointments.startTime') }}</label>
                            <p v-if="!form.case_manager_id" class="af-hint">
                                {{ $t('appointments.selectCMFirst') }}
                            </p>
                            <div v-else-if="loadingSlots" class="af-slots-loading">
                                <span class="af-spinner" /> {{ $t('common.loading') }}
                            </div>
                            <p v-else-if="availableTimeSlots.length === 0" class="af-hint af-hint--warn">
                                {{ $t('appointments.noSlots') }}
                            </p>
                            <select v-else v-model="form.start_time" class="af-select">
                                <option v-for="slot in availableTimeSlots" :key="slot.time" :value="slot.time"
                                    :disabled="!slot.available">
                                    {{ slot.time }}
                                    {{ slot.day_off ? '— 🌙 ' + $t('appointments.dayOff') : '' }}
                                    {{ slot.cm_taken ? '— ' + $t('appointments.taken') : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="af-section">
                            <label class="af-section__label">{{ $t('appointments.status') }}</label>
                            <div class="af-status-row">
                                <button v-for="s in ['pending', 'confirmed']" :key="s" class="af-status-btn"
                                    :class="[`af-status-btn--${s}`, { 'af-status-btn--active': form.status === s }]"
                                    @click="form.status = s">
                                    {{ $t(`appointments.${s}`) }}
                                </button>
                            </div>
                        </div>

                        <!-- Reason / Notas -->
                        <div class="af-section">
                            <label class="af-section__label">Reason</label>
                            <input v-model="form.notes" type="text" class="af-input"
                                :placeholder="$t('schedule.reasonPlaceholder')" />
                        </div>

                        <!-- Error -->
                        <p v-if="errorMsg" class="af-error">{{ errorMsg }}</p>
                    </div>

                    <!-- Footer -->
                    <div class="af-footer">
                        <button class="af-btn af-btn--cancel" @click="$emit('update:modelValue', false)">
                            {{ $t('common.cancel') }}
                        </button>
                        <button class="af-btn af-btn--save" :disabled="saving" @click="save">
                            <span v-if="saving" class="af-spinner" />
                            <span v-else>{{ $t('common.save') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, h } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppointmentStore } from '@/stores/appointmentStore'
import api from '@/plugins/axios'
import { toast } from 'vue3-toastify'

const { t } = useI18n()
const store = useAppointmentStore()

const defaultAvatar = 'https://ui-avatars.com/api/?name=C&background=1565c0&color=fff'

const props = defineProps({
    modelValue: Boolean,
    date: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'created'])

const clients = ref([])
const caseManagers = ref([])
const saving = ref(false)
const errorMsg = ref('')

const form = ref({
    client_id: '',
    case_manager_id: '',
    date: props.date,
    start_time: '',
    status: 'pending',
    notes: '',
})

watch(
    () => [form.value.date, form.value.case_manager_id],
    ([date, managerId]) => {
        if (date && managerId) {
            store.fetchSlots(date, managerId).then(() => {
                const first = store.formSlots.find(s => s.available)
                form.value.start_time = first?.time ?? ''
            })
        } else {
            form.value.start_time = ''
        }
    }
)

const availableTimeSlots = computed(() => store.formSlots)
const loadingSlots = computed(() => store.loadingSlots)

onMounted(async () => {
    const [cl, cm] = await Promise.all([
        api.get('/admin/users', { params: { role: 'client', per_page: 100 } }),
        api.get('/admin/case-managers/all'),
    ])
    clients.value = cl.data.data
    caseManagers.value = cm.data.managers

    if (form.value.date && form.value.case_manager_id) {
        await store.fetchSlots(form.value.date, form.value.case_manager_id)
        const first = store.formSlots.find(s => s.available)
        form.value.start_time = first?.time ?? ''
    }
})

async function save() {
    errorMsg.value = ''
    if (!form.value.client_id || !form.value.case_manager_id || !form.value.date || !form.value.start_time) {
        errorMsg.value = t('appointments.requiredFields')
        return
    }
    saving.value = true
    const result = await store.createAppointment({ ...form.value })
    saving.value = false

    if (result.success) {
        toast.success(
            () => h('div', {
                style: 'display:flex; flex-direction:column; align-items:center; gap:8px; padding:4px 0;'
            }, [
                h('img', {
                    src: '/images/success.gif',
                    style: 'width:140px; height:120px; object-fit:cover; border-radius:8px;'
                }),
                h('span', {
                    style: 'font-weight:700; font-size:0.95rem; text-align:center;'
                }, t('appointments.createdSuccess')),
                h('span', {
                    style: 'font-size:0.78rem; opacity:0.7; text-align:center;'
                }, t('appointments.emailSent')),
            ]),
            {
                autoClose: 4000,
                icon: false,
                style: 'min-width: 220px;',
            }
        )
        form.value = { client_id: '', case_manager_id: '', date: props.date, start_time: '', status: 'pending', notes: '' }
        emit('update:modelValue', false)
        emit('created')
    } else {
        toast.error(result.message ?? t('appointments.error'))
        errorMsg.value = result.message ?? t('appointments.error')
    }
}
</script>

<style>
.af-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1200;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.af-modal {
    background: #2a6dd9;
    border-radius: 24px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

/* Header */
.af-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 22px 16px;
}

.af-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.af-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 700;
    transition: background 0.2s;
}

.af-close:hover {
    background: rgba(255, 255, 255, 0.35);
}

/* Body */
.af-body {
    flex: 1;
    overflow-y: auto;
    padding: 0 22px 16px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Section */
.af-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.af-section__label {
    font-size: 0.95rem;
    font-weight: 600;
    color: #fff;
    text-align: center;
    letter-spacing: 0.02em;
}

/* Avatares */
.af-avatars {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}

.af-avatar-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: transform 0.2s;
}

.af-avatar-item:hover {
    transform: scale(1.08);
}

.af-avatar-ring {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
    overflow: hidden;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.af-avatar-item--selected .af-avatar-ring {
    border-color: #fff;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
}

.af-avatar-ring img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.af-avatar-name {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 500;
}

/* Inputs */
.af-input,
.af-select {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    border-radius: 14px;
    color: #fff;
    font-size: 0.95rem;
    padding: 12px 16px;
    outline: none;
    font-family: 'Segoe UI', sans-serif;
    width: 100%;
    transition: background 0.2s;
}

.af-input:focus,
.af-select:focus {
    background: rgba(255, 255, 255, 0.22);
}

.af-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.af-input::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

.af-select option {
    background: #2a6dd9;
    color: #fff;
}

.af-select option:disabled {
    color: rgba(255, 255, 255, 0.3);
}

/* Status buttons */
.af-status-row {
    display: flex;
    gap: 10px;
}

.af-status-btn {
    flex: 1;
    padding: 10px;
    border-radius: 12px;
    border: 2px solid transparent;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.2s, border-color 0.2s;
    color: #fff;
}

.af-status-btn--active {
    opacity: 1;
    border-color: #fff;
}

.af-status-btn--pending {
    background: rgba(245, 158, 11, 0.7);
}

.af-status-btn--confirmed {
    background: rgba(59, 130, 246, 0.7);
}

/* Hints */
.af-hint {
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.5);
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px dashed rgba(255, 255, 255, 0.2);
    margin: 0;
    text-align: center;
}

.af-hint--warn {
    color: #fcd34d;
    border-color: rgba(252, 211, 77, 0.3);
}

.af-slots-loading {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.85rem;
    padding: 10px 14px;
}

/* Error */
.af-error {
    color: #fca5a5;
    font-size: 0.82rem;
    text-align: center;
    margin: 0;
}

/* Footer */
.af-footer {
    display: flex;
    gap: 10px;
    padding: 16px 22px 20px;
}

.af-btn {
    flex: 1;
    padding: 13px;
    border-radius: 14px;
    border: none;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: filter 0.2s, transform 0.15s;
}

.af-btn--cancel {
    background: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.8);
}

.af-btn--cancel:hover {
    background: rgba(255, 255, 255, 0.25);
}

.af-btn--save {
    background: #1a3a6e;
    color: #fff;
}

.af-btn--save:hover:not(:disabled) {
    filter: brightness(1.2);
}

.af-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Spinner */
.af-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: af-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes af-spin {
    to {
        transform: rotate(360deg);
    }
}

/* Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s, transform 0.25s;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
    transform: scale(0.96);
}
</style>