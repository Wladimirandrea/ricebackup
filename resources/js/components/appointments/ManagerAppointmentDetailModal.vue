<!-- resources/js/components/appointments/ManagerAppointmentDetailModal.vue -->
<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="modelValue" class="ad-backdrop" @click.self="$emit('update:modelValue', false)">
                <div class="ad-modal">
                    <div class="ad-header" :class="`ad-header--${appointment?.status}`">
                        <div class="ad-header__info">
                            <span class="ad-header__status">{{ $t(`appointments.${appointment?.status}`) }}</span>
                            <span class="ad-header__time">{{ appointment?.start_time }} — {{ appointment?.end_time }}</span>
                        </div>
                        <div class="ad-header__actions">
                            <button class="ad-edit-btn" @click="toggleEdit">
                                <i :class="editing ? 'fa-solid fa-xmark' : 'fa-solid fa-pen'" />
                            </button>
                            <button class="ad-close" @click="$emit('update:modelValue', false)">✕</button>
                        </div>
                    </div>

                    <div class="ad-body">
                        <!-- Edit form -->
                        <Transition name="slide-down">
                            <div v-if="editing" class="ad-edit-form">
                                <div class="ad-edit-row">
                                    <div class="ad-edit-field">
                                        <label class="ad-edit-label">{{ $t('appointments.date') }}</label>
                                        <input v-model="editForm.date" type="date" class="ad-edit-input" />
                                    </div>
                                    <div class="ad-edit-field">
                                        <label class="ad-edit-label">{{ $t('appointments.startTime') }}</label>
                                        <select v-model="editForm.start_time" class="ad-edit-input">
                                            <option v-for="slot in editSlots" :key="slot.time" :value="slot.time" :disabled="!slot.available">
                                                {{ slot.time }} {{ !slot.available ? '—' + $t('appointments.taken') : '' }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <p v-if="editError" class="ad-edit-error">{{ editError }}</p>
                                <button class="ad-edit-save" :disabled="editSaving" @click="saveEdit">
                                    <span v-if="editSaving" class="ad-spinner" />
                                    <span v-else>{{ $t('common.save') }}</span>
                                </button>
                            </div>
                        </Transition>

                        <!-- Cliente -->
                        <div class="ad-person">
                            <img :src="appointment?.client?.profile_image || defaultAvatar" class="ad-person__avatar" />
                            <div class="ad-person__info">
                                <span class="ad-person__label">{{ $t('appointments.client') }}</span>
                                <span class="ad-person__name">{{ appointment?.client?.name }}</span>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div v-if="appointment?.notes" class="ad-notes">
                            <span class="ad-notes__label">{{ $t('appointments.notes') }}</span>
                            <p class="ad-notes__text">{{ appointment.notes }}</p>
                        </div>

                        <!-- Status -->
                        <div class="ad-status-btns">
                            <span class="ad-status__label">{{ $t('appointments.changeStatus') }}</span>
                            <div class="ad-status-row">
                                <button
                                    v-for="s in statuses"
                                    :key="s"
                                    class="ad-status-btn"
                                    :class="[`ad-status-btn--${s}`, { 'ad-status-btn--active': appointment?.status === s }]"
                                    :disabled="appointment?.status === s || saving"
                                    @click="changeStatus(s)"
                                >
                                    {{ $t(`appointments.${s}`) }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useManagerAppointmentStore } from '@/stores/managerAppointmentStore'

const store = useManagerAppointmentStore()
const defaultAvatar = 'https://ui-avatars.com/api/?name=C&background=1565c0&color=fff'
const saving    = ref(false)
const statuses  = ['pending', 'confirmed', 'completed', 'cancelled']
const editing   = ref(false)
const editSaving = ref(false)
const editError  = ref('')
const editSlots  = ref([])
const editForm   = ref({ date: '', start_time: '' })

const props = defineProps({
    modelValue:  Boolean,
    appointment: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'status-changed', 'appointment-updated'])

watch(() => props.modelValue, (val) => {
    if (val) {
        editing.value  = false
        editError.value = ''
        editForm.value = { date: props.appointment?.date ?? '', start_time: props.appointment?.start_time ?? '' }
    }
})

watch(() => editForm.value.date, async (date) => {
    if (!date) return
    await store.fetchSlots(date)
    editSlots.value = store.formSlots
    const current = store.formSlots.find(s => s.time === editForm.value.start_time && s.available)
    if (!current) {
        const first = store.formSlots.find(s => s.available)
        editForm.value.start_time = first?.time ?? ''
    }
})

function toggleEdit() {
    editing.value  = !editing.value
    editError.value = ''
    if (editing.value) {
        editForm.value = { date: props.appointment?.date ?? '', start_time: props.appointment?.start_time ?? '' }
        store.fetchSlots(editForm.value.date).then(() => { editSlots.value = store.formSlots })
    }
}

async function saveEdit() {
    editError.value = ''
    if (!editForm.value.date || !editForm.value.start_time) { editError.value = 'Fill all fields'; return }
    editSaving.value = true
    const result = await store.updateAppointment(props.appointment.id, editForm.value)
    editSaving.value = false
    if (result.success) { editing.value = false; emit('appointment-updated', result.appointment) }
    else editError.value = result.message ?? 'Error updating'
}

async function changeStatus(status) {
    saving.value = true
    await store.updateStatus(props.appointment.id, status)
    saving.value = false
    emit('status-changed')
}
</script>

<style>
.ad-backdrop {
    position: fixed; inset: 0; z-index: 1200;
    background: rgba(15,23,42,0.75);
    backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
}
.ad-modal {
    background: #1a2a4a;
    border-radius: 20px;
    width: 100%; max-width: 420px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.5);
    border: 1px solid rgba(255,255,255,0.1);
    overflow: hidden;
}
.ad-header {
    padding: 18px 22px;
    display: flex; align-items: center; justify-content: space-between;
}
.ad-header--pending   { background: rgba(245,158,11,0.25); border-bottom: 2px solid #f59e0b; }
.ad-header--confirmed { background: rgba(59,130,246,0.25); border-bottom: 2px solid #3b82f6; }
.ad-header--completed { background: rgba(34,197,94,0.25);  border-bottom: 2px solid #22c55e; }
.ad-header--cancelled { background: rgba(239,68,68,0.25);  border-bottom: 2px solid #ef4444; }
.ad-header__info { display: flex; flex-direction: column; gap: 2px; }
.ad-header__status { font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.06em; }
.ad-header__time   { font-size: 1rem; font-weight: 700; color: #fff; }

/* ✅ Fix: punto faltante */
.ad-header__actions { display: flex; align-items: center; gap: 8px; }

.ad-close, .ad-edit-btn {
    background: rgba(255,255,255,0.1); border: none; color: #fff;
    width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
    font-size: 0.82rem; transition: background 0.2s;
    display: flex; align-items: center; justify-content: center;
}
.ad-close:hover, .ad-edit-btn:hover { background: rgba(255,255,255,0.25); }

.ad-body { padding: 20px 22px; display: flex; flex-direction: column; gap: 16px; }
.ad-person {
    display: flex; align-items: center; gap: 14px;
    padding: 12px; border-radius: 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
}
.ad-person__avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); }
.ad-person__info { display: flex; flex-direction: column; gap: 2px; }
.ad-person__label { font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.04em; }
.ad-person__name  { font-size: 0.95rem; font-weight: 700; color: #fff; }
.ad-notes { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 12px; border: 1px solid rgba(255,255,255,0.08); }
.ad-notes__label { font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase; display: block; margin-bottom: 6px; }
.ad-notes__text  { margin: 0; font-size: 0.9rem; color: rgba(255,255,255,0.8); line-height: 1.5; }
.ad-status-btns  { display: flex; flex-direction: column; gap: 8px; }
.ad-status__label { font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.04em; }
.ad-status-row   { display: flex; gap: 8px; flex-wrap: wrap; }
.ad-status-btn {
    flex: 1; padding: 8px 4px; border-radius: 8px; border: none;
    font-size: 0.78rem; font-weight: 700; cursor: pointer;
    opacity: 0.5; transition: opacity 0.2s, transform 0.15s;
    min-width: 80px;
}
.ad-status-btn--active  { opacity: 1; }
.ad-status-btn:hover:not(:disabled):not(.ad-status-btn--active) { opacity: 0.8; transform: scale(1.03); }
.ad-status-btn:disabled { cursor: not-allowed; }
.ad-status-btn--pending   { background: #f59e0b; color: #fff; }
.ad-status-btn--confirmed { background: #3b82f6; color: #fff; }
.ad-status-btn--completed { background: #22c55e; color: #fff; }
.ad-status-btn--cancelled { background: #ef4444; color: #fff; }

/* Edit form */
.ad-edit-form {
    background: rgba(255,255,255,0.06);
    border-radius: 14px; padding: 16px;
    border: 1px solid rgba(255,255,255,0.1);
    display: flex; flex-direction: column; gap: 12px;
}
.ad-edit-field  { display: flex; flex-direction: column; gap: 6px; }
.ad-edit-label  { font-size: 0.7rem; font-weight: 600; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.04em; }
.ad-edit-input  {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 10px; color: #fff;
    font-size: 0.85rem; padding: 8px 12px;
    outline: none; width: 100%;
    font-family: 'Segoe UI', sans-serif;
    transition: border-color 0.2s;
}
.ad-edit-input:focus { border-color: #3b82f6; }
.ad-edit-input::-webkit-calendar-picker-indicator { filter: invert(1); }
.ad-edit-input option { background: #1a2a4a; }
.ad-edit-error  { color: #fca5a5; font-size: 0.78rem; text-align: center; margin: 0; }
.ad-edit-save   {
    background: #3b82f6; border: none; color: #fff;
    padding: 9px; border-radius: 10px; font-size: 0.88rem;
    font-weight: 700; cursor: pointer; width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: filter 0.2s;
}
.ad-edit-save:hover:not(:disabled) { filter: brightness(1.1); }
.ad-edit-save:disabled { opacity: 0.5; cursor: not-allowed; }
.ad-slots-loading { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.5); font-size: 0.85rem; padding: 8px 0; }

.ad-spinner {
    width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff; border-radius: 50%;
    animation: ad-spin 0.7s linear infinite; display: inline-block;
}
@keyframes ad-spin { to { transform: rotate(360deg); } }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s, transform 0.25s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.96); }
.slide-down-enter-active, .slide-down-leave-active { transition: opacity 0.2s, transform 0.2s; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>