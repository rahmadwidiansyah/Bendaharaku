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

import { ref, nextTick, onUnmounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useChatPending } from './useChatPending'
import { markStale, hasTransactionInContent } from '@/utils/stale.js'

export function useChat(initialMessages = [], initialConversationId = null, initialHasMore = false) {
    const { t } = useI18n()
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

    /** Pesan bot yang masih diproses di background queue (botId → interval) */
    const pendingTimers   = new Map()
    /** Waktu mulai polling per pesan (botId → timestamp) untuk batas timeout */
    const pendingStartedAt = new Map()
    const { trackBotMessage, untrackBotMessage } = useChatPending()

    /** Batas maksimal polling pesan bot — 90 detik cukup untuk normal AI response */
    const POLL_MAX_MS = 90 * 1000

    // ── Pending bot message (async queue) ─────────────────────────

    function isPendingMessage(msg) {
        return msg?.role === 'assistant' && (msg.status === 'pending' || msg.status === 'processing')
    }

    function hasPendingMessages() {
        return pendingTimers.size > 0
    }

    function stopPollingBotMessage(botId) {
        const timer = pendingTimers.get(botId)
        if (timer) clearInterval(timer)
        pendingTimers.delete(botId)
        pendingStartedAt.delete(botId)
    }

    function pollBotMessage(botId) {
        if (!botId || pendingTimers.has(botId)) return
        if (!pendingStartedAt.has(botId)) pendingStartedAt.set(botId, Date.now())

        const tick = async () => {
            // Timeout: polling berhenti & bubble ditandai gagal supaya tidak muter terus
            if (Date.now() - (pendingStartedAt.get(botId) ?? Date.now()) > POLL_MAX_MS) {
                stopPollingBotMessage(botId)
                untrackBotMessage(botId)

                const idx = messages.value.findIndex((m) => m.id === botId)
                if (idx !== -1) {
                    messages.value[idx] = { 
                        ...messages.value[idx], 
                        status: 'failed',
                        content: [{ type: 'error', message: t('chat.timeout'), severity: 'error' }]
                    }
                }
                error.value = t('chat.timeout')
                if (!hasPendingMessages()) {
                    isLoading.value = false
                    isTyping.value = false
                }
                return
            }

            try {
                const { data } = await axios.get(route('chat.message.status', { id: botId }))

                if (data.status === 'completed' || data.status === 'failed') {
                    stopPollingBotMessage(botId)
                    untrackBotMessage(botId)

                    const idx = messages.value.findIndex((m) => m.id === botId)
                    if (idx !== -1) {
                        messages.value[idx] = normalizeMessage(data.bot_message ?? messages.value[idx])
                    }

                    // WA-style: update user bubble status juga biar tidak stuck "Memproses" meski sukses
                    const botMeta = data.bot_message?.metadata ?? {}
                    const evidenceUuid = botMeta.evidence_uuid || botMeta.evidenceUuid
                    if (evidenceUuid) {
                        const newStatus = data.status === 'completed' ? 'READY' : data.status === 'failed' ? 'FAILED' : 'PROCESSING'
                        updateEvidenceStatus(evidenceUuid, newStatus)
                    }

                    // Semua page finansial stale jika ada kartu transaksi → tandai untuk auto-reload saat kembali
                    if (data.status === 'completed' && data.bot_message?.content && hasTransactionInContent(data.bot_message.content)) {
                        markStale()
                    }

                    if (!isAtBottom.value) {
                        unreadCount.value += 1
                    }

                    if (data.status === 'failed' && data.error_message) {
                        error.value = data.error_message
                    }

                    if (!hasPendingMessages()) {
                        isLoading.value = false
                        isTyping.value = false
                        await scrollToBottom(true)
                    }
                }
            } catch {
                // Pesan tidak ditemukan / network — hentikan polling
                stopPollingBotMessage(botId)
                untrackBotMessage(botId)
                if (!hasPendingMessages()) {
                    isLoading.value = false
                    isTyping.value = false
                }
            }
        }

        pendingTimers.set(botId, setInterval(tick, 2000))
        tick()
    }

    /** Resume polling untuk pesan pending dari riwayat (misal setelah kembali ke halaman) */
    function resumePending() {
        for (const m of messages.value) {
            if (isPendingMessage(m)) {
                trackBotMessage(m.id)
                pollBotMessage(m.id)
            }
        }
    }

    onUnmounted(() => {
        for (const botId of [...pendingTimers.keys()]) {
            stopPollingBotMessage(botId)
        }
    })

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

            // Selalu update conversationId dari response — baik sukses maupun error.
            // Ini kritis agar frontend tidak kehilangan conversation_id saat AI error,
            // sehingga history tetap ter-load saat user kembali ke halaman chat.
            if (data.conversation_id) {
                conversationId.value = data.conversation_id
            }

            // Replace optimistic user message dengan data dari server (dapat id asli)
            if (data.user_message) {
                const idx = messages.value.findIndex((m) => m._localId === userMsg._localId)
                if (idx !== -1) {
                    messages.value[idx] = normalizeMessage(data.user_message)
                }
            }

            if (data.bot_message) {
                const bot = normalizeMessage(data.bot_message)
                messages.value.push(bot)

                // Jika bot langsung completed dengan kartu transaksi (non-pending) → semua page stale
                if (!isPendingMessage(bot) && hasTransactionInContent(bot.content)) {
                    markStale()
                }

                // Proses AI berjalan di background queue → polling status
                if (isPendingMessage(bot)) {
                    trackBotMessage(bot.id)
                    pollBotMessage(bot.id)
                } else if (!isAtBottom.value) {
                    unreadCount.value += 1
                }
            }

        } catch (err) {
            // HTTP error (network down, 500 tanpa body, dsb)
            // Coba ambil conversation_id dari response error jika ada
            const errData = err?.response?.data
            if (errData?.conversation_id) {
                conversationId.value = errData.conversation_id
            }

            // Jika server return bot_message dalam error response, gunakan itu
            if (errData?.bot_message) {
                const idx = messages.value.findIndex((m) => m._localId === userMsg._localId)
                if (idx !== -1) {
                    messages.value[idx] = normalizeMessage(errData.user_message ?? userMsg)
                }
                messages.value.push(normalizeMessage(errData.bot_message))
            } else {
                // Fallback: tampilkan error bubble lokal
                error.value = t('chat.error.sendFailed')
                messages.value.push(buildErrorMessage(err))
            }
        } finally {
            isLoading.value = false
            if (!hasPendingMessages()) {
                isTyping.value = false
            }
            await scrollToBottom(true)
        }
    }

    // ── Send evidence (image upload) ──────────────────────────────────

    async function sendEvidenceMessage(evidenceUuid, localPreviewUrl, text = '') {
        if (isLoading.value) return

        error.value = null

        // Push user bubble dengan image preview secara optimistis
        const userMsg = {
            id:         null,
            _localId:   `local_${Date.now()}_${Math.random()}`,
            role:       'user',
            content:    [
                { type: 'image', localPreviewUrl, evidenceUuid, uploading: false },
                ...(text.trim() ? [{ type: 'text', text: text.trim() }] : []),
            ],
            metadata:   { evidence_uuid: evidenceUuid },
            created_at: new Date().toISOString(),
        }
        messages.value.push(userMsg)
        await scrollToBottom(true, true)

        isLoading.value = true
        isTyping.value  = true

        try {
            const { data } = await axios.post(route('chat.message'), {
                message:         text.trim() || '[Evidence]',
                conversation_id: conversationId.value,
                evidence_uuid:   evidenceUuid,
            })

            if (data.conversation_id) {
                conversationId.value = data.conversation_id
            }

            // Replace optimistic user bubble dengan server data
            if (data.user_message) {
                const idx = messages.value.findIndex((m) => m._localId === userMsg._localId)
                if (idx !== -1) {
                    messages.value[idx] = normalizeMessage(data.user_message)
                }
            }

            if (data.bot_message) {
                const bot = normalizeMessage(data.bot_message)
                messages.value.push(bot)

                if (!isPendingMessage(bot) && hasTransactionInContent(bot.content)) {
                    markStale()
                }

                if (isPendingMessage(bot)) {
                    trackBotMessage(bot.id)
                    pollBotMessage(bot.id)
                } else if (!isAtBottom.value) {
                    unreadCount.value += 1
                }
            }

        } catch (err) {
            const errData = err?.response?.data
            if (errData?.conversation_id) {
                conversationId.value = errData.conversation_id
            }

            if (errData?.bot_message) {
                const idx = messages.value.findIndex((m) => m._localId === userMsg._localId)
                if (idx !== -1) {
                    messages.value[idx] = normalizeMessage(errData.user_message ?? userMsg)
                }
                messages.value.push(normalizeMessage(errData.bot_message))
            } else {
                error.value = t('chat.error.evidenceFailed')
                messages.value.push(buildErrorMessage(err))
            }
        } finally {
            isLoading.value = false
            if (!hasPendingMessages()) {
                isTyping.value = false
            }
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
            status:     msg.status ?? 'completed',
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
            ?? t('chat.error.connection')
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

    /**
     * Update data transaksi di dalam message tertentu.
     * Dipanggil setelah wallet assignment berhasil,
     * untuk sinkronisasi state global messages.
     *
     * @param {string|number} messageId    - ID ChatMessage
     * @param {number} transactionId       - ID TransactionLog
     * @param {object} transactionPatch    - Data partial yang di-merge
     */
    function updateTransactionInMessage(messageId, transactionId, transactionPatch) {
        const msgIdx = messages.value.findIndex(m => m.id === messageId)
        if (msgIdx === -1) return

        const msg = messages.value[msgIdx]
        if (!Array.isArray(msg.content)) return

        const updated = msg.content.map(comp => {
            if (comp.type === 'transaction_card' && comp.transaction?.id === transactionId) {
                return {
                    ...comp,
                    transaction: { ...comp.transaction, ...transactionPatch },
                    needs_wallet: transactionPatch.is_cleared ? false : comp.needs_wallet,
                }
            }
            return comp
        })

        messages.value[msgIdx] = { ...msg, content: updated }
    }

    /**
     * Update status image/evidence bubble setelah commit.
     * Mencari semua message user yang mengandung { type: 'image', evidenceUuid }
     * dan mengubah evidenceStatus menjadi 'COMMITTED' + committed = true.
     *
     * @param {string} evidenceUuid  - UUID evidence yang sudah di-commit
     */
    function updateEvidenceInMessage(evidenceUuid) {
        if (!evidenceUuid) return
        messages.value = messages.value.map(msg => {
            if (msg.role !== 'user') return msg
            if (!Array.isArray(msg.content)) return msg
            const hasTarget = msg.content.some(
                c => c.type === 'image' && c.evidenceUuid === evidenceUuid
            )
            if (!hasTarget) return msg
            return {
                ...msg,
                content: msg.content.map(c => {
                    if (c.type === 'image' && c.evidenceUuid === evidenceUuid) {
                        return { ...c, evidenceStatus: 'COMMITTED', committed: true }
                    }
                    return c
                }),
            }
        })
    }

    function updateEvidenceStatus(evidenceUuid, status) {
        if (!evidenceUuid || !status) return
        const normalized = String(status).toUpperCase()
        messages.value = messages.value.map(msg => {
            if (msg.role !== 'user') return msg
            if (!Array.isArray(msg.content)) return msg
            const hasTarget = msg.content.some(
                c => c.type === 'image' && (c.evidenceUuid === evidenceUuid || c.evidence_uuid === evidenceUuid)
            )
            if (!hasTarget) return msg
            return {
                ...msg,
                content: msg.content.map(c => {
                    if (c.type === 'image' && (c.evidenceUuid === evidenceUuid || c.evidence_uuid === evidenceUuid)) {
                        return { ...c, evidenceStatus: normalized, evidence_status: normalized }
                    }
                    return c
                }),
            }
        })
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
        sendEvidenceMessage,
        retryLastMessage,
        regenerateMessage,
        loadMore,
        scrollToBottom,
        jumpToLatest,
        onScrollUpdate,
        updateTransactionInMessage,
        updateEvidenceInMessage,
        updateEvidenceStatus,
        resumePending,
    }
}
