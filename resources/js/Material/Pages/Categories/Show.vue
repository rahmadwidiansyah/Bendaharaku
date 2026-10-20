<script setup>
/**
 * Material/Pages/Categories/Show.vue — detail kategori M3.
 * Kontrak data/perilaku sama dengan Pages/Categories/Show.vue Default:
 * filter tanggal lewat query start_date/end_date, klik transaksi membuka
 * detail, dan kategori sistem tidak bisa dihapus dari halaman edit.
 *
 * Layout: satu kolom di mobile; dua kolom di >=1024px (hero+ringkasan
 * kiri yang sticky, daftar transaksi kanan) agar baris transaksi tidak
 * membentang selebar konten 72rem.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3DateRangeModal from '@/Material/Components/M3DateRangeModal.vue'
import M3TransactionDetailModal from '@/Material/Components/M3TransactionDetailModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatDate, formatNumber } from '@/utils/format.js'

const { t } = useI18n()

const props = defineProps({
    category: { type: Object, required: true },
    transactions: { type: Array, default: () => [] },
    totalUsage: { type: Number, default: 0 },
    isSystem: { type: Boolean, default: false },
    startDate: { type: String, default: '' },
    endDate: { type: String, default: '' },
})

const showDateModal = ref(false)
const showDetailModal = ref(false)
const selectedTransaction = ref(null)

const typeName = (name) => ({
    Income: t('types.income'),
    Expense: t('types.expense'),
    Transfer: t('types.transfer'),
    Debt: t('types.debt'),
    Receivable: t('types.receivable'),
}[name] ?? t('types.other'))

const typeKey = (name) => String(name ?? '').toLowerCase()

const amountClass = (trx) => {
    const name = trx?.type?.name
    if (name === 'Income') return 'm3-cat-show__amount--income'
    if (name === 'Transfer') return 'm3-cat-show__amount--transfer'
    if (name === 'Debt') return 'm3-cat-show__amount--debt'
    if (name === 'Receivable') return 'm3-cat-show__amount--receivable'
    return 'm3-cat-show__amount--expense'
}

const amountSign = (trx) => {
    const name = trx?.type?.name
    if (name === 'Income') return '+'
    if (name === 'Transfer') return ''
    if (['Debt', 'Receivable'].includes(name) && trx.source_wallet?.group_type === 'System') return '+'
    return '-'
}

const openDetail = (trx) => {
    selectedTransaction.value = {
        ...trx,
        amount: Number(trx.amount),
        category: props.category,
        source_wallet: trx.source_wallet,
        destination_wallet: trx.destination_wallet,
        time: new Date(trx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
        date: formatDate(trx.date),
    }
    showDetailModal.value = true
}

const formatDateRange = () => `${formatDate(props.startDate)} – ${formatDate(props.endDate)}`

const totalClass = computed(() => `m3-cat-show__total--${typeKey(props.category.type?.name)}`)
</script>

<template>
    <M3Layout :title="category.category_name">
        <Head :title="category.category_name" />

        <div class="m3-cat-show">
            <header class="m3-cat-show__topbar">
                <M3IconButton :href="route('categories.index')" :aria-label="t('category.show.back')">
                    <AppIcon icon="arrow-left" />
                </M3IconButton>
                <h1 class="m3-cat-show__topbar-title">{{ t('category.title') }}</h1>
            </header>

            <div class="m3-cat-show__layout">
                <div class="m3-cat-show__aside">
                    <M3Card variant="filled" padding="lg" class="m3-cat-show__hero">
                        <M3IconButton
                            variant="tonal"
                            :href="route('categories.edit', category.id)"
                            :aria-label="t('btn.edit')"
                            class="m3-cat-show__edit"
                        >
                            <AppIcon icon="pencil" />
                        </M3IconButton>

                        <span class="m3-cat-show__hero-icon-wrap" aria-hidden="true">
                            <AppIcon
                                :icon="category.icon"
                                fallback="folder"
                                :class="['m3-cat-show__hero-icon', getM3CategoryIconClass(category.type?.name)]"
                            />
                        </span>

                        <h1 class="m3-cat-show__name">{{ category.category_name }}</h1>

                        <div class="m3-cat-show__meta">
                            <span class="m3-cat-show__type" :class="`m3-cat-show__type--${typeKey(category.type?.name)}`">
                                <span class="m3-cat-show__type-dot" aria-hidden="true" />
                                {{ typeName(category.type?.name) }}
                            </span>
                            <span v-if="isSystem" class="m3-cat-show__system">
                                <AppIcon icon="lock" class="m3-cat-show__system-icon" />
                                {{ t('category.systemCategory') }}
                            </span>
                        </div>
                    </M3Card>

                    <M3Card variant="elevated" padding="md" class="m3-cat-show__summary">
                        <p class="m3-cat-show__summary-label">
                            {{ t('common.total') + ' ' + t('common.period') }}
                        </p>
                        <p class="m3-cat-show__total" :class="totalClass">
                            <span class="m3-cat-show__currency">{{ t('common.currency') }}</span>
                            {{ formatNumber(totalUsage) }}
                        </p>

                        <button
                            type="button"
                            class="m3-cat-show__range-chip"
                            :aria-label="`${t('common.dateRange')}: ${formatDateRange()}`"
                            @click="showDateModal = true"
                        >
                            <AppIcon icon="calendar-days" class="m3-cat-show__range-icon" />
                            <span>{{ formatDateRange() }}</span>
                        </button>
                    </M3Card>
                </div>

                <section class="m3-cat-show__transactions">
                    <header class="m3-cat-show__section-head">
                        <h2 class="m3-cat-show__section-title">{{ t('category.show.transactions') }}</h2>
                        <span class="m3-cat-show__count">
                            {{ transactions.length }} {{ t('category.transaction') }}
                        </span>
                    </header>

                    <M3Card variant="outlined" padding="none" class="m3-cat-show__list">
                        <M3ListItem
                            v-for="(trx, index) in transactions"
                            :key="trx.id"
                            clickable
                            :divider="index < transactions.length - 1"
                            @click="openDetail(trx)"
                        >
                            <template #icon>
                                <span class="m3-cat-show__trx-icon-wrap">
                                    <AppIcon
                                        :icon="trx.category?.icon ?? category.icon"
                                        fallback="file-text"
                                        :class="['m3-cat-show__trx-icon', getM3CategoryIconClass(trx.type?.name)]"
                                    />
                                </span>
                            </template>

                            {{ trx.notes || t('transaction.detail.noNote') }}

                            <template #supporting>
                                <span class="m3-cat-show__trx-support">
                                    <span>
                                        {{ formatDate(trx.date) }} ·
                                        {{ new Date(trx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                    <span v-if="trx.subject && trx.subject !== '-'">
                                        {{ trx.subject }}
                                    </span>
                                    <span v-if="trx.source_wallet || trx.destination_wallet">
                                        {{ trx.source_wallet?.name }}
                                        <template v-if="trx.source_wallet && trx.destination_wallet">→</template>
                                        {{ trx.destination_wallet?.name }}
                                    </span>
                                </span>
                            </template>

                            <template #trailing>
                                <span class="m3-cat-show__trx-trailing">
                                    <span class="m3-cat-show__amount" :class="amountClass(trx)">
                                        <template v-if="amountSign(trx)">{{ amountSign(trx) }}</template>
                                        {{ formatNumber(trx.amount) }}
                                    </span>
                                    <span class="m3-cat-show__badge" :class="`m3-cat-show__badge--${typeKey(trx.type?.name)}`">
                                        {{ typeName(trx.type?.name) }}
                                    </span>
                                </span>
                            </template>
                        </M3ListItem>

                        <div v-if="transactions.length === 0" class="m3-cat-show__empty">
                            <span class="m3-cat-show__empty-icon-wrap" aria-hidden="true">
                                <AppIcon icon="receipt-text" class="m3-cat-show__empty-icon" />
                            </span>
                            <p>{{ t('category.show.noTransactions') }}</p>
                        </div>
                    </M3Card>
                </section>
            </div>
        </div>

        <M3DateRangeModal
            :show="showDateModal"
            :start-date="startDate"
            :end-date="endDate"
            :action="route('categories.show', category.id)"
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
.m3-cat-show {
    --m3-cat-hero-size: 4.5rem;

    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-cat-show__topbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-cat-show__topbar-title {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
}

.m3-cat-show__layout {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    min-width: 0;
}

.m3-cat-show__aside {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-width: 0;
}

.m3-cat-show__hero {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-cat-show__edit {
    position: absolute;
    top: var(--m3-space-2);
    right: var(--m3-space-2);
}

.m3-cat-show__hero-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-cat-hero-size);
    height: var(--m3-cat-hero-size);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface);
}

.m3-cat-show__hero-icon {
    width: var(--m3-size-icon-xl);
    height: var(--m3-size-icon-xl);
}

.m3-cat-show__name {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-headline-medium-size);
    line-height: var(--m3-type-headline-medium-line-height);
    font-weight: var(--m3-type-emphasized-headline-medium-weight);
    letter-spacing: var(--m3-type-headline-medium-tracking);
    overflow-wrap: anywhere;
}

.m3-cat-show__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
}

.m3-cat-show__type,
.m3-cat-show__system {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-height: 2rem;
    padding: 0 var(--m3-space-3);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
}

.m3-cat-show__type--income { background: var(--m3-color-income-container); color: var(--m3-color-on-income-container); }
.m3-cat-show__type--expense { background: var(--m3-color-expense-container); color: var(--m3-color-on-expense-container); }
.m3-cat-show__type--transfer { background: var(--m3-color-transfer-container); color: var(--m3-color-on-transfer-container); }
.m3-cat-show__type--debt { background: var(--m3-color-debt-container); color: var(--m3-color-on-debt-container); }
.m3-cat-show__type--receivable { background: var(--m3-color-receivable-container); color: var(--m3-color-on-receivable-container); }

.m3-cat-show__type-dot {
    width: var(--m3-space-1-5);
    height: var(--m3-space-1-5);
    border-radius: var(--m3-radius-full);
    background: currentColor;
}

.m3-cat-show__system {
    padding: 0 var(--m3-space-2);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface-variant);
}

.m3-cat-show__system-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-cat-show__summary {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-cat-show__summary-label {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
}

.m3-cat-show__total {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-headline-medium-size);
    line-height: var(--m3-type-headline-medium-line-height);
    font-weight: var(--m3-type-emphasized-headline-medium-weight);
    letter-spacing: var(--m3-type-headline-medium-tracking);
    font-variant-numeric: tabular-nums;
}

.m3-cat-show__total--income { color: var(--m3-color-income); }
.m3-cat-show__total--expense { color: var(--m3-color-expense); }
.m3-cat-show__total--transfer { color: var(--m3-color-transfer); }
.m3-cat-show__total--debt { color: var(--m3-color-debt); }
.m3-cat-show__total--receivable { color: var(--m3-color-receivable); }

.m3-cat-show__currency {
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-body-medium-weight);
}

.m3-cat-show__range-chip {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: var(--m3-space-2);
    min-height: 2rem;
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

.m3-cat-show__range-chip:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-cat-show__range-chip:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-cat-show__range-icon {
    flex-shrink: 0;
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-cat-show__transactions {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-cat-show__section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    padding: 0 var(--m3-space-1);
}

.m3-cat-show__section-title {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
}

.m3-cat-show__count {
    flex-shrink: 0;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
}

.m3-cat-show__list {
    overflow: hidden;
}

.m3-cat-show__trx-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-cat-show__trx-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-cat-show__trx-support {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
}

.m3-cat-show__trx-support > span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-cat-show__trx-trailing {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--m3-space-1);
}

.m3-cat-show__amount {
    white-space: nowrap;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-emphasized-body-large-weight);
    letter-spacing: var(--m3-type-body-large-tracking);
}

.m3-cat-show__amount--income { color: var(--m3-color-income); }
.m3-cat-show__amount--expense { color: var(--m3-color-expense); }
.m3-cat-show__amount--transfer { color: var(--m3-color-transfer); }
.m3-cat-show__amount--debt { color: var(--m3-color-debt); }
.m3-cat-show__amount--receivable { color: var(--m3-color-receivable); }

.m3-cat-show__badge {
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-xs);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    white-space: nowrap;
}

.m3-cat-show__badge--income { background: var(--m3-color-income-container); color: var(--m3-color-on-income-container); }
.m3-cat-show__badge--expense { background: var(--m3-color-expense-container); color: var(--m3-color-on-expense-container); }
.m3-cat-show__badge--transfer { background: var(--m3-color-transfer-container); color: var(--m3-color-on-transfer-container); }
.m3-cat-show__badge--debt { background: var(--m3-color-debt-container); color: var(--m3-color-on-debt-container); }
.m3-cat-show__badge--receivable { background: var(--m3-color-receivable-container); color: var(--m3-color-on-receivable-container); }

.m3-cat-show__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-8) var(--m3-space-4);
    text-align: center;
    color: var(--m3-color-on-surface-variant);
}

.m3-cat-show__empty p {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-cat-show__empty-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-space-12);
    height: var(--m3-space-12);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-primary);
}

.m3-cat-show__empty-icon {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

@media (min-width: 64rem) {
    .m3-cat-show__topbar {
        display: none;
    }

    .m3-cat-show__layout {
        display: grid;
        grid-template-columns: minmax(0, 22rem) minmax(0, 1fr);
        align-items: start;
    }

    .m3-cat-show__aside {
        position: sticky;
        top: var(--m3-space-4);
    }
}
</style>
