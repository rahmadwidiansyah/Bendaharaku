/**
 * markDashboardStale.js — shim backward-compat, delegasi ke stale.js generik.
 * Semua page sekarang pakai '@/utils/stale.js'.
 */
export { markStale, markDashboardStale, hasTransactionInContent } from '@/utils/stale.js'
