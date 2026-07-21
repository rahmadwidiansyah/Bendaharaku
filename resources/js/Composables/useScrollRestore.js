/**
 * useScrollRestore.js
 *
 * Composable untuk menyimpan dan memulihkan posisi scroll halaman.
 *
 * Cara pakai — key eksplisit:
 *   import { useScrollRestore } from '@/Composables/useScrollRestore.js'
 *   useScrollRestore('settings-index')
 *
 * Cara pakai — key otomatis dari pathname (untuk layout yang dipakai banyak halaman):
 *   useScrollRestore()   // key = window.location.pathname, e.g. '/settings/ai/models'
 *
 * Cara kerja:
 *   1. onMounted  — cek sessionStorage, jika ada simpanan scroll untuk key ini,
 *                   restore posisi setelah content ready.
 *   2. router.on('start') — sebelum navigasi pergi, simpan scroll Y saat ini
 *                           ke sessionStorage dengan key yang diberikan.
 *   3. onBeforeUnmount    — hapus listener router saat komponen di-unmount.
 *
 * Scroll target: window (tidak ada overflow container custom di AuthenticatedLayout).
 * Retry: restoration diulang 2x (100ms + 400ms) untuk menangani konten dinamis.
 */

import { onMounted, onBeforeUnmount, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

const SS_PREFIX = 'scroll_restore_'

/**
 * @param {string|null} [key] - Unik per halaman.
 *   null/undefined → gunakan window.location.pathname sebagai key otomatis.
 */
export function useScrollRestore(key = null) {
    let stopListener = null
    let restored = false

    const resolveKey = () => {
        const k = key ?? (typeof window !== 'undefined' ? window.location.pathname : 'default')
        return SS_PREFIX + k
    }

    const getScrollTop = () => window.scrollY

    const setScrollTop = (y) => {
        window.scrollTo({ top: y, behavior: 'instant' })
    }

    const tryRestore = () => {
        if (restored) return
        const saved = sessionStorage.getItem(resolveKey())
        if (saved !== null) {
            setScrollTop(parseInt(saved, 10))
            restored = true
        }
    }

    onMounted(async () => {
        await nextTick()
        // Coba restore setelah 100ms (konten awal)
        setTimeout(tryRestore, 100)
        // Coba lagi setelah 400ms (konten dinamis seperti gambar, v-for)
        setTimeout(tryRestore, 400)

        stopListener = router.on('start', () => {
            sessionStorage.setItem(resolveKey(), String(getScrollTop()))
            restored = false
        })
    })

    onBeforeUnmount(() => {
        if (stopListener) stopListener()
    })
}
