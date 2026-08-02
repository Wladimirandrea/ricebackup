<template>
    <Teleport to="body">
        <Transition name="pw-fade">
            <div v-if="store.passwordModal.open" class="pw-backdrop" @click.self="store.closePasswordModal()">
                <div class="pw-modal">

                    <!-- Header -->
                    <div class="pw-header">
                        <div class="pw-header__icon">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div class="pw-header__text">
                            <h3 class="pw-title">{{ $t('managerClients.changePassword') }}</h3>
                            <p class="pw-subtitle">{{ store.passwordModal.client?.name }}</p>
                        </div>
                        <button class="pw-close" @click="store.closePasswordModal()">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="pw-body">

                        <div class="pw-field">
                            <label class="pw-label">{{ $t('managerClients.newPassword') }}</label>
                            <div class="pw-input-wrap">
                                <input
                                    v-model="form.password"
                                    :type="showPw ? 'text' : 'password'"
                                    class="pw-input"
                                    :placeholder="'••••••••'"
                                    autocomplete="new-password"
                                    @keyup.enter="submit"
                                />
                                <button type="button" class="pw-eye" @click="showPw = !showPw">
                                    <i :class="showPw ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pw-field">
                            <label class="pw-label">{{ $t('managerClients.confirmPassword') }}</label>
                            <div class="pw-input-wrap">
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPw ? 'text' : 'password'"
                                    class="pw-input"
                                    :placeholder="'••••••••'"
                                    autocomplete="new-password"
                                    @keyup.enter="submit"
                                />
                            </div>
                        </div>

                        <!-- Strength bar -->
                        <div class="pw-strength">
                            <div class="pw-strength__bar">
                                <div
                                    class="pw-strength__fill"
                                    :class="`pw-strength__fill--${strength.level}`"
                                    :style="{ width: strength.percent + '%' }"
                                />
                            </div>
                            <span class="pw-strength__label" :class="`pw-strength__label--${strength.level}`">
                                {{ strength.label }}
                            </span>
                        </div>

                        <!-- Error -->
                        <p v-if="store.passwordModal.error" class="pw-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            {{ store.passwordModal.error }}
                        </p>

                    </div>

                    <!-- Footer -->
                    <div class="pw-footer">
                        <button class="pw-btn pw-btn--cancel" @click="store.closePasswordModal()">
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            class="pw-btn pw-btn--save"
                            :disabled="store.passwordModal.loading || !canSubmit"
                            @click="submit"
                        >
                            <span v-if="store.passwordModal.loading" class="pw-spinner" />
                            <i v-else class="fa-solid fa-check"></i>
                            {{ store.passwordModal.loading ? $t('users.form.saving') : $t('common.save') }}
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useManagerClientStore } from '@/stores/managerClientStore'
import { toast } from 'vue3-toastify'

const { t } = useI18n()
const store = useManagerClientStore()

const showPw = ref(false)
const form   = ref({ password: '', password_confirmation: '' })

// Reset form when modal opens
watch(() => store.passwordModal.open, (val) => {
    if (val) {
        form.value = { password: '', password_confirmation: '' }
        showPw.value = false
    }
})

const canSubmit = computed(() =>
    form.value.password.length >= 8 &&
    form.value.password === form.value.password_confirmation
)

// Password strength
const strength = computed(() => {
    const pw = form.value.password
    if (!pw) return { level: 'empty', percent: 0, label: '' }

    let score = 0
    if (pw.length >= 8)  score++
    if (pw.length >= 12) score++
    if (/[A-Z]/.test(pw)) score++
    if (/[0-9]/.test(pw)) score++
    if (/[^A-Za-z0-9]/.test(pw)) score++

    if (score <= 1) return { level: 'weak',   percent: 25,  label: t('managerClients.pwWeak') }
    if (score <= 2) return { level: 'fair',   percent: 50,  label: t('managerClients.pwFair') }
    if (score <= 3) return { level: 'good',   percent: 75,  label: t('managerClients.pwGood') }
    return              { level: 'strong', percent: 100, label: t('managerClients.pwStrong') }
})

async function submit() {
    if (!canSubmit.value) return
    const result = await store.changePassword(
        store.passwordModal.client.id,
        form.value.password,
        form.value.password_confirmation
    )
    if (result?.success) {
        toast.success(t('managerClients.passwordUpdated'))
    }
}
</script>

<style scoped>
/* Backdrop */
.pw-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1300;
    background: rgba(10, 18, 35, 0.8);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

/* Modal */
.pw-modal {
    background: #0f1e35;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 32px 80px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}

/* Header */
.pw-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 20px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.pw-header__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(37, 99, 235, 0.2);
    border: 1px solid rgba(37, 99, 235, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #60a5fa;
    font-size: 1rem;
    flex-shrink: 0;
}

.pw-header__text { flex: 1; }

.pw-title {
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 2px;
}

.pw-subtitle {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.45);
    margin: 0;
}

.pw-close {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: none;
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    transition: background 0.2s, color 0.2s;
}

.pw-close:hover { background: rgba(255, 255, 255, 0.12); color: #fff; }

/* Body */
.pw-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.pw-field { display: flex; flex-direction: column; gap: 6px; }

.pw-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.5);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.pw-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.pw-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 10px 40px 10px 14px;
    color: #fff;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
    font-family: 'Segoe UI', sans-serif;
}

.pw-input:focus { border-color: #2563eb; }
.pw-input::placeholder { color: rgba(255, 255, 255, 0.2); }

.pw-eye {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.3);
    cursor: pointer;
    font-size: 0.85rem;
    transition: color 0.2s;
    display: flex;
    align-items: center;
}

.pw-eye:hover { color: rgba(255, 255, 255, 0.7); }

/* Strength */
.pw-strength {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pw-strength__bar {
    flex: 1;
    height: 4px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 2px;
    overflow: hidden;
}

.pw-strength__fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s, background-color 0.3s;
}

.pw-strength__fill--weak   { background: #ef4444; }
.pw-strength__fill--fair   { background: #f59e0b; }
.pw-strength__fill--good   { background: #3b82f6; }
.pw-strength__fill--strong { background: #22c55e; }

.pw-strength__label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    min-width: 44px;
    text-align: right;
}

.pw-strength__label--weak   { color: #ef4444; }
.pw-strength__label--fair   { color: #f59e0b; }
.pw-strength__label--good   { color: #3b82f6; }
.pw-strength__label--strong { color: #22c55e; }

/* Error */
.pw-error {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #f87171;
    font-size: 0.8rem;
    margin: 0;
    padding: 8px 12px;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Footer */
.pw-footer {
    display: flex;
    gap: 10px;
    padding: 16px 20px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.07);
}

.pw-btn {
    flex: 1;
    padding: 11px;
    border-radius: 12px;
    border: none;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: filter 0.2s, transform 0.15s;
    font-family: 'Segoe UI', sans-serif;
}

.pw-btn--cancel {
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.pw-btn--cancel:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.pw-btn--save {
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #fff;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);
}

.pw-btn--save:hover:not(:disabled) { filter: brightness(1.1); transform: translateY(-1px); }
.pw-btn--save:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

/* Spinner */
.pw-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: pw-spin 0.7s linear infinite;
    display: inline-block;
}

@keyframes pw-spin { to { transform: rotate(360deg); } }

/* Transition */
.pw-fade-enter-active, .pw-fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.pw-fade-enter-from, .pw-fade-leave-to { opacity: 0; transform: scale(0.96); }
</style>