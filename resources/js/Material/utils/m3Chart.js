/**
 * m3Chart.js — tema Chart.js untuk halaman Material 3.
 *
 * Chrome chart (grid, ticks, tooltip) dibaca dari token `--m3-*` dan sadar
 * skema light/dark (kelas `.light` pada <html>). Tooltip memakai pasangan
 * inverse-surface/on-inverse-surface (peran M3 untuk elemen kontras),
 * sesuai design.md §11.2 (satu konfigurasi tooltip untuk semua chart).
 */

export const m3CssVar = (name, fallback) =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback

export const m3IsLight = () => document.documentElement.classList.contains('light')

export const m3ChartFontFamily = () =>
    getComputedStyle(document.body).fontFamily || 'Inter, system-ui, sans-serif'

export const m3ChartChrome = () => {
    const light = m3IsLight()
    return {
        grid: light ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)',
        ticks: m3CssVar('--m3-color-on-surface-variant', '#9CA3AF'),
        labels: m3CssVar('--m3-color-on-surface-variant', '#D1D5DB'),
        tooltipBg: m3CssVar('--m3-color-inverse-surface', light ? '#e3e2e6' : '#2f3033'),
        tooltipTitle: m3CssVar('--m3-color-inverse-on-surface', light ? '#1a1c1e' : '#f0f0f3') + 'b3',
        tooltipBody: m3CssVar('--m3-color-inverse-on-surface', light ? '#1a1c1e' : '#f0f0f3'),
        // Kartu chart memakai M3Card `filled` = surface-container-highest.
        surface: m3CssVar('--m3-color-surface-container-highest', '#e3e2e6'),
        brand: m3CssVar('--m3-color-primary', '#82d5ca'),
    }
}

export const m3TooltipOptions = (chrome, { swatch = false } = {}) => ({
    enabled: true,
    backgroundColor: chrome.tooltipBg,
    titleColor: chrome.tooltipTitle,
    titleFont: { size: 11, weight: 'bold' },
    bodyColor: chrome.tooltipBody,
    bodyFont: { size: 13, weight: 'bold' },
    padding: { x: 12, y: 8 },
    cornerRadius: 8,
    displayColors: swatch,
    boxWidth: 12,
    boxHeight: 12,
    boxPadding: 4,
})
