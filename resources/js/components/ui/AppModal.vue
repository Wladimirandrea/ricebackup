<script setup>
defineProps({
    show:  { type: Boolean, default: false },
    title: { type: String, default: '' },
    size:  { type: String, default: 'md' },
})

const emit = defineEmits(['close'])
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="modal-overlay" @click.self="emit('close')">
                <div class="modal-box" :class="`modal-${size}`">
                    <div class="modal-header">
                        <h2 class="modal-title">{{ title }}</h2>
                        <button class="modal-close" @click="emit('close')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    overflow-y: auto;
}
.modal-box {
    background: #0f1e35;
    border: 1px solid #1e2a3a;
    border-radius: 20px;
    width: 100%;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    margin: auto 0;
}
.modal-sm { max-width: 400px; }
.modal-md { max-width: 560px; }
.modal-lg { max-width: 720px; }
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #1e2a3a;
    position: sticky;
    top: 0;
    background: #0f1e35;
    z-index: 1;
}
.modal-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: white;
    font-family: 'Segoe UI', sans-serif;
}
.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: rgba(255,255,255,0.05);
    color: #7a8aaa;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, color 0.2s;
    flex-shrink: 0;
}
.modal-close:hover { background: rgba(255,255,255,0.1); color: white; }
.modal-body { padding: 24px; }

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .modal-box, .modal-leave-active .modal-box { transition: transform 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-box, .modal-leave-to .modal-box { transform: scale(0.95) translateY(-10px); }

@media (min-width: 768px) {
    .modal-overlay {
        align-items: center;
        padding: 40px 20px;
    }
}
</style>
