<script setup>
/**
 * M3SettingsPage — kerangka halaman Settings tema M3.
 *
 * Menyediakan container, judul in-content (mobile), tombol kembali opsional,
 * dan varian lebar: `list` (hub/daftar) atau `content` (form/pengaturan).
 * Semua warna dan ukuran memakai token --m3-*.
 */
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    backRoute: { type: String, default: '' },
    backLabel: { type: String, default: '' },
    width: {
        type: String,
        default: 'content',
        validator: (v) => ['list', 'content'].includes(v),
    },
})
</script>

<template>
    <div class="m3-settings-page" :class="`m3-settings-page--${width}`">
        <div v-if="backRoute" class="m3-settings-page__back">
            <M3IconButton :href="route(backRoute)" :aria-label="backLabel || $t('common.back')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </M3IconButton>
        </div>

        <M3PageTitle :title="title" :subtitle="subtitle">
            <template v-if="$slots.actions" #actions>
                <slot name="actions" />
            </template>
        </M3PageTitle>

        <slot />
    </div>
</template>

<style scoped>
.m3-settings-page {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    width: 100%;
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-settings-page--list {
    max-width: var(--m3-size-list-max);
}

.m3-settings-page--content {
    max-width: var(--m3-size-content-max);
}

.m3-settings-page__back {
    display: flex;
    margin-bottom: calc(-1 * var(--m3-space-3));
}
</style>
