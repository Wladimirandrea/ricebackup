<template>
    <div class="mc-view">
        <AppTopbar
            :title="$t('managerClients.title')"
            :crumbs="[
                { label: $t('nav.dashboard'), icon: 'fa-house', route: 'manager.dashboard' },
                { label: $t('managerClients.title'), icon: 'fa-users' },
            ]"
            :search="true"
            :search-value="store.search"
            :search-placeholder="$t('managerClients.searchPlaceholder')"
            @update:search-value="onSearch"
        />

        <div class="mc-body">

            <!-- Loading -->
            <div v-if="store.loading" class="mc-loading">
                <div v-for="n in 6" :key="n" class="mc-skeleton" />
            </div>

            <!-- Error -->
            <div v-else-if="store.error" class="mc-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>{{ store.error }}</p>
            </div>

            <!-- Empty -->
            <div v-else-if="store.clients.length === 0" class="mc-empty">
                <div class="mc-empty__icon">👤</div>
                <h3>{{ $t('managerClients.noClients') }}</h3>
                <p>{{ $t('managerClients.noClientsDesc') }}</p>
            </div>

            <!-- Grid de cards -->
            <TransitionGroup v-else name="card-fade" tag="div" class="mc-grid">
                <ClientCard
                    v-for="client in store.clients"
                    :key="client.id"
                    :client="client"
                    @change-password="store.openPasswordModal(client)"
                />
            </TransitionGroup>

        </div>

        <!-- Mini-modal contraseña -->
        <PasswordModal />
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useManagerClientStore } from '@/stores/managerClientStore'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import ClientCard from '@/components/caseManager/ClientCard.vue'
import PasswordModal from '@/components/caseManager/PasswordModal.vue'

const store = useManagerClientStore()

let searchTimer = null
function onSearch(val) {
    store.search = val
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => store.fetchClients(), 350)
}

onMounted(() => store.fetchClients())
</script>

<style scoped>
.mc-view {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.mc-body {
    flex: 1;
    overflow-y: auto;
    padding: 28px 32px;
}

/* Grid */
.mc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

/* Loading skeleton */
.mc-loading {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.mc-skeleton {
    height: 240px;
    border-radius: 20px;
    background: linear-gradient(90deg,
        rgba(255,255,255,0.06) 25%,
        rgba(255,255,255,0.12) 50%,
        rgba(255,255,255,0.06) 75%
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}

@keyframes shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Error */
.mc-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 64px 0;
    color: #f87171;
    font-size: 0.9rem;
}

.mc-error i { font-size: 2.5rem; opacity: 0.7; }

/* Empty */
.mc-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 80px 0;
    text-align: center;
}

.mc-empty__icon { font-size: 4rem; }

.mc-empty h3 {
    color: rgba(255,255,255,0.8);
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}

.mc-empty p {
    color: rgba(255,255,255,0.4);
    font-size: 0.88rem;
    margin: 0;
}

/* Transitions */
.card-fade-enter-active { transition: opacity 0.3s, transform 0.3s; }
.card-fade-enter-from   { opacity: 0; transform: translateY(12px); }

@media (max-width: 767px) {
    .mc-body { padding: 16px; }
    .mc-grid  { grid-template-columns: 1fr; }
}
</style>