<script setup>
/**
 * M3SettingsPage — kerangka halaman Settings tema M3.
 *
 * Menyediakan container, judul in-content (mobile), tombol kembali opsional,
 * dan varian lebar: `list` (hub/daftar) atau `content` (form/pengaturan).
 * Tombol kembali memakai goBack (halaman asal; fallback `backRoute`).
 * Semua warna dan ukuran memakai token --m3-*.
 *
 * Mode `shell`: layout master-detail desktop — panel navigasi Settings di kiri
 * (sticky, seluruh kategori + state aktif via route().current) dan konten
 * halaman di kanan. Di mobile panel disembunyikan; navigasi kembali tetap
 * lewat tombol kembali existing, sehingga pengalaman mobile tak berubah.
 */
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3SettingsShell from '@/Material/Components/Settings/M3SettingsShell.vue'
import { goBack } from '@/Material/utils/goBack.js'

const props = defineProps({
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
    // Master-detail: panel navigasi Settings tampil di sisi kiri (desktop).
    shell: { type: Boolean, default: false },
})
</script>

<template>
    <M3SettingsShell v-if="shell" :width="width" class="m3-settings-page-body">
        <div
            class="m3-settings-page mx-auto w-full m3-settings-page--embedded"
            :class="`m3-settings-page--${width}`"
        >
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
    </M3SettingsShell>

    <div
        v-else
        class="m3-settings-page mx-auto w-full"
        :class="`m3-settings-page--${width}`"
    >
        <!-- Mobile: ikon kembali sejajar satu baris dengan judul (slot leading
             M3PageTitle) — bukan baris tersendiri di atas judul. -->
        <!-- Desktop non-shell: bar kembali sendiri, karena judul in-content
             disembunyikan (desktop memakai app bar). -->
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

/* Halaman ter-embed di dalam shell: padding horizontal ditangani shell saat
   mode desktop aktif (lihat :deep di M3SettingsShell); di mobile padding
   penuh halaman tetap berlaku. */

/* Bar kembali hanya tampil di desktop non-shell (judul in-content disembunyikan);
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
