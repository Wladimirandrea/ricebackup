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