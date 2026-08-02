<template>
    <div class="cc-card" :class="{ 'cc-card--inactive': !client.is_active }">

        <!-- Franja superior decorativa -->
        <div class="cc-card__stripe" />

        <!-- Avatar + estado -->
        <div class="cc-card__avatar-wrap">
            <div class="cc-card__avatar-ring">
                <img :src="client.profile_image_url" :alt="client.name" class="cc-card__avatar" />
            </div>
            <span class="cc-card__status" :class="client.is_active ? 'cc-card__status--active' : 'cc-card__status--inactive'">
                <span class="cc-card__status-dot" />
                {{ client.is_active ? $t('managerClients.active') : $t('managerClients.inactive') }}
            </span>
        </div>

        <!-- Info principal -->
        <div class="cc-card__info">
            <h3 class="cc-card__name">{{ client.name }}</h3>
            <p class="cc-card__email">
                <i class="fa-solid fa-envelope"></i>
                {{ client.email }}
            </p>
        </div>

        <!-- Stats -->
        <div class="cc-card__stats">
            <div class="cc-stat">
                <span class="cc-stat__value">{{ client.appointments_count }}</span>
                <span class="cc-stat__label">{{ $t('managerClients.totalAppts') }}</span>
            </div>
            <div class="cc-stat-divider" />
            <div class="cc-stat">
                <span class="cc-stat__value cc-stat__value--pending">{{ client.pending_count }}</span>
                <span class="cc-stat__label">{{ $t('managerClients.pending') }}</span>
            </div>
            <div class="cc-stat-divider" />
            <div class="cc-stat">
                <span class="cc-stat__value cc-stat__value--date">
                    {{ formatDate(client.created_at) }}
                </span>
                <span class="cc-stat__label">{{ $t('managerClients.since') }}</span>
            </div>
        </div>

        <!-- Acciones -->
        <div class="cc-card__actions">
            <button class="cc-btn cc-btn--password" @click="$emit('change-password')">
                <i class="fa-solid fa-key"></i>
                {{ $t('managerClients.changePassword') }}
            </button>
        </div>

    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

defineProps({
    client: { type: Object, required: true },
})

defineEmits(['change-password'])

function formatDate(date) {
    if (!date) return '—'
    return new Date(date + 'T00:00:00').toLocaleDateString(
        locale.value === 'es' ? 'es-ES' : 'en-US',
        { month: 'short', year: 'numeric' }
    )
}
</script>

<style scoped>
/* ── Card base ── */
.cc-card {
    position: relative;
    background: rgba(255, 255, 255, 0.07);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 24px 24px;
    transition: transform 0.22s cubic-bezier(.34,1.56,.64,1),
                box-shadow 0.22s ease,
                border-color 0.2s;
}

.cc-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.35);
    border-color: rgba(255, 255, 255, 0.22);
}

.cc-card--inactive {
    opacity: 0.62;
    filter: grayscale(0.4);
}

/* Franja decorativa superior */
.cc-card__stripe {
    width: calc(100% + 2px);
    height: 6px;
    margin: -1px -1px 0;
    background: linear-gradient(90deg, #2563eb, #06b6d4, #2563eb);
    background-size: 200% 100%;
    animation: stripe-flow 3s linear infinite;
    flex-shrink: 0;
}

.cc-card--inactive .cc-card__stripe {
    background: linear-gradient(90deg, #475569, #64748b, #475569);
    animation: none;
}

@keyframes stripe-flow {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Avatar */
.cc-card__avatar-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    margin-top: 24px;
    margin-bottom: 16px;
}

.cc-card__avatar-ring {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    padding: 3px;
    background: conic-gradient(
        #2563eb 0deg,
        #06b6d4 90deg,
        #2563eb 180deg,
        #06b6d4 270deg,
        #2563eb 360deg
    );
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
    transition: box-shadow 0.2s;
}

.cc-card:hover .cc-card__avatar-ring {
    box-shadow: 0 6px 28px rgba(37, 99, 235, 0.6);
}

.cc-card--inactive .cc-card__avatar-ring {
    background: conic-gradient(#475569 0deg, #64748b 180deg, #475569 360deg);
    box-shadow: none;
}

.cc-card__avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(15, 25, 50, 0.8);
}

/* Status badge */
.cc-card__status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.cc-card__status--active {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.4);
    color: #4ade80;
}

.cc-card__status--inactive {
    background: rgba(100, 116, 139, 0.2);
    border: 1px solid rgba(100, 116, 139, 0.3);
    color: #94a3b8;
}

.cc-card__status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.cc-card__status--active .cc-card__status-dot {
    animation: pulse 1.8s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* Info */
.cc-card__info {
    text-align: center;
    width: 100%;
    margin-bottom: 20px;
}

.cc-card__name {
    font-size: 1.15rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 6px;
    letter-spacing: 0.01em;
}

.cc-card__email {
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.5);
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.cc-card__email i {
    font-size: 0.72rem;
    color: #2563eb;
}

/* Stats */
.cc-card__stats {
    display: flex;
    align-items: center;
    gap: 0;
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 14px 0;
    margin-bottom: 20px;
}

.cc-stat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

.cc-stat-divider {
    width: 1px;
    height: 32px;
    background: rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.cc-stat__value {
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}

.cc-stat__value--pending { color: #fbbf24; }

.cc-stat__value--date {
    font-size: 0.85rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.8);
}

.cc-stat__label {
    font-size: 0.65rem;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

/* Acciones */
.cc-card__actions {
    width: 100%;
    display: flex;
    gap: 10px;
}

.cc-btn {
    flex: 1;
    padding: 10px 16px;
    border-radius: 12px;
    border: none;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: filter 0.2s, transform 0.15s;
    font-family: 'Segoe UI', sans-serif;
}

.cc-btn--password {
    background: rgba(37, 99, 235, 0.2);
    border: 1px solid rgba(37, 99, 235, 0.4);
    color: #93c5fd;
}

.cc-btn--password:hover {
    filter: brightness(1.2);
    transform: translateY(-1px);
}

.cc-btn--password i { font-size: 0.8rem; }
</style>