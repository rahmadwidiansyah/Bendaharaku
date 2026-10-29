<script setup>
/**
 * M3SettingsBack — tombol kembali ke hub Settings.
 *
 * Dua mode:
 * - inline (default false): merender M3IconButton polos tanpa pembungkus,
 *   untuk ditanam di slot `#leading` M3PageTitle sehingga sejajar satu
 *   baris dengan judul di mobile.
 * - default (tanpa inline): bar ikon tersendiri yang hanya tampil di
 *   desktop — di desktop judul in-content disembunyikan (judul ada di
 *   app bar) sehingga bar ini satu-satunya aksi kembali.
 *
 * Keduanya memakai goBack (kembali ke halaman asal pembuka; fallback
 * route settings.index). Visual & perilaku identik dengan back bawaan
 * M3SettingsPage (ikon chevron kiri, margin negatif menempel judul).
 */
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import { goBack } from '@/Material/utils/goBack.js'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    inline: { type: Boolean, default: false },
})

const { t } = useI18n()

const handleBack = () => goBack(route('settings.index'))
</script>

<template>
    <M3IconButton v-if="inline" :aria-label="t('common.back')" @click="handleBack">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
    </M3IconButton>

    <div v-else class="m3-settings-back">
        <M3IconButton :aria-label="t('common.back')" @click="handleBack">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </M3IconButton>
    </div>
</template>

<style scoped>
/* Mode pembungkus: hanya desktop — di mobile ikon kembali dipasang
   via varian inline di slot leading M3PageTitle. */
.m3-settings-back {
    display: none;
}

@media (min-width: 1024px) {
    .m3-settings-back {
        display: flex;
        margin-bottom: calc(-1 * var(--m3-space-3));
    }
}
</style>
