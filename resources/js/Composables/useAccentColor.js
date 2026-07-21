const STYLE_TAG_ID = 'accent-color-override'

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

export const DEFAULT_ACCENT = 'purple'

const LS_KEY = 'ls_accent_color'
const IS_CUSTOM_PREFIX = 'custom:'

export function isCustomColor(color) {
    return color && color.startsWith(IS_CUSTOM_PREFIX)
}

export function getColorValue(color) {
    if (isCustomColor(color)) return color.replace(IS_CUSTOM_PREFIX, '')
    return color
}

function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
    } : null
}

function rgbToHex(r, g, b) {
    const toHex = (n) => {
        const clamped = Math.max(0, Math.min(255, Math.round(n)))
        return clamped.toString(16).padStart(2, '0')
    }
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`
}

function mixColor(hex, weight) {
    const rgb = hexToRgb(hex)
    if (!rgb) return hex
    if (weight === 0) return hex
    if (weight > 0) {
        const w = Math.min(weight, 1)
        return rgbToHex(
            rgb.r + (255 - rgb.r) * w,
            rgb.g + (255 - rgb.g) * w,
            rgb.b + (255 - rgb.b) * w,
        )
    }
    const w = Math.min(-weight, 1)
    return rgbToHex(
        rgb.r * (1 - w),
        rgb.g * (1 - w),
        rgb.b * (1 - w),
    )
}

function generatePalette(baseHex) {
    const clean = baseHex.replace('#', '')
    if (clean.length === 3) {
        const r = clean[0].repeat(2)
        const g = clean[1].repeat(2)
        const b = clean[2].repeat(2)
        return generatePalette(`#${r}${g}${b}`)
    }
    return {
        300: mixColor(baseHex, 0.55),
        400: mixColor(baseHex, 0.30),
        500: baseHex,
        600: mixColor(baseHex, -0.20),
        700: mixColor(baseHex, -0.40),
    }
}

function resolvePalette(color) {
    if (!color) return ACCENT_PALETTES[DEFAULT_ACCENT]
    if (isCustomColor(color)) {
        const hex = color.replace(IS_CUSTOM_PREFIX, '')
        return generatePalette(hex)
    }
    return ACCENT_PALETTES[color] || ACCENT_PALETTES[DEFAULT_ACCENT]
}

function getBrandColor(palette) {
    return palette[500] || '#a855f7'
}

function hexToRgba(hex, alpha) {
    const rgb = hexToRgb(hex)
    if (!rgb) return `rgba(168, 85, 247, ${alpha})`
    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`
}

function buildOverrideCSS(palette) {
    const vars = Object.entries(palette)
        .map(([shade, hex]) => `  --color-purple-${shade}: ${hex};`)
        .join('\n')

    const brand = getBrandColor(palette)
    const muted = palette[700] || '#7e22ce'
    const border = hexToRgba(brand, 0.3)
    const subtle = hexToRgba(brand, 0.1)

    return `:root {
${vars}
  --color-brand: ${brand};
  --color-brand-muted: ${muted};
  --color-brand-subtle: ${subtle};
  --color-brand-border: ${border};
  --shadow-brand: 0 4px 24px -4px ${hexToRgba(brand, 0.25)};
}`
}

export function applyAccentColor(color) {
    const palette = resolvePalette(color)
    const css = buildOverrideCSS(palette)

    let styleTag = document.getElementById(STYLE_TAG_ID)
    if (!styleTag) {
        styleTag = document.createElement('style')
        styleTag.id = STYLE_TAG_ID
        document.head.appendChild(styleTag)
    }
    styleTag.textContent = css
}

export function saveAccentColor(color) {
    if (!color) return
    localStorage.setItem(LS_KEY, color)
    applyAccentColor(color)
}

export function initAccentColor() {
    const saved = localStorage.getItem(LS_KEY)
    const color = saved || DEFAULT_ACCENT
    applyAccentColor(color)
    return color
}

export function getStoredAccentColor() {
    return localStorage.getItem(LS_KEY) || DEFAULT_ACCENT
}

export function isValidHex(hex) {
    return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(hex)
}

export { ACCENT_PALETTES }
