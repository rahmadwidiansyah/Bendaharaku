<script setup>
import TransactionSummaryItem from './TransactionSummaryItem.vue'
import MarkdownRenderer from './MarkdownRenderer.vue'

const props = defineProps({
  title:       { type: String, default: '' },
  emoji:       { type: String, default: '📊' },
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
        <span class="text-base leading-none">{{ emoji }}</span>
        <h3 class="text-sm font-bold text-white leading-tight truncate">
          <MarkdownRenderer :content="title" inline />
        </h3>
      </div>
      <div class="flex items-center gap-3 text-2xs text-gray-500">
        <span v-if="count">{{ count }} {{ countLabel }}</span>
        <span v-if="total" class="font-semibold tabular-nums" :class="accent === 'rose' ? 'text-rose-400/80' : 'text-emerald-400/80'">
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
        <div v-else class="px-3.5 py-2 text-xs text-gray-500 truncate">
          {{ item }}
        </div>
      </template>
    </div>
  </div>
</template>
