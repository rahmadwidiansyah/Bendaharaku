/**
 * i18n/index.js
 *
 * Setup vue-i18n instance untuk Bendaharaku.
 *
 * Mendukung:
 *   - Bahasa Indonesia (id) — default fallback
 *   - English (en)
 *
 * Locale priority:
 *   1. User preference (localStorage: 'locale')
 *   2. Browser/device language
 *   3. Fallback ke 'id' (Bahasa Indonesia)
 */

import { createI18n } from 'vue-i18n'
import id from './locales/id.js'
import en from './locales/en.js'

/** Locale yang didukung aplikasi */
export const SUPPORTED_LOCALES = ['id', 'en']

/**
 * Resolve locale dari preferensi device/browser.
 * Ambil dua karakter pertama (misal 'en-US' → 'en').
 */
function getDeviceLocale() {
    const lang = navigator.language || navigator.userLanguage || 'id'
    const prefix = lang.split('-')[0].toLowerCase()
    return SUPPORTED_LOCALES.includes(prefix) ? prefix : 'id'
}

/**
 * Resolve locale awal:
 * 1. localStorage 'locale' jika ada dan valid
 * 2. Device language
 * 3. Fallback 'id'
 */
export function resolveInitialLocale() {
    const saved = localStorage.getItem('locale')
    if (saved && SUPPORTED_LOCALES.includes(saved)) return saved
    return getDeviceLocale()
}

const i18n = createI18n({
    legacy: false,          // Wajib false untuk Composition API
    locale: resolveInitialLocale(),
    fallbackLocale: 'id',
    messages: { id, en },
    // Matikan warning "no translation found" untuk production
    missingWarn: import.meta.env.DEV,
    fallbackWarn: import.meta.env.DEV,
})

export default i18n
