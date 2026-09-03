<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="modelValue" class="cm-backdrop" @click.self="$emit('update:modelValue', false)">
                <div class="cm-modal">
                    <div class="cm-icon" :class="`cm-icon--${variant}`">
                        <i :class="iconClass"></i>
                    </div>
                    <h3 class="cm-title">{{ title }}</h3>
                    <p class="cm-message">{{ message }}</p>
                    <div class="cm-actions">
                        <button class="cm-btn cm-btn--cancel" :disabled="loading" @click="$emit('update:modelValue', false)">
                            {{ cancelLabel }}
                        </button>
                        <button class="cm-btn cm-btn--confirm" :class="`cm-btn--${variant}`" :disabled="loading" @click="$emit('confirm')">
                            <span v-if="loading" class="cm-spinner"></span>
                            <span v-else>{{ confirmLabel }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: Boolean,
    title: { type: String, required: true },
    message: { type: String, required: true },
    confirmLabel: { type: String, default: 'Confirm' },
    cancelLabel: { type: String, default: 'Cancel' },
    variant: { type: String, default: 'danger' }, // danger | primary
    loading: { type: Boolean, default: false },
})
defineEmits(['update:modelValue', 'confirm'])

const iconClass = computed(() =>
    props.variant === 'danger' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-question'
)
</script>

<style scoped>
.cm-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.cm-modal {
    width: 100%;
    max-width: 360px;
    margin: 16px;
    padding: 28px 24px 24px;
    border-radius: 16px;
    background: #131c2e;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
    text-align: center;
}

.cm-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.cm-icon--danger {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

.cm-icon--primary {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}

.cm-title {
    margin: 0 0 8px;
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
}

.cm-message {
    margin: 0 0 24px;
    font-size: 0.88rem;
    color: rgba(255, 255, 255, 0.55);
    line-height: 1.5;
}

.cm-actions {
    display: flex;
    gap: 10px;
}

.cm-btn {
    flex: 1;
    padding: 11px 0;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid transparent;
    transition: opacity 0.2s, background 0.2s;
}

.cm-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.cm-btn--cancel {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.8);
}

.cm-btn--cancel:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.14);
}

.cm-btn--danger {
    background: #ef4444;
    color: #fff;
}

.cm-btn--danger:hover:not(:disabled) {
    background: #dc2626;
}

.cm-btn--primary {
    background: #3b82f6;
    color: #fff;
}

.cm-btn--primary:hover:not(:disabled) {
    background: #2563eb;
}

.cm-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: cm-spin 0.6s linear infinite;
}

@keyframes cm-spin {
    to { transform: rotate(360deg); }
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>