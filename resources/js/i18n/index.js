import { createI18n } from 'vue-i18n'
import es from './locales/es.json'
import en from './locales/en.json'
import fr from './locales/fr.json'
import ar from './locales/ar.json'

const savedLocale = localStorage.getItem('locale') || 'en'

export const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'es',
    messages: { es, en, fr, ar },
})

export function useLocale() {
    const { locale } = i18n.global

    function setLocale(lang) {
        locale.value = lang
        localStorage.setItem('locale', lang)
        document.documentElement.setAttribute('lang', lang)
        
        // Ajuste automático de orientación de texto para el idioma árabe (RTL)
        document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr')
    }

    return { locale, setLocale }
}