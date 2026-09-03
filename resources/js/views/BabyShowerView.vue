<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const guestId = route.params.guestId;

const currentGuest = ref(null);
const gifts = ref([]);
const message = ref('');

const loadData = async () => {
  try {
    const response = await axios.get(`/api/baby-shower/guest/${guestId}`)
    console.log('Datos recibidos:', response.data)
    currentGuest.value = response.data.guest
    gifts.value = response.data.gifts
  } catch (error) {
    console.error('Error al cargar datos:', error)
  }
}

const selectGift = async (giftId) => {
  try {
    const response = await axios.post(`/api/baby-shower/${giftId}/select`, {
      guest_id: guestId
    });
    message.value = response.data.message;
    await loadData();
  } catch (error) {
    alert(error.response?.data?.message || 'Error al seleccionar el regalo');
  }
};

onMounted(loadData);
</script>

<template>
  <div v-if="currentGuest" class="max-w-2xl mx-auto p-4">
    <h1 class="text-2xl font-bold">¡Bienvenido/a, {{ currentGuest.name }}!</h1>
    <p class="text-gray-600 mb-4">Estos son los regalos que hemos recibido para el Baby Shower, si deseas llevar alguno puedes eligir uno y asi no se repiten los regalos</p>

    <div v-if="message" class="p-3 mb-4 bg-green-100 text-green-700 rounded">
      {{ message }}
    </div>

    <div class="grid gap-3">
      <div 
        v-for="gift in gifts" 
        :key="gift.id" 
        class="flex justify-between items-center p-4 border rounded-lg"
        :class="{ 'bg-gray-100 opacity-60': gift.guest_id !== null }"
      >
        <div class="flex items-center gap-3">
          <img 
            v-if="gift.image_url" 
            :src="gift.image_url" 
            :alt="gift.name"
            class="w-16 h-16 object-cover rounded"
          />
          <div>
            <span class="font-medium">{{ gift.name }}</span>
            <span v-if="gift.guest_id" class="block text-sm text-gray-500">
              {{ gift.guest_id == guestId ? ' (Reservado por ti)' : ' (Ya reservado)' }}
            </span>
          </div>
        </div>

        <button 
          @click="selectGift(gift.id)"
          :disabled="gift.guest_id !== null"
          class="px-4 py-2 text-white rounded font-bold"
          :class="gift.guest_id !== null ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
        >
          {{ gift.guest_id !== null ? 'No disponible' : 'Elegir regalo' }}
        </button>
      </div>
    </div>
  </div>
</template>