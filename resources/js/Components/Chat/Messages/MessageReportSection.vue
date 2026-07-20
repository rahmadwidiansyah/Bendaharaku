<script setup>
import { useI18n } from 'vue-i18n'
import { computed } from 'vue'
const { t } = useI18n()
const props = defineProps({ component: { type: Object, required: true }, content: { type: Array, default: () => [] } })

// Determine variant from translationKey
const variant = computed(() => {
    const key = props.component.translationKey || ''
    if (key.includes('balance') || key.includes('saldo') || key.includes('balance_title')) return 'saldo'
    if (key.includes('wallet') || key.includes('wallet_title')) return 'wallet'
    if (key.includes('asset') || key.includes('asset_title')) return 'asset'
    if (key.includes('transaction_today') || key.includes('transaction_today_title')) return 'transactions'
    if (key.includes('category') || key.includes('category_title')) return 'category'
    if (key.includes('income') || key.includes('income_title')) return 'income'
    if (key.includes('expense') || key.includes('expense_title')) return 'expense'
    return 'default'
})

// Try to find an adjacent inline text that looks like a total (contains 'Rp' or digits)
const footerText = computed(() => {
    try {
        const comps = props.content || []
        // find index of this report_section occurrence by matching translationKey and items
        const idx = comps.findIndex(c => c.type === 'report_section' && (c.translationKey || '') === (props.component.translationKey || ''))
        if (idx === -1) return null
        for (let i = idx + 1; i <= idx + 3 && i < comps.length; i++) {
            const nc = comps[i]
            if (!nc) continue
            if (nc.type === 'text' && /Rp|\d{1,3}(?:\.\d{3})+|\d+/.test(nc.text || '')) {
                return nc.text
            }
        }
    } catch (e) { /* ignore */ }
    return null
})

const titleText = computed(() => props.component.title || (props.component.translationKey ? t(props.component.translationKey) : ''))
</script>

<template>
    <div :class="['overflow-hidden', variant === 'income' ? 'bg-emerald-900/30' : variant === 'expense' ? 'bg-rose-900/30' : 'bg-transparent']">
        <div class="p-4 flex items-start gap-3" :class="variant === 'wallet' ? 'border border-white/6' : 'border-b border-white/6'">
            <div class="flex-1">
                <div class="text-sm font-semibold text-white leading-tight">{{ titleText }}</div>
                <div class="text-2xs text-gray-400 mt-1">{{ new Date().toLocaleDateString() }}</div>
            </div>
            <div v-if="footerText" class="text-sm font-semibold text-white text-right ml-2">
                <div class="text-2xs text-gray-400">Total</div>
                <div class="text-lg font-bold">{{ footerText }}</div>
            </div>
        </div>

        <div class="p-3">
            <template v-if="variant === 'saldo'">
                <div class="grid grid-cols-2 gap-2">
                    <div v-for="(item, idx) in component.items" :key="idx" class="flex justify-between items-center p-2 rounded-md bg-gray-800/40">
                        <div class="text-2xs text-gray-300 truncate">{{ item.split(' — ')[1] ?? item }}</div>
                        <div class="text-2xs text-gray-100 font-mono">{{ item.split(' — ')[0] ?? '' }}</div>
                    </div>
                </div>
            </template>

            <template v-else-if="variant === 'wallet'">
                <div class="space-y-2">
                    <div v-for="(item, idx) in component.items" :key="idx" class="p-3 rounded-md bg-gray-800/30 border border-white/6">
                        <div class="text-sm font-semibold text-white">{{ item.split(':')[0] ?? item }}</div>
                        <div class="text-2xs text-gray-400 mt-1">Saldo</div>
                        <div class="text-base font-bold">{{ item.split(':').slice(1).join(':') ?? '' }}</div>
                    </div>
                </div>
            </template>

            <template v-else-if="variant === 'category'">
                <div class="flex flex-wrap gap-2">
                    <span v-for="(item, idx) in component.items" :key="idx" class="px-3 py-1 rounded-full bg-gray-800/30 text-2xs text-gray-200">{{ item }}</span>
                </div>
            </template>

            <template v-else>
                <ul class="space-y-2">
                    <li v-for="(item, idx) in component.items" :key="idx" class="p-2 rounded-md bg-gray-800/30 text-2xs text-gray-200">{{ item }}</li>
                </ul>
            </template>
        </div>

        <div v-if="!footerText" class="px-3 pb-3 pt-0">
            <slot name="footer">
                <!-- show nothing if no footerText; inline bubble will show totals -->
            </slot>
        </div>
    </div>
</template>
