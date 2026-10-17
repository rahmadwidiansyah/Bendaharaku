/**
 * useM3ColorScheme — skema warna Material Design 3 (Multi-UI).
 *
 * Arsitektur:
 *  - Tujuh skema lengkap (light + dark, semua role) didefinisikan sebagai CSS
 *    statis di `resources/css/m3-schemes.css`, dipilih lewat atribut
 *    `<html data-m3-scheme="…">`. Tidak ada komputasi warna saat runtime.
 *  - Sumber kebenaran nilai adalah database (`users.ui_m3_color_scheme`),
 *    dirender server-side ke `<meta name="m3-color-scheme">` + head script
 *    Blade (tanpa flash), dibagikan sebagai `auth.user.ui_m3_color_scheme`.
 *  - Preferensi ini INDEPENDEN dari accent_color UI Default
 *    (`useAccentColor.js` → `--color-brand`): keduanya tidak saling menimpa.
 *
 * Modul ini hanya MEMBACA preferensi dan mencerminkannya ke DOM.
 * Penyimpanan melalui PATCH `settings.application.appearance.update`.
 */
import { ref } from 'vue';
import { M3_SCHEME_PREVIEW } from '@/Material/m3SchemeData.generated.js';

export const M3_SCHEME_KEYS = ['teal', 'baseline', 'ocean', 'forest', 'sunset', 'rose', 'midnight'];

export const DEFAULT_M3_COLOR_SCHEME = 'teal';

const m3ColorScheme = ref(DEFAULT_M3_COLOR_SCHEME);
let initialized = false;

export function isValidM3ColorScheme(value) {
    return M3_SCHEME_KEYS.includes(value);
}

export function normalizeM3ColorScheme(value) {
    return isValidM3ColorScheme(value) ? value : DEFAULT_M3_COLOR_SCHEME;
}

/** Warna pratinjau (hex) untuk picker di halaman Appearance. */
export function getM3SchemePreview(key) {
    return M3_SCHEME_PREVIEW[normalizeM3ColorScheme(key)] ?? null;
}

function readFromMeta() {
    const meta = document.querySelector('meta[name="m3-color-scheme"]');
    return normalizeM3ColorScheme(meta?.getAttribute('content'));
}

export function applyM3ColorScheme(key) {
    document.documentElement.setAttribute('data-m3-scheme', normalizeM3ColorScheme(key));
}

/**
 * Initialize once, before app mount. Idempotent — safe to call from both
 * app.js (pre-mount) and layouts (onMounted verification).
 */
export function initM3ColorScheme() {
    m3ColorScheme.value = readFromMeta();
    applyM3ColorScheme(m3ColorScheme.value);
    initialized = true;
    return m3ColorScheme.value;
}

/**
 * Sync from fresh Inertia props (e.g. after navigation) so the attribute
 * stays consistent when the server value changes without a reload.
 */
export function syncM3ColorSchemeFromProps(props) {
    const value = normalizeM3ColorScheme(props?.auth?.user?.ui_m3_color_scheme);
    if (value !== m3ColorScheme.value) {
        m3ColorScheme.value = value;
        applyM3ColorScheme(value);
    }
}

export function isM3ColorSchemeInitialized() {
    return initialized;
}

export function useM3ColorScheme() {
    return {
        schemeKeys: M3_SCHEME_KEYS,
        defaultScheme: DEFAULT_M3_COLOR_SCHEME,
        scheme: m3ColorScheme,
        getM3SchemePreview,
        applyM3ColorScheme,
        initM3ColorScheme,
        syncM3ColorSchemeFromProps,
    };
}
