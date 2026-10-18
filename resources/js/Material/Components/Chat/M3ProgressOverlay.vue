<script setup>
/**
 * M3ProgressOverlay — overlay progres di dalam area chat.
 * Dipakai saat memproses bukti/aksi panjang tanpa memblokir seluruh halaman.
 */
import { useI18n } from 'vue-i18n'
import M3ProgressBar from '@/Material/Components/M3ProgressBar.vue'

const { t } = useI18n()

defineProps({
    progress: { type: Number, default: 0 },
    show: { type: Boolean, default: false },
})
</script>

<template>
    <div v-if="show" class="m3-progress-overlay" role="status" :aria-label="t('common.processing')">
        <span class="m3-progress-overlay__spinner" aria-hidden="true" />
        <div class="m3-progress-overlay__bar">
            <M3ProgressBar :value="progress" size="sm" />
        </div>
    </div>
</template>

<style scoped>
.m3-progress-overlay {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    background: color-mix(in srgb, var(--m3-color-scrim) 50%, transparent);
}

.m3-progress-overlay__spinner {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: var(--m3-radius-full);
    border: 2px solid color-mix(in srgb, var(--m3-color-primary) 30%, transparent);
    border-top-color: var(--m3-color-primary);
    animation: m3-progress-overlay-spin 0.7s linear infinite;
}

@keyframes m3-progress-overlay-spin {
    to { transform: rotate(360deg); }
}

.m3-progress-overlay__bar {
    width: var(--m3-space-12);
}
</style>
