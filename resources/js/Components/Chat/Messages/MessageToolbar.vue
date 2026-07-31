<script setup>
/**
 * MessageToolbar.vue
 *
 * Toolbar aksi hover di bot bubble.
 * Berisi: Salin teks, Generate Ulang, Coba Lagi (error only).
 *
 * Pakai useClipboard agar copy logic tidak duplikat.
 */

import { useClipboard } from '@/Composables/useClipboard.js'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    message:       { type: Object,  required: true },
    isError:       { type: Boolean, default: false },
    canRegenerate: { type: Boolean, default: true },
})

const emit = defineEmits(['copy', 'retry', 'regenerate'])

const { copied, copy } = useClipboard()

/** Ekstrak semua teks dari content array */
function extractText(message) {
    return (message.content ?? [])
        .filter(c => c.type === 'text' && c.text?.trim())
        .map(c => c.text)
        .join('\n')
        .trim()
}

async function handleCopy() {
    const text = extractText(props.message)
    if (!text) return
    await copy(text)
    emit('copy')
}
</script>

<template>
    <div class="flex items-center gap-0.5">
        <!-- Salin teks -->
        <button
            @click.stop="handleCopy"
            :class="[
                'w-6 h-6 flex items-center justify-center rounded-lg transition-all',
                copied
                    ? 'text-income-text'
                    : 'text-gray-600 hover:text-gray-300 hover:bg-white/8',
            ]"
            :title="copied ? t('chatTransaction.copied') : t('chatTransaction.copyMessage')"
            :aria-label="t('chatTransaction.copyMessage')"
        >
            <svg v-if="copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </button>

        <!-- Generate Ulang (semua bot message, kecuali error) -->
        <button
            v-if="canRegenerate && !isError"
            @click.stop="$emit('regenerate', message)"
            class="w-6 h-6 flex items-center justify-center rounded-lg text-gray-600 hover:text-purple-400 hover:bg-purple-500/10 transition-all"
            :title="t('chatTransaction.regenerateAnswer')"
            :aria-label="t('chatTransaction.regenerate')"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>

        <!-- Coba Lagi (error only) -->
        <button
            v-if="isError"
            @click.stop="$emit('retry', message)"
            class="flex items-center gap-1 px-1.5 h-6 rounded-lg text-expense-text hover:bg-expense-bg-hover transition-all"
            :title="t('chatTransaction.retry')"
            :aria-label="t('chatTransaction.retrySend')"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span class="text-2xs font-medium">{{ t('chatTransaction.retry') }}</span>
        </button>
    </div>
</template>
