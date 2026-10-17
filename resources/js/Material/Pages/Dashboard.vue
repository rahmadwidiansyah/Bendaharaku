<script setup>
/**
 * Material/Pages/Dashboard.vue — Dashboard Material Design 3 Expressive.
 *
 * Kontrak data & interaksi IDENTIK dengan Pages/Dashboard.vue Default:
 * 17 props yang sama dari DashboardController, composables bersama
 * (useBalanceVisibility, useLayoutPreference, useCalendar), util format
 * yang sama, dan TransactionDetailModal yang dipakai ulang apa adanya.
 * (Keterbatasan visual fase 1: modal detail memakai styling Default
 * karena BaseModal memakai token `--color-*`. Fungsional identik.)
 */
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PortfolioCard from '@/Material/Pages/Dashboard/M3PortfolioCard.vue'
import M3CashflowCard from '@/Material/Pages/Dashboard/M3CashflowCard.vue'
import M3UpcomingDebts from '@/Material/Pages/Dashboard/M3UpcomingDebts.vue'
import M3TransactionList from '@/Material/Pages/Dashboard/M3TransactionList.vue'
import M3Calendar from '@/Material/Pages/Dashboard/M3Calendar.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch, onUnmounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { useCalendar } from '@/Composables/useCalendar'
import { formatNumber, parseDateOnly, formatDate } from '@/utils/format.js'

const { t } = useI18n()

const { isDesktopLayout } = useLayoutPreference()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

const TYPE_NAME_KEYS = {
    Income: 'types.income',
    Expense: 'types.expense',
    Transfer: 'types.transfer',
    Debt: 'types.debt',
    Receivable: 'types.receivable',
}

const props = defineProps({
    totalPortfolio: Number,
    totalLiquid: Number,
    totalInvest: Number,
    totalHutang: Number,
    totalPiutang: Number,
    thisMonthIncome: Number,
    thisMonthExpense: Number,
    transactions: Object,
    dailyTotals: Object,
    calendarMarkers: Array,
    listLimit: Number,
    pinnedWallets: Array,
    wallets: Array,
    startDate: String,
    endDate: String,
    filters: Object,
    upcomingDebts: Array,
})

// ─── Transaction modal ───
const showModal = ref(false)
const selectedTransaction = ref(null)

const openModal = (trx) => {
    selectedTransaction.value = trx
    showModal.value = true
}

// ─── Filter type ───
const type = ref(props.filters?.type || '')
const showSortModal = ref(false)

const setType = (newType) => {
    type.value = newType
    showSortModal.value = false
}

watch(type, () => {
    router.get(
        route('dashboard'),
        { type: type.value, start_date: props.startDate, end_date: props.endDate },
        { preserveState: true, replace: true },
    )
})

// ─── History tabs ───
const collapsedDates = ref({})
const activeHistoryTab = ref('detail')

const toggleDate = (dateKey) => {
    collapsedDates.value[dateKey] = !collapsedDates.value[dateKey]
}

// ─── State halaman daftar ───
const rows = ref([...(props.transactions?.data || [])])
const nextCursor = ref(props.transactions?.next_cursor || null)
const hasMore = ref(!!props.transactions?.has_more)
const totals = ref({ ...(props.dailyTotals || {}) })
const isLoadingMore = ref(false)
const loadError = ref(null)
let loadAbort = null

const resetListFromProps = () => {
    rows.value = [...(props.transactions?.data || [])]
    nextCursor.value = props.transactions?.next_cursor || null
    hasMore.value = !!props.transactions?.has_more
    totals.value = { ...(props.dailyTotals || {}) }
    fullyLoadedDates.value = new Set()
    loadError.value = null
}
watch(() => [props.startDate, props.endDate, props.filters, props.transactions?.data], resetListFromProps)
onUnmounted(() => loadAbort?.abort())

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
                cursor: nextCursor.value,
                limit: props.listLimit || 50,
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

// ─── Grouping ───
const groupedTransactions = computed(() => {
    const groups = {}
    for (const trx of rows.value) {
        const dateKey = typeof trx.raw_date === 'string' ? trx.raw_date.slice(0, 10) : trx.raw_date

        if (!groups[dateKey]) {
            const server = totals.value[dateKey] || { income: 0, expense: 0 }
            groups[dateKey] = { date: trx.date, transactions: [], income: server.income || 0, expense: server.expense || 0 }
        }
        groups[dateKey].transactions.push(trx)
    }
    return groups
})

const calendarGroups = computed(() => {
    const merged = {}
    for (const [dateKey, tg] of Object.entries(totals.value || {})) {
        merged[dateKey] = { date: dateKey, transactions: [], income: tg.income || 0, expense: tg.expense || 0 }
    }
    for (const [dateKey, g] of Object.entries(groupedTransactions.value)) {
        if (!merged[dateKey]) merged[dateKey] = { date: g.date, transactions: [], income: 0, expense: 0 }
        merged[dateKey].transactions = g.transactions
        if (merged[dateKey].date === dateKey) merged[dateKey].date = g.date
    }
    return merged
})

// ─── Calendar composable ───
const {
    getLocalYMD,
    selectedCalendarDate,
    calendarFilter,
    calendarMonthName,
    canGoNextMonth,
    selectedDateFormatted,
    calendarDays,
    prevMonth,
    nextMonth,
    selectDate,
} = useCalendar({
    initialDate: computed(() => props.startDate),
    groupedTransactions: calendarGroups,
    onNavigate: (startDate, endDate) => {
        router.get(
            route('dashboard'),
            { type: type.value, start_date: startDate, end_date: endDate },
            { preserveState: true, replace: true },
        )
    },
})

const todayYMD = computed(() => getLocalYMD(new Date()))

const selectedDateTransactions = computed(() => {
    const grp = groupedTransactions.value[selectedCalendarDate.value]
    return grp ? { [selectedCalendarDate.value]: grp } : {}
})

const fullyLoadedDates = ref(new Set())
const ensureSelectedDateLoaded = async (dateStr) => {
    if (activeHistoryTab.value !== 'calendar' || !dateStr) return
    if (fullyLoadedDates.value.has(dateStr)) return
    try {
        let cursor = null
        for (let i = 0; i < 20; i++) {
            const { data } = await axios.get(route('transactions.page'), {
                params: {
                    start_date: dateStr,
                    end_date: dateStr,
                    type: type.value || undefined,
                    cursor: cursor || undefined,
                    limit: 100,
                },
            })
            const seen = new Set(rows.value.map(rowKey))
            for (const row of data.data || []) {
                if (!seen.has(rowKey(row))) {
                    seen.add(rowKey(row))
                    rows.value.push(row)
                }
            }
            if (!data.has_more || !data.next_cursor) break
            cursor = data.next_cursor
        }
        fullyLoadedDates.value.add(dateStr)
    } catch (err) {
        // Gagal melengkapi: tampilkan yang sudah termuat.
    }
}
watch([selectedCalendarDate, activeHistoryTab], ([d, tab]) => {
    if (tab === 'calendar') ensureSelectedDateLoaded(d)
})

const visibleTransactions = computed(() =>
    activeHistoryTab.value === 'calendar' ? selectedDateTransactions.value : groupedTransactions.value,
)

// ─── Period label ───
const formattedPeriod = computed(() => {
    const start = parseDateOnly(props.startDate)
    const end = parseDateOnly(props.endDate)
    if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()
        && start.getDate() === 1 && end.getDate() >= 28) {
        return formatDate(props.startDate, { month: 'long', year: 'numeric' })
    }
    return `${formatDate(props.startDate, { day: 'numeric', month: 'short' })} - ${formatDate(props.endDate, { day: 'numeric', month: 'short', year: 'numeric' })}`
})

// ─── Type helpers ───
const getTypeName = (typeName) => t(TYPE_NAME_KEYS[typeName] ?? 'types.other')

const getWalletName = (trx) => {
    const typeName = trx.type?.name
    if (typeName === 'Transfer') {
        return [trx.source_wallet?.name, trx.destination_wallet?.name].filter(Boolean).join(' → ')
    }
    const isIncomeLike = typeName === 'Income'
        || (['Debt', 'Receivable'].includes(typeName) && trx.source_wallet?.group_type === 'System')
    return isIncomeLike ? (trx.destination_wallet?.name || '') : (trx.source_wallet?.name || '')
}

const calendarDayNames = computed(() => [
    t('dashboard.calendar.sun'),
    t('dashboard.calendar.mon'),
    t('dashboard.calendar.tue'),
    t('dashboard.calendar.wed'),
    t('dashboard.calendar.thu'),
    t('dashboard.calendar.fri'),
    t('dashboard.calendar.sat'),
])

// Selected-date label untuk tab kalender (di dalam list untuk calendar tab)
const showSelectedDateLabel = computed(() => activeHistoryTab.value === 'calendar')
</script>

<template>
    <M3Layout>
        <Head title="Dashboard" />

        <div class="m3-dash" :class="isDesktopLayout ? 'm3-dash--desktop' : ''">
            <!-- Kolom kiri: aset -->
            <div class="m3-dash__assets">
                <M3PortfolioCard
                    :total-portfolio="totalPortfolio"
                    :is-visible="isBalanceVisible"
                    :wallets="wallets"
                    :total-hutang="totalHutang"
                    :total-piutang="totalPiutang"
                    @toggle-visibility="toggleVisibility"
                />
                <M3CashflowCard
                    :income="thisMonthIncome"
                    :expense="thisMonthExpense"
                    :is-visible="isBalanceVisible"
                />
                <M3UpcomingDebts :upcoming-debts="upcomingDebts" :is-visible="isBalanceVisible" />
            </div>

            <!-- Kolom kanan: histori -->
            <div class="m3-dash__history">
                <div class="m3-history-head">
                    <h2 class="m3-history-head__title">{{ $t('dashboard.transactionHistory') }}</h2>
                    <div class="m3-tabs" role="tablist">
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeHistoryTab === 'detail'"
                            :class="['m3-tabs__btn', activeHistoryTab === 'detail' ? 'm3-tabs__btn--active' : '']"
                            @click="activeHistoryTab = 'detail'"
                        >{{ $t('dashboard.detailTab') }}</button>
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="activeHistoryTab === 'calendar'"
                            :class="['m3-tabs__btn', activeHistoryTab === 'calendar' ? 'm3-tabs__btn--active' : '']"
                            @click="activeHistoryTab = 'calendar'"
                        >{{ $t('dashboard.calendarTab') }}</button>
                    </div>
                </div>

                <!-- Navigasi bulan + filter tipe -->
                <div class="m3-period-row">
                    <div class="m3-period">
                        <M3IconButton :aria-label="$t('common.back')" @click="prevMonth">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </M3IconButton>
                        <p class="m3-period__label">
                            <span class="m3-period__eyebrow">Periode</span>
                            <span class="m3-period__value">{{ formattedPeriod }}</span>
                        </p>
                        <M3IconButton :aria-label="$t('common.next')" :disabled="!canGoNextMonth" @click="nextMonth">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </M3IconButton>
                    </div>
                    <M3Button
                        variant="outlined"
                        size="sm"
                        :class="type ? 'm3-filter-btn--active' : ''"
                        @click="showSortModal = true"
                    >
                        <template #leading>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25" />
                            </svg>
                        </template>
                        {{ type ? getTypeName(type) : $t('types.all') }}
                    </M3Button>
                </div>

                <!-- Kalender -->
                <div v-if="activeHistoryTab === 'calendar'" class="m3-dash__calendar">
                    <M3Calendar
                        :days="calendarDays"
                        :day-names="calendarDayNames"
                        :selected-date="selectedCalendarDate"
                        :today="todayYMD"
                        :filter="calendarFilter"
                        @select="selectDate"
                        @filter="(v) => { calendarFilter.value = v }"
                    />
                    <h3 v-if="showSelectedDateLabel" class="m3-selected-date">{{ selectedDateFormatted }}</h3>
                </div>

                <!-- Daftar transaksi -->
                <M3TransactionList
                    :groups="visibleTransactions"
                    :active-tab="activeHistoryTab"
                    :collapsed-dates="collapsedDates"
                    :is-visible="isBalanceVisible"
                    :can-load-more="hasMore"
                    :is-loading-more="isLoadingMore"
                    :load-error="loadError"
                    :get-wallet-name="getWalletName"
                    @toggle-date="toggleDate"
                    @open-modal="openModal"
                    @load-more="loadMore"
                />
            </div>
        </div>

        <!-- Popup filter tipe -->
        <M3Modal :show="showSortModal" :title="$t('dashboard.filterType')" max-width="md" @close="showSortModal = false">
            <div class="m3-type-grid">
                <M3Button
                    :variant="!type ? 'filled' : 'outlined'"
                    full-width
                    class="m3-type-grid__all"
                    @click="setType('')"
                >
                    {{ $t('types.all') }}
                </M3Button>
                <M3Button
                    v-for="key in ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable']"
                    :key="key"
                    :variant="type === key ? 'filled' : 'outlined'"
                    full-width
                    @click="setType(key)"
                >
                    {{ getTypeName(key) }}
                </M3Button>
            </div>
        </M3Modal>

        <TransactionDetailModal :show="showModal" :transaction="selectedTransaction" @close="showModal = false" />
    </M3Layout>
</template>

<style scoped>
.m3-dash {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    width: 100%;
    max-width: 72rem;
    margin: 0 auto;
    padding: var(--m3-space-4) var(--m3-space-4) var(--m3-space-4);
}

@media (min-width: 1024px) {
    .m3-dash--desktop {
        flex-direction: row;
        align-items: flex-start;
        gap: 2rem;
        padding: 1.25rem;
    }

    .m3-dash--desktop .m3-dash__assets {
        width: 33.333%;
        position: sticky;
        top: 5rem;
    }

    .m3-dash--desktop .m3-dash__history {
        width: 66.667%;
    }
}

.m3-dash__assets {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-dash__history {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-history-head {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-history-head__title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    font-weight: 600;
    color: var(--m3-color-on-surface);
}

.m3-tabs {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.25rem;
    padding: 0.25rem;
    border-radius: var(--m3-radius-full);
    border: 1px solid var(--m3-color-outline-variant);
    background: var(--m3-color-surface-container-low);
}

.m3-tabs__btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.375rem 0.75rem;
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    cursor: pointer;
    overflow: hidden;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-tabs__btn::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-tabs__btn:hover::after {
    opacity: 0.08;
}

.m3-tabs__btn:active::after {
    opacity: 0.12;
}

.m3-tabs__btn--active {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

.m3-tabs__btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-period-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.5rem;
    align-items: center;
}

.m3-period {
    display: flex;
    align-items: center;
    min-width: 0;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: var(--m3-radius-md);
    border: 1px solid var(--m3-color-outline-variant);
    background: var(--m3-color-surface-container-low);
}

.m3-period__label {
    margin: 0;
    flex: 1;
    min-width: 0;
    text-align: center;
}

.m3-period__eyebrow {
    display: block;
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
    color: var(--m3-color-primary);
}

.m3-period__value {
    display: block;
    font-size: var(--m3-type-title-small-size);
    font-weight: 600;
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    border-radius: var(--m3-radius-md);
    border: 1px solid var(--m3-color-outline-variant);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-title-small-size);
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
}

.m3-filter-btn svg {
    width: 0.875rem;
    height: 0.875rem;
}

.m3-filter-btn--active {
    color: var(--m3-color-primary);
    border-color: var(--m3-color-primary);
}

.m3-dash__calendar {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.m3-selected-date {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    font-weight: 600;
    color: var(--m3-color-on-surface-variant);
}

.m3-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.m3-type-grid__all {
    grid-column: span 2;
}
</style>
