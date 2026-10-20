<script setup>
/**
 * M3ProgressOverlay — overlay progres di dalam area chat.
 * Dipakai saat memproses bukti/aksi panjang tanpa memblokir seluruh halaman.
 */
import { useI18n } from 'vue-i18n'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3ProgressBar from '@/Material/Components/M3ProgressBar.vue'

const { t } = useI18n()

defineProps({
    progress: { type: Number, default: 0 },
    show: { type: Boolean, default: false },
})
</script>

<template>
    <div v-if="show" class="m3-progress-overlay" role="status" :aria-label="t('common.processing')">
        <M3CircularProgress class="m3-progress-overlay__spinner" size="md" />
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
    color: var(--m3-color-primary);
}

.m3-progress-overlay__bar {
    width: var(--m3-space-12);
}
</style>
