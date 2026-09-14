<template>
  <div 
    @click="enterInvitation" 
    class="relative min-h-screen bg-slate-950 text-white overflow-hidden flex flex-col justify-between items-center selection:bg-pink-500 selection:text-white cursor-pointer select-none"
  >
    
    <!-- 1. Video de Fondo Desenfocado (Efecto Ambiente / Blur) -->
    <video 
      ref="bgVideo"
      class="absolute inset-0 w-full h-full object-cover opacity-30 blur-2xl pointer-events-none hidden sm:block" 
      autoplay 
      muted 
      playsinline 
      loop
    >
      <source :src="videoPath" type="video/mp4" />
    </video>

    <!-- 2. Video Principal Centrado -->
    <div class="absolute inset-0 flex items-center justify-center">
      <video 
        ref="mainVideo"
        class="w-full h-full max-h-screen object-contain pointer-events-none" 
        autoplay 
        muted 
        playsinline 
        loop
      >
        <source :src="videoPath" type="video/mp4" />
        Tu navegador no soporta la reproducción de video.
      </video>
      
      <!-- Overlay degradado sutil -->
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950/40 via-transparent to-slate-950/40 pointer-events-none"></div>
    </div>

    <!-- 3. Encabezado y Control de Audio -->
    <header class="relative z-20 w-full max-w-4xl px-6 pt-6 flex justify-between items-center">
      <span class="text-xs sm:text-sm font-semibold tracking-widest uppercase bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full border border-white/20 shadow-sm">
        Baby Shower
      </span>

      <!-- Botón de Audio con @click.stop para no activar la navegación al presionarlo -->
      <button 
        @click.stop="toggleMute"
        type="button"
        class="flex items-center gap-2 bg-white/10 hover:bg-white/20 active:scale-95 backdrop-blur-md px-4 py-2 rounded-full border border-white/20 transition-all text-xs sm:text-sm font-medium shadow-md cursor-pointer"
      >
        <span v-if="isMuted" class="flex items-center gap-1.5">
          <svg class="w-4 h-4 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
          </svg>
          Activar Audio
        </span>
        <span v-else class="flex items-center gap-1.5">
          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          </svg>
          Silenciar
        </span>
      </button>
    </header>

    <!-- Indicador Flotante Inferior -->
    <div class="relative z-20 pb-8 text-center animate-bounce">
      <span class="text-xs sm:text-sm font-light tracking-wide bg-black/40 backdrop-blur-md px-5 py-2 rounded-full border border-white/10 text-slate-300">
        Toca en cualquier lugar para entrar ✨
      </span>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const guestId = ref(route.params.guestId || 1)
const videoPath = ref('/videos/tu-video.mp4')

const bgVideo = ref(null)
const mainVideo = ref(null)
const isMuted = ref(true)

const toggleMute = () => {
  isMuted.value = !isMuted.value

  if (mainVideo.value) {
    mainVideo.value.muted = isMuted.value
    if (!isMuted.value) {
      mainVideo.value.play().catch((err) => {
        console.warn('La reproducción de audio fue bloqueada por el navegador:', err)
      })
    }
  }

  if (bgVideo.value) {
    bgVideo.value.muted = true
  }
}

const enterInvitation = () => {
  router.push({ name: 'baby-shower-card', params: { guestId: guestId.value } })
}
</script>