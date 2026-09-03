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
const langOpen = ref(false)

const languages = [
    { code: 'en', name: 'English', flag: '🇺🇸' },
    { code: 'es', name: 'Español', flag: '🇲🇽' },
    { code: 'fr', name: 'Français', flag: '🇫🇷' },
    { code: 'ar', name: 'العربية', flag: '🇸🇦' },
]

const currentLanguage = computed(() => {
    return languages.find(l => l.code === locale.value) || languages[0]
})

function selectLocale(langCode) {
    setLocale(langCode)
    langOpen.value = false
}

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
                    <span v-if="errors.email" class="error-text">
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
                    <span v-if="errors.password" class="error-text">
                        {{ errors.password[0] }}
                    </span>
                </div>

                <!-- Submit -->
                <button type="submit" class="login-btn" :disabled="isLoading">
                    {{ isLoading ? t('auth.login.submitting') : t('auth.login.submit') }}
                </button>

                <div class="login-footer" style="margin-top: 12px;">
                    <a href="#" @click.prevent="router.push({ name: 'forgot-password' })">
                        {{ t('auth.forgot.title') }}
                    </a>
                </div>

            </form>

            <!-- Selector de idioma -->
            <div class="login-footer lang-footer">
                <div class="lang-selector">
                    <button class="lang-toggle-btn" @click="langOpen = !langOpen">
                        <span>{{ currentLanguage.flag }} {{ currentLanguage.name }}</span>
                        <svg class="chevron" :class="{ rotated: langOpen }" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>

                    <div v-if="langOpen" class="lang-menu">
                        <button 
                            v-for="lang in languages" 
                            :key="lang.code" 
                            class="lang-menu-item"
                            :class="{ active: locale === lang.code }"
                            @click="selectLocale(lang.code)"
                        >
                            <span>{{ lang.name }}</span>
                            <span>{{ lang.flag }}</span>
                        </button>
                    </div>
                </div>
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

.error-text {
    color: #ff8a89;
    font-size: 12px;
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

/* Lang Selector Menu */
.lang-footer {
    position: relative;
    display: flex;
    justify-content: center;
}

.lang-selector {
    position: relative;
}

.lang-toggle-btn {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    color: #c9d4e8;
    padding: 6px 14px;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s, color 0.2s;
}

.lang-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.chevron {
    transition: transform 0.2s;
}

.chevron.rotated {
    transform: rotate(180deg);
}

.lang-menu {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #111827;
    border: 1px solid #2a3a55;
    border-radius: 12px;
    padding: 6px;
    width: 150px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    z-index: 10;
}

.lang-menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 8px 12px;
    background: none;
    border: none;
    color: #c9d4e8;
    font-size: 12px;
    cursor: pointer;
    border-radius: 6px;
    transition: background 0.2s;
}

.lang-menu-item:hover,
.lang-menu-item.active {
    background: #1e2a3a;
    color: #fff;
}
</style>