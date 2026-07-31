<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    status: { type: String, default: null },
    loading: { type: Boolean, default: false },
    progress: { type: Number, default: 0 },
})

const config = computed(() => {
    if (props.loading) {
        return {
            color: 'text-purple-400',
            bg: 'bg-purple-500/10',
            dot: 'bg-purple-400',
            key: props.progress > 0 ? t('chat.uploading') + ' ' + props.progress + '%' : t('chat.uploading'),
        }
    }
    const map = {
        PENDING:    { color: 'text-gray-400', bg: 'bg-gray-500/10', dot: 'bg-gray-400', key: t('chat.status.pending') },
        UPLOADING:  { color: 'text-transfer-text', bg: 'bg-transfer-bg', dot: 'bg-transfer-text', key: t('chat.status.uploading') },
        UPLOADED:   { color: 'text-income-text', bg: 'bg-income-bg', dot: 'bg-income-text', key: t('chat.status.uploaded') },
        PROCESSING: { color: 'text-debt-text', bg: 'bg-debt-bg', dot: 'bg-debt-text', key: t('chat.status.processing') },
        QUEUED:     { color: 'text-transfer-text', bg: 'bg-transfer-bg', dot: 'bg-transfer-text', key: t('chat.status.queued') },
        OCR_COMPLETED:  { color: 'text-transfer-text', bg: 'bg-transfer-bg', dot: 'bg-transfer-text', key: t('chat.status.ocrCompleted') },
        CLASSIFIED: { color: 'text-transfer-text', bg: 'bg-transfer-bg', dot: 'bg-transfer-text', key: t('chat.status.classified') },
        PARSED:     { color: 'text-receivable-text', bg: 'bg-receivable-bg', dot: 'bg-receivable-text', key: t('chat.status.parsed') },
        RESOLVED:   { color: 'text-receivable-text', bg: 'bg-receivable-bg', dot: 'bg-receivable-text', key: t('chat.status.resolved') },
        READY:      { color: 'text-income-text', bg: 'bg-income-bg', dot: 'bg-income-text', key: t('chat.status.ready') },
        COMPLETED:  { color: 'text-income-text', bg: 'bg-income-bg', dot: 'bg-income-text', key: t('chat.status.completed') },
        FAILED:     { color: 'text-expense-text', bg: 'bg-expense-bg', dot: 'bg-expense-text', key: t('chat.status.failed') },
    }
    return map[props.status] ?? null
})

const showLoadingDots = computed(() =>
    props.loading || props.status === 'UPLOADING' || props.status === 'PROCESSING' || props.status === 'QUEUED'
)
</script>

<template>
    <div v-if="config" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md" :class="config.bg">
        <span v-if="showLoadingDots" class="flex gap-0.5">
            <span class="w-1 h-1 rounded-full animate-bounce" :class="config.dot" style="animation-delay: 0s" />
            <span class="w-1 h-1 rounded-full animate-bounce" :class="config.dot" style="animation-delay: 0.15s" />
            <span class="w-1 h-1 rounded-full animate-bounce" :class="config.dot" style="animation-delay: 0.3s" />
        </span>
        <span v-else class="w-1.5 h-1.5 rounded-full" :class="config.dot" />
        <span class="text-2xs font-semibold whitespace-nowrap" :class="config.color">{{ config.key }}</span>
    </div>
</template>
