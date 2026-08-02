<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLocale } from '@/i18n'

const logoUrl = '/images/raise-logo.png'
const { t } = useI18n()
const authStore = useAuthStore()
const { locale, setLocale } = useLocale()
const router = useRouter()

const showPassword = ref(false)

// ── Form State ───────────────────────────────────────────────
const form = ref({
    email: '',
    password: '',
})

const errors = ref({})
const serverError = ref('')
const isLoading = ref(false)

// ── Submit ───────────────────────────────────────────────────
async function handleLogin() {
    errors.value = {}
    serverError.value = ''
    isLoading.value = true

    try {
        await authStore.login(form.value)
        // La redirección la maneja el store
    } catch (err) {
        const status = err.response?.status

        if (status === 422) {
            // Errores de validación del Form Request de Laravel
            errors.value = err.response.data.errors || {}
        } else if (status === 401) {
            serverError.value = t('auth.login.invalid_credentials')
        } else if (status === 403) {
            serverError.value = t('auth.login.account_disabled')
        } else {
            serverError.value = t('auth.login.generic_error')
        }
    } finally {
        isLoading.value = false
    }
}

function toggleLocale() {
    setLocale(locale.value === 'es' ? 'en' : 'es')
}
</script>
<template>
    <div class="login-page">
        <div class="login-card">

            <!-- Logo -->
            <div class="login-logo">
                <img :src="logoUrl" alt="Raise Logo" />
            </div>

            <!-- Título -->
            <h1 class="login-title">{{ t('auth.login.title') }}</h1>

            <!-- Error de servidor -->
            <div v-if="serverError" class="login-error">
                {{ serverError }}
            </div>

            <!-- Formulario -->
            <form class="login-form" @submit.prevent="handleLogin" novalidate>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">{{ t('auth.login.email') }}</label>
                    <input id="email" v-model="form.email" type="email" :placeholder="t('auth.login.email_placeholder')"
                        :style="errors.email ? 'border-color: #e24b4a' : ''" autocomplete="email" />
                    <span v-if="errors.email" style="color:#ff8a89; font-size:12px;">
                        {{ errors.email[0] }}
                    </span>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">{{ t('auth.login.password') }}</label>
                    <div class="input-wrapper">
                        <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                            :placeholder="t('auth.login.password_placeholder')"
                            :style="errors.password ? 'border-color: #e24b4a' : ''" autocomplete="current-password" />
                        <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                            <!-- Ojo abierto -->
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <!-- Ojo cerrado -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                                <line x1="1" y1="1" x2="23" y2="23" />
                            </svg>
                        </button>
                    </div>
                    <span v-if="errors.password" style="color:#ff8a89; font-size:12px;">
                        {{ errors.password[0] }}
                    </span>
                </div>

                <!-- Submit -->
                <button type="submit" class="login-btn" :disabled="isLoading">
                    {{ isLoading ? t('auth.login.submitting') : t('auth.login.submit') }}
                </button>

                <div class="login-footer" style="margin-top: 12px;">
                    <a href="#" @click.prevent="router.push({ name: 'forgot-password' })">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

            </form>

            <!-- Footer -->
            <div class="login-footer">
                <a href="#" @click.prevent="toggleLocale">
                    {{ locale === 'es' ? '🇺🇸 Switch to English' : '🇪🇸 Cambiar a Español' }}
                </a>
            </div>

        </div>
    </div>
</template>

<style scoped>
.login-page {
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(to bottom,
            #00152b 0%,
            #054894 35%,
            #10448b 70%,
            #00152b 100%);
}

.login-card {
    width: 100%;
    max-width: 420px;
    background: rgba(255, 255, 255, 0.07);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 24px;
    padding: 40px 36px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45),
        inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

.login-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 24px;
}

.login-logo img {
    height: 140px;
    width: auto;
    object-fit: contain;
}

.login-title {
    text-align: center;
    font-size: 22px;
    font-weight: 600;
    color: white;
    margin: 0 0 28px;
    font-family: 'Segoe UI', sans-serif;
}

.login-error {
    margin-bottom: 16px;
    padding: 12px 14px;
    background: rgba(226, 75, 74, 0.15);
    border: 1px solid rgba(226, 75, 74, 0.4);
    color: #ff8a89;
    border-radius: 10px;
    font-size: 13px;
    white-space: pre-line;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 13px;
    font-weight: 500;
    color: #c9d4e8;
    font-family: 'Segoe UI', sans-serif;
}

.form-group input {
    background: #1e2a3a;
    border: 1px solid #2a3a55;
    border-radius: 10px;
    padding: 11px 14px;
    color: #c9d4e8;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
    font-family: 'Segoe UI', sans-serif;
    width: 100%;
    box-sizing: border-box;
}

.form-group input::placeholder {
    color: #4a5a70;
}

.form-group input:focus {
    border-color: #4a90e2;
}

.login-btn {
    margin-top: 6px;
    width: 100%;
    padding: 13px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
    font-family: 'Segoe UI', sans-serif;
}

.login-btn:hover:not(:disabled) {
    background: #2563eb;
    transform: translateY(-1px);
}

.login-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.login-footer {
    margin-top: 24px;
    text-align: center;
    font-size: 13px;
    color: #7a8aaa;
    font-family: 'Segoe UI', sans-serif;
}

.login-footer a {
    color: #4a90e2;
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper input {
    width: 100%;
    padding-right: 44px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: #4a5a70;
    padding: 0;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.toggle-password:hover {
    color: #c9d4e8;
}
</style>