<!-- resources/js/views/auth/ForgotPasswordView.vue -->
<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useLocale } from '@/i18n'
import api from '@/plugins/axios'

const { t }                  = useI18n()
const router                 = useRouter()
const { locale, setLocale }  = useLocale()

const email       = ref('')
const errors      = ref({})
const serverError = ref('')
const successMsg  = ref('')
const isLoading   = ref(false)

async function handleSubmit() {
    errors.value      = {}
    serverError.value = ''
    successMsg.value  = ''
    isLoading.value   = true

    try {
        const { data } = await api.post('/auth/forgot-password', { email: email.value })
        successMsg.value = data.message
        email.value = ''
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {}
        } else {
            serverError.value = t('auth.forgot.generic_error')
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

            <h1 class="login-title">{{ t('auth.forgot.title') }}</h1>
            <p class="login-subtitle">{{ t('auth.forgot.subtitle') }}</p>

            <div v-if="successMsg" class="login-success">
                {{ successMsg }}
            </div>

            <div v-if="serverError" class="login-error">
                {{ serverError }}
            </div>

            <form v-if="!successMsg" class="login-form" @submit.prevent="handleSubmit" novalidate>
                <div class="form-group">
                    <label for="email">{{ t('auth.forgot.email') }}</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        placeholder="tucorreo@gmail.com"
                        :style="errors.email ? 'border-color: #e24b4a' : ''"
                        autocomplete="email"
                    />
                    <span v-if="errors.email" style="color:#ff8a89; font-size:12px;">
                        {{ errors.email[0] }}
                    </span>
                </div>

                <button type="submit" class="login-btn" :disabled="isLoading">
                    {{ isLoading ? t('auth.forgot.submitting') : t('auth.forgot.submit') }}
                </button>
            </form>

            <div class="login-footer">
                <a href="#" @click.prevent="router.push({ name: 'login' })">
                    ← {{ t('auth.forgot.back_to_login') }}
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
.login-title {
    text-align: center;
    font-size: 22px;
    font-weight: 600;
    color: white;
    margin: 0 0 8px;
    font-family: 'Segoe UI', sans-serif;
}
.login-subtitle {
    text-align: center;
    font-size: 13px;
    color: #7a8aaa;
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
}
.login-success {
    margin-bottom: 16px;
    padding: 12px 14px;
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.4);
    color: #4ade80;
    border-radius: 10px;
    font-size: 13px;
    text-align: center;
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
.form-group input::placeholder { color: #4a5a70; }
.form-group input:focus { border-color: #4a90e2; }
.login-btn {
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
.login-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.login-footer {
    margin-top: 24px;
    text-align: center;
    font-size: 13px;
    color: #7a8aaa;
    font-family: 'Segoe UI', sans-serif;
}
.login-footer a { color: #4a90e2; text-decoration: none; }
.login-footer a:hover { text-decoration: underline; }
</style>