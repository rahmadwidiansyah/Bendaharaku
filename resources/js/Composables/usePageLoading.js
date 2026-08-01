/**
 * usePageLoading.js
 *
 * Singleton composable — satu instance dipakai seluruh aplikasi.
 *
 * Cara kerja:
 *   1. Listen ke router.on('start')  → set isLoading=true, catat pendingUrl
 *                                       pendingUrl fallback ke window.location.pathname
 *                                       agar skeleton tampil dari halaman MANAPUN
 *   2. Listen ke router.on('finish') → tunggu MIN_MS sejak start, baru set isLoading=false
 *   3. Listen ke router.on('error')  → sama seperti finish
 *
 * currentSkeleton diturunkan dari pendingUrl (URL tujuan, bukan URL asal).
 *
 * Singleton dijaga dengan flag `initialized` agar event listener
 * tidak didaftarkan berkali-kali walau composable di-call di banyak komponen.
 *
 * contentVisible = !isLoading (dipakai layout untuk hide/show konten lama via opacity)
 */

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const MIN_MS = 300 // minimum skeleton display agar tidak berkedip

// ── Singleton state (module-level, tidak di-reset antar komponen) ──
const isLoading      = ref(false)
const pendingUrl     = ref(null)   // URL tujuan yang sedang dimuat
let   startedAt      = 0
let   initialized    = false
let   finishTimeout  = null

// ── URL → nama skeleton ───────────────────────────────────────────
// Urutan penting: pattern lebih spesifik harus di atas
const SKELETON_MAP = [
    { pattern: /\/transactions\/create/,       skeleton: null                   }, // fullscreen, skip
    { pattern: /\/transactions\/\d+\/edit/,    skeleton: null                   }, // fullscreen, skip
    { pattern: /\/transactions/,               skeleton: 'TransactionSkeleton'  },
    { pattern: /^\/$|\/dashboard/,             skeleton: 'DashboardSkeleton'    },
    { pattern: /\/budgeting/,                  skeleton: 'BudgetingSkeleton'    },
    { pattern: /\/wallets/,                    skeleton: 'AssetSkeleton'        },
    { pattern: /\/analytics/,                  skeleton: 'StatisticsSkeleton'   },
    { pattern: /\/categories/,                 skeleton: 'StatisticsSkeleton'   },
    { pattern: /\/loans/,                      skeleton: 'TransactionSkeleton'  },
    { pattern: /\/settings/,                   skeleton: 'SettingsSkeleton'     },
    { pattern: /\/profile/,                    skeleton: 'SettingsSkeleton'     },
]

/**
 * Resolve nama skeleton dari URL pathname.
 * Fallback ke 'DashboardSkeleton' jika tidak ada match.
 */
function resolveSkeletonFromUrl(pathname) {
    if (!pathname) return null
    const match = SKELETON_MAP.find(m => m.pattern.test(pathname))
    // match ditemukan tapi skeleton = null → halaman fullscreen, jangan tampilkan overlay
    if (match && match.skeleton === null) return null
    return match?.skeleton ?? 'DashboardSkeleton'
}

const currentSkeleton = computed(() => resolveSkeletonFromUrl(pendingUrl.value))

// ── Init listener sekali saja ─────────────────────────────────────
function initListeners() {
    if (initialized) return
    initialized = true

    router.on('start', (event) => {
        const visit = event.detail?.visit

        // Abaikan request yang bukan full-page navigation (preserve state + url, misal: search/filter)
        if (visit?.preserveState && visit?.preserveUrl) return

        // Bersihkan timeout sebelumnya jika ada navigasi cepat berurutan
        if (finishTimeout) {
            clearTimeout(finishTimeout)
            finishTimeout = null
        }

        startedAt = Date.now()

        // Gunakan URL tujuan dari visit.url, fallback ke lokasi browser saat ini
        // Ini kunci agar skeleton selalu resolve dengan benar dari halaman MANAPUN
        const destination = visit?.url?.pathname ?? window.location.pathname
        pendingUrl.value  = destination
        isLoading.value   = true
    })

    const finish = () => {
        const elapsed   = Date.now() - startedAt
        const remaining = MIN_MS - elapsed

        if (remaining > 0) {
            finishTimeout = setTimeout(() => {
                isLoading.value  = false
                finishTimeout    = null
            }, remaining)
        } else {
            isLoading.value = false
        }
    }

    router.on('finish', finish)
    router.on('error',  finish)
}

// ── Composable export ─────────────────────────────────────────────
export function usePageLoading() {
    initListeners()

    return {
        isLoading,
        pendingUrl,
        currentSkeleton,
    }
}
