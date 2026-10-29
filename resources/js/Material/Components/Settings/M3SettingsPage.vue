<script setup>
/**
 * M3SettingsPage — kerangka halaman Settings tema M3.
 *
 * Menyediakan container, judul in-content (mobile), tombol kembali opsional,
 * dan varian lebar: `list` (hub/daftar) atau `content` (form/pengaturan).
 * Tombol kembali memakai goBack (halaman asal; fallback `backRoute`).
 * Semua warna dan ukuran memakai token --m3-*.
 */
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import { goBack } from '@/Material/utils/goBack.js'

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    backRoute: { type: String, default: '' },
    backLabel: { type: String, default: '' },
    width: {
        type: String,
        // Default 'list' (40rem): ukuran baca form yang sehat dan konsisten
        // dengan hub — form tidak perlu direntang 72rem (audit: input selebar
        // 1120px sulit discan; Appearance sendiri sudah membatasi opsinya ke
        // 640px). Beri 'content' eksplisit hanya untuk halaman yang butuh
        // lebar (mis. tabel besar).
        default: 'list',
        validator: (v) => ['list', 'content'].includes(v),
    },
})
</script>

<template>
    <div
        class="m3-settings-page mx-auto w-full"
        :class="[
            `m3-settings-page--${width}`,
            width === 'list' ? 'max-w-4xl' : 'max-w-6xl',
        ]"
    >
        <!-- Mobile: ikon kembali sejajar satu baris dengan judul (slot leading
             M3PageTitle) — bukan baris tersendiri di atas judul. -->
        <!-- Desktop: bar kembali sendiri, karena judul in-content disembunyikan
             (desktop memakai app bar). -->
        <div
            v-if="backRoute"
            class="m3-settings-page__back m3-settings-page__back--desktop"
        >
            <M3IconButton
                :aria-label="backLabel || $t('common.back')"
                @click="goBack(route(backRoute))"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </M3IconButton>
        </div>

        <M3PageTitle :title="title" :subtitle="subtitle">
            <template v-if="backRoute" #leading>
                <M3IconButton
                    :aria-label="backLabel || $t('common.back')"
                    @click="goBack(route(backRoute))"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </M3IconButton>
            </template>
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
    gap: var(--m3-density-section-gap, var(--m3-space-6));
    width: 100%;
    padding: var(--m3-density-page-inset, var(--m3-space-4));
}

/* Bar kembali hanya tampil di desktop (judul in-content disembunyikan);
   di mobile ikon kembali hidup di slot leading M3PageTitle. */
.m3-settings-page__back--desktop {
    display: none;
}

@media (min-width: 1024px) {
    .m3-settings-page__back--desktop {
        display: flex;
        margin-bottom: calc(-1 * var(--m3-space-3));
    }
}
</style>
