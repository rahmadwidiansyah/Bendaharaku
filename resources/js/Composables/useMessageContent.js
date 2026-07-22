/**
 * useMessageContent.js
 *
 * Composable untuk mem-parse struktur konten pesan chat.
 * Diextract dari ChatMessage.vue agar bisa diuji dan direuse.
 *
 * Usage:
 *   const { isUser, isBot, isErrorMessage, filteredInline, cardComponents, userText } =
 *       useMessageContent(() => props.message)
 */

import { computed, isRef } from 'vue'

/** Tipe komponen yang masuk ke dalam bubble (inline) */
const INLINE_TYPES = new Set(['text', 'divider', 'suggestion', 'image'])

/**
 * @param {Object | Ref<Object> | (() => Object)} messageSource - Pesan atau getter
 * @returns {Object} computed properties untuk render konten pesan
 */
export function useMessageContent(messageSource) {
    // Normalise ke computed
    const message = isRef(messageSource)
        ? messageSource
        : computed(typeof messageSource === 'function' ? messageSource : () => messageSource)

    const isUser = computed(() => message.value?.role === 'user')
    const isBot  = computed(() => message.value?.role === 'assistant')

    /**
     * Apakah pesan ini mengandung error bubble.
     */
    const isErrorMessage = computed(() =>
        (message.value?.content ?? []).some(c => c.type === 'error') ||
        message.value?.metadata?.error === true
    )

    /**
     * Teks dari komponen pertama bertipe 'text' (untuk user bubble).
     */
    const userText = computed(() => {
        const first = message.value?.content?.[0]
        return first?.type === 'text' ? (first.text ?? '') : ''
    })

    /**
     * Semua komponen dengan type valid (filter text kosong).
     */
    const validComponents = computed(() =>
        (message.value?.content ?? []).filter(comp => {
            if (!comp?.type) return false
            if (comp.type === 'text') return comp.text?.trim()?.length > 0
            return true
        })
    )

    /**
     * Komponen yang masuk ke dalam satu bubble (text, divider, suggestion).
     */
    const inlineComponents = computed(() =>
        validComponents.value.filter(c => INLINE_TYPES.has(c.type))
    )

    /**
     * Komponen yang ditampilkan di luar bubble (transaction_card, summary_card, error).
     */
    const cardComponents = computed(() =>
        validComponents.value.filter(c => !INLINE_TYPES.has(c.type))
    )

    /**
     * Inline tanpa divider yang tidak valid:
     * - Divider di awal atau akhir
     * - Divider berturutan
     */
    const filteredInline = computed(() => {
        const items = inlineComponents.value
        return items.filter((comp, i) => {
            if (comp.type !== 'divider') return true
            const prev = items[i - 1]
            const next = items[i + 1]
            if (!prev || !next) return false                     // awal/akhir
            if (prev.type === 'divider' || next.type === 'divider') return false // berturutan
            return true
        })
    })

    return {
        isUser,
        isBot,
        isErrorMessage,
        userText,
        validComponents,
        inlineComponents,
        cardComponents,
        filteredInline,
    }
}
