<script setup>
/**
 * M3TransactionSummaryItem — satu baris transaksi di ringkasan laporan
 * (paritas TransactionSummaryItem Default). Ikon memakai kelas M3 bertoken.
 */
import { computed } from 'vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass, getM3WalletIconClass } from '@/Material/utils/m3IconColor.js'
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
    if (isWallet.value) return 'm3-tsi__amount--wallet'
    return isExpense.value ? 'm3-tsi__amount--expense' : 'm3-tsi__amount--income'
})

const typeName = computed(() => {
    const map = { income: 'Income', expense: 'Expense', debt: 'Debt', receivable: 'Receivable' }
    return map[props.type] || 'Income'
})

const iconClass = computed(() => {
    if (isWallet.value) return getM3WalletIconClass()
    return getM3CategoryIconClass(typeName.value)
})

const isMeaningful = (v) => v !== null && v !== undefined && String(v).trim() !== '' && String(v).trim() !== '-'
</script>

<template>
    <div class="m3-tsi">
        <AppIcon :icon="toLucide(categoryIcon)" :class="['m3-tsi__icon', iconClass]" fallback="file-text" />
        <div class="m3-tsi__body">
            <span v-if="isMeaningful(category)" class="m3-tsi__name">{{ category }}</span>
            <span v-if="groupType" class="m3-tsi__meta">{{ groupType }}</span>
            <span v-if="wallet && !isWallet && isMeaningful(wallet)" class="m3-tsi__meta m3-tsi__meta--mobile">{{ wallet }}</span>
        </div>
        <div class="m3-tsi__right">
            <span class="m3-tsi__amount" :class="accentClass">{{ amount }}</span>
            <span v-if="date && !isWallet && isMeaningful(date)" class="m3-tsi__date">{{ date }}</span>
        </div>
    </div>
</template>

<style scoped>
.m3-tsi {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2-5);
    min-height: 2.25rem;
    padding: var(--m3-space-1-5) var(--m3-space-3);
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-tsi:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 4%, transparent);
}

.m3-tsi__icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

.m3-tsi__body {
    flex: 1;
    min-width: 0;
}

.m3-tsi__name {
    display: block;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-tsi__meta {
    display: block;
    margin-top: 1px;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-tsi__right {
    flex-shrink: 0;
    text-align: end;
}

.m3-tsi__amount {
    display: block;
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    font-variant-numeric: tabular-nums;
}

.m3-tsi__amount--income { color: var(--m3-color-income); }
.m3-tsi__amount--expense { color: var(--m3-color-expense); }
.m3-tsi__amount--wallet { color: var(--m3-color-on-surface); font-weight: var(--m3-type-emphasized-title-small-weight); }

.m3-tsi__date {
    display: block;
    margin-top: 1px;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-tsi__meta--mobile {
    display: block;
}

@media (min-width: 640px) {
    .m3-tsi__meta--mobile {
        display: none;
    }
}
</style>
