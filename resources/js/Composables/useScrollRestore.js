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
 *                   restore posisi setelah next tick agar DOM sudah render.
 *   2. router.on('start') — sebelum navigasi pergi, simpan scroll Y saat ini
 *                           ke sessionStorage dengan key yang diberikan.
 *   3. onBeforeUnmount    — hapus listener router saat komponen di-unmount.
 *
 * Scroll target: window (tidak ada overflow container custom di AuthenticatedLayout).
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

    const resolveKey = () => {
        const k = key ?? (typeof window !== 'undefined' ? window.location.pathname : 'default')
        return SS_PREFIX + k
    }

    const getScrollTop = () => window.scrollY

    const setScrollTop = (y) => {
        window.scrollTo({ top: y, behavior: 'instant' })
    }

    onMounted(async () => {
        // Tunggu DOM render selesai sebelum restore
        await nextTick()
        // Delay kecil untuk konten dinamis (v-for, animasi masuk, dll)
        await new Promise(resolve => setTimeout(resolve, 50))

        const saved = sessionStorage.getItem(resolveKey())
        if (saved !== null) {
            setScrollTop(parseInt(saved, 10))
        }

        // Simpan scroll sebelum setiap navigasi pergi dari halaman ini
        stopListener = router.on('start', () => {
            sessionStorage.setItem(resolveKey(), String(getScrollTop()))
        })
    })

    onBeforeUnmount(() => {
        if (stopListener) stopListener()
    })
}
