<script setup>
defineProps({
    show:    { type: Boolean, default: false },
    title:   { type: String, default: 'Confirm' },
    message: { type: String, default: 'Are you sure?' },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="modal-overlay" @click.self="emit('close')">
                <div class="modal-box">
                    <div class="modal-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h2 class="modal-title">{{ title }}</h2>
                    <p class="modal-message">{{ message }}</p>
                    <div class="modal-actions">
                        <button class="btn-cancel" @click="emit('close')" :disabled="loading">
                            Cancel
                        </button>
                        <button class="btn-confirm" @click="emit('confirm')" :disabled="loading">
                            <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
                            {{ loading ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999; padding: 20px;
}
.modal-box {
    background: #0f1e35;
    border: 1px solid #1e2a3a;
    border-radius: 20px;
    width: 100%; max-width: 380px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 25px 60px rgba(0,0,0,0.5);
}
.modal-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: rgba(255,77,79,0.15);
    border: 1px solid rgba(255,77,79,0.3);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    font-size: 1.4rem; color: #ff6b6b;
}
.modal-title {
    margin: 0 0 8px;
    font-size: 18px; font-weight: 600; color: white;
    font-family: 'Segoe UI', sans-serif;
}
.modal-message {
    margin: 0 0 24px;
    font-size: 14px; color: #7a8aaa;
    font-family: 'Segoe UI', sans-serif;
    line-height: 1.5;
}
.modal-actions { display: flex; gap: 10px; justify-content: center; }
.btn-cancel {
    padding: 10px 20px;
    background: rgba(255,255,255,0.04);
    border: 1px solid #2a3a55;
    border-radius: 10px; color: #7a8aaa;
    font-size: 14px; cursor: pointer;
    transition: background 0.2s;
    font-family: 'Segoe UI', sans-serif;
}
.btn-cancel:hover { background: rgba(255,255,255,0.08); color: white; }
.btn-confirm {
    padding: 10px 20px;
    background: linear-gradient(180deg, rgba(255,77,79,0.2), rgba(255,77,79,0.1));
    border: 1px solid rgba(255,77,79,0.4);
    border-radius: 10px; color: white;
    font-size: 14px; font-weight: 600; cursor: pointer;
    transition: background 0.2s;
    font-family: 'Segoe UI', sans-serif;
    display: flex; align-items: center; gap: 8px;
}
.btn-confirm:hover:not(:disabled) {
    background: linear-gradient(180deg, rgba(255,77,79,0.3), rgba(255,77,79,0.2));
}
.btn-confirm:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
