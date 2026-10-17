/**
 * useM3ColorScheme — Skema warna preset untuk Material Design 3 theme.
 *
 * Setiap preset menyediakan primary / secondary / tertiary yang berbeda
 * dari default theme accent (`--color-brand`). Saat M3 UI aktif (`.ui-m3`),
 * variabel `--m3-color-primary`, `--m3-color-secondary`, `--m3-color-tertiary`
 * beserta container dan on-* varian ditimpa oleh preset yang dipilih.
 *
 * Persistensi: localStorage (klien-side visual preference).
 */
const STYLE_TAG_ID = 'm3-color-scheme-override'
const LS_KEY = 'ls_m3_color_scheme'

const M3_COLOR_SCHEMES = {
  ocean: {
    label: 'Ocean',
    desc: 'Biru sejuk, kuat dan terpercaya',
    primary: '#0284c7',
    secondary: '#0ea5e9',
    tertiary: '#22d3ee',
  },
  forest: {
    label: 'Hutan',
    desc: 'Hijau tumbuh, segar dan sehat',
    primary: '#15803d',
    secondary: '#22c55e',
    tertiary: '#86efac',
  },
  sunset: {
    label: 'Sunset',
    desc: 'Oranye hangat, energik dan optimis',
    primary: '#ea580c',
    secondary: '#f97316',
    tertiary: '#fb923c',
  },
  midnight: {
    label: 'Midnight',
    desc: 'Ungu malam, premium dan mendalam',
    primary: '#4338ca',
    secondary: '#6366f1',
    tertiary: '#a5b4fc',
  },
  rose: {
    label: 'Rose',
    desc: 'Merah muda lembut, empati dan hangat',
    primary: '#be185d',
    secondary: '#ec4899',
    tertiary: '#f9a8d4',
  },
}

const DEFAULT_SCHEME = 'ocean'

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

function luminance(hex) {
  const rgb = hexToRgb(hex)
  if (!rgb) return 0
  const sRGB = [rgb.r, rgb.g, rgb.b].map(c => {
    const v = c / 255
    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4)
  })
  return 0.2126 * sRGB[0] + 0.7152 * sRGB[1] + 0.0722 * sRGB[2]
}

function contrastRatio(hex1, hex2) {
  const l1 = luminance(hex1)
  const l2 = luminance(hex2)
  const lighter = Math.max(l1, l2)
  const darker = Math.min(l1, l2)
  return (lighter + 0.05) / (darker + 0.05)
}

function getContrastColor(hex) {
  return contrastRatio(hex, '#ffffff') >= contrastRatio(hex, '#000000')
    ? '#ffffff'
    : '#000000'
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

function buildSchemeColors(scheme) {
  const { primary, secondary, tertiary } = scheme

  const onPrimary = getContrastColor(primary)
  const onSecondary = getContrastColor(secondary)
  const onTertiary = getContrastColor(tertiary)

  // Container: tint ringan (dark scheme ~15% opacity pada surface)
  const primaryContainer = mixColor(primary, 0.15)
  const secondaryContainer = mixColor(secondary, 0.15)
  const tertiaryContainer = mixColor(tertiary, 0.15)

  // On-container: warna asli (readable against container)
  const onPrimaryContainer = primary
  const onSecondaryContainer = secondary
  const onTertiaryContainer = tertiary

  return {
    '--m3-color-primary': primary,
    '--m3-color-on-primary': onPrimary,
    '--m3-color-primary-container': primaryContainer,
    '--m3-color-on-primary-container': onPrimaryContainer,
    '--m3-color-secondary': secondary,
    '--m3-color-on-secondary': onSecondary,
    '--m3-color-secondary-container': secondaryContainer,
    '--m3-color-on-secondary-container': onSecondaryContainer,
    '--m3-color-tertiary': tertiary,
    '--m3-color-on-tertiary': onTertiary,
    '--m3-color-tertiary-container': tertiaryContainer,
    '--m3-color-on-tertiary-container': onTertiaryContainer,
  }
}

function buildOverrideCSS(schemeKey) {
  const scheme = M3_COLOR_SCHEMES[schemeKey]
  if (!scheme) return ''
  const vars = buildSchemeColors(scheme)
  const entries = Object.entries(vars)
    .map(([name, value]) => `  ${name}: ${value};`)
    .join('\n')

  return `.ui-m3 {\n${entries}\n}`
}

let currentScheme = null

export function getM3ColorSchemes() {
  return M3_COLOR_SCHEMES
}

export function getM3DefaultScheme() {
  return DEFAULT_SCHEME
}

export function getM3CurrentScheme() {
  return currentScheme
}

export function applyM3ColorScheme(schemeKey) {
  const scheme = M3_COLOR_SCHEMES[schemeKey]
  if (!scheme) return

  const css = buildOverrideCSS(schemeKey)

  let styleTag = document.getElementById(STYLE_TAG_ID)
  if (!styleTag) {
    styleTag = document.createElement('style')
    styleTag.id = STYLE_TAG_ID
    document.head.appendChild(styleTag)
  }
  styleTag.textContent = css

  currentScheme = schemeKey
}

export function saveM3ColorScheme(schemeKey) {
  if (!schemeKey) return
  localStorage.setItem(LS_KEY, schemeKey)
  applyM3ColorScheme(schemeKey)
}

export function initM3ColorScheme() {
  const saved = localStorage.getItem(LS_KEY)
  const schemeKey = saved || DEFAULT_SCHEME
  applyM3ColorScheme(schemeKey)
  return schemeKey
}

export function useM3ColorScheme() {
  return {
    schemes: M3_COLOR_SCHEMES,
    defaultScheme: DEFAULT_SCHEME,
    currentScheme: () => currentScheme,
    applyM3ColorScheme,
    saveM3ColorScheme,
    initM3ColorScheme,
  }
}
