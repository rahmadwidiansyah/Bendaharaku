/**
 * useChat.js
 *
 * Core composable untuk Web Chat.
 *
 * Tanggung jawab:
 * - Menyimpan daftar messages (reaktif)
 * - Kirim pesan ke backend (POST /chat/message)
 * - Load riwayat dari initial props Inertia
 * - Load lebih banyak pesan (pagination ke belakang)
 * - Auto-scroll ke bawah
 * - State: isLoading, isTyping
 *
 * Arsitektur:
 *   Pages/Chat/Index.vue
 *     → useChat()           ← state + API calls
 *     → useChatCommands()   ← command list + sheet
 *     → ChatArea.vue        ← render messages
 *     → ChatComposer.vue    ← input + send
 */

import { ref, nextTick } from 'vue'
import axios from 'axios'

export function useChat(initialMessages = [], initialConversationId = null) {
    // ── State ─────────────────────────────────────────────────────
    const messages        = ref([...initialMessages])
    const conversationId  = ref(initialConversationId)
    const isLoading       = ref(false)  // waiting for bot response
    const isTyping        = ref(false)  // typing indicator visible
    const hasMore         = ref(true)   // ada riwayat lebih lama di server
    const isLoadingMore   = ref(false)  // sedang load riwayat lama
    const error           = ref(null)
    const chatAreaRef     = ref(null)   // ref ke ChatArea DOM element
    const isAtBottom      = ref(true)   // apakah user sedang di bawah
    const showJumpBtn     = ref(false)  // tampilkan tombol jump-to-latest

    // ── Scroll ────────────────────────────────────────────────────

    /**
     * Scroll ke pesan terbawah.
     * Hanya scroll jika user memang di bawah, kecuali force=true.
     * @param {boolean} smooth - Gunakan smooth scroll (default: false untuk initial load)
     * @param {boolean} force  - Paksa scroll meski user tidak di bawah
     */
    async function scrollToBottom(smooth = false, force = false) {
        await nextTick()
        const el = chatAreaRef.value
        if (!el) return
        if (force || isAtBottom.value) {
            el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'instant' })
        }
    }

    /**
     * Paksa scroll ke bawah selalu (untuk tombol jump-to-latest).
     */
    async function jumpToLatest() {
        await nextTick()
        const el = chatAreaRef.value
        if (!el) return
        el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' })
        isAtBottom.value = true
        showJumpBtn.value = false
    }

    /**
     * Dipanggil dari ChatArea saat scroll event untuk update state posisi scroll.
     * @param {number} scrollTop
     * @param {number} scrollHeight
     * @param {number} clientHeight
     */
    function onScrollUpdate(scrollTop, scrollHeight, clientHeight) {
        const distFromBottom = scrollHeight - scrollTop - clientHeight
        isAtBottom.value = distFromBottom < 80
        showJumpBtn.value = distFromBottom > 200
    }

    // ── Send message ──────────────────────────────────────────────

    /**
     * Kirim pesan user ke backend dan tambahkan respons bot ke messages.
     * @param {string} text - Teks pesan dari user
     */
    async function sendMessage(text) {
        if (!text.trim() || isLoading.value) return

        error.value = null

        // 1. Tambah pesan user ke UI secara optimistis
        const userMsg = buildLocalMessage('user', text)
        messages.value.push(userMsg)
        await scrollToBottom(true, true) // force=true karena user baru kirim

        // 2. Tampilkan typing indicator
        isLoading.value = true
        isTyping.value  = true

        try {
            const { data } = await axios.post(route('chat.message'), {
                message:         text,
                conversation_id: conversationId.value,
            })

            // Update conversation_id jika baru dibuat
            if (data.conversation_id) {
                conversationId.value = data.conversation_id
            }

            // Ganti pesan user optimistis dengan data dari server (dapat real ID)
            const idx = messages.value.findIndex((m) => m._localId === userMsg._localId)
            if (idx !== -1) {
                messages.value[idx] = normalizeMessage(data.user_message)
            }

            // Tambah respons bot
            messages.value.push(normalizeMessage(data.bot_message))

        } catch (err) {
            error.value = 'Gagal mengirim pesan. Periksa koneksi internet.'

            // Tambah error bubble dari bot
            messages.value.push(buildErrorMessage(err))

        } finally {
            isLoading.value = false
            isTyping.value  = false
            await scrollToBottom(true) // tanpa force — hanya scroll jika user di bawah
        }
    }

    // ── Load history (pagination) ─────────────────────────────────

    /**
     * Load pesan lebih lama (scroll ke atas → trigger ini).
     */
    async function loadMore() {
        if (isLoadingMore.value || !hasMore.value) return

        isLoadingMore.value = true

        // Simpan scroll position agar tidak loncat setelah prepend
        const container = chatAreaRef.value
        const prevScrollHeight = container?.scrollHeight ?? 0

        try {
            const oldestId = messages.value[0]?.id ?? null

            const { data } = await axios.get(route('chat.history'), {
                params: {
                    conversation_id: conversationId.value,
                    before:          oldestId,
                    limit:           20,
                },
            })

            if (data.messages.length > 0) {
                // Prepend pesan lama ke depan array
                messages.value = [
                    ...data.messages.map(normalizeMessage),
                    ...messages.value,
                ]
            }

            hasMore.value = data.has_more ?? false

            // Restore scroll position agar user tidak kehilangan posisi baca
            await nextTick()
            if (container) {
                container.scrollTop = container.scrollHeight - prevScrollHeight
            }

        } catch (err) {
            console.error('useChat: loadMore error', err)
        } finally {
            isLoadingMore.value = false
        }
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Normalisasi format pesan dari server ke format lokal yang konsisten.
     */
    function normalizeMessage(msg) {
        return {
            id:         msg.id,
            _localId:   msg._localId ?? null,
            role:       msg.role,
            content:    msg.content ?? [],
            metadata:   msg.metadata ?? {},
            created_at: msg.created_at,
        }
    }

    /**
     * Buat pesan lokal (optimistic, sebelum server respond).
     */
    function buildLocalMessage(role, text) {
        return {
            id:         null,
            _localId:   `local_${Date.now()}_${Math.random()}`,
            role,
            content:    [{ type: 'text', text }],
            metadata:   {},
            created_at: new Date().toISOString(),
        }
    }

    /**
     * Buat error bubble dari bot saat request gagal.
     */
    function buildErrorMessage(err) {
        const message = err?.response?.data?.message
            ?? 'Gagal terhubung ke server. Coba lagi.'

        return {
            id:         null,
            _localId:   `error_${Date.now()}`,
            role:       'assistant',
            content:    [{
                type:     'error',
                message,
                severity: 'error',
            }],
            metadata:   { error: true },
            created_at: new Date().toISOString(),
        }
    }

    /**
     * Kirim ulang pesan user terakhir.
     * Hapus error bubble bot terakhir jika ada, lalu kirim ulang.
     */
    async function retryLastMessage() {
        // Cari teks pesan user terakhir
        const userMessages = messages.value.filter(m => m.role === 'user')
        if (userMessages.length === 0) return
        const lastUserMsg = userMessages[userMessages.length - 1]
        const text = lastUserMsg?.content?.[0]?.text ?? ''
        if (!text.trim()) return

        // Hapus error bubble bot paling akhir jika memang error
        const last = messages.value[messages.value.length - 1]
        if (last?.role === 'assistant' && last?.metadata?.error) {
            messages.value.pop()
        }

        await sendMessage(text)
    }

    /**
     * Generate ulang respons bot untuk pesan user sebelum bot message tertentu.
     * @param {Object} botMessage - Pesan bot yang ingin di-regenerate
     */
    async function regenerateMessage(botMessage) {
        // Cari index bot message ini
        const idx = messages.value.findIndex(m =>
            (m.id && m.id === botMessage.id) ||
            (m._localId && m._localId === botMessage._localId)
        )
        if (idx === -1) return

        // Cari pesan user sebelum bot message ini
        let userText = ''
        for (let i = idx - 1; i >= 0; i--) {
            if (messages.value[i].role === 'user') {
                userText = messages.value[i].content?.[0]?.text ?? ''
                break
            }
        }
        if (!userText.trim()) return

        // Hapus bot message yang di-regenerate (dan semua setelahnya jika ada)
        messages.value.splice(idx)

        await sendMessage(userText)
    }

    return {
        // State
        messages,
        conversationId,
        isLoading,
        isTyping,
        hasMore,
        isLoadingMore,
        error,
        chatAreaRef,
        isAtBottom,
        showJumpBtn,

        // Actions
        sendMessage,
        retryLastMessage,
        regenerateMessage,
        loadMore,
        scrollToBottom,
        jumpToLatest,
        onScrollUpdate,
    }
}
