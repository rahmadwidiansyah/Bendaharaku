<script setup>
/**
 * M3TransactionList — daftar transaksi dikelompokkan per tanggal.
 * Struktur & data identik histori Default (group.date/income/expense,
 * trx.category/type/source_wallet, collapsedDates, Load More, empty state).
 */
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { formatNumber } from '@/utils/format.js'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'

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
                    <div class="m3-trx-list">
                        <button
                            v-for="trx in group.transactions"
                            :key="(trx.is_draft ? 'd' : 't') + ':' + trx.id"
                            type="button"
                            class="m3-trx"
                            @click="$emit('open-modal', trx)"
                        >
                            <span class="m3-trx__icon-wrap">
                                <AppIcon :icon="trx.category?.icon" fallback="file-text" :class="['m3-trx__icon', getM3CategoryIconClass(trx.type?.name)]" />
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
                    </div>
                </div>
            </div>
        </div>

        <div v-if="canLoadMore && activeTab === 'detail'" class="m3-load-more">
            <M3Button variant="outlined" size="sm" :disabled="isLoadingMore" @click="$emit('load-more')">
                {{ isLoadingMore ? $t('common.loading') : $t('common.loadMore') }}
            </M3Button>
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
    gap: var(--m3-space-2);
}

.m3-day-group {
    border-bottom: 1px solid var(--m3-color-outline-variant);
    padding-bottom: var(--m3-space-1);
}

.m3-day-group:last-child {
    border-bottom: none;
}

.m3-day-header {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1-5);
    width: 100%;
    padding: var(--m3-space-2-5) var(--m3-space-1);
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
    font-size: var(--m3-type-title-small-size);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-day-header__totals {
    display: flex;
    gap: var(--m3-space-2-5);
    flex-shrink: 0;
    font-size: var(--m3-type-title-small-size);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    white-space: nowrap;
}

.m3-day-header__income {
    color: var(--m3-color-income);
}

.m3-day-header__expense {
    color: var(--m3-color-expense);
}

.m3-chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
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

.m3-trx-list {
    display: flex;
    flex-direction: column;
}

.m3-trx {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    width: 100%;
    padding: var(--m3-space-3) var(--m3-space-3);
    background: none;
    border: none;
    border-bottom: 1px solid var(--m3-color-outline-variant);
    cursor: pointer;
    text-align: left;
    color: var(--m3-color-on-surface);
    overflow: hidden;
}

.m3-trx:last-child {
    border-bottom: none;
}

.m3-trx::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-trx:hover::after {
    opacity: 0.08;
}

.m3-trx:active::after {
    opacity: 0.12;
}

.m3-trx:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-trx__icon-wrap {
    position: relative;
    flex-shrink: 0;
}

.m3-trx__icon {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    flex-shrink: 0;
}

.m3-trx__draft-dot {
    position: absolute;
    top: calc(-1 * var(--m3-space-1));
    right: calc(-1 * var(--m3-space-1));
    width: var(--m3-space-2);
    height: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-debt);
}

.m3-trx__main {
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex: 1;
    min-width: 0;
    line-height: var(--m3-type-body-small-line-height);
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
    font-weight: var(--m3-type-body-small-weight);
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-trx__side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    flex-shrink: 0;
    min-width: 0;
    max-width: 45%;
}

.m3-trx__amount {
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
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
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-body-small-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-load-more {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-3) 0;
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
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

@media (min-width: 1024px) and (max-width: 1400px), (min-width: 1024px) and (max-height: 820px) {
    .m3-day-header {
        padding-top: var(--m3-space-1-5);
        padding-bottom: var(--m3-space-1-5);
    }

    .m3-trx {
        padding-top: var(--m3-space-1-5);
        padding-bottom: var(--m3-space-1-5);
    }
}
</style>
