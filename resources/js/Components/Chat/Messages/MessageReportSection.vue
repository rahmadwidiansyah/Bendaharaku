<script setup>
import { useI18n } from 'vue-i18n'
import { computed } from 'vue'
import TransactionSummaryList from './TransactionSummaryList.vue'
import MarkdownRenderer from './MarkdownRenderer.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getWalletIconColor } from '@/Composables/useIcon.js'
import { toLucide } from '@/utils/chatIcons.js'

const { t } = useI18n()

const props = defineProps({
  component: { type: Object, required: true },
  content:   { type: Array, default: () => [] },
})

const variant = computed(() => {
  const key = props.component.translationKey || ''
  if (key.includes('balance') || key.includes('saldo')   || key.includes('balance_title') || key.includes('balance_list'))  return 'saldo'
  if (key.includes('wallet')  || key.includes('wallet_title'))                               return 'wallet'
  if (key.includes('asset')   || key.includes('asset_title'))                                return 'asset'
  if (key.includes('transaction_today') || key.includes('transaction_today_title'))          return 'transactions'
  if (key.includes('category')|| key.includes('category_title'))                             return 'category'
  if (key.includes('income')  || key.includes('income_title'))                               return 'income'
  if (key.includes('expense') || key.includes('expense_title'))                              return 'expense'
  return 'default'
})

const stripLeadingEmoji = (text) =>
  String(text ?? '').replace(/^(\p{Extended_Pictographic}[\u{FE0F}\u{200D}\u{20E3}]*)[\s]*/u, '')

const titleText = computed(() => {
  const title = props.component.title || (props.component.translationKey ? t(props.component.translationKey) : '')
  return props.component.emoji ? stripLeadingEmoji(title) : title
})

const hasStructuredItems = computed(() =>
  Array.isArray(props.component.items) && props.component.items.length > 0 && typeof props.component.items[0] === 'object'
)

// Check if this is the new saldo header variant (first section with total_label)
const isSaldoHeader = computed(() =>
  variant.value === 'saldo' &&
  props.component.translationKey?.includes('balance_title') &&
  props.component.items?.some?.(item => item?.label?.includes?.('Total Saldo') || item?.label?.includes?.('Total Balance'))
)

const isSaldoList = computed(() =>
  variant.value === 'saldo' &&
  props.component.translationKey?.includes('balance_list')
)

const isSummarizable = computed(() =>
  (variant.value === 'income' || variant.value === 'expense' || variant.value === 'transactions') && hasStructuredItems.value
)

const hasStructuredCategorySections = computed(() =>
  variant.value === 'category' &&
  Array.isArray(props.component.items) &&
  props.component.items.length > 0 &&
  typeof props.component.items[0] === 'object' &&
  'categories' in props.component.items[0]
)

function parseItem(item) {
  if (!item || typeof item !== 'string') return null
  if (!item.includes(' — ')) return null
  const parts = item.split(' — ')
  if (parts.length < 4) return null
  return { date: parts[0], type: parts[1], category: parts[2], amount: parts[3], wallet: parts[4] || '' }
}

// Render wallet type badge
function getWalletTypeLabel(groupType) {
  if (!groupType) return ''
  const labels = {
    'Asset': t('chat.command.wallet_type_asset'),
    'Liquid': t('chat.command.wallet_type_liquid'),
    'System': t('chat.command.wallet_type_system'),
  }
  return labels[groupType] || groupType
}

// Detect if icon value is a file path/URL (needs <img>) or emoji (needs <span>)
function isIconUrl(icon) {
  return icon && (icon.includes('.') || icon.startsWith('http') || icon.includes('/'))
}

function defaultIcon(value) {
    return value && value !== 'wallet' ? value : 'credit-card'
}

const walletIconClass = computed(() => getWalletIconColor())

function onImgError(e, fallback) {
  e.target.style.display = 'none'
  if (!e.target.nextElementSibling) {
    const span = document.createElement('span')
    span.className = 'text-lg leading-none shrink-0'
    span.textContent = fallback
    e.target.after(span)
  } else {
    e.target.nextElementSibling.style.display = ''
  }
}
</script>

<template>
  <!-- Saldo Header variant (first section with total and count) -->
  <div v-if="isSaldoHeader" class="mx-2 my-1.5 rounded-xl overflow-hidden bg-white/[0.015] border border-white/[0.06]">
    <div class="px-3.5 pt-3 pb-2">
      <div class="flex items-center gap-2 mb-1">
        <AppIcon v-if="component.emoji" :icon="toLucide(component.emoji)" class="w-4 h-4 shrink-0 text-purple-400" fallback="wallet" />
        <h3 class="text-sm font-bold text-white leading-tight truncate">
          <MarkdownRenderer :content="titleText" inline />
        </h3>
      </div>
      <div class="flex items-center gap-3 text-2xs text-gray-500">
        <template v-for="(item, idx) in component.items" :key="idx">
          <div class="flex items-center gap-1">
            <span class="font-medium text-gray-400">{{ item.label }}</span>
            <span v-if="item.value" class="font-semibold tabular-nums text-white">{{ item.value }}</span>
          </div>
          <span v-if="idx < component.items.length - 1" class="text-gray-600">·</span>
        </template>
      </div>
    </div>
  </div>

  <!-- Saldo List variant (wallet list with icons) -->
  <div v-else-if="isSaldoList" class="mx-2 my-1.5 rounded-xl overflow-hidden bg-white/[0.015] border border-white/[0.06]">
    <div class="divide-y divide-white/[0.03]">
      <div v-for="(item, idx) in component.items" :key="idx"
        class="flex items-center justify-between px-3.5 py-2.5 hover:bg-white/[0.02] transition-colors group">
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
          <img v-if="isIconUrl(item.icon)"
            :src="item.icon"
            class="w-5 h-5 rounded-full object-cover shrink-0"
            @error="(e) => onImgError(e, defaultIcon(item.icon))"
          />
          <AppIcon v-else :icon="toLucide(item.icon)" :class="['w-5 h-5 shrink-0', walletIconClass]" fallback="credit-card" />
          <div class="min-w-0">
            <span class="text-xs font-medium text-white truncate block">{{ item.name }}</span>
            <span v-if="item.group_type" class="text-2xs text-gray-500">
              {{ getWalletTypeLabel(item.group_type) }}
            </span>
          </div>
        </div>
        <span class="text-xs font-semibold text-white tabular-nums ml-2 shrink-0">{{ item.amount }}</span>
      </div>
    </div>
  </div>

  <!-- Summarizable variants (income/expense/transactions) with structured data -->
  <TransactionSummaryList
    v-else-if="isSummarizable"
    :title="titleText"
    :emoji="component.emoji"
    :items="component.items"
    :total="component.total"
    :count="component.count"
    :count-label="variant === 'saldo' ? 'dompet' : 'transaksi'"
    :accent="variant === 'expense' ? 'rose' : 'emerald'"
  />

  <!-- Other variants or fallback for string-based items -->
  <div
    v-else
    class="mx-2 my-1.5 rounded-xl overflow-hidden"
    :class="variant === 'income' ? 'bg-income-bg' : variant === 'expense' ? 'bg-expense-bg' : 'bg-white/[0.03]'"
  >
    <!-- Header -->
    <div class="px-3.5 py-2.5 border-b border-white/[0.04]">
      <div class="flex items-center justify-between min-w-0">
        <div class="flex items-center gap-2 min-w-0">
          <AppIcon v-if="component.emoji" :icon="toLucide(component.emoji)" class="w-4 h-4 shrink-0 text-purple-400" fallback="bar-chart-3" />
          <h3 class="text-sm font-semibold text-white leading-tight truncate">
            <MarkdownRenderer :content="titleText" inline />
          </h3>
        </div>
      </div>
      <div v-if="variant === 'asset'" class="flex items-center gap-3 text-2xs text-gray-500 mt-0.5">
        <span v-if="component.count" class="font-medium text-gray-400">{{ component.count }} {{ t('chat.command.asset_count') }}</span>
        <span v-if="component.total" class="font-semibold tabular-nums text-income-text/80">Total {{ component.total }}</span>
      </div>
    </div>

    <!-- Content -->
    <div class="divide-y divide-white/[0.04]">

      <!-- Wallet variant: one-line-per-wallet with balance -->
      <template v-if="variant === 'wallet'">
        <div v-for="(item, idx) in component.items" :key="idx"
          class="flex items-center justify-between px-3.5 py-2.5 hover:bg-white/[0.02] transition-colors">
          <span class="text-xs font-medium text-gray-200 truncate">{{ item.split(':')[0] ?? item }}</span>
          <span class="text-xs font-semibold text-white tabular-nums ml-2 shrink-0">{{ item.split(':').slice(1).join(':') ?? '' }}</span>
        </div>
      </template>

      <!-- Asset variant: nama kiri, nominal kanan -->
      <template v-else-if="variant === 'asset'">
        <div v-for="(item, idx) in component.items" :key="idx"
          class="flex items-center justify-between px-3.5 py-2 hover:bg-white/[0.02] transition-colors">
          <span class="text-xs text-gray-300 truncate">{{ item.split(':')[0] ?? item }}</span>
          <span class="text-xs font-semibold text-white tabular-nums ml-2 shrink-0">{{ item.split(':').slice(1).join(':') ?? '' }}</span>
        </div>
      </template>

      <!-- Category variant: sectioned by type -->
      <template v-else-if="variant === 'category'">
        <div v-if="hasStructuredCategorySections" class="px-3.5 pt-3 pb-2">
          <div v-for="(section, sIdx) in component.items" :key="sIdx" class="mb-4 last:mb-0">
            <div class="flex items-center gap-2 mb-2">
              <AppIcon :icon="toLucide(section.type_icon)" class="w-4 h-4 shrink-0 text-violet-400" fallback="folder" />
              <h4 class="text-xs font-bold text-white leading-tight">
                {{ section.label_key ? t(section.label_key) : section.type_name }}
              </h4>
              <span class="text-2xs text-gray-500 tabular-nums">({{ section.categories.length }})</span>
            </div>
            <div class="pl-5 space-y-1">
              <div v-for="(cat, cIdx) in section.categories" :key="cIdx"
                class="flex items-center gap-2 text-xs text-gray-300">
                <AppIcon icon="dot" class="w-3 h-3 shrink-0 text-gray-600" />
                <span>{{ cat }}</span>
              </div>
            </div>
            <div v-if="sIdx < component.items.length - 1" class="mt-4 mx-0 h-px bg-white/[0.04]" />
          </div>
        </div>
        <div v-else class="px-3.5 py-2.5 space-y-2">
          <div v-for="(item, idx) in component.items" :key="idx"
            class="flex items-center gap-2 text-xs text-gray-300">
            <span class="w-1.5 h-1.5 rounded-full shrink-0 opacity-60" :class="idx % 2 === 0 ? 'bg-purple-400' : 'bg-gray-600'"></span>
            <span>{{ item }}</span>
          </div>
        </div>
      </template>

      <!-- Default/transactions fallback: string-based items -->
      <template v-else>
        <div v-for="(item, idx) in component.items" :key="idx" class="hover:bg-white/[0.02] transition-colors">
          <div v-if="parseItem(item)" class="flex items-center justify-between px-3.5 py-2 gap-2">
            <div class="flex items-center gap-2 min-w-0 flex-1">
              <span class="text-3xs text-gray-500 tabular-nums shrink-0 w-9">{{ parseItem(item).date }}</span>
              <span class="w-1.5 h-1.5 rounded-full shrink-0"
                :class="parseItem(item).type.toLowerCase() === 'income' ? 'bg-income-text' :
                  parseItem(item).type.toLowerCase() === 'expense' ? 'bg-expense-text' : 'bg-transfer-text'">
              </span>
              <span class="text-xs text-gray-200 font-medium truncate">{{ parseItem(item).category }}</span>
              <span v-if="parseItem(item).wallet" class="text-3xs text-gray-500 truncate max-w-[60px]">{{ parseItem(item).wallet }}</span>
            </div>
            <span class="text-xs font-semibold tabular-nums shrink-0 ml-1"
              :class="parseItem(item).type.toLowerCase() === 'income' ? 'text-income-text' :
                parseItem(item).type.toLowerCase() === 'expense' ? 'text-expense-text' : 'text-transfer-text'">
              {{ parseItem(item).amount }}
            </span>
          </div>
          <div v-else class="flex items-start gap-2 px-3.5 py-2 text-xs text-gray-400">
            <AppIcon icon="sparkles" class="w-3.5 h-3.5 shrink-0 text-purple-400/60" />
            <span class="leading-relaxed">{{ item }}</span>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>
