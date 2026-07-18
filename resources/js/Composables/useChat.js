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
 * - Unread count: berapa pesan baru masuk saat user sedang scroll ke atas
 * - State: isLoading, isTyping, showJumpBtn, unreadCount
 */

import { ref, nextTick } from 'vue'
import axios from 'axios'

export function useChat(initialMessages = [], initialConversationId = null, initialHasMore = false) {
    // ── State ─────────────────────────────────────────────────────
    const messages        = ref([...initialMessages])
    const conversationId  = ref(initialConversationId)
    const isLoading       = ref(false)
    const isTyping        = ref(false)
    const hasMore         = ref(initialHasMore)
    const isLoadingMore   = ref(false)
    const error           = ref(null)
    const chatAreaRef     = ref(null)
    const isAtBottom      = ref(true)
    const showJumpBtn     = ref(false)
    /** Jumlah pesan baru dari bot yang masuk saat user sedang scroll ke atas */
    const unreadCount     = ref(0)

    // ── Scroll ────────────────────────────────────────────────────

    async function scrollToBottom(smooth = false, force = false) {
        await nextTick()
        const el = chatAreaRef.value
        if (!el) return
        if (force || isAtBottom.value) {
            el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'instant' })
        }
    }

    async function jumpToLatest() {
        await nextTick()
        const el = chatAreaRef.value
        if (!el) return
        el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' })
        isAtBottom.value  = true
        showJumpBtn.value = false
        unreadCount.value = 0
    }

    function onScrollUpdate(scrollTop, scrollHeight, clientHeight) {
        const distFromBottom = scrollHeight - scrollTop - clientHeight
        isAtBottom.value  = distFromBottom < 80
        showJumpBtn.value = distFromBottom > 200
        // Reset unread saat user sudah kembali ke bawah
        if (isAtBottom.value) {
            unreadCount.value = 0
        }
    }

    // ── Send message ──────────────────────────────────────────────

    async function sendMessage(text) {
        if (!text.trim() || isLoading.value) return

        error.value = null

        const userMsg = buildLocalMessage('user', text)
        messages.value.push(userMsg)
        await scrollToBottom(true, true)

        isLoading.value = true
        isTyping.value  = true

        try {
            const { data } = await axios.post(route('chat.message'), {
                message:         text,
                conversation_id: conversationId.value,
            })

            if (data.conversation_id) {
                conversationId.value = data.conversation_id
            }

            const idx = messages.value.findIndex((m) => m._localId === userMsg._localId)
            if (idx !== -1) {
                messages.value[idx] = normalizeMessage(data.user_message)
            }

            messages.value.push(normalizeMessage(data.bot_message))

            // Increment unread jika user tidak di bawah
            if (!isAtBottom.value) {
                unreadCount.value += 1
            }

        } catch (err) {
            error.value = 'Gagal mengirim pesan. Periksa koneksi internet.'
            messages.value.push(buildErrorMessage(err))

        } finally {
            isLoading.value = false
            isTyping.value  = false
            await scrollToBottom(true)
        }
    }

    // ── Load history ──────────────────────────────────────────────

    async function loadMore() {
        if (isLoadingMore.value || !hasMore.value) return

        isLoadingMore.value = true

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
                messages.value = [
                    ...data.messages.map(normalizeMessage),
                    ...messages.value,
                ]
            }

            hasMore.value = data.has_more ?? false

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

    function buildErrorMessage(err) {
        const message = err?.response?.data?.message
            ?? 'Gagal terhubung ke server. Coba lagi.'
        return {
            id:         null,
            _localId:   `error_${Date.now()}`,
            role:       'assistant',
            content:    [{ type: 'error', message, severity: 'error' }],
            metadata:   { error: true },
            created_at: new Date().toISOString(),
        }
    }

    async function retryLastMessage() {
        const userMessages = messages.value.filter(m => m.role === 'user')
        if (userMessages.length === 0) return
        const text = userMessages[userMessages.length - 1]?.content?.[0]?.text ?? ''
        if (!text.trim()) return

        const last = messages.value[messages.value.length - 1]
        if (last?.role === 'assistant' && last?.metadata?.error) {
            messages.value.pop()
        }
        await sendMessage(text)
    }

    async function regenerateMessage(botMessage) {
        const idx = messages.value.findIndex(m =>
            (m.id && m.id === botMessage.id) ||
            (m._localId && m._localId === botMessage._localId)
        )
        if (idx === -1) return

        let userText = ''
        for (let i = idx - 1; i >= 0; i--) {
            if (messages.value[i].role === 'user') {
                userText = messages.value[i].content?.[0]?.text ?? ''
                break
            }
        }
        if (!userText.trim()) return

        messages.value.splice(idx)
        await sendMessage(userText)
    }

    return {
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
        unreadCount,
        sendMessage,
        retryLastMessage,
        regenerateMessage,
        loadMore,
        scrollToBottom,
        jumpToLatest,
        onScrollUpdate,
    }
}
