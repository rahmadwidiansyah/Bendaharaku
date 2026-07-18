/**
 * useLocale.js
 *
 * Composable untuk mengelola preferensi bahasa pengguna.
 *
 * Fitur:
 *   - Deteksi bahasa device/browser secara otomatis
 *   - Simpan preferensi ke localStorage (reaktif di frontend)
 *   - Simpan ke DB via PATCH /settings/locale (agar chat Telegram & Web pakai locale user)
 *   - Switch bahasa secara reaktif tanpa reload
 *   - Pilihan: Auto (ikuti device), id, en
 *
 * Singleton — state dibagi seluruh aplikasi.
 */

import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { SUPPORTED_LOCALES } from '@/i18n/index.js'

const STORAGE_KEY = 'locale'

// ── Singleton ──────────────────────────────────────────────────────
// 'auto' berarti ikuti device, string lain = preferensi manual
const preference = ref(localStorage.getItem(STORAGE_KEY) || 'auto')

export function useLocale() {
    const { locale } = useI18n()

    /**
     * Locale yang aktif saat ini (dipakai oleh vue-i18n)
     */
    const currentLocale = computed(() => locale.value)

    /**
     * Preferensi yang tersimpan: 'auto' | 'id' | 'en'
     */
    const currentPreference = computed(() => preference.value)

    /**
     * Ganti bahasa aplikasi.
     *
     * @param {'auto'|'id'|'en'} newLocale
     *   - 'auto' → hapus preferensi, ikuti device
     *   - 'id'/'en' → simpan preferensi, switch langsung
     */
    function setLocale(newLocale) {
        if (newLocale === 'auto') {
            localStorage.removeItem(STORAGE_KEY)
            preference.value = 'auto'
            // Resolve dari device
            const deviceLang   = navigator.language || navigator.userLanguage || 'id'
            const devicePrefix = deviceLang.split('-')[0].toLowerCase()
            locale.value = SUPPORTED_LOCALES.includes(devicePrefix) ? devicePrefix : 'id'
        } else if (SUPPORTED_LOCALES.includes(newLocale)) {
            localStorage.setItem(STORAGE_KEY, newLocale)
            preference.value = newLocale
            locale.value     = newLocale
        }

        // Simpan ke DB agar chat (Telegram & Web) membaca locale yang benar.
        // Silent fail — tidak blokir UI jika request gagal.
        axios.patch('/settings/locale', { locale: newLocale }).catch((err) => {
            console.warn('[useLocale] Gagal simpan locale ke server:', err?.message)
        })
    }

    /**
     * Label human-readable untuk preferensi saat ini
     */
    const localeLabel = computed(() => {
        const map = { auto: 'Auto', id: 'Bahasa Indonesia', en: 'English' }
        return map[preference.value] ?? preference.value
    })

    return {
        currentLocale,
        currentPreference,
        localeLabel,
        setLocale,
        SUPPORTED_LOCALES,
    }
}
