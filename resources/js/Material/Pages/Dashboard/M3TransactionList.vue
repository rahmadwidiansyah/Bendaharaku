<script setup>
/**
 * M3TransactionList — daftar transaksi dikelompokkan per tanggal.
 * Struktur & data identik histori Default (group.date/income/expense,
 * trx.category/type/source_wallet, collapsedDates, Load More, empty state).
 */
import AppIcon from '@/Components/AppIcon.vue'
import { getCategoryIconColor } from '@/Composables/useIcon.js'
import { formatNumber } from '@/utils/format.js'
import M3Card from '@/Material/Components/M3Card.vue'

defineProps({
    groups: { type: Object, default: () => ({}) },
    activeTab: { type: String, default: 'detail' }, // detail | calendar
    collapsedDates: { type: Object, default: () => ({}) },
    isVisible: { type: Boolean, default: true },
    canLoadMore: { type: Boolean, default: false },
    isLoadingMore: { type: Boolean, default: false },
    loadError: { type: String, default: null },
    getWalletName: { type: Function, required: true },
})

defineEmits(['toggle-date', 'open-modal', 'load-more'])

const masked = (v, isVisible) => isVisible ? formatNumber(v) : '••••••••'

const amountClass = (trx) => {
    const typeName = trx.type?.name
    if (typeName === 'Income') return 'm3-trx__amount--income'
    if (['Debt', 'Receivable'].includes(typeName) && trx.source_wallet?.group_type === 'System') return 'm3-trx__amount--income'
    if (typeName === 'Transfer') return 'm3-trx__amount--transfer'
    return 'm3-trx__amount--expense'
}

const amountSign = (trx) => {
    const typeName = trx.type?.name
    if (typeName === 'Income') return '+'
    if (['Debt', 'Receivable'].includes(typeName) && trx.source_wallet?.group_type === 'System') return '+'
    if (typeName === 'Transfer') return ''
    return '-'
}
</script>

<template>
    <div class="m3-history">
        <div v-for="(group, dateKey) in groups" :key="dateKey" class="m3-day-group">
            <button
                v-if="activeTab === 'detail'"
                type="button"
                class="m3-day-header"
                :aria-expanded="!collapsedDates[dateKey]"
                @click="$emit('toggle-date', dateKey)"
            >
                <svg class="m3-chevron" :class="!collapsedDates[dateKey] ? 'm3-chevron--open' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="m3-day-header__date">{{ group.date }}</span>
                <span class="m3-day-header__totals">
                    <span v-if="group.income > 0" class="m3-day-header__income">+{{ masked(group.income, isVisible) }}</span>
                    <span v-if="group.expense > 0" class="m3-day-header__expense">-{{ masked(group.expense, isVisible) }}</span>
                </span>
            </button>
            <div v-else class="m3-day-header m3-day-header--static">
                <span class="m3-day-header__date">{{ group.date }}</span>
                <span class="m3-day-header__totals">
                    <span v-if="group.income > 0" class="m3-day-header__income">+{{ masked(group.income, isVisible) }}</span>
                    <span v-if="group.expense > 0" class="m3-day-header__expense">-{{ masked(group.expense, isVisible) }}</span>
                </span>
            </div>

            <div
                class="m3-collapse"
                :style="{ gridTemplateRows: activeTab === 'detail' && collapsedDates[dateKey] ? '0fr' : '1fr' }"
            >
                <div class="m3-collapse__inner" :class="activeTab === 'detail' && collapsedDates[dateKey] ? 'm3-collapse__inner--hidden' : ''">
                    <M3Card variant="filled" padding="none" class="m3-trx-card">
                        <button
                            v-for="trx in group.transactions"
                            :key="(trx.is_draft ? 'd' : 't') + ':' + trx.id"
                            type="button"
                            class="m3-trx"
                            @click="$emit('open-modal', trx)"
                        >
                            <span class="m3-trx__icon-wrap">
                                <AppIcon :icon="trx.category?.icon" fallback="file-text" :class="['m3-trx__icon', getCategoryIconColor(trx.type?.name)]" />
                                <span v-if="!trx.is_cleared" class="m3-trx__draft-dot" aria-hidden="true" />
                            </span>
                            <span class="m3-trx__main">
                                <span class="m3-trx__title">{{ trx.category?.category_name || $t('types.transfer') }}</span>
                                <span class="m3-trx__sub">{{ getWalletName(trx) }}</span>
                            </span>
                            <span class="m3-trx__side">
                                <span class="m3-trx__amount" :class="amountClass(trx)">
                                    {{ amountSign(trx) }}{{ isVisible ? formatNumber(trx.amount) : '••••' }}
                                </span>
                                <span class="m3-trx__time">{{ trx.time }}</span>
                            </span>
                        </button>
                    </M3Card>
                </div>
            </div>
        </div>

        <div v-if="canLoadMore && activeTab === 'detail'" class="m3-load-more">
            <button type="button" class="m3-load-more__btn" :disabled="isLoadingMore" @click="$emit('load-more')">
                {{ isLoadingMore ? $t('common.loading') : $t('common.loadMore') }}
            </button>
            <p v-if="loadError" class="m3-load-more__error">{{ loadError }}</p>
        </div>

        <M3Card v-if="Object.keys(groups).length === 0" variant="outlined" padding="lg" class="m3-empty">
            <p class="m3-empty__text">
                {{ activeTab === 'calendar' ? $t('dashboard.noTransactions') : $t('common.dataEmpty') }}
            </p>
        </M3Card>
    </div>
</template>

<style scoped>
.m3-history {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.m3-day-group {
    border-bottom: 1px solid var(--m3-color-outline-variant);
    padding-bottom: 0.25rem;
}

.m3-day-group:last-child {
    border-bottom: none;
}

.m3-day-header {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    width: 100%;
    padding: 0.625rem 0.25rem;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--m3-color-primary);
    text-align: left;
}

.m3-day-header--static {
    cursor: default;
}

.m3-day-header__date {
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-day-header__totals {
    display: flex;
    gap: 0.625rem;
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
}

.m3-day-header__income {
    color: var(--m3-color-income);
}

.m3-day-header__expense {
    color: var(--m3-color-expense);
}

.m3-chevron {
    width: 0.875rem;
    height: 0.875rem;
    flex-shrink: 0;
    transition: transform var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-chevron--open {
    transform: rotate(90deg);
}

.m3-collapse {
    display: grid;
    transition: grid-template-rows var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-collapse__inner {
    overflow: hidden;
    transition: opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-collapse__inner--hidden {
    opacity: 0;
}

.m3-trx-card {
    overflow: hidden;
}

.m3-trx {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    width: 100%;
    padding: 0.625rem 1rem;
    background: none;
    border: none;
    border-bottom: 1px solid var(--m3-color-outline-variant);
    cursor: pointer;
    text-align: left;
    color: var(--m3-color-on-surface);
}

.m3-trx:last-child {
    border-bottom: none;
}

.m3-trx:hover {
    background: rgba(227, 226, 230, 0.06);
}

.m3-trx__icon-wrap {
    position: relative;
    flex-shrink: 0;
}

.m3-trx__icon {
    width: 1.25rem;
    height: 1.25rem;
}

.m3-trx__draft-dot {
    position: absolute;
    top: -0.25rem;
    right: -0.25rem;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-debt);
}

.m3-trx__main {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    line-height: 1.25rem;
}

.m3-trx__title {
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-trx__sub {
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-trx__side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    flex-shrink: 0;
}

.m3-trx__amount {
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
}

.m3-trx__amount--income {
    color: var(--m3-color-income);
}

.m3-trx__amount--expense {
    color: var(--m3-color-expense);
}

.m3-trx__amount--transfer {
    color: var(--m3-color-transfer);
}

.m3-trx__time {
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-load-more {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 0;
}

.m3-load-more__btn {
    padding: 0.625rem 1.25rem;
    border-radius: var(--m3-radius-md);
    border: 1px solid var(--m3-color-outline-variant);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    cursor: pointer;
}

.m3-load-more__btn:disabled {
    opacity: 0.38;
    cursor: wait;
}

.m3-load-more__error {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-expense);
}

.m3-empty {
    text-align: center;
}

.m3-empty__text {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}
</style>
