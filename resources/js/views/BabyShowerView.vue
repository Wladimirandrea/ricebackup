<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

// 1. Capturar el nombre del invitado desde la ruta (:name)
const guestSlug = ref(route.params.name)
const guestName = ref('Cargando...')
const gifts = ref([])

// 2. Obtener datos del invitado y sus regalos desde Laravel
const fetchGuestData = async () => {
  if (!guestSlug.value) return
  try {
    const response = await axios.get(`/api/baby-shower/guest/${guestSlug.value}`)
    if (response.data) {
      guestName.value = response.data.guest.name
      gifts.value = response.data.gifts
    }
  } catch (error) {
    console.error('Error al cargar datos:', error)
    guestName.value = 'Invitado'
  }
}

// Función para reservar regalo usando el ID del invitado obtenido de la respuesta
const selectGift = async (giftId) => {
  // Asegúrate de enviar el id real del invitado que cargó la API
  // ... tu lógica actual de selección de regalo ...
}

onMounted(() => {
  fetchGuestData()
})
</script>

<template>
  <div v-if="currentGuest" class="page-container" :style="{ backgroundImage: `url('/images/regalos.jpeg')` }">
    <div class="content-wrapper">
      <h1 class="title-fantasy">¡Bienvenido/a, {{ currentGuest.name }}!</h1>

      <div class="description-card">
        <p class="description-text-es">
          Estos son los regalos que hemos recibido para el Baby Shower, si deseas llevar alguno puedes elegir uno y así no se repiten los regalos.
        </p>
        <p class="description-text-en">
          For the baby shower gifts we have received, if you wish to bring any, you can choose one so that the gifts are not repeated.
        </p>
      </div>

      <div v-if="message" class="success-message">{{ message }}</div>

      <div class="gifts-grid">
        <div
          v-for="gift in gifts"
          :key="gift.id"
          class="gift-card"
          :class="{ 'gift-card--reserved': gift.is_full && !gift.is_selected_by_me }"
        >
          <div class="gift-image-wrapper">
            <img
              v-if="gift.image_url"
              :src="gift.image_url"
              :alt="gift.name"
              class="gift-image"
            />
            <div v-else class="gift-icon-placeholder">
              <svg class="gift-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
            </div>
          </div>

          <div class="gift-info">
            <span class="gift-name">{{ gift.name }}</span>
            <span v-if="gift.is_selected_by_me" class="gift-status">
              (Reservado por ti)
            </span>
            <span v-else-if="gift.is_full" class="gift-status">
              (Ya reservado)
            </span>
          </div>

          <button
            @click="selectGift(gift.id)"
            :disabled="gift.is_full || gift.is_selected_by_me"
            class="gift-button"
            :class="{ 'gift-button--disabled': gift.is_full || gift.is_selected_by_me }"
          >
            <template v-if="gift.is_selected_by_me">Reservado</template>
            <template v-else-if="gift.is_full">Agotado</template>
            <template v-else>Elegir</template>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');

.page-container {
  min-height: 100vh;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  padding: 1rem;
}

.content-wrapper {
  max-width: 42rem;
  margin: 0 auto;
}

.title-fantasy {
  font-family: 'Great Vibes', cursive;
  font-size: 2.6rem;
  color: #ffffff;
  text-shadow: 0 3px 8px rgba(0, 0, 0, 0.7), 0 0 15px rgba(255, 220, 235, 0.5);
  line-height: 1.2;
  font-weight: normal;
  margin-bottom: 1rem;
  text-align: center;
}

.description-card {
  margin-bottom: 1.5rem;
  text-align: center;
  background-color: rgba(0, 0, 0, 0.65);
  padding: 1rem;
  border-radius: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  margin-left: 0.5rem;
  margin-right: 0.5rem;
}

.description-text-es {
  color: #ffffff;
  font-weight: 600;
  font-size: 0.875rem;
  line-height: 1.6;
}

.description-text-en {
  color: #fbcfe8;
  font-size: 0.75rem;
  margin-top: 0.5rem;
  font-weight: 400;
}

.success-message {
  padding: 0.75rem;
  margin-bottom: 1.25rem;
  background-color: #16a34a;
  color: #ffffff;
  border-radius: 0.75rem;
  text-align: center;
  font-weight: 500;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  font-size: 0.75rem;
  margin-left: 1rem;
  margin-right: 1rem;
}

.gifts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  margin-top: 3rem;
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

.gift-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  border: 1px solid #fce7f3;
  border-radius: 0.75rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  background-color: #ffffff;
  transition: all 0.2s ease;
}

.gift-card--reserved {
  opacity: 0.6;
}

.gift-image-wrapper {
  width: 3.5rem;
  height: 3.5rem;
  margin-bottom: 0.5rem;
  flex-shrink: 0;
}

.gift-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.gift-icon-placeholder {
  width: 100%;
  height: 100%;
  background-color: #fdf2f8;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f472b6;
  border: 1px solid #fce7f3;
}

.gift-icon {
  width: 1.75rem;
  height: 1.75rem;
}

.gift-info {
  text-align: center;
  margin-bottom: 0.75rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.gift-name {
  font-weight: 700;
  color: #111827;
  font-size: 0.75rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.375;
}

.gift-status {
  display: block;
  font-size: 0.625rem;
  color: #6b7280;
  margin-top: 0.25rem;
  font-weight: 500;
}

.gift-button {
  width: 100%;
  padding: 0.375rem 0.5rem;
  color: #ffffff;
  border-radius: 0.5rem;
  font-weight: 700;
  font-size: 0.75rem;
  transition: background-color 0.2s ease;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  border: none;
  cursor: pointer;
  background-color: #db2777;
}

.gift-button:hover:not(.gift-button--disabled) {
  background-color: #be185d;
}

.gift-button--disabled {
  background-color: #d1d5db;
  cursor: not-allowed;
}

@media (min-width: 640px) {
  .page-container {
    padding: 1.5rem;
  }

  .description-card {
    padding: 1.25rem;
  }

  .description-text-es {
    font-size: 1rem;
  }

  .description-text-en {
    font-size: 0.875rem;
  }

  .success-message {
    font-size: 0.875rem;
  }

  .gifts-grid {
    gap: 1rem;
    margin-top: 5rem;
    padding-left: 4rem;
    padding-right: 4rem;
  }

  .gift-image-wrapper {
    width: 4rem;
    height: 4rem;
  }

  .gift-name {
    font-size: 0.875rem;
  }

  .gift-status {
    font-size: 0.75rem;
  }

  .gift-button {
    font-size: 0.875rem;
  }
}
</style>
