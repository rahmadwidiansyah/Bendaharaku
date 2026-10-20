<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3DateRangeModal from '@/Material/Components/M3DateRangeModal.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3TransactionDetailModal from '@/Material/Components/M3TransactionDetailModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass, getM3WalletIconClass } from '@/Material/utils/m3IconColor.js'
import { formatDate, formatNumber } from '@/utils/format.js'

const { t } = useI18n()

const props = defineProps({
    wallet: { type: Object, required: true },
    transactions: { type: Object, default: () => ({}) },
    startDate: { type: String, default: '' },
    endDate: { type: String, default: '' },
})

const transactionList = computed(() => props.transactions?.data ?? [])
const totalTransactions = computed(() => props.transactions?.total ?? transactionList.value.length)
const hasPagination = computed(() => (props.transactions?.last_page ?? 1) > 1)

const showDateModal = ref(false)
const showDetailModal = ref(false)
const selectedTransaction = ref(null)

const groupLabel = computed(() =>
    props.wallet.group_type === 'Asset'
        ? t('wallet.groupTypes.asset')
        : t('wallet.groupTypes.liquid'),
)

const typeName = (name) => ({
    Income: t('types.income'),
    Expense: t('types.expense'),
    Transfer: t('types.transfer'),
    Debt: t('types.debt'),
    Receivable: t('types.receivable'),
}[name] ?? t('types.other'))

const typeKey = (name) => String(name ?? '').toLowerCase()

const isIncoming = (trx) => trx.destination_wallet_id === props.wallet.id
const amountSign = (trx) => (isIncoming(trx) ? '+' : '-')
const amountClass = (trx) => (isIncoming(trx) ? 'm3-wallet-show__amount--income' : 'm3-wallet-show__amount--expense')

const formatDateRange = () => `${formatDate(props.startDate)} – ${formatDate(props.endDate)}`

const formatTime = (value) => {
    if (!value) return ''
    return new Date(value)
        .toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        .replace('.', ':')
}

const openDetail = (trx) => {
    selectedTransaction.value = {
        ...trx,
        date: formatDate(trx.date),
        time: formatTime(trx.created_at),
    }
    showDetailModal.value = true
}
</script>

<template>
    <M3Layout :title="wallet.name">
        <Head :title="wallet.name" />

        <div class="m3-wallet-show">
            <header class="m3-wallet-show__topbar">
                <M3IconButton :href="route('wallets.index')" :aria-label="t('common.back')">
                    <AppIcon icon="arrow-left" />
                </M3IconButton>
                <h1 class="m3-wallet-show__topbar-title">{{ wallet.name }}</h1>
            </header>

            <div class="m3-wallet-show__layout">
                <div class="m3-wallet-show__aside">
                    <M3Card variant="filled" padding="lg" class="m3-wallet-show__hero">
                        <M3IconButton
                            variant="tonal"
                            :href="route('wallets.edit', wallet.id)"
                            :aria-label="t('btn.edit')"
                            class="m3-wallet-show__edit"
                        >
                            <AppIcon icon="pencil" />
                        </M3IconButton>

                        <span class="m3-wallet-show__hero-icon-wrap" aria-hidden="true">
                            <AppIcon
                                :icon="wallet.icon"
                                fallback="wallet"
                                :class="['m3-wallet-show__hero-icon', getM3WalletIconClass()]"
                            />
                        </span>

                        <h2 class="m3-wallet-show__name">{{ wallet.name }}</h2>

                        <span class="m3-wallet-show__group">
                            <span class="m3-wallet-show__group-dot" aria-hidden="true" />
                            {{ groupLabel }}
                        </span>

                        <div class="m3-wallet-show__balance-block">
                            <p class="m3-wallet-show__balance-label">{{ t('common.balance') }}</p>
                            <p
                                class="m3-wallet-show__balance"
                                :class="{ 'm3-wallet-show__balance--negative': parseFloat(wallet.balance) < 0 }"
                            >
                                <span class="m3-wallet-show__currency">{{ t('common.currency') }}</span>
                                {{ formatNumber(wallet.balance) }}
                            </p>
                        </div>
                    </M3Card>
                </div>

                <section class="m3-wallet-show__transactions">
                    <header class="m3-wallet-show__section-head">
                        <div class="m3-wallet-show__section-title-block">
                            <h2 class="m3-wallet-show__section-title">{{ t('wallet.recentMutation') }}</h2>
                            <p class="m3-wallet-show__section-count">
                                {{ totalTransactions }} {{ t('category.transaction') }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="m3-wallet-show__range-chip"
                            :aria-label="`${t('common.dateRange')}: ${formatDateRange()}`"
                            @click="showDateModal = true"
                        >
                            <AppIcon icon="calendar-days" class="m3-wallet-show__range-icon" />
                            <span>{{ formatDateRange() }}</span>
                        </button>
                    </header>

                    <M3Card variant="outlined" padding="none" class="m3-wallet-show__list">
                        <M3ListItem
                            v-for="(trx, index) in transactionList"
                            :key="trx.id"
                            clickable
                            :divider="index < transactionList.length - 1"
                            @click="openDetail(trx)"
                        >
                            <template #icon>
                                <span class="m3-wallet-show__trx-icon-wrap">
                                    <AppIcon
                                        :icon="trx.category?.icon"
                                        fallback="file-text"
                                        :class="['m3-wallet-show__trx-icon', getM3CategoryIconClass(trx.type?.name)]"
                                    />
                                </span>
                            </template>

                            {{ trx.category?.category_name || t('types.transfer') }}

                            <template #supporting>
                                <span class="m3-wallet-show__trx-support">
                                    <span>
                                        {{ formatDate(trx.date) }} · {{ formatTime(trx.created_at) }}
                                    </span>
                                    <span v-if="trx.source_wallet || trx.destination_wallet">
                                        {{ trx.source_wallet?.name }}
                                        <template v-if="trx.source_wallet && trx.destination_wallet">→</template>
                                        {{ trx.destination_wallet?.name }}
                                    </span>
                                </span>
                            </template>

                            <template #trailing>
                                <span class="m3-wallet-show__trx-trailing">
                                    <span class="m3-wallet-show__amount" :class="amountClass(trx)">
                                        {{ amountSign(trx) }}{{ formatNumber(trx.amount) }}
                                    </span>
                                    <span class="m3-wallet-show__badge" :class="`m3-wallet-show__badge--${typeKey(trx.type?.name)}`">
                                        {{ typeName(trx.type?.name) }}
                                    </span>
                                </span>
                            </template>
                        </M3ListItem>

                        <div v-if="transactionList.length === 0" class="m3-wallet-show__empty">
                            <span class="m3-wallet-show__empty-icon-wrap" aria-hidden="true">
                                <AppIcon icon="receipt-text" class="m3-wallet-show__empty-icon" />
                            </span>
                            <p class="m3-wallet-show__empty-text">{{ t('wallet.emptyMutation') }}</p>
                        </div>
                    </M3Card>

                    <nav v-if="hasPagination" class="m3-wallet-show__pagination" :aria-label="t('wallet.recentMutation')">
                        <M3Button
                            variant="text"
                            size="sm"
                            :href="transactions.prev_page_url || undefined"
                            :disabled="!transactions.prev_page_url"
                        >
                            <template #leading>
                                <AppIcon icon="chevron-left" />
                            </template>
                            {{ t('common.back') }}
                        </M3Button>

                        <span class="m3-wallet-show__page">
                            {{ transactions.current_page }} / {{ transactions.last_page }}
                        </span>

                        <M3Button
                            variant="text"
                            size="sm"
                            :href="transactions.next_page_url || undefined"
                            :disabled="!transactions.next_page_url"
                        >
                            {{ t('common.next') }}
                            <template #trailing>
                                <AppIcon icon="chevron-right" />
                            </template>
                        </M3Button>
                    </nav>
                </section>
            </div>
        </div>

        <M3DateRangeModal
            :show="showDateModal"
            :start-date="startDate"
            :end-date="endDate"
            :action="route('wallets.show', wallet.id)"
            @close="showDateModal = false"
        />

        <M3TransactionDetailModal
            :show="showDetailModal"
            :transaction="selectedTransaction"
            @close="showDetailModal = false"
        />
    </M3Layout>
</template>

<style scoped>
.m3-wallet-show {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-wallet-show__topbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-wallet-show__topbar-title {
    margin: 0;
    min-width: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-wallet-show__layout {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    min-width: 0;
}

.m3-wallet-show__aside {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-width: 0;
}

.m3-wallet-show__hero {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-wallet-show__edit {
    position: absolute;
    top: var(--m3-space-2);
    right: var(--m3-space-2);
}

.m3-wallet-show__hero-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: calc(var(--m3-size-touch-target-lg) * 1.5);
    height: calc(var(--m3-size-touch-target-lg) * 1.5);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-wallet-show__hero-icon {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
}

.m3-wallet-show__name {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    letter-spacing: var(--m3-type-headline-small-tracking);
}

.m3-wallet-show__group {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-1) var(--m3-space-3);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
}

.m3-wallet-show__group-dot {
    width: var(--m3-space-2);
    height: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
}

.m3-wallet-show__balance-block {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-3);
    width: 100%;
}

.m3-wallet-show__balance-label {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-wallet-show__balance {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: center;
    gap: var(--m3-space-2);
    margin: 0;
    max-width: 100%;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-headline-medium-size);
    line-height: var(--m3-type-headline-medium-line-height);
    font-weight: var(--m3-type-emphasized-headline-medium-weight);
    letter-spacing: var(--m3-type-headline-medium-tracking);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.m3-wallet-show__balance--negative {
    color: var(--m3-color-expense);
}

.m3-wallet-show__currency {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-title-medium-weight);
}

.m3-wallet-show__transactions {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-wallet-show__section-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    padding: 0 var(--m3-space-1);
}

.m3-wallet-show__section-title-block {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
}

.m3-wallet-show__section-title {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
}

.m3-wallet-show__section-count {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
}

.m3-wallet-show__range-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-height: var(--m3-space-8);
    max-width: 100%;
    padding: 0 var(--m3-space-3);
    border: 1px solid var(--m3-color-outline);
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-family: inherit;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-wallet-show__range-chip:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-wallet-show__range-chip:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-wallet-show__range-icon {
    flex-shrink: 0;
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-wallet-show__list {
    overflow: hidden;
}

.m3-wallet-show__trx-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-wallet-show__trx-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-wallet-show__trx-support {
    display: inline-flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1) var(--m3-space-2);
}

.m3-wallet-show__trx-trailing {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--m3-space-1);
}

.m3-wallet-show__amount {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.m3-wallet-show__amount--income {
    color: var(--m3-color-income);
}

.m3-wallet-show__amount--expense {
    color: var(--m3-color-expense);
}

.m3-wallet-show__badge {
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-xs);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    white-space: nowrap;
}

.m3-wallet-show__badge--income {
    background: var(--m3-color-income-container);
    color: var(--m3-color-on-income-container);
}

.m3-wallet-show__badge--expense {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-wallet-show__badge--transfer {
    background: var(--m3-color-transfer-container);
    color: var(--m3-color-on-transfer-container);
}

.m3-wallet-show__badge--debt {
    background: var(--m3-color-debt-container);
    color: var(--m3-color-on-debt-container);
}

.m3-wallet-show__badge--receivable {
    background: var(--m3-color-receivable-container);
    color: var(--m3-color-on-receivable-container);
}

.m3-wallet-show__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-8) var(--m3-space-4);
    text-align: center;
}

.m3-wallet-show__empty-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
}

.m3-wallet-show__empty-icon {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-wallet-show__empty-text {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-wallet-show__pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-1);
}

.m3-wallet-show__page {
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    font-variant-numeric: tabular-nums;
}

@media (min-width: 64rem) {
    .m3-wallet-show__topbar {
        display: none;
    }

    .m3-wallet-show__balance {
        font-size: var(--m3-type-display-small-size);
        line-height: var(--m3-type-display-small-line-height);
        font-weight: var(--m3-type-emphasized-display-small-weight);
        letter-spacing: var(--m3-type-display-small-tracking);
    }

    .m3-wallet-show__layout {
        display: grid;
        grid-template-columns: minmax(0, 22rem) minmax(0, 1fr);
        align-items: start;
    }

    .m3-wallet-show__aside {
        position: sticky;
        top: var(--m3-space-4);
    }
}
</style>
