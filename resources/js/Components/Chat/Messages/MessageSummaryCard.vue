<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
    component: { type: Object, required: true },
})
</script>

<template>
    <div class="rounded-xl border border-white/8 bg-gray-900/80 overflow-hidden">
        <!-- Label -->
        <div class="px-3.5 py-2.5 border-b border-white/5">
            <p class="text-xs font-semibold text-gray-300 leading-tight">{{ component.label }}</p>
        </div>

        <!-- Stats row -->
        <div class="flex divide-x divide-white/5">
            <!-- Total -->
            <div class="flex-1 flex flex-col items-center py-2.5">
                <span class="text-lg font-black text-white tabular-nums leading-tight">{{ component.total }}</span>
                <span class="text-2xs text-gray-600 mt-0.5">{{ t('common.total') }}</span>
            </div>

            <!-- Success -->
            <div class="flex-1 flex flex-col items-center py-2.5">
                <span class="text-lg font-black text-emerald-400 tabular-nums leading-tight">{{ component.success }}</span>
                <span class="text-2xs text-gray-600 mt-0.5">{{ t('common.success') }}</span>
            </div>

            <!-- Failed -->
            <div v-if="component.failed > 0" class="flex-1 flex flex-col items-center py-2.5">
                <span class="text-lg font-black text-red-400 tabular-nums leading-tight">{{ component.failed }}</span>
                <span class="text-2xs text-gray-600 mt-0.5">{{ t('common.error') }}</span>
            </div>
        </div>
    </div>
</template>
