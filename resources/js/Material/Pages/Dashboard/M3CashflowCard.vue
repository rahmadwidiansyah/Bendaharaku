<script setup>
/**
 * M3CashflowCard — ringkasan pemasukan & pengeluaran bulan berjalan.
 * Data & format identik mini-cashflow Dashboard Default
 * (formatNumber + useBalanceVisibility). Warna semantik M3.
 */
import { formatNumber } from '@/utils/format.js'
import M3Card from '@/Material/Components/M3Card.vue'

defineProps({
    income: { type: Number, default: 0 },
    expense: { type: Number, default: 0 },
    isVisible: { type: Boolean, default: true },
})
</script>

<template>
    <div class="m3-cashflow">
        <M3Card variant="filled" padding="md" class="m3-cashflow__cell">
            <div class="m3-cashflow__icon m3-cashflow__icon--income" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </div>
            <p class="m3-cashflow__label">{{ $t('dashboard.income') }}</p>
            <p class="m3-cashflow__amount m3-cashflow__amount--income" aria-live="polite">
                <span class="m3-cashflow__currency">Rp</span>{{ isVisible ? formatNumber(income) : '••••' }}
            </p>
        </M3Card>
        <M3Card variant="filled" padding="md" class="m3-cashflow__cell">
            <div class="m3-cashflow__icon m3-cashflow__icon--expense" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
            </div>
            <p class="m3-cashflow__label">{{ $t('dashboard.expense') }}</p>
            <p class="m3-cashflow__amount m3-cashflow__amount--expense" aria-live="polite">
                <span class="m3-cashflow__currency">Rp</span>{{ isVisible ? formatNumber(expense) : '••••' }}
            </p>
        </M3Card>
    </div>
</template>

<style scoped>
.m3-cashflow {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.m3-cashflow__cell {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    min-width: 0;
}

.m3-cashflow__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-cashflow__icon svg {
    width: 1rem;
    height: 1rem;
}

.m3-cashflow__icon--income {
    background: var(--m3-color-income-container);
    color: var(--m3-color-on-income-container);
}

.m3-cashflow__icon--expense {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-cashflow__label {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-cashflow__amount {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-cashflow__currency {
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
    margin-right: 0.25rem;
}

.m3-cashflow__amount--income {
    color: var(--m3-color-income);
}

.m3-cashflow__amount--expense {
    color: var(--m3-color-expense);
}
</style>
