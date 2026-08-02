// resources/js/app.js
import { createApp }   from 'vue'
import { createPinia } from 'pinia'
import axios           from 'axios'
import App    from './App.vue'
import router from './router'
import { i18n } from './i18n'
import Vue3Toastify, { toast } from 'vue3-toastify'  // ✅
import 'vue3-toastify/dist/index.css'                 // ✅

axios.defaults.baseURL = 'http://192.168.12.125:8000'
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

const token = localStorage.getItem('auth_token')
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

const app   = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)
app.use(Vue3Toastify, {                // ✅
    autoClose: 4000,
    position: 'top-right',
    theme: 'dark',
    clearOnUrlChange: false,
})
app.mount('#app')