/**
 * useAccentColor.js
 *
 * Composable untuk mengelola accent color global aplikasi.
 *
 * Cara kerja:
 * - Inject/update sebuah <style> tag di <head> yang mengoverride seluruh
 *   Tailwind purple-* utility classes ke warna accent yang dipilih user.
 * - Setiap accent warna memiliki palette lengkap (300–700) agar semua state
 *   (hover, active, ring, dll) tampil konsisten.
 * - Dipanggil dari app.js saat startup (init dari localStorage/default),
 *   dan dari Appearance.vue saat user mengubah warna (preview langsung).
 *
 * Kenapa pakai CSS override dan bukan CSS variable saja?
 * - Seluruh komponen menggunakan hardcode Tailwind classes seperti
 *   `bg-purple-600`, `text-purple-400`, `border-purple-500`, dll.
 * - Tailwind v4 generate class dari @theme token (--color-brand), tapi
 *   class `bg-purple-*` bukan custom token — mereka static Tailwind classes.
 * - Satu-satunya cara mengubahnya tanpa refactor 69+ file adalah dengan
 *   mengoverride CSS custom property `--color-purple-*` yang digunakan
 *   Tailwind secara internal untuk tiap shade.
 */

const STYLE_TAG_ID = 'accent-color-override'

/**
 * Definisi palette tiap warna accent.
 * Key = nama warna, Value = object shade → hex.
 * Shade yang disediakan harus mencakup semua yang dipakai di komponen.
 */
const ACCENT_PALETTES = {
    purple: {
        300: '#d8b4fe',
        400: '#c084fc',
        500: '#a855f7',
        600: '#9333ea',
        700: '#7e22ce',
    },
    blue: {
        300: '#93c5fd',
        400: '#60a5fa',
        500: '#3b82f6',
        600: '#2563eb',
        700: '#1d4ed8',
    },
    green: {
        300: '#6ee7b7',
        400: '#34d399',
        500: '#10b981',
        600: '#059669',
        700: '#047857',
    },
    orange: {
        300: '#fdba74',
        400: '#fb923c',
        500: '#f97316',
        600: '#ea580c',
        700: '#c2410c',
    },
    red: {
        300: '#fca5a5',
        400: '#f87171',
        500: '#ef4444',
        600: '#dc2626',
        700: '#b91c1c',
    },
    pink: {
        300: '#f9a8d4',
        400: '#f472b6',
        500: '#ec4899',
        600: '#db2777',
        700: '#be185d',
    },
}

/** Default accent jika localStorage kosong */
export const DEFAULT_ACCENT = 'purple'

/** Key localStorage */
const LS_KEY = 'ls_accent_color'

/**
 * Generate CSS string yang mengoverride Tailwind purple-* custom properties.
 *
 * Tailwind v4 menggunakan CSS custom properties dengan format:
 *   --color-purple-{shade}: {hex};
 * pada :root. Kita override di :root juga agar specificity sama.
 */
function buildOverrideCSS(color) {
    const palette = ACCENT_PALETTES[color]
    if (!palette) return ''

    const vars = Object.entries(palette)
        .map(([shade, hex]) => `  --color-purple-${shade}: ${hex};`)
        .join('\n')

    return `:root {\n${vars}\n}`
}

/**
 * Terapkan accent color ke DOM dengan mengupdate <style> tag override.
 * Aman dipanggil berulang kali (idempotent).
 *
 * @param {string} color - Nama warna dari ACCENT_PALETTES
 */
export function applyAccentColor(color) {
    const accentName = ACCENT_PALETTES[color] ? color : DEFAULT_ACCENT
    const css = buildOverrideCSS(accentName)

    let styleTag = document.getElementById(STYLE_TAG_ID)
    if (!styleTag) {
        styleTag = document.createElement('style')
        styleTag.id = STYLE_TAG_ID
        document.head.appendChild(styleTag)
    }
    styleTag.textContent = css
}

/**
 * Simpan pilihan accent ke localStorage dan terapkan ke DOM.
 *
 * @param {string} color
 */
export function saveAccentColor(color) {
    const accentName = ACCENT_PALETTES[color] ? color : DEFAULT_ACCENT
    localStorage.setItem(LS_KEY, accentName)
    applyAccentColor(accentName)
}

/**
 * Init accent color saat app startup.
 * Baca dari localStorage, fallback ke DEFAULT_ACCENT.
 */
export function initAccentColor() {
    const saved = localStorage.getItem(LS_KEY)
    const color = (saved && ACCENT_PALETTES[saved]) ? saved : DEFAULT_ACCENT
    applyAccentColor(color)
    return color
}

/**
 * Baca accent color yang tersimpan di localStorage.
 * @returns {string}
 */
export function getStoredAccentColor() {
    const saved = localStorage.getItem(LS_KEY)
    return (saved && ACCENT_PALETTES[saved]) ? saved : DEFAULT_ACCENT
}

export { ACCENT_PALETTES }
