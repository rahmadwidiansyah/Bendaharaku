/**
 * stale.js — Generik stale handler untuk semua page.
 *
 * Chat membuat transaksi via axios (di luar Inertia) → semua page yang menampilkan
 * saldo/transaksi/charts jadi stale jika user kembali via bfcache / history.back.
 * Util ini menandai semua page terkait agar saat user kembali, page auto-reload.
 *
 * Dipakai:
 *   markStale()            → dari chat (transaction selesai)
 *   consumeStaleForRoute() → dari layout/page saat mount / pageshow
 */

// Semua page yang terdampak transaksi (Income/Expense/Transfer/Debt/Receivable)
export const STALE_AFFECTED_PAGES = [
    'dashboard',
    'transactions',
    'wallets',
    'analytics',
    'budgeting',
    'loans',
    'categories',
]

const STALE_PREFIX = 'bendaharaku:stale:'
const STALE_BROADCAST = 'bendaharaku:stale'
const STALE_ALL_KEY = 'bendaharaku:stale:all'

export function hasTransactionInContent(content) {
    if (!Array.isArray(content)) return false
    return content.some(c => c.type === 'transaction_card' || c.type === 'summary_card')
}

/**
 * Tandai page-page sebagai stale.
 * Default: semua page terdampak (dipanggil dari chat).
 * Bisa dipanggil spesifik: markStale(['wallets'])
 */
export function markStale(pages = STALE_AFFECTED_PAGES) {
    try {
        const ts = String(Date.now())
        for (const p of pages) {
            sessionStorage.setItem(STALE_PREFIX + p, ts)
            localStorage.setItem(STALE_PREFIX + p, ts)
        }
        // broadcast key untuk layout global
        sessionStorage.setItem(STALE_ALL_KEY, ts)
        localStorage.setItem(STALE_ALL_KEY, ts)
        window.dispatchEvent(new CustomEvent('stale:updated', { detail: { pages } }))
        window.dispatchEvent(new CustomEvent(STALE_BROADCAST, { detail: { pages } }))
        // backward-compat dashboard key
        sessionStorage.setItem('bendaharaku:dashboard-stale', '1')
        localStorage.setItem('bendaharaku:dashboard-stale', '1')
        setTimeout(() => {
            try {
                localStorage.removeItem(STALE_ALL_KEY)
                localStorage.removeItem('bendaharaku:dashboard-stale')
                for (const p of pages) localStorage.removeItem(STALE_PREFIX + p)
            } catch {}
        }, 1500)
    } catch {}
}

// backward compat
export function markDashboardStale() {
    markStale()
}

/**
 * Cek apakah route saat ini stale dan perlu reload.
 * routeName: dari route().current() → 'dashboard', 'wallets.index', dll.
 * Prefix match: 'wallets.index' → key 'wallets'
 */
export function isStaleForRoute(routeName) {
    if (!routeName) return false
    try {
        // cek all
        if (sessionStorage.getItem(STALE_ALL_KEY)) return true
        if (sessionStorage.getItem('bendaharaku:dashboard-stale') && routeName.startsWith('dashboard')) return true
        for (const p of STALE_AFFECTED_PAGES) {
            if (sessionStorage.getItem(STALE_PREFIX + p) && routeName.startsWith(p)) return true
        }
    } catch {}
    return false
}

export function consumeStaleForRoute(routeName) {
    if (!routeName) return false
    let consumed = false
    try {
        if (sessionStorage.getItem(STALE_ALL_KEY)) {
            sessionStorage.removeItem(STALE_ALL_KEY)
            consumed = true
        }
        if (sessionStorage.getItem('bendaharaku:dashboard-stale')) {
            sessionStorage.removeItem('bendaharaku:dashboard-stale')
            if (routeName.startsWith('dashboard')) consumed = true
        }
        for (const p of STALE_AFFECTED_PAGES) {
            const k = STALE_PREFIX + p
            if (sessionStorage.getItem(k)) {
                sessionStorage.removeItem(k)
                if (routeName.startsWith(p)) consumed = true
                // juga hapus all agar tidak double reload di page lain
                // tapi jangan hapus prefix lain — biarkan page lain tetap stale sampai dikunjungi
            }
        }
        // jika all key terbaca, anggap consumed untuk route apapun yang affected
        if (consumed) return true
        // fallback: cek generic
        return isStaleForRoute(routeName)
    } catch { return false }
}

export function clearAllStale() {
    try {
        sessionStorage.removeItem(STALE_ALL_KEY)
        sessionStorage.removeItem('bendaharaku:dashboard-stale')
        for (const p of STALE_AFFECTED_PAGES) sessionStorage.removeItem(STALE_PREFIX + p)
    } catch {}
}
