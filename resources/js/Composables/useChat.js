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

    // ── Scroll ────────────────────────────────────────────────────

    /**
     * Scroll ke pesan terbawah.
     * @param {boolean} smooth - Gunakan smooth scroll (default: false untuk initial load)
     */
    async function scrollToBottom(smooth = false) {
        await nextTick()
        if (chatAreaRef.value) {
            chatAreaRef.value.scrollTo({
                top: chatAreaRef.value.scrollHeight,
                behavior: smooth ? 'smooth' : 'instant',
            })
        }
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
        await scrollToBottom(true)

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
            await scrollToBottom(true)
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

        // Actions
        sendMessage,
        loadMore,
        scrollToBottom,
    }
}
