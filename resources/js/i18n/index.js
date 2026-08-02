// resources/js/i18n/index.js
import { createI18n } from 'vue-i18n'
import es from './locales/es.json'
import en from './locales/en.json'

const savedLocale = localStorage.getItem('locale') || 'en'

export const i18n = createI18n({   // ← debe decir "export const i18n"
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'es',
    messages: { es, en },
})

export function useLocale() {
    const { locale } = i18n.global

    function setLocale(lang) {
        locale.value = lang
        localStorage.setItem('locale', lang)
        document.documentElement.setAttribute('lang', lang)
    }

    return { locale, setLocale }
}