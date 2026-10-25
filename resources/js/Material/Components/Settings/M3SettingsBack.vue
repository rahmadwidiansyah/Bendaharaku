<script setup>
/**
 * M3SettingsBack — tombol kembali ke hub Settings untuk halaman sub-settings
 * yang memakai kerangka sendiri (AI/Models, AI/Bot, AI/Memory) sehingga tidak
 * melewati M3SettingsPage. Visual & perilaku identik dengan back bawaan
 * M3SettingsPage (ikon chevron kiri, margin negatif menempel judul).
 */
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
    <div class="m3-settings-back">
        <M3IconButton :href="route('settings.index')" :aria-label="t('common.back')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </M3IconButton>
    </div>
</template>

<style scoped>
.m3-settings-back {
    display: flex;
    margin-bottom: calc(-1 * var(--m3-space-3));
}
</style>
