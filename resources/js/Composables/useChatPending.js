/**
 * useChatPending.js
 *
 * Global poller untuk pesan bot yang masih diproses di background queue.
 *
 * Modul-level singleton: state & timer hidup di level modul, sehingga polling
 * tetap berjalan walau user pindah halaman. Saat sebuah pesan mencapai status
 * terminal (completed/failed) dan user TIDAK berada di halaman /chat,
 * muncul toast notifikasi (sukses: bot sudah menjawab; gagal: error).
 *
 * Saat user sedang di /chat, poller global diam — halaman chat polling
 * sendiri (useChat.js) agar bubble bisa di-render langsung.
 */

import { ref } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useToast } from './useToast'

const pendingIds = ref([])
/** Waktu mulai tracking per id (id → timestamp) untuk batas timeout */
const pendingSince = new Map()
let timer = null

/** Batas umur pesan pending di poller global — setelahnya dilepas (job sudah 5 menit) */
const MAX_AGE_MS = 10 * 60 * 1000

export function useChatPending() {
    const page = usePage()
    const { t } = useI18n()
    const { showToast } = useToast()

    function trackBotMessage(id) {
        if (!id || pendingIds.value.includes(id)) return
        pendingIds.value.push(id)
        if (!pendingSince.has(id)) pendingSince.set(id, Date.now())
        ensurePolling()
    }

    function untrackBotMessage(id) {
        const idx = pendingIds.value.indexOf(id)
        if (idx !== -1) pendingIds.value.splice(idx, 1)
        pendingSince.delete(id)
    }

    function ensurePolling() {
        if (timer || pendingIds.value.length === 0) return
        timer = setInterval(pollOnce, 2000)
        pollOnce()
    }

    async function pollOnce() {
        // Saat di halaman /chat, biarkan useChat.js yang menangani
        if (page.url.startsWith('/chat')) return

        for (const id of [...pendingIds.value]) {
            // Lewat batas umur → lepas dari tracking, jangan polling selamanya
            if (Date.now() - (pendingSince.get(id) ?? Date.now()) > MAX_AGE_MS) {
                untrackBotMessage(id)
                continue
            }

            try {
                const { data } = await axios.get(route('chat.message.status', { id }))
                if (data.status === 'completed' || data.status === 'failed') {
                    untrackBotMessage(id)
                    if (data.status === 'completed') {
                        showToast(t('chat.replyReady', { bot: page.props.auth?.user?.bot_name ?? 'Ken-Chan' }))
                    } else {
                        showToast(t('chat.replyFailed'), 'error')
                    }
                }
            } catch {
                // Pesan tidak ditemukan / network — biarkan diproses tick berikutnya
            }
        }

        if (pendingIds.value.length === 0 && timer) {
            clearInterval(timer)
            timer = null
        }
    }

    return { trackBotMessage, untrackBotMessage }
}
