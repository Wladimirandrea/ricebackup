// resources/js/plugins/echo.js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import api from '@/plugins/axios'

window.Pusher = Pusher

// Logs de Pusher: siempre en dev; en producción solo si VITE_REVERB_DEBUG=true
// (actívalo temporalmente para depurar y vuelve a quitarlo)
Pusher.logToConsole =
    import.meta.env.DEV || import.meta.env.VITE_REVERB_DEBUG === 'true'

const key    = import.meta.env.VITE_REVERB_APP_KEY
const host   = import.meta.env.VITE_REVERB_HOST
const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https'
const port   = Number(import.meta.env.VITE_REVERB_PORT ?? 443)

if (!key || !host) {
    console.error(
        '[Reverb] Faltan VITE_REVERB_APP_KEY o VITE_REVERB_HOST. ' +
        'Recuerda: las variables VITE_* se compilan en el build, hay que redesplegar.'
    )
}

const echo = new Echo({
    broadcaster:        'reverb',
    key,
    wsHost:             host,
    wsPort:             port,
    wssPort:            port,
    forceTLS:           scheme === 'https',
    enabledTransports:  ['ws', 'wss'],
    disableStats:       true,

    // ── Reconexión automática ──────────────────────────────
    activityTimeout:    30000,
    pongTimeout:        10000,
    unavailableTimeout: 10000,

    // ── Autorización de canales privados usando la instancia api ─
    // OJO: si `api` tiene baseURL '/api', esta petición va a /api/broadcasting/auth
    // y la ruta debe existir ahí (ver routes/channels.php / bootstrap/app.php).
    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            api.post('/broadcasting/auth', {
                socket_id:    socketId,
                channel_name: channel.name,
            })
                .then(response => callback(null, response.data))
                .catch(error  => callback(error, null))
        },
    }),
})

// ── Listeners de estado de conexión ───────────────────────
const connection = echo.connector.pusher.connection

connection.bind('connected', () => {
    console.info('[Reverb] ✅ Conectado')
})
connection.bind('disconnected', () => {
    console.warn('[Reverb] ❌ Desconectado — intentando reconectar...')
})
connection.bind('unavailable', () => {
    console.warn('[Reverb] ⚠️ Servidor no disponible — reintentando...')
})
connection.bind('failed', () => {
    console.error('[Reverb] 💥 Conexión fallida — sin soporte WebSocket')
})
connection.bind('error', (err) => {
    console.error('[Reverb] Error:', err)
})

export default echo