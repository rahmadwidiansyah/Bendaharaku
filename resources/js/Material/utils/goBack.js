import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * goBack — navigasi kembali ke halaman asal sebuah sub-page/hanyutan dilakukan,
 * alih-alih memakai target hardcoded.
 *
 * - Riwayat > 1: history.back() — kembali ke halaman yang membuka (More →
 *   Search → More, wizard transaksi → kategori create, dsb.). Sesi Inertia
 *   tetap hidup sehingga state wizard maupun scroll halaman asal terjaga.
 * - Riwayat kosong (direct load / deep link): fallback ke URL fallback
 *   (default Dashboard) — perilaku M3 leading-edge navigasi.
 *
 * fallbackHref boleh berupa route hasil route('...').
 */
export function goBack(fallbackHref = route('dashboard')) {
    if (window.history.length > 1) {
        window.history.back()
        return
    }
    router.visit(fallbackHref, { preserveState: false })
}
