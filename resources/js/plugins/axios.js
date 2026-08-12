import axios from 'axios'
import { i18n } from '@/i18n'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://192.168.12.125:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept':       'application/json',
    },
})

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    config.headers['Accept-Language'] = i18n.global.locale.value

    // Si el body es FormData (por ejemplo, al subir archivos/imágenes),
    // eliminamos el Content-Type fijo para que el navegador genere
    // automáticamente el correcto, incluyendo el boundary necesario
    // para multipart/form-data. Sin esto, los archivos llegan
    // corruptos o vacíos al backend.
    if (config.data instanceof FormData) {
        delete config.headers['Content-Type']
    }

    return config
})

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const isLoginRequest = error.config?.url?.includes('/auth/login')
        if (error.response?.status === 401 && !isLoginRequest) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            window.location.href = '/'
        }

        return Promise.reject(error)
    }
)

export default api