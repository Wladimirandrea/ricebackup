// resources/js/plugins/echo.js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import api from '@/plugins/axios'

window.Pusher = Pusher

// Habilitar logs de Pusher en desarrollo para debug
if (import.meta.env.DEV) {
    Pusher.logToConsole = true
}

// URL base sin el prefijo /api
const appUrl = import.meta.env.VITE_API_URL || 'http://192.168.12.125:8000/api'

const echo = new Echo({
    broadcaster:        'reverb',
    key:                import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:             import.meta.env.VITE_REVERB_HOST,
    wsPort:             import.meta.env.VITE_REVERB_PORT       ?? 8080,
    wssPort:            import.meta.env.VITE_REVERB_PORT       ?? 8080,
    forceTLS:           (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports:  ['ws', 'wss'],
    disableStats:       true,
    // ── Reconexión automática ──────────────────────────────
    activityTimeout:    30000,
    pongTimeout:        10000,
    unavailableTimeout: 10000,
    // ── Autorización de canales privados con token Sanctum ─
    authorizer: (channel) => {
        return {
            authorize: (socketId, callback) => {
                api.post('/broadcasting/auth', {
                    socket_id:    socketId,
                    channel_name: channel.name,
                }, {
                    baseURL: appUrl  // ← apunta a /broadcasting/auth sin el prefijo /api
                })
                .then(response => callback(null, response.data))
                .catch(error  => callback(error, null))
            },
        }
    },
})

// ── Listeners de estado de conexión ───────────────────────
echo.connector.pusher.connection.bind('connected', () => {
    console.info('[Reverb] ✅ Conectado')
})
echo.connector.pusher.connection.bind('disconnected', () => {
    console.warn('[Reverb] ❌ Desconectado — intentando reconectar...')
})
echo.connector.pusher.connection.bind('unavailable', () => {
    console.warn('[Reverb] ⚠️ Servidor no disponible — reintentando...')
})
echo.connector.pusher.connection.bind('failed', () => {
    console.error('[Reverb] 💥 Conexión fallida — sin soporte WebSocket')
})
echo.connector.pusher.connection.bind('error', (err) => {
    console.error('[Reverb] Error:', err)
})

export default echo