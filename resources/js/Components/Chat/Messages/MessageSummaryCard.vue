<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    component: { type: Object, required: true },
})

const allSuccess = computed(() => props.component.all_success)
const allFailed = computed(() => props.component.all_failed)
const isPartial = computed(() => !allSuccess.value && !allFailed.value)

const statusBadge = computed(() => {
    if (allSuccess.value) return { text: t('common.success'), cls: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20' }
    if (allFailed.value) return { text: t('common.error'), cls: 'text-red-400 bg-red-500/10 border-red-500/20' }
    return { text: t('common.partial'), cls: 'text-amber-400 bg-amber-500/10 border-amber-500/20' }
})

const headerIcon = computed(() => {
    if (allSuccess.value) return '✅'
    if (allFailed.value) return '❌'
    return '⚠️'
})
</script>

<template>
    <div class="border-b border-white/10">
        <div class="flex items-center justify-between px-3.5 pt-3 pb-2 bg-white/5 border-b border-white/5">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-sm shrink-0">{{ headerIcon }}</span>
                <span class="text-xs font-semibold text-gray-300 truncate">{{ t('chat.multi.result') }}</span>
            </div>
            <span class="text-2xs font-bold px-1.5 py-0.5 rounded-full border shrink-0" :class="statusBadge.cls">
                {{ statusBadge.text }}
            </span>
        </div>

        <div class="flex divide-x divide-white/5">
            <div class="flex-1 flex flex-col items-center py-3 px-1">
                <span class="text-xl font-black text-white tabular-nums leading-none">{{ component.total }}</span>
                <span class="text-2xs text-gray-500 mt-1.5">{{ t('common.total') }}</span>
            </div>
            <div class="flex-1 flex flex-col items-center py-3 px-1">
                <span class="text-xl font-black text-emerald-400 tabular-nums leading-none">{{ component.success }}</span>
                <span class="text-2xs text-gray-500 mt-1.5">{{ t('common.success') }}</span>
            </div>
            <div v-if="component.failed > 0" class="flex-1 flex flex-col items-center py-3 px-1">
                <span class="text-xl font-black text-red-400 tabular-nums leading-none">{{ component.failed }}</span>
                <span class="text-2xs text-gray-500 mt-1.5">{{ t('common.error') }}</span>
            </div>
            <div v-else class="flex-1 flex flex-col items-center py-3 px-1 opacity-30">
                <span class="text-xl font-black text-gray-600 tabular-nums leading-none">{{ component.failed }}</span>
                <span class="text-2xs text-gray-600 mt-1.5">{{ t('common.error') }}</span>
            </div>
        </div>

        <div class="px-3.5 pb-2.5 pt-1">
            <span class="text-2xs" :class="allSuccess ? 'text-emerald-400/70' : (allFailed ? 'text-red-400/70' : 'text-amber-400/70')">{{ component.label }}</span>
        </div>
    </div>
</template>
