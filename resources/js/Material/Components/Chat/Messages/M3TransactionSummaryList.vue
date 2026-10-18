<script setup>
/**
 * M3TransactionSummaryList — daftar transaksi ringkas (paritas Default).
 * `accent` 'rose' → expense, selainnya income (kontrak prop dipertahankan).
 */
import M3TransactionSummaryItem from './M3TransactionSummaryItem.vue'
import M3MarkdownRenderer from './M3MarkdownRenderer.vue'
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
    <div class="m3-tsl">
        <div class="m3-tsl__header">
            <div class="m3-tsl__title-row">
                <AppIcon :icon="toLucide(emoji)" iconClass="m3-tsl__icon" fallback="bar-chart-3" />
                <h3 class="m3-tsl__title">
                    <M3MarkdownRenderer :content="title" inline />
                </h3>
            </div>
            <div class="m3-tsl__meta-row">
                <span v-if="count">{{ count }} {{ countLabel }}</span>
                <span v-if="total" class="m3-tsl__total" :class="accent === 'rose' ? 'm3-tsl__total--expense' : 'm3-tsl__total--income'">
                    Total {{ total }}
                </span>
            </div>
        </div>

        <div class="m3-tsl__divider" aria-hidden="true" />

        <div class="m3-tsl__items">
            <template v-for="(item, idx) in items" :key="idx">
                <M3TransactionSummaryItem
                    v-if="!isString(item)"
                    :type="item.type"
                    :category="item.category || item.name"
                    :category-icon="item.category_icon || item.icon"
                    :date="item.date"
                    :amount="item.amount"
                    :wallet="item.wallet"
                    :group-type="item.group_type"
                />
                <div v-else class="m3-tsl__string-item">{{ item }}</div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.m3-tsl {
    margin: var(--m3-space-1-5) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-lowest);
    overflow: hidden;
}

.m3-tsl__header {
    padding: var(--m3-space-3) var(--m3-space-3) var(--m3-space-2);
}

.m3-tsl__title-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-tsl__icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-tsl__title {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-tsl__meta-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-1);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-tsl__total {
    font-weight: var(--m3-type-emphasized-label-small-weight);
    font-variant-numeric: tabular-nums;
}

.m3-tsl__total--income { color: var(--m3-color-income); }
.m3-tsl__total--expense { color: var(--m3-color-expense); }

.m3-tsl__divider {
    height: 1px;
    margin: 0 var(--m3-space-3);
    background: var(--m3-color-outline-variant);
}

.m3-tsl__items > * + * {
    border-top: 1px solid color-mix(in srgb, var(--m3-color-outline-variant) 55%, transparent);
}

.m3-tsl__string-item {
    padding: var(--m3-space-2) var(--m3-space-3);
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
