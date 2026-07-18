/**
 * usePageLoading.js
 *
 * Singleton composable — satu instance dipakai seluruh aplikasi.
 *
 * Cara kerja:
 *   1. Listen ke router.on('start')  → set isLoading=true, catat pendingUrl
 *   2. Listen ke router.on('finish') → tunggu MIN_MS sejak start, baru set isLoading=false
 *   3. Listen ke router.on('error')  → sama seperti finish
 *
 * currentSkeleton diturunkan dari pendingUrl:
 *   url pattern → nama skeleton component
 *
 * Singleton dijaga dengan flag `initialized` agar event listener
 * tidak didaftarkan berkali-kali walau composable di-call di banyak komponen.
 */

import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const MIN_MS = 280 // minimum skeleton display agar tidak berkedip

// ── Singleton state (module-level, tidak di-reset antar komponen) ──
const isLoading  = ref(false)
const pendingUrl = ref(null)   // URL yang sedang dimuat
let   startedAt  = 0
let   initialized = false

// ── URL → nama skeleton ───────────────────────────────────────────
const SKELETON_MAP = [
    { pattern: /^\/$|\/dashboard/,            skeleton: 'DashboardSkeleton'    },
    { pattern: /\/transactions\/create/,       skeleton: null                   }, // fullscreen, skip
    { pattern: /\/transactions\/\d+\/edit/,    skeleton: null                   }, // fullscreen, skip
    { pattern: /\/transactions/,               skeleton: 'TransactionSkeleton'  },
    { pattern: /\/wallets/,                    skeleton: 'AssetSkeleton'        },
    { pattern: /\/analytics/,                  skeleton: 'StatisticsSkeleton'   },
    { pattern: /\/categories/,                 skeleton: 'StatisticsSkeleton'   },
    { pattern: /\/loans/,                      skeleton: 'TransactionSkeleton'  },
    { pattern: /\/settings/,                   skeleton: 'SettingsSkeleton'     },
    { pattern: /\/profile/,                    skeleton: 'SettingsSkeleton'     },
]

const currentSkeleton = computed(() => {
    if (!pendingUrl.value) return null
    const match = SKELETON_MAP.find(m => m.pattern.test(pendingUrl.value))
    return match?.skeleton ?? 'DashboardSkeleton'
})

// ── Init listener sekali saja ─────────────────────────────────────
function initListeners() {
    if (initialized) return
    initialized = true

    router.on('start', (event) => {
        // Abaikan request yang bukan full-page navigation (preserve state, dll)
        const visit = event.detail?.visit
        if (visit?.preserveState && visit?.preserveUrl) return

        startedAt        = Date.now()
        pendingUrl.value = visit?.url?.pathname ?? null
        isLoading.value  = true
    })

    const finish = () => {
        const elapsed = Date.now() - startedAt
        const remaining = MIN_MS - elapsed

        if (remaining > 0) {
            setTimeout(() => { isLoading.value = false }, remaining)
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
