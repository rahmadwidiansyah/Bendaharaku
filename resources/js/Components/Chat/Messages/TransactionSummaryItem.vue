<script setup>
import { computed } from 'vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getCategoryIconColor, getWalletIconColor } from '@/Composables/useIcon.js'
import { toLucide } from '@/utils/chatIcons.js'

const props = defineProps({
  type:         { type: String, default: 'expense' },
  category:     { type: String, default: '' },
  categoryIcon: { type: String, default: 'file-text' },
  date:         { type: String, default: '' },
  amount:       { type: String, default: '' },
  wallet:       { type: String, default: '' },
  groupType:    { type: String, default: '' },
})

const isExpense = computed(() => props.type === 'expense')
const isWallet = computed(() => !!props.groupType)
const accentClass = computed(() => {
  if (isWallet.value) return 'text-[var(--color-text-primary)] font-bold'
  return isExpense.value ? 'text-expense-text' : 'text-income-text'
})

const typeName = computed(() => {
  const map = { income: 'Income', expense: 'Expense', debt: 'Debt', receivable: 'Receivable' }
  return map[props.type] || 'Income'
})

const iconColor = computed(() => {
  if (isWallet.value) return getWalletIconColor()
  return getCategoryIconColor(typeName.value)
})
</script>

<template>
  <div class="flex items-center gap-2.5 px-3.5 py-1.5 hover:bg-white/[0.02] transition-colors min-h-[2.25rem]">
    <AppIcon :icon="toLucide(categoryIcon)" :class="['inline w-4 h-4 shrink-0', iconColor]" fallback="file-text" />
    <div class="flex-1 min-w-0">
      <span class="text-xs font-medium text-gray-200 truncate leading-tight block">{{ category }}</span>
      <span v-if="groupType" class="text-2xs text-[var(--color-text-muted)] leading-tight block mt-px">{{ groupType }}</span>
      <span v-if="wallet && !isWallet" class="text-2xs text-[var(--color-text-muted)] leading-tight block mt-px sm:hidden">{{ wallet }}</span>
    </div>
    <div class="text-right shrink-0">
      <span class="text-xs font-semibold tabular-nums leading-tight" :class="accentClass">{{ amount }}</span>
      <span v-if="date && !isWallet" class="block text-2xs text-[var(--color-text-muted)] leading-tight mt-px">{{ date }}</span>
    </div>
  </div>
</template>
