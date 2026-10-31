<script setup>
/**
 * M3SettingsShell — grid master-detail halaman Settings (desktop).
 *
 * Kolom kiri: M3SettingsNav (panel navigasi, hanya tampil ≥1024px DAN saat
 * user tidak memaksa layout mobile via preferensi layout). Kolom kanan:
 * slot konten halaman. Di mobile slot dirender penuh — pengalaman mobile
 * existing tidak berubah.
 *
 * Dipakai langsung oleh M3SettingsPage (mode shell) dan oleh halaman
 * Settings dengan struktur custom (AI/Models, AI/Bot, AI/Memory,
 * Chat/Settings) yang membungkus konten existing di slot.
 */
import M3SettingsNav from '@/Material/Components/Settings/M3SettingsNav.vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'

defineProps({
    // Lebar maksimum kolom konten: 'list' (42rem) atau 'content' (56rem).
    width: {
        type: String,
        default: 'list',
        validator: (v) => ['list', 'content'].includes(v),
    },
})

const { isDesktopLayout } = useLayoutPreference()
</script>

<template>
    <div
        class="m3-settings-shell"
        :class="[`m3-settings-shell--${width}`, isDesktopLayout ? 'm3-settings-shell--desktop' : '']"
    >
        <M3SettingsNav v-if="isDesktopLayout" />
        <div class="m3-settings-shell__body">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.m3-settings-shell {
    display: flex;
    flex-direction: column;
    width: 100%;
}

@media (min-width: 1024px) {
    .m3-settings-shell--desktop {
        display: grid;
        grid-template-columns: minmax(0, 15.5rem) minmax(0, 1fr);
        gap: var(--m3-space-8);
        align-items: start;
        justify-content: center;
        padding-inline: var(--m3-density-page-inset, var(--m3-space-4));
        /* Tinggi minimum = area di bawah app bar: tanpa ini, subpage berkonten
           pendek membuat grid row lebih pendek dari panel nav — sticky offset
           tak muat dan browser menahan nav di posisi lain (list
           "turun-naik" antar halaman). */
        min-height: calc(100vh - 5rem);
    }

    /* Konten embedded dari M3SettingsPage: padding horizontal ditangani
       shell, sehingga nol-kan milik halaman (hindari dobel inset). */
    .m3-settings-shell--desktop :deep(.m3-settings-page--embedded) {
        padding-inline: 0;
    }

    .m3-settings-shell--desktop.m3-settings-shell--list .m3-settings-shell__body {
        max-width: 42rem;
    }

    .m3-settings-shell--desktop.m3-settings-shell--content .m3-settings-shell__body {
        max-width: 56rem;
    }
}
</style>
