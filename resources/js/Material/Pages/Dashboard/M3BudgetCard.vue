<script setup>
/**
 * M3BudgetCard — ringkasan budget bulan berjalan untuk dashboard M3.
 * Props `budget` dari DashboardController (reuse getBudgetSummary):
 * null | { totalBudget, totalSpent, percentage, items: [{name, target, spent, percentage}] }
 * Kartu disembunyikan bila user belum membuat budget bulan ini.
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ProgressBar from '@/Material/Components/M3ProgressBar.vue'

const props = defineProps({
    budget: { type: Object, default: null },
})

const { t } = useI18n()

const fmt = (n) => new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0))

const overall = computed(() => ({
    pct: Math.min(100, Math.max(0, Number(props.budget?.percentage) || 0)),
    over: Number(props.budget?.totalSpent) > Number(props.budget?.totalBudget),
}))
</script>

<template>
    <M3Card v-if="budget" variant="outlined" padding="md" class="m3-budget-card">
        <div class="m3-budget-card__head">
            <p class="m3-budget-card__title">{{ $t('dashboard.widgets.budgetTitle') }}</p>
            <p class="m3-budget-card__pct" :class="{ 'm3-budget-card__pct--over': overall.over }">
                {{ overall.pct }}%
            </p>
        </div>
        <p class="m3-budget-card__totals">
            Rp {{ fmt(budget.totalSpent) }}
            <span class="m3-budget-card__budget">/ Rp {{ fmt(budget.totalBudget) }}</span>
        </p>
        <M3ProgressBar :value="overall.pct" :over="overall.over" size="sm" :aria-label="$t('dashboard.widgets.budgetTitle')" />

        <ul class="m3-budget-card__items">
            <li v-for="item in budget.items" :key="item.name" class="m3-budget-card__item">
                <div class="m3-budget-card__item-head">
                    <span class="m3-budget-card__item-name">{{ item.name }}</span>
                    <span class="m3-budget-card__item-amount">Rp {{ fmt(item.spent) }}</span>
                </div>
                <M3ProgressBar
                    :value="Math.min(100, Math.max(0, item.percentage))"
                    :over="item.spent > item.target"
                    size="sm"
                    :aria-label="item.name"
                />
            </li>
        </ul>
    </M3Card>
</template>

<style scoped>
.m3-budget-card__head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-budget-card__title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-budget-card__pct {
    margin: 0;
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    color: var(--m3-color-primary);
}

.m3-budget-card__pct--over {
    color: var(--m3-color-error);
}

.m3-budget-card__totals {
    margin: 0 0 var(--m3-space-2);
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight, 600);
    color: var(--m3-color-on-surface);
}

.m3-budget-card__budget {
    color: var(--m3-color-on-surface-variant);
    font-weight: var(--m3-type-body-medium-weight);
}

.m3-budget-card__items {
    list-style: none;
    margin: var(--m3-space-3) 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2-5);
}

.m3-budget-card__item-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--m3-space-2);
    margin-bottom: var(--m3-space-1);
}

.m3-budget-card__item-name {
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-budget-card__item-amount {
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight, 600);
    color: var(--m3-color-on-surface);
    flex-shrink: 0;
}
</style>
