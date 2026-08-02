<template>
  <div class="cm-view">

    <AppTopbar
      :title="$t('caseManagers.title')"
      :crumbs="[
        { label: $t('users.crumbs.dashboard'), icon: 'fa-house',    route: 'admin.dashboard' },
        { label: $t('caseManagers.title'),      icon: 'fa-user-tie' },
      ]"
      :actions="[]"
      :search="true"
      :search-value="search"
      :search-placeholder="$t('caseManagers.searchPlaceholder')"
      @update:search-value="onSearchUpdate"
    />

    <div class="cm-view__body">

      <!-- Loading skeleton -->
      <div v-if="store.loading" class="cm-view__grid-inner">
        <div v-for="n in 8" :key="n" class="cm-card-skeleton" />
      </div>

      <!-- Error -->
      <div v-else-if="store.error" class="cm-view__error">
        {{ store.error }}
      </div>

      <!-- Grid -->
      <div v-else class="cm-view__grid">
        <TransitionGroup name="card-list" tag="div" class="cm-view__grid-inner">
          <CaseManagerCard
            v-for="manager in managers"
            :key="manager.id"
            :manager="manager"
            @view-clients="store.openClientsModal"
          />
        </TransitionGroup>
      </div>

      <!-- Empty -->
      <div v-if="!store.loading && managers.length === 0 && !store.error" class="cm-view__empty">
        <span>👥</span>
        <p>{{ $t('caseManagers.empty') }}</p>
      </div>

      <!-- Paginación -->
      <div v-if="meta.last_page > 1" class="cm-view__pagination">
        <button :disabled="meta.current_page <= 1" class="cm-page-btn" @click="changePage(meta.current_page - 1)">‹</button>
        <span class="cm-page-info">{{ meta.current_page }} / {{ meta.last_page }}</span>
        <button :disabled="meta.current_page >= meta.last_page" class="cm-page-btn" @click="changePage(meta.current_page + 1)">›</button>
      </div>

    </div>

    <CaseManagerClientsModal
      v-model="store.modalOpen"
      :manager="store.modalManager"
      :clients="store.modalClients"
      :unassigned="store.modalUnassigned"
      :loading="store.modalLoading"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCaseManagerStore } from '@/stores/caseManagerStore'
import CaseManagerCard from '@/components/caseManager/CaseManagerCard.vue'
import CaseManagerClientsModal from '@/components/caseManager/CaseManagerClientsModal.vue'
import AppTopbar from '@/components/layout/AppTopbar.vue'

const store  = useCaseManagerStore()
const search = ref('')
let searchTimer = null

const managers = computed(() => store.managers ?? [])
const meta     = computed(() => store.meta ?? { current_page: 1, last_page: 1 })

function onSearchUpdate(val) {
  search.value = val
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    store.fetchManagers({ search: val, page: 1 })
  }, 350)
}

function changePage(page) {
  store.fetchManagers({ search: search.value, page })
}

onMounted(() => store.fetchManagers())
</script>

<style scoped>
.cm-view {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.cm-view__body {
  padding: 24px;
  flex: 1;
  overflow-y: auto;
}
.cm-view__grid { width: 100%; }
.cm-view__grid-inner {
  display: flex;
  flex-wrap: wrap;
  gap: 28px;
  justify-content: flex-start;
}
.cm-card-skeleton {
  width: 220px; min-height: 340px; border-radius: 20px;
  background: linear-gradient(90deg, #e3eaf4 25%, #f0f4f8 50%, #e3eaf4 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.cm-view__empty,
.cm-view__error {
  display: flex; flex-direction: column;
  align-items: center; gap: 8px;
  padding: 48px 0; color: #90a4ae; font-size: 1rem;
}
.cm-view__empty span { font-size: 3rem; }
.cm-view__pagination {
  display: flex; align-items: center;
  justify-content: center; gap: 16px; margin-top: 36px;
}
.cm-page-btn {
  width: 38px; height: 38px; border-radius: 8px;
  border: 1.5px solid #cfd8dc; background: #fff;
  font-size: 1.2rem; cursor: pointer; color: #1565c0;
  transition: background 0.15s, border-color 0.15s;
}
.cm-page-btn:hover:not(:disabled) { background: #e3f2fd; border-color: #1565c0; }
.cm-page-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.cm-page-info { font-size: 0.88rem; color: #546e7a; font-weight: 600; }
.card-list-enter-active, .card-list-leave-active { transition: opacity 0.3s, transform 0.3s; }
.card-list-enter-from, .card-list-leave-to { opacity: 0; transform: translateY(12px); }

@media (max-width: 767px) {
  .cm-view__body { padding: 16px; }
  .cm-view__grid-inner { justify-content: center; }
}
</style>