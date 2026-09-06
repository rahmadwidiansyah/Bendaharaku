<script setup>
import TransactionSummaryItem from './TransactionSummaryItem.vue'
import MarkdownRenderer from './MarkdownRenderer.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { toLucide } from '@/utils/chatIcons.js'

const props = defineProps({
  title:       { type: String, default: '' },
  emoji:       { type: String, default: 'bar-chart-3' },
  items:       { type: Array, default: () => [] },
  total:       { type: String, default: '' },
  count:       { type: [Number, String], default: 0 },
  countLabel:  { type: String, default: 'transaksi' },
  accent:      { type: String, default: 'rose' },
})

function isString(item) {
  return typeof item === 'string'
}
</script>

<template>
  <div class="mx-2 my-1.5 rounded-xl overflow-hidden border border-white/[0.06] bg-white/[0.015]">
    <!-- Header -->
    <div class="px-3.5 pt-3 pb-2">
      <div class="flex items-center gap-2 mb-1">
        <AppIcon :icon="toLucide(emoji)" class="w-4 h-4 shrink-0 text-[var(--color-brand)]" fallback="bar-chart-3" />
        <h3 class="text-sm font-bold text-[var(--color-text-primary)] leading-tight truncate">
          <MarkdownRenderer :content="title" inline />
        </h3>
      </div>
      <div class="flex items-center gap-3 text-2xs text-[var(--color-text-muted)]">
        <span v-if="count">{{ count }} {{ countLabel }}</span>
        <span v-if="total" class="font-semibold tabular-nums" :class="accent === 'rose' ? 'text-expense-text/80' : 'text-income-text/80'">
          Total {{ total }}
        </span>
      </div>
    </div>

    <!-- Divider -->
    <div class="mx-3.5 h-px bg-white/[0.06]" />

    <!-- Transaction list -->
    <div class="divide-y divide-white/[0.03]">
      <template v-for="(item, idx) in items" :key="idx">
        <TransactionSummaryItem
          v-if="!isString(item)"
          :type="item.type"
          :category="item.category || item.name"
          :category-icon="item.category_icon || item.icon"
          :date="item.date"
          :amount="item.amount"
          :wallet="item.wallet"
          :group-type="item.group_type"
        />
        <div v-else class="px-3.5 py-2 text-xs text-[var(--color-text-muted)] truncate">
          {{ item }}
        </div>
      </template>
    </div>
  </div>
</template>
