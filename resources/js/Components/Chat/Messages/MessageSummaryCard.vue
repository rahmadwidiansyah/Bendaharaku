<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  component: { type: Object, required: true },
})

const allSuccess = computed(() => props.component.all_success)
const allFailed = computed(() => props.component.all_failed)

const tint = computed(() => {
  if (allSuccess.value) return { fg: 'text-emerald-400', bg: 'bg-emerald-500/10', dot: 'bg-emerald-500' }
  if (allFailed.value) return { fg: 'text-red-400', bg: 'bg-red-500/10', dot: 'bg-red-500' }
  return { fg: 'text-amber-400', bg: 'bg-amber-500/10', dot: 'bg-amber-500' }
})
</script>

<template>
  <div class="mx-2 my-1.5 rounded-xl bg-white/[0.03] overflow-hidden">
    <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-white/[0.04]">
      <div class="flex items-center gap-2">
        <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tint.dot"></span>
        <span class="text-xs font-semibold text-gray-200">{{ t('chat.multi.result') }}</span>
      </div>
      <span class="text-2xs font-medium px-2 py-0.5 rounded-full" :class="[tint.bg, tint.fg]">
        {{ allSuccess ? t('common.success') : allFailed ? t('common.error') : t('common.partial') }}
      </span>
    </div>

    <div class="flex items-stretch divide-x divide-white/[0.04]">
      <div v-for="(stat, si) in [
        { label: t('common.total'), value: component.total, cls: 'text-white' },
        { label: t('common.success'), value: component.success, cls: 'text-emerald-400' },
        { label: t('common.error'), value: component.failed, cls: 'text-red-400' },
      ]" :key="si" class="flex-1 flex flex-col items-center py-3 px-1">
        <span class="text-lg font-bold tabular-nums leading-none" :class="stat.cls">{{ stat.value }}</span>
        <span class="text-2xs text-gray-500 mt-1">{{ stat.label }}</span>
      </div>
    </div>

    <p v-if="component.label" class="px-3.5 pb-2.5 pt-1 text-2xs" :class="tint.fg + '/70'">
      {{ component.label }}
    </p>
  </div>
</template>
