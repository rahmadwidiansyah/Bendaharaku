<script setup>
/**
 * Riwayat transaksi versi M3.
 *
 * Kontrak data/query identik dengan Transactions/Index Default:
 * cursor pagination (`transactions.page`), filter type/search/date,
 * subtotal harian dari server, dan detail modal M3.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3DateRangeModal from '@/Material/Components/M3DateRangeModal.vue'
import M3TransactionDetailModal from '@/Material/Components/M3TransactionDetailModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { computed, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { formatDate, formatNumber } from '@/utils/format.js'

const { t } = useI18n()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

const props = defineProps({
    transactions: { type: Object, default: () => ({}) },
    dailyTotals: { type: Object, default: () => ({}) },
    calendarMarkers: { type: Array, default: () => [] },
    startDate: { type: String, default: '' },
    endDate: { type: String, default: '' },
    filters: { type: Object, default: () => ({}) },
    limit: { type: Number, default: 50 },
})

const TYPE_FILTERS = [
    { value: '', labelKey: 'types.all' },
    { value: 'Income', labelKey: 'types.income' },
    { value: 'Expense', labelKey: 'types.expense' },
    { value: 'Transfer', labelKey: 'types.transfer' },
    { value: 'Debt', labelKey: 'types.debt' },
    { value: 'Receivable', labelKey: 'types.receivable' },
]

// ─── State halaman (halaman 1 dari server, sisanya via endpoint JSON) ───
const rows = ref([...(props.transactions?.data || [])])
const nextCursor = ref(props.transactions?.next_cursor || null)
const hasMore = ref(!!props.transactions?.has_more)
const totals = ref({ ...(props.dailyTotals || {}) })
const isLoadingMore = ref(false)
const loadError = ref(null)
let loadAbort = null

const type = ref(props.filters?.type || '')
const searchInput = ref(props.filters?.search || '')
const showDateModal = ref(false)
let searchTimer = null

const resetFromProps = () => {
    loadAbort?.abort()
    isLoadingMore.value = false
    rows.value = [...(props.transactions?.data || [])]
    nextCursor.value = props.transactions?.next_cursor || null
    hasMore.value = !!props.transactions?.has_more
    totals.value = { ...(props.dailyTotals || {}) }
    loadError.value = null
    type.value = props.filters?.type || ''
    searchInput.value = props.filters?.search || ''
}

watch(
    () => [
        props.startDate,
        props.endDate,
        props.filters?.type,
        props.filters?.search,
        props.transactions?.data,
        props.dailyTotals,
    ],
    resetFromProps,
)

onUnmounted(() => {
    clearTimeout(searchTimer)
    loadAbort?.abort()
})

// ─── Filter ───────────────────────────────────────────────────────
const applyFilters = () => {
    clearTimeout(searchTimer)
    loadAbort?.abort()
    router.get(
        route('transactions.index'),
        {
            type: type.value || undefined,
            search: searchInput.value || undefined,
            start_date: props.startDate,
            end_date: props.endDate,
        },
        { preserveState: true, replace: true },
    )
}

const onSearchInput = (value) => {
    searchInput.value = value
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 350)
}

const setType = (value) => {
    if (type.value === value) return
    type.value = value
    applyFilters()
}

const dateModalParams = computed(() => ({
    type: type.value || undefined,
    search: searchInput.value || undefined,
}))

const dateRangeLabel = computed(() => {
    if (!props.startDate || !props.endDate) return t('common.dateRange')
    return `${formatDate(props.startDate)} – ${formatDate(props.endDate)}`
})

// ─── Muat lebih banyak ────────────────────────────────────────────
const rowKey = (trx) => `${trx.is_draft ? 'd' : 't'}:${trx.id}`

const loadMore = async () => {
    if (isLoadingMore.value || !hasMore.value || !nextCursor.value) return
    isLoadingMore.value = true
    loadError.value = null
    loadAbort?.abort()
    loadAbort = new AbortController()

    try {
        const { data } = await axios.get(route('transactions.page'), {
            params: {
                start_date: props.startDate,
                end_date: props.endDate,
                type: type.value || undefined,
                search: searchInput.value || undefined,
                cursor: nextCursor.value,
                limit: props.limit || 50,
            },
            signal: loadAbort.signal,
        })

        const seen = new Set(rows.value.map(rowKey))
        for (const row of data.data || []) {
            if (!seen.has(rowKey(row))) {
                seen.add(rowKey(row))
                rows.value.push(row)
            }
        }
        nextCursor.value = data.next_cursor || null
        hasMore.value = !!data.has_more
    } catch (err) {
        if (!axios.isCancel(err)) loadError.value = t('common.loadMoreFailed')
    } finally {
        isLoadingMore.value = false
    }
}

// ─── Pengelompokan per tanggal (subtotal dari server) ─────────────
const groupedTransactions = computed(() => {
    const groups = {}
    for (const trx of rows.value) {
        const dateKey = typeof trx.raw_date === 'string' ? trx.raw_date.slice(0, 10) : trx.raw_date
        if (!groups[dateKey]) {
            const server = totals.value[dateKey] || { income: 0, expense: 0 }
            groups[dateKey] = {
                date: trx.date,
                transactions: [],
                income: server.income || 0,
                expense: server.expense || 0,
            }
        }
        groups[dateKey].transactions.push(trx)
    }
    return groups
})

const isEmpty = computed(() => Object.keys(groupedTransactions.value).length === 0)

// ─── Modal detail ─────────────────────────────────────────────────
const showModal = ref(false)
const selectedTransaction = ref(null)

const openModal = (trx) => {
    selectedTransaction.value = trx
    showModal.value = true
}

// ─── Helper tampilan ──────────────────────────────────────────────
const isIncomeLike = (trx) => {
    const name = trx.type?.name
    return name === 'Income'
        || (['Debt', 'Receivable'].includes(name) && trx.source_wallet?.group_type === 'System')
}

const isTransfer = (trx) => trx.type?.name === 'Transfer'

const amountTone = (trx) => {
    if (isIncomeLike(trx)) return 'm3-tx__value--income'
    if (isTransfer(trx)) return 'm3-tx__value--transfer'
    return 'm3-tx__value--expense'
}

const amountSign = (trx) => {
    if (isIncomeLike(trx)) return '+'
    if (isTransfer(trx)) return ''
    return '-'
}

const displayAmount = (value) => (isBalanceVisible.value ? formatNumber(value) : '••••••••')

const getWalletName = (trx) => {
    const name = trx.type?.name
    if (name === 'Transfer') {
        return [trx.source_wallet?.name, trx.destination_wallet?.name].filter(Boolean).join(' → ')
    }
    return isIncomeLike(trx) ? (trx.destination_wallet?.name || '') : (trx.source_wallet?.name || '')
}
</script>

<template>
    <M3Layout :title="t('nav.recordDesc')">
        <Head :title="t('nav.recordDesc')" />

        <div class="m3-tx">
            <M3PageTitle :title="t('nav.recordDesc')" :subtitle="dateRangeLabel" />

            <M3Card variant="outlined" padding="md" class="m3-tx__filters">
                <div class="m3-tx__search-row">
                    <M3TextField
                        :model-value="searchInput"
                        type="search"
                        :label="t('nav.search')"
                        :placeholder="t('common.search')"
                        @update:model-value="onSearchInput"
                        @enter="applyFilters"
                    />
                    <M3IconButton
                        :aria-label="t('header.toggleBalance')"
                        @click="toggleVisibility"
                    >
                        <AppIcon :icon="isBalanceVisible ? 'eye' : 'eye-off'" />
                    </M3IconButton>
                </div>

                <div class="m3-tx__date-row">
                    <M3Button variant="outlined" class="m3-tx__date-btn" @click="showDateModal = true">
                        <template #leading>
                            <AppIcon icon="calendar-days" class="m3-tx__date-icon" />
                        </template>
                        {{ dateRangeLabel }}
                    </M3Button>
                </div>

                <div class="m3-tx__types" role="group" :aria-label="t('common.filter')">
                    <M3Chip
                        v-for="item in TYPE_FILTERS"
                        :key="item.value || 'all'"
                        :selected="type === item.value"
                        @click="setType(item.value)"
                    >
                        {{ t(item.labelKey) }}
                    </M3Chip>
                </div>
            </M3Card>

            <div v-if="!isEmpty" class="m3-tx__groups">
                <section
                    v-for="(group, dateKey) in groupedTransactions"
                    :key="dateKey"
                    class="m3-tx__group"
                    :aria-labelledby="`m3-tx-group-${dateKey}`"
                >
                    <header class="m3-tx__group-head">
                        <h2 :id="`m3-tx-group-${dateKey}`" class="m3-tx__group-date">
                            {{ group.date }}
                        </h2>
                        <div class="m3-tx__group-totals">
                            <span v-if="group.income > 0" class="m3-tx__value--income">
                                +{{ displayAmount(group.income) }}
                            </span>
                            <span v-if="group.expense > 0" class="m3-tx__value--expense">
                                -{{ displayAmount(group.expense) }}
                            </span>
                        </div>
                    </header>

                    <M3Card variant="outlined" padding="none" class="m3-tx__list">
                        <M3ListItem
                            v-for="(trx, index) in group.transactions"
                            :key="rowKey(trx)"
                            clickable
                            :divider="index < group.transactions.length - 1"
                            @click="openModal(trx)"
                        >
                            <template #icon>
                                <span class="m3-tx__icon">
                                    <AppIcon
                                        :icon="trx.category?.icon"
                                        fallback="file-text"
                                        :class="['m3-tx__icon-glyph', getM3CategoryIconClass(trx.type?.name)]"
                                    />
                                </span>
                            </template>

                            <span class="m3-tx__headline">
                                {{ trx.category?.category_name || t('types.transfer') }}
                            </span>

                            <template #supporting>
                                <span class="m3-tx__support">
                                    <span class="m3-tx__support-wallet">{{ getWalletName(trx) }}</span>
                                    <template v-if="!trx.is_cleared">
                                        <span class="m3-tx__support-sep" aria-hidden="true">·</span>
                                        <span class="m3-tx__draft">{{ t('transaction.draft') }}</span>
                                    </template>
                                </span>
                            </template>

                            <template #trailing>
                                <span class="m3-tx__trailing">
                                    <span class="m3-tx__amount" :class="amountTone(trx)">
                                        <span>{{ amountSign(trx) }}</span>
                                        <span class="m3-tx__currency">{{ t('common.currency') }}</span>
                                        <span>{{ displayAmount(trx.amount) }}</span>
                                    </span>
                                    <span class="m3-tx__time">{{ trx.time }}</span>
                                </span>
                            </template>
                        </M3ListItem>
                    </M3Card>
                </section>
            </div>

            <M3Card v-else variant="outlined" padding="lg" class="m3-tx__empty">
                <span class="m3-tx__empty-icon" aria-hidden="true">
                    <AppIcon icon="receipt" class="m3-tx__empty-glyph" />
                </span>
                <p class="m3-tx__empty-title">{{ t('common.dataEmpty') }}</p>
                <M3Button :href="route('transactions.create')" variant="tonal">
                    <template #leading>
                        <AppIcon icon="plus" />
                    </template>
                    {{ t('nav.newRecord') }}
                </M3Button>
            </M3Card>

            <div v-if="hasMore" class="m3-tx__more">
                <M3Button variant="outlined" :disabled="isLoadingMore" @click="loadMore">
                    <template v-if="isLoadingMore" #leading>
                        <M3CircularProgress size="xs" />
                    </template>
                    {{ isLoadingMore ? t('common.loading') : t('common.loadMore') }}
                </M3Button>
                <p v-if="loadError" class="m3-tx__error" role="alert">{{ loadError }}</p>
            </div>
        </div>

        <M3DateRangeModal
            :show="showDateModal"
            :start-date="startDate"
            :end-date="endDate"
            :action="route('transactions.index')"
            :params="dateModalParams"
            @close="showDateModal = false"
        />

        <M3TransactionDetailModal
            :show="showModal"
            :transaction="selectedTransaction"
            @close="showModal = false"
        />
    </M3Layout>
</template>

<style scoped>
.m3-tx {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

/* ── Filter ─────────────────────────────────────────────────────── */
.m3-tx__filters {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-tx__search-row,
.m3-tx__date-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-tx__search-row > :first-child {
    flex: 1;
    min-width: 0;
}

.m3-tx__date-btn {
    max-width: 100%;
}

.m3-tx__date-btn :deep(.m3-btn__label) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-tx__date-icon,
.m3-tx__icon-glyph {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-tx__types {
    display: flex;
    gap: var(--m3-space-2);
    overflow-x: auto;
    padding-bottom: var(--m3-space-1);
    scrollbar-width: none;
}

.m3-tx__types::-webkit-scrollbar {
    display: none;
}

.m3-tx__types :deep(.m3-chip) {
    flex-shrink: 0;
}

/* ── Grup tanggal ───────────────────────────────────────────────── */
.m3-tx__groups {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
}

.m3-tx__group {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-tx__group-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    padding: 0 var(--m3-space-1);
}

.m3-tx__group-date {
    margin: 0;
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    text-transform: uppercase;
    color: var(--m3-color-primary);
}

.m3-tx__group-totals {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    white-space: nowrap;
}

/* ── Baris transaksi ────────────────────────────────────────────── */
.m3-tx__list {
    overflow: hidden;
}

.m3-tx__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-tx__headline {
    color: var(--m3-color-on-surface);
}

.m3-tx__support {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-tx__support-wallet {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-tx__support-sep {
    color: var(--m3-color-outline);
}

.m3-tx__draft {
    padding: 0 var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    white-space: nowrap;
}

.m3-tx__trailing {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--m3-space-0-5);
}

.m3-tx__amount {
    display: inline-flex;
    align-items: baseline;
    gap: var(--m3-space-0-5);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    white-space: nowrap;
}

.m3-tx__currency {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
}

.m3-tx__time {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-tx__value--income {
    color: var(--m3-color-income);
}

.m3-tx__value--expense {
    color: var(--m3-color-expense);
}

.m3-tx__value--transfer {
    color: var(--m3-color-transfer);
}

/* ── Empty & load more ─────────────────────────────────────────── */
.m3-tx__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-3);
    text-align: center;
}

.m3-tx__empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
}

.m3-tx__empty-glyph {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-tx__empty-title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
    color: var(--m3-color-on-surface);
}

.m3-tx__more {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) 0;
}

.m3-tx__error {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-body-small-weight);
    letter-spacing: var(--m3-type-body-small-tracking);
    color: var(--m3-color-error);
    text-align: center;
}

@media (min-width: 64rem) {
    .m3-tx {
        padding: var(--m3-space-6);
    }

    .m3-tx__filters {
        flex-direction: row;
        align-items: center;
        flex-wrap: wrap;
    }

    .m3-tx__search-row {
        flex: 1 1 18rem;
    }

    .m3-tx__date-row {
        flex-shrink: 0;
    }

    .m3-tx__types {
        flex-basis: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-tx * {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}
</style>
