<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
    size: { type: String, default: 'sm' },
})

const emit = defineEmits(['retry', 'delete'])
</script>

<template>
    <div class="flex items-center gap-1">
        <button
            type="button"
            @click="emit('retry')"
            class="flex items-center gap-1 px-2 py-1 rounded-lg text-2xs font-semibold text-amber-400 hover:text-amber-300 hover:bg-amber-500/10 transition-colors"
            :aria-label="t('chat.retryEvidence')"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ t('chat.retry') }}
        </button>
        <button
            type="button"
            @click="emit('delete')"
            class="flex items-center gap-1 px-2 py-1 rounded-lg text-2xs font-semibold text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors"
            :aria-label="t('chat.removeEvidence')"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            {{ t('common.delete') }}
        </button>
    </div>
</template>
