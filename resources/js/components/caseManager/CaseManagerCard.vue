<!-- resources/js/components/caseManager/CaseManagerCard.vue -->
<template>
  <div class="cm-card">
    <!-- Badge logo superior izquierdo -->
    <div class="cm-card__badge">
      <img :src="logoBadge" alt="badge" class="cm-card__badge-img" />
    </div>

    <!-- Contador clientes superior derecho -->
    <div class="cm-card__clients-badge">
      <span class="cm-card__clients-dot" />
      {{ manager.clients_count }}
      {{ manager.clients_count === 1 ? $t('caseManagers.client') : $t('caseManagers.clients') }}
    </div>

    <!-- Hero: curva + avatar -->
    <div class="cm-card__hero">
      <div class="cm-card__curve" />
      <div class="cm-card__avatar-ring">
        <img
          :src="manager.profile_image || defaultAvatar"
          :alt="manager.name"
          class="cm-card__avatar"
        />
      </div>
    </div>

    <!-- Info glassmorphism -->
    <div class="cm-card__glass">
      <h3 class="cm-card__name">
        <span class="cm-card__name--regular">{{ firstName }}</span>
        <span class="cm-card__name--accent"> {{ lastName }}</span>
      </h3>
      <p class="cm-card__role">
        Job <span class="cm-card__role--accent">{{ $t('caseManagers.roleLabel') }}</span>
      </p>

      <!-- Botón -->
      <button class="cm-card__btn" @click="$emit('view-clients', manager)">
        {{ $t('caseManagers.viewClients') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const logoBadge = '/images/raise-logo.png'
const props = defineProps({
  manager: { type: Object, required: true },
})

defineEmits(['view-clients'])

const defaultAvatar = 'https://ui-avatars.com/api/?name=CM&background=1565c0&color=fff'

const nameParts = computed(() => props.manager.name.trim().split(' '))
const firstName  = computed(() => nameParts.value[0] ?? '')
const lastName   = computed(() => nameParts.value.slice(1).join(' ') || '')
</script>

<style scoped>
.cm-card {
  position: relative;
  width: 230px;
  border-radius: 22px;
  background: linear-gradient(160deg, #1a2a4a 0%, #1565c0 48%, transparent 48%);
  box-shadow: 0 8px 32px rgba(21, 101, 192, 0.35), 0 2px 8px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  border: 2px solid #1e3a6e;
  overflow: hidden;
  min-height: 360px;
  transition: transform 0.22s cubic-bezier(.34,1.56,.64,1), box-shadow 0.22s ease;
}
.cm-card:hover {
  transform: translateY(-6px) scale(1.03);
  box-shadow: 0 16px 48px rgba(21, 101, 192, 0.45);
}

/* ── Badge logo ── */
.cm-card__badge {
  position: absolute;
  top: 10px; left: 12px;
  z-index: 10;
  width: 56px; height: 56px;
}
.cm-card__badge-img {
  width: 100%; height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));
}

/* ── Contador clientes ── */
.cm-card__clients-badge {
  position: absolute;
  top: 14px; right: 12px;
  z-index: 10;
  display: flex; align-items: center; gap: 5px;
  background: rgba(255,255,255,0.12);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 20px;
  padding: 4px 10px;
  font-size: 0.78rem;
  font-weight: 600;
  color: #fff;
}
.cm-card__clients-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #43a047;
  box-shadow: 0 0 6px #43a047;
  display: inline-block;
}

/* ── Hero ── */
.cm-card__hero {
  position: relative;
  width: 100%;
  height: 185px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cm-card__curve {
  position: absolute;
  right: -20px; top: 8px;
  width: 115px; height: 150px;
  background: #1565c0;
  border-radius: 50% 0 0 50%;
  z-index: 0;
}
.cm-card__avatar-ring {
  position: relative; z-index: 2;
  width: 128px; height: 128px;
  border-radius: 50%;
  background: conic-gradient(#1565c0 0deg, #42a5f5 120deg, #1565c0 360deg);
  padding: 4px;
  box-shadow: 0 4px 20px rgba(21, 101, 192, 0.5);
}
.cm-card__avatar {
  width: 100%; height: 100%;
  border-radius: 50%;
  object-fit: cover;
  background: #fff;
  border: 3px solid #fff;
}

/* ── Glassmorphism info ── */
.cm-card__glass {
  width: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 18px 16px 20px;

  /* Glassmorphism */
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border-top: 1px solid rgba(255, 255, 255, 0.35);
}

.cm-card__name {
  font-size: 1.18rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  margin: 0 0 4px;
  text-transform: uppercase;
  text-align: center;
}
.cm-card__name--regular { color: #1a2a4a; }
.cm-card__name--accent  { color: #43a047; }

.cm-card__role {
  font-size: 0.83rem;
  color: #546e7a;
  margin: 0 0 16px;
  letter-spacing: 0.02em;
}
.cm-card__role--accent { color: #43a047; font-weight: 600; }

/* ── Botón ── */
.cm-card__btn {
  width: 82%;
  padding: 11px 0;
  border-radius: 12px;
  border: none;
  background: linear-gradient(180deg, #d0d5dd 0%, #9aa3af 100%);
  box-shadow:
    inset 0 2px 4px rgba(255,255,255,0.6),
    inset 0 -2px 4px rgba(0,0,0,0.18),
    0 2px 8px rgba(0,0,0,0.15);
  color: #fff;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  cursor: pointer;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
  transition: filter 0.15s, transform 0.15s;
}
.cm-card__btn:hover  { filter: brightness(1.08); transform: scale(1.03); }
.cm-card__btn:active { transform: scale(0.97); filter: brightness(0.96); }
</style>