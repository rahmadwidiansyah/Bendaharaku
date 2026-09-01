/**
 * utils/format.js
 * ─────────────────────────────────────────────────────────────────
 * Single source of truth untuk semua formatter angka dan tanggal.
 *
 * Usage:
 *   import { formatRupiah, formatCompact, formatDate, formatLocalYMD } from '@/utils/format.js';
 */

const ID_LOCALE = 'id-ID';

/**
 * Format angka ke format Rupiah tanpa simbol "Rp".
 * Contoh: 150000 → "150.000"
 */
export const formatNumber = (n) => {
    if (n === null || n === undefined) return '0';
    return new Intl.NumberFormat(ID_LOCALE).format(n);
};

/**
 * Format angka ke format Rupiah lengkap dengan prefix "Rp ".
 * Contoh: 150000 → "Rp 150.000"
 */
export const formatRupiah = (n) => {
    if (n === null || n === undefined) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat(ID_LOCALE).format(n);
};

/**
 * Format angka besar ke bentuk kompak.
 * Contoh: 1500000 → "1,5 Jt" | 2000000000 → "2 M"
 */
export const formatCompact = (n) => {
    if (n === null || n === undefined) return '0';
    const abs = Math.abs(n);
    if (abs >= 1_000_000_000) return (n / 1_000_000_000).toFixed(1).replace('.0', '') + ' M';
    if (abs >= 1_000_000)     return (n / 1_000_000).toFixed(1).replace('.0', '') + ' Jt';
    if (abs >= 1_000)         return (n / 1_000).toFixed(1).replace('.0', '') + ' Rb';
    return String(n);
};

/**
 * Format string/Date ke tanggal lokal Indonesia.
 * Contoh: "2025-07-17" → "17 Jul 2025"
 */
export const parseDateOnly = (value) => {
    const match = typeof value === 'string' ? value.match(/^(\d{4})-(\d{2})-(\d{2})/) : null;
    return match
        ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
        : null;
};

export const formatDate = (dateString, options = {}) => {
    if (!dateString) return '';
    const defaultOptions = { day: '2-digit', month: 'short', year: 'numeric' };
    const date = parseDateOnly(dateString) ?? new Date(dateString);
    return date.toLocaleDateString(ID_LOCALE, { ...defaultOptions, ...options });
};

export const isTodayDateOnly = (value) => {
    const date = parseDateOnly(value);
    if (!date) return false;
    return formatLocalYMD(date) === formatLocalYMD();
};

export const isFutureDateOnly = (value) => {
    const date = parseDateOnly(value);
    if (!date) return false;
    return formatLocalYMD(date) > formatLocalYMD();
};

/**
 * Kembalikan tanggal hari ini dalam format "YYYY-MM-DD" berdasarkan timezone lokal.
 * Aman dipakai sebagai default value untuk <input type="date">.
 */
export const formatLocalYMD = (date = new Date()) => {
    const year  = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day   = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

/**
 * Format angka ke format mata uang USD untuk estimasi biaya API.
 * Contoh: 0.001234 → "$0.0012"
 */
export const formatUSD = (n, minimumFractionDigits = 4) => {
    if (n === null || n === undefined) return '$0';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits,
    }).format(n);
};
