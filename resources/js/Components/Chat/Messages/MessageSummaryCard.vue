<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  component: { type: Object, required: true },
  metadata: { type: Object, default: () => ({}) },
})

const isEvidence = computed(() => !!props.metadata?.evidence_uuid)

const allSuccess = computed(() => props.component.all_success)
const allFailed = computed(() => props.component.all_failed)

const tint = computed(() => {
  if (allSuccess.value) return { fg: 'text-income-text', bg: 'bg-income-bg', dot: 'bg-income-text' }
  if (allFailed.value) return { fg: 'text-expense-text', bg: 'bg-expense-bg', dot: 'bg-expense-text' }
  return { fg: 'text-debt-text', bg: 'bg-debt-bg', dot: 'bg-debt-text' }
})
</script>

<template>
  <div class="mx-2 my-1.5 rounded-xl bg-white/[0.03] overflow-hidden">
    <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-white/[0.04]">
      <div class="flex items-center gap-2">
        <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tint.dot"></span>
        <span class="text-xs font-semibold text-gray-200">{{ isEvidence ? t('chat.evidence.summaryTitle', { count: component.total }) : t('chat.multi.result') }}</span>
      </div>
      <span class="text-2xs font-medium px-2 py-0.5 rounded-full" :class="[tint.bg, tint.fg]">
        {{ allSuccess ? t('common.success') : allFailed ? t('common.error') : t('common.partial') }}
      </span>
    </div>

    <div class="flex items-stretch divide-x divide-white/[0.04]">
      <div v-for="(stat, si) in [
        { label: t('common.total'), value: component.total, cls: 'text-[var(--color-text-primary)]' },
        { label: t('common.success'), value: component.success, cls: 'text-income-text' },
        { label: t('common.error'), value: component.failed, cls: 'text-expense-text' },
      ]" :key="si" class="flex-1 flex flex-col items-center py-3 px-1">
        <span class="text-lg font-bold tabular-nums leading-none" :class="stat.cls">{{ stat.value }}</span>
        <span class="text-2xs text-[var(--color-text-muted)] mt-1">{{ stat.label }}</span>
      </div>
    </div>

    <p v-if="component.label" class="px-3.5 pb-2.5 pt-1 text-2xs" :class="tint.fg + '/70'">
      {{ component.label }}
    </p>
  </div>
</template>
