<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="modelValue" class="cm-modal-backdrop" @click.self="$emit('update:modelValue', false)">
        <div class="cm-modal">

          <!-- ── LEFT: Card del case manager ── -->
          <div class="cm-modal__left">
            <div class="cm-left__badge">
              <img :src="logoBadge" alt="RAISE" />
            </div>
            <div class="cm-left__hero">
              <div class="cm-left__curve" />
              <div class="cm-left__avatar-ring">
                <img :src="manager?.profile_image || defaultAvatar" :alt="manager?.name" class="cm-left__avatar" />
              </div>
            </div>
            <div class="cm-left__info">
              <h2 class="cm-left__name">{{ manager?.name }}</h2>
              <p class="cm-left__position">Job <span>Case Manager</span></p>
              <div class="cm-left__details">
                <div class="cm-left__detail-row">
                  <span class="cm-left__detail-label">Email :</span>
                  <span class="cm-left__detail-value">{{ manager?.email }}</span>
                </div>
                <div class="cm-left__detail-row">
                  <span class="cm-left__detail-label">{{ $t('caseManagers.assignedClients') }} :</span>
                  <span class="cm-left__detail-value">{{ assignedList.length }}</span>
                </div>
              </div>
              <div class="cm-left__barcode">
                <svg viewBox="0 0 200 40" xmlns="http://www.w3.org/2000/svg">
                  <g fill="#1a2a4a" opacity="0.7">
                    <rect v-for="(bar, i) in barcode" :key="i" :x="bar.x" y="0" :width="bar.w" height="40" />
                  </g>
                </svg>
              </div>
            </div>
          </div>

          <!-- ── RIGHT: Clientes ── -->
          <div class="cm-modal__right">
            <button class="cm-modal__close" @click="$emit('update:modelValue', false)">✕</button>

            <div class="cm-tabs">
              <button class="cm-tab" :class="{ 'cm-tab--active': activeTab === 'assigned' }"
                @click="activeTab = 'assigned'; currentPage = 1">
                {{ $t('caseManagers.assignedClients') }}
                <span class="cm-tab__count">{{ assignedList.length }}</span>
              </button>
              <button class="cm-tab" :class="{ 'cm-tab--active': activeTab === 'unassigned' }"
                @click="activeTab = 'unassigned'; currentPage = 1">
                {{ $t('caseManagers.unassigned') }}
                <span class="cm-tab__count">{{ unassignedList.length }}</span>
              </button>
            </div>

            <div v-if="loading" class="cm-right__loading">
              <div class="cm-spinner" />
            </div>

            <div v-else class="cm-clients-grid">
              <template v-if="paginatedList.length > 0">
                <!-- ← @click agregado aquí -->
                <div v-for="client in paginatedList" :key="client.id" class="cm-client-item"
                  @click="store.openClientDetail(client)">
                  <div class="cm-client-item__avatar-ring">
                    <img :src="client.profile_image || defaultAvatar" :alt="client.name" />
                  </div>
                  <span class="cm-client-item__name">{{ client.name }}</span>
                </div>
              </template>

              <div v-else class="cm-right__empty">
                <span>👤</span>
                <p>{{ activeTab === 'assigned' ? $t('caseManagers.noClients') : $t('caseManagers.noUnassigned') }}</p>
              </div>
            </div>

            <div v-if="totalPages > 1" class="cm-pagination">
              <button class="cm-pag-btn" :disabled="currentPage <= 1" @click="currentPage--">&lt;</button>
              <button v-for="p in totalPages" :key="p" class="cm-pag-btn cm-pag-btn--num"
                :class="{ 'cm-pag-btn--active': p === currentPage }" @click="currentPage = p">{{ p }}</button>
              <button class="cm-pag-btn" :disabled="currentPage >= totalPages" @click="currentPage++">&gt;</button>
            </div>
          </div>

        </div>
      </div>
    </Transition>




  </Teleport>
  <!-- ← ClientDetailModal DENTRO del Teleport -->
  <ClientDetailModal v-model="store.clientModalOpen" :client="store.selectedClient" :managers="store.allManagers"
    :action-loading="store.actionLoading" :current-manager-id="store.currentManagerId" @reassign="store.reassignClient"
    @release="store.releaseClient" />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useCaseManagerStore } from '@/stores/caseManagerStore'
import ClientDetailModal from '@/components/caseManager/ClientDetailModal.vue'

const store = useCaseManagerStore()

const logoBadge = '/images/raise-logo.png'
const defaultAvatar = 'https://ui-avatars.com/api/?name=C&background=1565c0&color=fff'

const props = defineProps({
  modelValue: Boolean,
  manager: { type: Object, default: null },
  clients: { type: Array, default: () => [] },
  unassigned: { type: Array, default: () => [] },
  loading: Boolean,
})
defineEmits(['update:modelValue'])

const activeTab = ref('assigned')
const currentPage = ref(1)
const perPage = 9

const assignedList = computed(() => props.clients)
const unassignedList = computed(() => props.unassigned)
const activeList = computed(() => activeTab.value === 'assigned' ? assignedList.value : unassignedList.value)
const totalPages = computed(() => Math.ceil(activeList.value.length / perPage))
const paginatedList = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return activeList.value.slice(start, start + perPage)
})

const barcode = computed(() => {
  const bars = []; let x = 0
  for (let i = 0; i < 40; i++) {
    const w = Math.random() > 0.5 ? 3 : 2
    if (i % 2 === 0) bars.push({ x, w })
    x += w + (Math.random() > 0.6 ? 2 : 1)
  }
  return bars
})
</script>

<style>
/* ── Backdrop ── */
.cm-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.cm-modal {
  display: flex;
  border-radius: 22px;
  overflow: hidden;
  width: 100%;
  max-width: 860px;
  max-height: 90vh;
  box-shadow: 0 32px 80px rgba(0, 0, 0, 0.4);
}

.cm-modal__left {
  position: relative;
  width: 280px;
  min-width: 280px;
  background: linear-gradient(160deg, #1a2a4a 0%, #1565c0 50%, #e8edf5 50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  overflow: hidden;
}

.cm-left__badge {
  position: absolute;
  top: 12px;
  left: 14px;
  z-index: 10;
  width: 62px;
  height: 62px;
}

.cm-left__badge img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.5));
}

.cm-left__hero {
  position: relative;
  width: 100%;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cm-left__curve {
  position: absolute;
  right: -22px;
  top: 10px;
  width: 120px;
  height: 160px;
  background: #1565c0;
  border-radius: 50% 0 0 50%;
  z-index: 0;
}

.cm-left__avatar-ring {
  position: relative;
  z-index: 2;
  width: 138px;
  height: 138px;
  border-radius: 50%;
  background: conic-gradient(#1565c0 0deg, #42a5f5 120deg, #1565c0 360deg);
  padding: 4px;
  box-shadow: 0 4px 24px rgba(21, 101, 192, 0.5);
}

.cm-left__avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #fff;
  background: #fff;
}

.cm-left__info {
  flex: 1;
  width: 100%;
  background: #e8edf5;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 18px 20px;
}

.cm-left__name {
  font-size: 1.15rem;
  font-weight: 800;
  color: #1a2a4a;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  text-align: center;
  margin: 0 0 4px;
}

.cm-left__position {
  font-size: 0.82rem;
  color: #546e7a;
  margin: 0 0 14px;
}

.cm-left__position span {
  color: #43a047;
  font-weight: 600;
}

.cm-left__details {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}

.cm-left__detail-row {
  display: flex;
  gap: 6px;
  font-size: 0.78rem;
  justify-content: center;
  flex-wrap: wrap;
  text-align: center;
}

.cm-left__detail-label {
  color: #546e7a;
  font-weight: 600;
}

.cm-left__detail-value {
  color: #1a2a4a;
}

.cm-left__barcode {
  width: 85%;
  margin-top: auto;
}

.cm-left__barcode svg {
  width: 100%;
  height: 36px;
}

/* RIGHT */
.cm-modal__right {
  position: relative;
  flex: 1;
  background: linear-gradient(135deg, #cfd8dc 0%, #b0bec5 100%);
  display: flex;
  flex-direction: column;
  padding: 20px 20px 16px;
  overflow: hidden;
}

.cm-modal__close {
  position: absolute;
  top: 12px;
  right: 14px;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  color: #1a2a4a;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.2s;
  z-index: 5;
}

.cm-modal__close:hover {
  background: rgba(255, 255, 255, 0.45);
}

.cm-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 18px;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 5px;
  border: 1px solid rgba(255, 255, 255, 0.4);
}

.cm-tab {
  flex: 1;
  padding: 9px 12px;
  border-radius: 9px;
  border: none;
  background: transparent;
  font-size: 0.85rem;
  font-weight: 600;
  color: #37474f;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.cm-tab--active {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(12px);
  color: #1a2a4a;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.cm-tab__count {
  background: #1565c0;
  color: #fff;
  border-radius: 10px;
  padding: 1px 7px;
  font-size: 0.72rem;
  font-weight: 700;
}

.cm-right__loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cm-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: #1565c0;
  border-radius: 50%;
  animation: cm-spin 0.8s linear infinite;
}

@keyframes cm-spin {
  to {
    transform: rotate(360deg);
  }
}

.cm-clients-grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px 12px;
  overflow-y: auto;
  padding: 4px 2px;
  align-content: start;
}

.cm-client-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
  cursor: pointer;
}

.cm-client-item__avatar-ring {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: conic-gradient(#c0c0c0 0deg, #e8e8e8 60deg, #a0a0a0 120deg,
      #d8d8d8 180deg, #c0c0c0 240deg, #e8e8e8 300deg, #c0c0c0 360deg);
  padding: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25), inset 0 1px 2px rgba(255, 255, 255, 0.6);
  transition: transform 0.2s;
}

.cm-client-item:hover .cm-client-item__avatar-ring {
  transform: scale(1.07);
}

.cm-client-item__avatar-ring img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid rgba(255, 255, 255, 0.8);
}

.cm-client-item__name {
  font-size: 0.78rem;
  font-weight: 600;
  color: #1a2a4a;
  text-align: center;
  max-width: 90px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cm-right__empty {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 32px 0;
  color: #546e7a;
}

.cm-right__empty span {
  font-size: 2.5rem;
}

.cm-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 14px;
}

.cm-pag-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(8px);
  color: #1a2a4a;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cm-pag-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.6);
}

.cm-pag-btn--active {
  background: #1565c0 !important;
  color: #fff;
  box-shadow: 0 2px 8px rgba(21, 101, 192, 0.4);
}

.cm-pag-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s, transform 0.25s;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: scale(0.96);
}

@media (max-width: 767px) {
  .cm-modal-backdrop {
    align-items: center;
    padding: 16px 20px;
  }

  .cm-modal {
    flex-direction: column;
    max-width: 100%;
    max-height: 95vh;
    border-radius: 22px;
  }

  .cm-modal__left {
    width: 100%;
    min-width: unset;
    flex-direction: row;
    align-items: center;
    padding: 16px;
    background: linear-gradient(135deg, #1a2a4a 0%, #1565c0 100%);
    min-height: unset;
  }

  .cm-left__badge {
    position: relative;
    top: unset;
    left: unset;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
  }

  .cm-left__hero {
    width: 70px;
    height: 70px;
    flex-shrink: 0;
  }

  .cm-left__curve {
    display: none;
  }

  .cm-left__avatar-ring {
    width: 70px;
    height: 70px;
  }

  .cm-left__info {
    flex: 1;
    background: transparent;
    padding: 0 12px;
    align-items: flex-start;
  }

  .cm-left__name {
    font-size: 0.95rem;
    color: #fff;
  }

  .cm-left__position {
    color: rgba(255, 255, 255, 0.7);
    margin: 0 0 4px;
  }

  .cm-left__position span {
    color: #a5f3d0;
  }

  .cm-left__details {
    margin-bottom: 0;
  }

  .cm-left__detail-row {
    justify-content: flex-start;
  }

  .cm-left__detail-label {
    color: rgba(255, 255, 255, 0.6);
  }

  .cm-left__detail-value {
    color: #fff;
  }

  .cm-left__barcode {
    display: none;
  }

  .cm-modal__right {
    flex: 1;
    overflow-y: auto;
    border-radius: 0;
  }

  .cm-clients-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Manager asignado */
.cd-panel__cm-title {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #546e7a;
  text-transform: uppercase;
  margin-bottom: 8px;
  margin-top: 8px;
}

.cd-panel__avatar-ring--assigned {
  background: conic-gradient(#43a047 0deg, #66bb6a 60deg, #2e7d32 120deg,
      #4caf50 180deg, #43a047 240deg, #66bb6a 300deg, #43a047 360deg) !important;
}

/* Item asignado en verde */
.cd-reassign-item--assigned {
  background: rgba(67, 160, 71, 0.2) !important;
  border: 1.5px solid rgba(67, 160, 71, 0.5);
}

.cd-reassign-item--assigned:hover {
  background: rgba(67, 160, 71, 0.3) !important;
}

.cd-reassign-item__badge {
  margin-left: auto;
  background: #43a047;
  color: #fff;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}
</style>