<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

// 1. Capturar el nombre del invitado desde la URL (:name)
const guestSlug = ref(route.params.name)
const guestName = ref('Cargando...')

// 2. Estado de Audio
const bgMusic = ref(null)
const isPlaying = ref(false)

// 3. Estado del Contador
const days = ref('00')
const hours = ref('00')
const minutes = ref('00')
const seconds = ref('00')
let timerInterval = null

// Iconos SVG para el reproductor
const noteIcon = "M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"
const muteIcon = "M4.27 3L3 4.27l9 9v.28c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 3.02 0 3.84-2.12l4.42 4.42L19.73 21 4.27 3zM14 7h4V3h-6v5.18l2 2V7z"

// 4. Obtener nombre del invitado desde Laravel usando el parámetro de la ruta
const fetchGuestData = async () => {
  if (!guestSlug.value) return
  try {
    const response = await axios.get(`/api/baby-shower/guest/${guestSlug.value}`)
    if (response.data && response.data.guest) {
      guestName.value = response.data.guest.name
    }
  } catch (error) {
    console.error('Error al cargar invitado:', error)
    guestName.value = 'Familia y Amigos'
  }
}

// 5. Lógica del Contador
const targetDate = new Date("September 27, 2026 14:00:00").getTime()

const updateCountdown = () => {
  const now = new Date().getTime()
  const difference = targetDate - now

  if (difference > 0) {
    days.value = String(Math.floor(difference / (1000 * 60 * 60 * 24))).padStart(2, '0')
    hours.value = String(Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0')
    minutes.value = String(Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0')
    seconds.value = String(Math.floor((difference % (1000 * 60)) / 1000)).padStart(2, '0')
  } else {
    days.value = "00"
    hours.value = "00"
    minutes.value = "00"
    seconds.value = "00"
  }
}

// 6. Control del Reproductor de Música
const toggleAudio = () => {
  if (!bgMusic.value) return
  if (bgMusic.value.paused) {
    bgMusic.value.play().then(() => {
      isPlaying.value = true
    }).catch(err => console.log("Autoplay bloqueado por el navegador:", err))
  } else {
    bgMusic.value.pause()
    isPlaying.value = false
  }
}

const handleFirstInteraction = () => {
  if (bgMusic.value && bgMusic.value.paused) {
    bgMusic.value.play().then(() => {
      isPlaying.value = true
    }).catch(() => {})
  }
  window.removeEventListener('click', handleFirstInteraction)
  window.removeEventListener('touchstart', handleFirstInteraction)
}

// 7. Navegación a la vista de regalos usando el nombre
const goToGiftRegistry = () => {
  router.push({ 
    name: 'baby-shower-guest', 
    params: { name: guestSlug.value } 
  })
}

onMounted(() => {
  fetchGuestData()
  updateCountdown()
  timerInterval = setInterval(updateCountdown, 1000)

  window.addEventListener('click', handleFirstInteraction, { once: true })
  window.addEventListener('touchstart', handleFirstInteraction, { once: true })
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
  window.removeEventListener('click', handleFirstInteraction)
  window.removeEventListener('touchstart', handleFirstInteraction)
})
</script>

<template>
  <div class="card-wrapper">
    <!-- ELEMENTO DE AUDIO MÚSICA DE FONDO EN BUCLE -->
    <audio ref="bgMusic" :src="'/audio/fondo.mp3'" loop preload="auto"></audio>

    <!-- BOTÓN FLOTANTE DE CONTROL DE MÚSICA -->
    <button class="audio-btn" type="button" @click="toggleAudio" aria-label="Control de Música">
      <svg viewBox="0 0 24 24">
        <path :d="isPlaying ? noteIcon : muteIcon" />
      </svg>
      <span>{{ isPlaying ? 'Música' : 'Pausa' }}</span>
    </button>

    <!-- SECCIÓN 1: INVITACIÓN PRINCIPAL -->
    <div class="card-container invitation-card">
      <div class="content-top">
        <!-- Saludo dinámico con el nombre del invitado -->
        <h2 class="guest-greeting">¡Hola, {{ guestName }}!</h2>
        <p class="top-header">¡Una princesita está en camino!</p>
      </div>

      <div class="content-middle">
        <p class="invitation-for">Te invitamos a celebrar el Baby Shower de</p>
        <h1 class="baby-name">Pauline Juliette</h1>

        <div class="date-container">
          <span class="month">SEPTIEMBRE</span>
          <div class="date-row">
            <span class="day-name">DOMINGO</span>
            <span class="day-number">27</span>
            <span class="time">2:00 PM</span>
          </div>
        </div>

        <div class="location-container">
          <p>2228 QUINCY ST</p>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 2: MIS PAPITOS -->
    <div class="card-container papitos-card">
      <div class="content-top">
        <h2 class="papitos-title">Mis papitos</h2>
        <h3 class="papitos-names">Carmen y Wladimir</h3>
      </div>

      <div class="content-middle">
        <div class="polaroid-gallery">
          <div class="polaroid">
            <img :src="'/images/1.jpeg'" alt="Foto 1">
          </div>
          <div class="polaroid">
            <img :src="'/images/4.jpeg'" alt="Ecografía 1">
          </div>
          <div class="polaroid">
            <img :src="'/images/5.jpeg'" alt="Ecografía 2">
          </div>
        </div>
      </div>

      <div class="content-bottom">
        <p class="papitos-text">Están emocionados y quieren que formes parte de mi llegada</p>
      </div>
    </div>

    <!-- SECCIÓN 3: GLOBO Y CONTADOR -->
    <div class="card-container balloon-card">
      <div class="content-top">
        <h2 class="celebrate-title">¡No puedo esperar para celebrar contigo!</h2>
      </div>

      <div class="content-bottom">
        <div class="timer-container">
          <div class="timer-digits">
            <div class="timer-item">
              <span class="num">{{ days }}</span>
              <span class="label">Días</span>
            </div>
            <span class="separator">:</span>
            <div class="timer-item">
              <span class="num">{{ hours }}</span>
              <span class="label">Horas</span>
            </div>
            <span class="separator">:</span>
            <div class="timer-item">
              <span class="num">{{ minutes }}</span>
              <span class="label">Minutos</span>
            </div>
            <span class="separator">:</span>
            <div class="timer-item">
              <span class="num">{{ seconds }}</span>
              <span class="label">Segundos</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 4: UBICACIÓN Y MAPS -->
    <div class="card-container map-card">
      <div class="content-top">
        <h3 class="location-title">¿Cómo Llegar?</h3>
        
        <div class="map-container">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3184.2882823616606!2d-94.5126302!3d37.0506307!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x87c8801b97b00001%3A0x6fbca808b28f80cb!2s2228%20Quincy%20St%2C%20Joplin%2C%20MO%2064804!5e0!3m2!1ses!2sus!4v1710000000000!5m2!1ses!2sus" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
        
        <p class="address-text">2228 Quincy St, Joplin, MO 64804</p>
      </div>
    </div>

    <!-- SECCIÓN 5: MESA DE REGALOS -->
    <div class="card-container gifts-card">
      <div class="content-top">
        <h3 class="gifts-title">Mesa de Regalos</h3>
      </div>

      <div class="content-bottom">
        <p class="gifts-text">
          Tu presencia y cariño son el mejor regalo de todos. Si deseas hacernos llegar un pequeño detalle, aquí encontrarás una lista de regalos que ya hemos recibido.
        </p>

        <button type="button" class="gift-btn" @click="goToGiftRegistry">
          Ver Regalos
        </button>
      </div>
    </div>

    <!-- SECCIÓN 6: TE ESPERAMOS -->
    <div class="card-container family-card">
      <div class="content-bottom">
        <h2 class="waiting-title">¡Te Esperamos!</h2>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Great+Vibes&family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap');

.card-wrapper {
  font-family: 'Montserrat', sans-serif;
  background-color: #2a1a28;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 10px;
  color: #ffffff;
  gap: 40px;
  width: 100%;
}

.audio-btn {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  color: #ffffff;
  padding: 10px 16px;
  border-radius: 30px;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
  transition: all 0.3s ease;
}

.audio-btn:hover {
  background: rgba(255, 255, 255, 0.4);
  transform: scale(1.05);
}

.audio-btn svg {
  width: 20px;
  height: 20px;
  fill: currentColor;
}

.audio-btn span {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.card-container {
  position: relative;
  width: 100%;
  max-width: 440px;
  aspect-ratio: 9 / 16;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  padding: 35px 20px 40px;
  text-align: center;
}

.card-container::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg, 
    rgba(35, 15, 30, 0.45) 0%, 
    rgba(0, 0, 0, 0.05) 40%, 
    rgba(35, 15, 30, 0.55) 100%
  );
  z-index: 1;
}

.content-top,
.content-middle,
.content-bottom {
  position: relative;
  z-index: 2;
  width: 100%;
}

.invitation-card {
  background: url('/images/imagen-princesa.jpg') no-repeat center center / cover;
}

.guest-greeting {
  font-family: 'Great Vibes', cursive;
  font-size: 2.8rem;
  color: #ffffff;
  text-shadow: 0 3px 8px rgba(0, 0, 0, 0.8), 0 0 12px rgba(255, 220, 235, 0.6);
  line-height: 1.1;
  margin-bottom: 6px;
}

.top-header {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  letter-spacing: 2px;
  color: #ffffff;
  text-shadow: 0 2px 5px rgba(0, 0, 0, 0.7);
  font-weight: 600;
  text-transform: uppercase;
}

.invitation-for {
  font-size: 0.7rem;
  letter-spacing: 2px;
  color: #fce4ec;
  text-transform: uppercase;
  font-weight: 600;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
  margin-bottom: 2px;
}

.baby-name {
  font-family: 'Great Vibes', cursive;
  font-size: 3.5rem;
  color: #ffffff;
  text-shadow: 0 3px 10px rgba(80, 15, 45, 0.9), 0 0 20px rgba(255, 220, 235, 0.6);
  font-weight: normal;
  line-height: 1.1;
  margin-bottom: 14px;
}

.date-container {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 12px;
  padding: 10px 16px;
  margin: 0 auto 14px;
  max-width: 320px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
}

.month {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.9rem;
  letter-spacing: 3px;
  font-weight: 700;
  color: #ffffff;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
  margin-bottom: 4px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.35);
  padding-bottom: 4px;
}

.date-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 4px;
}

.day-name, .time {
  font-size: 0.75rem;
  letter-spacing: 1.5px;
  font-weight: 600;
  color: #ffffff;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
  flex: 1;
}

.day-number {
  font-family: 'Playfair Display', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: #ffffff;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.7);
  padding: 0 14px;
  border-left: 1px solid rgba(255, 255, 255, 0.4);
  border-right: 1px solid rgba(255, 255, 255, 0.4);
  line-height: 1;
}

.location-container {
  font-size: 0.8rem;
  letter-spacing: 1.5px;
  color: #ffffff;
  font-weight: 600;
  text-transform: uppercase;
  text-shadow: 0 2px 5px rgba(0, 0, 0, 0.8);
  line-height: 1.5;
}

.papitos-card {
  background: url('/images/imagen-princesa.jpg') no-repeat center center / cover;
  justify-content: space-between;
  padding-top: 25px;
  padding-bottom: 25px;
}

.papitos-title {
  font-family: 'Cinzel', serif;
  font-size: 1.2rem;
  letter-spacing: 3px;
  color: #ffffff;
  text-transform: uppercase;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.9);
  font-weight: 700;
  margin-bottom: 2px;
}

.papitos-names {
  font-family: 'Great Vibes', cursive;
  font-size: 3.2rem;
  color: #ffffff;
  text-shadow: 0 3px 10px rgba(0, 0, 0, 0.9), 0 0 18px rgba(255, 220, 235, 0.6);
  font-weight: normal;
  line-height: 1.1;
  margin-bottom: 6px;
}

.papitos-text {
  font-family: 'Great Vibes', cursive;
  font-size: 2.2rem;
  line-height: 1.2;
  color: #ffffff;
  text-shadow: 0 3px 8px rgba(0, 0, 0, 0.8), 0 0 15px rgba(255, 220, 235, 0.5);
  font-weight: normal;
  padding: 0 10px;
}

.polaroid-gallery {
  position: relative;
  width: 100%;
  height: 290px;
  margin: 5px 0;
}

.polaroid {
  position: absolute;
  background: #ffffff;
  padding: 6px 6px 18px 6px;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.6);
  border-radius: 3px;
  transition: transform 0.3s ease;
}

.polaroid img {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  display: block;
  border-radius: 2px;
}

.polaroid:nth-child(1) {
  top: 0;
  left: 50%;
  transform: translateX(-50%) rotate(-3deg);
  width: 46%;
  z-index: 2;
}

.polaroid:nth-child(2) {
  bottom: 0;
  left: 4%;
  transform: rotate(-6deg);
  width: 43%;
  z-index: 1;
}

.polaroid:nth-child(3) {
  bottom: 0;
  right: 4%;
  transform: rotate(5deg);
  width: 43%;
  z-index: 1;
}

.balloon-card {
  background: url('/images/imagen-globo.jpg') no-repeat center center / cover;
}

.celebrate-title {
  font-family: 'Great Vibes', cursive;
  font-size: 2.6rem;
  color: #ffffff;
  text-shadow: 0 3px 8px rgba(0, 0, 0, 0.7), 0 0 15px rgba(255, 220, 235, 0.5);
  line-height: 1.2;
  font-weight: normal;
}

.timer-container {
  background: rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 16px;
  padding: 14px 10px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.timer-digits {
  display: flex;
  justify-content: space-around;
  align-items: center;
  font-family: 'Playfair Display', serif;
  font-size: 1.9rem;
  font-weight: 700;
  color: #ffffff;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
}

.timer-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.timer-item span.num {
  line-height: 1;
}

.timer-item span.label {
  font-family: 'Montserrat', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 1.5px;
  font-weight: 600;
  color: #fce4ec;
  text-transform: uppercase;
  margin-top: 6px;
}

.separator {
  font-size: 1.4rem;
  opacity: 0.8;
  margin-bottom: 12px;
}

.map-card {
  background: url('/images/imagen-jeep.jpg') no-repeat center center / cover;
  justify-content: flex-start;
  padding-top: 25px;
}

.location-title {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  letter-spacing: 2px;
  color: #ffffff;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 12px;
}

.map-container {
  background: rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 18px;
  padding: 8px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
  width: 100%;
  height: 230px;
  overflow: hidden;
}

.map-container iframe {
  width: 100%;
  height: 100%;
  border: 0;
  border-radius: 12px;
}

.address-text {
  font-size: 0.75rem;
  letter-spacing: 1.5px;
  font-weight: 600;
  color: #ffffff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
  margin-top: 10px;
  text-transform: uppercase;
}

.gifts-card {
  background: url('/images/Gemini_Generated_Image_o5g62go5g62go5g6.jpeg') no-repeat center center / cover;
  justify-content: space-between;
}

.gifts-title {
  font-family: 'Cinzel', serif;
  font-size: 1.4rem;
  letter-spacing: 3px;
  color: #ffffff;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
  font-weight: 700;
  text-transform: uppercase;
}

.gifts-text {
  font-family: 'Great Vibes', cursive;
  font-size: 2.1rem;
  line-height: 1.2;
  color: #ffffff;
  text-shadow: 0 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(255, 220, 235, 0.6);
  font-weight: normal;
  padding: 0 10px;
  margin-bottom: 20px;
}

.gift-btn {
  display: inline-block;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  color: #ffffff;
  font-family: 'Montserrat', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-decoration: none;
  padding: 14px 28px;
  border-radius: 30px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
  transition: all 0.3s ease;
  cursor: pointer;
}

.gift-btn:hover {
  background: rgba(255, 255, 255, 0.4);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.45);
}

.family-card {
  background: url('/images/familia.jpg') no-repeat center top / cover;
  justify-content: flex-end;
}

.waiting-title {
  font-family: 'Great Vibes', cursive;
  font-size: 3.8rem;
  color: #ffffff;
  text-shadow: 0 3px 10px rgba(0, 0, 0, 0.9), 0 0 20px rgba(255, 220, 235, 0.6);
  font-weight: normal;
  margin-bottom: 10px;
}

@media (max-width: 380px) {
  .guest-greeting { font-size: 2.3rem; }
  .top-header { font-size: 0.75rem; }
  .baby-name { font-size: 2.8rem; }
  .day-number { font-size: 1.8rem; }
  .location-container { font-size: 0.75rem; }
  .papitos-title { font-size: 1rem; }
  .papitos-names { font-size: 2.6rem; }
  .papitos-text { font-size: 1.8rem; }
  .polaroid-gallery { height: 250px; }
  .celebrate-title { font-size: 2.1rem; }
  .timer-digits { font-size: 1.5rem; }
  .timer-item span.label { font-size: 0.55rem; }
  .map-container { height: 190px; }
  .gifts-title { font-size: 1.2rem; }
  .gifts-text { font-size: 1.7rem; margin-bottom: 15px; }
  .gift-btn { font-size: 0.75rem; padding: 12px 22px; }
  .waiting-title { font-size: 3.2rem; }
  .audio-btn { top: 12px; right: 12px; padding: 8px 12px; }
}
</style>
