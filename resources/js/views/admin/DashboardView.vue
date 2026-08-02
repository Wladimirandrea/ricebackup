<script setup>
import AppTopbar from '@/components/layout/AppTopbar.vue'

const crumbs = [
    { label: 'Dashboard', icon: 'fa-house', route: null },
]

const actions = []
</script>

<template>
    <div class="page">
        <AppTopbar
            title="Dashboard"
            :crumbs="crumbs"
            :actions="actions"
        />
        <div class="content">
            <!-- Aquí va el contenido del dashboard -->
        </div>
    </div>
</template>

<style scoped>
.page {
    display: grid;
    grid-template-rows: auto 1fr;
    height: 100%;
    overflow: hidden;
}
.content {
    overflow-y: auto;
    padding: 20px;
}
</style>