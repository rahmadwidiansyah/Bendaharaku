<script setup>
/**
 * M3TransactionDetailModal — detail transaksi versi M3.
 *
 * Paritas perilaku dengan TransactionDetailModal Default (kontrak aksi sama):
 *   - Konfirmasi draft (PATCH transactions.confirm) hanya jika !is_cleared
 *   - Edit (link transactions.edit) & Hapus (DELETE transactions.destroy)
 *   - Dialog konfirmasi sebelum hapus / konfirmasi draft
 * Presentasi: dialog di desktop, bottom sheet di mobile (M3Modal).
 */
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { route } from 'ziggy-js'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { formatNumber, formatDate } from '@/utils/format.js'

const { t } = useI18n()

const props = defineProps({
    show: Boolean,
    transaction: Object,
})

const emit = defineEmits(['close'])

// ─── Delete flow ──────────────────────────────────────────────────
const showDeleteConfirm = ref(false)
const isDeleting = ref(false)

const deleteTransaction = () => {
    isDeleting.value = true
    router.delete(route('transactions.destroy', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false
            emit('close')
        },
        onFinish: () => { isDeleting.value = false },
    })
}

// ─── Draft confirm flow ───────────────────────────────────────────
const showConfirmDraft = ref(false)
const isConfirming = ref(false)

const confirmDraft = () => {
    isConfirming.value = true
    router.patch(route('transactions.confirm', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmDraft.value = false
            emit('close')
        },
        onFinish: () => { isConfirming.value = false },
    })
}

// ─── Helpers (mapping identik M3TransactionList) ──────────────────
const getTypeName = (name) => t({
    Income: 'types.income',
    Expense: 'types.expense',
    Transfer: 'types.transfer',
    Debt: 'types.debt',
    Receivable: 'types.receivable',
}[name] ?? 'types.other')

const typeBadgeClass = (name) => ({
    Income: 'm3-trxd__badge--income',
    Expense: 'm3-trxd__badge--expense',
    Transfer: 'm3-trxd__badge--transfer',
    Debt: 'm3-trxd__badge--debt',
    Receivable: 'm3-trxd__badge--receivable',
}[name] ?? 'm3-trxd__badge--neutral')

const amountClass = (trx) => {
    if (!trx?.type) return ''
    const name = trx.type.name
    if (name === 'Income') return 'm3-trxd__amount--income'
    if (['Debt', 'Receivable'].includes(name) && trx.source_wallet?.group_type === 'System') return 'm3-trxd__amount--income'
    if (name === 'Transfer') return 'm3-trxd__amount--transfer'
    return 'm3-trxd__amount--expense'
}

const amountSign = (trx) => {
    if (!trx?.type) return ''
    const name = trx.type.name
    if (name === 'Income') return '+'
    if (name === 'Transfer') return ''
    if (['Debt', 'Receivable'].includes(name) && trx.source_wallet?.group_type === 'System') return '+'
    return '-'
}

const dueDateText = (trx) => {
    if (trx.due_date_type === 'fixed')   return formatDate(trx.due_date)
    if (trx.due_date_type === 'monthly') return `Tgl ${trx.due_date_interval} • Bulanan`
    if (trx.due_date_type === 'daily')   return `Setiap ${trx.due_date_interval} hari`
    return ''
}
</script>

<template>
    <!-- ─── Detail utama: dialog di desktop, sheet di mobile ─── -->
    <M3Modal
        :show="show && !showDeleteConfirm && !showConfirmDraft"
        :title="$t('transaction.detail.title')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="sm"
        @close="emit('close')"
    >
        <div v-if="transaction" class="m3-trxd">
            <!-- ── Hero: ikon kategori + nama + badge ── -->
            <div class="m3-trxd__hero">
                <span class="m3-trxd__hero-icon">
                    <AppIcon
                        :icon="transaction.category?.icon"
                        fallback="file-text"
                        :class="['m3-trxd__ico', getM3CategoryIconClass(transaction.type?.name)]"
                    />
                </span>
                <h3 class="m3-trxd__cat">{{ transaction.category?.category_name || $t('types.transfer') }}</h3>
                <div class="m3-trxd__badges">
                    <span class="m3-trxd__badge" :class="typeBadgeClass(transaction.type?.name)">
                        {{ getTypeName(transaction.type?.name) }}
                    </span>
                    <span v-if="!transaction.is_cleared" class="m3-trxd__badge m3-trxd__badge--draft">
                        {{ $t('transaction.draft') }}
                    </span>
                </div>
            </div>

            <!-- ── Nominal (warna semantik finansial) ── -->
            <div class="m3-trxd__amount-card">
                <p class="m3-trxd__amount-label">{{ $t('transaction.amount') }}</p>
                <p class="m3-trxd__amount" :class="amountClass(transaction)">
                    <span v-if="amountSign(transaction)" class="m3-trxd__amount-sign">{{ amountSign(transaction) }}</span>
                    <span class="m3-trxd__amount-currency">Rp</span><span class="m3-trxd__amount-value">{{ formatNumber(transaction.amount) }}</span>
                </p>
            </div>

            <!-- ── Metadata ── -->
            <div class="m3-trxd__rows">
                <div class="m3-trxd__row">
                    <span class="m3-trxd__label">{{ $t('transaction.detail.date') }}</span>
                    <span class="m3-trxd__value">{{ transaction.date }} • {{ transaction.time }}</span>
                </div>

                <div class="m3-trxd__row">
                    <span class="m3-trxd__label">{{ $t('transaction.detail.wallet') }}</span>
                    <span class="m3-trxd__value m3-trxd__value--wallet">
                        <span class="m3-trxd__wallet-name">{{ transaction.source_wallet?.name }}</span>
                        <svg v-if="transaction.destination_wallet?.name" class="m3-trxd__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                        <span v-if="transaction.destination_wallet?.name" class="m3-trxd__wallet-name">{{ transaction.destination_wallet.name }}</span>
                    </span>
                </div>

                <div v-if="transaction.subject && transaction.subject !== '-'" class="m3-trxd__row">
                    <span class="m3-trxd__label">{{ $t('transaction.detail.party') }}</span>
                    <span class="m3-trxd__value">{{ transaction.subject }}</span>
                </div>

                <div v-if="transaction.due_date_type" class="m3-trxd__row">
                    <span class="m3-trxd__label">{{ $t('transaction.detail.dueDate') }}</span>
                    <span class="m3-trxd__value m3-trxd__value--due">{{ dueDateText(transaction) }}</span>
                </div>

                <div class="m3-trxd__row">
                    <span class="m3-trxd__label">{{ $t('transaction.detail.note') }}</span>
                    <span class="m3-trxd__value m3-trxd__value--note">{{ transaction.notes || $t('transaction.detail.noNote') }}</span>
                </div>

                <div class="m3-trxd__row">
                    <span class="m3-trxd__label">{{ $t('transaction.detail.transactionId') }}</span>
                    <span class="m3-trxd__value">#{{ transaction.id }}</span>
                </div>
            </div>
        </div>

        <template #footer>
            <div v-if="transaction" class="m3-trxd__actions">
                <M3Button
                    v-if="!transaction.is_cleared"
                    variant="filled"
                    size="lg"
                    full-width
                    class="m3-trxd__draft-btn"
                    @click="showConfirmDraft = true"
                >
                    <template #leading>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    {{ $t('transaction.confirmDraft') }}
                </M3Button>

                <div class="m3-trxd__actions-row">
                    <M3Button
                        variant="outlined"
                        size="lg"
                        full-width
                        :href="route('transactions.edit', { transaction: transaction.id, is_draft: transaction.is_draft })"
                    >
                        <template #leading>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                            </svg>
                        </template>
                        {{ $t('transaction.detail.editBtn') }}
                    </M3Button>

                    <M3Button
                        variant="text"
                        size="lg"
                        full-width
                        class="m3-trxd__danger-text"
                        @click="showDeleteConfirm = true"
                    >
                        <template #leading>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </template>
                        {{ transaction.is_draft ? 'Batalkan Draft' : $t('transaction.detail.deleteBtn') }}
                    </M3Button>
                </div>
            </div>
        </template>
    </M3Modal>

    <!-- ─── Dialog: konfirmasi draft ─── -->
    <M3Modal
        :show="showConfirmDraft"
        :closeable="!isConfirming"
        max-width="sm"
        variant="dialog"
        mobile-variant="sheet"
        :aria-label="$t('transaction.confirmDraftQ')"
        @close="showConfirmDraft = false"
    >
        <div class="m3-trxd__dialog">
            <span class="m3-trxd__dialog-icon m3-trxd__dialog-icon--draft">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <h3 class="m3-trxd__dialog-title">{{ $t('transaction.confirmDraftQ') }}</h3>
            <p class="m3-trxd__dialog-text">{{ $t('transaction.confirmDraftDetail') }}</p>
            <p class="m3-trxd__dialog-warn">{{ $t('transaction.confirmDraftWarn') }}</p>
        </div>

        <template #footer>
            <M3Button variant="text" :disabled="isConfirming" @click="showConfirmDraft = false">
                {{ $t('btn.no') }}
            </M3Button>
            <M3Button
                variant="filled"
                class="m3-trxd__draft-btn"
                :disabled="isConfirming"
                @click="confirmDraft"
            >
                {{ isConfirming ? $t('common.processing') : $t('transaction.yesConfirm') }}
            </M3Button>
        </template>
    </M3Modal>

    <!-- ─── Dialog: konfirmasi hapus ─── -->
    <M3Modal
        :show="showDeleteConfirm"
        :closeable="!isDeleting"
        max-width="sm"
        variant="dialog"
        mobile-variant="sheet"
        :aria-label="$t('transaction.deleteTitle')"
        @close="showDeleteConfirm = false"
    >
        <div class="m3-trxd__dialog">
            <span class="m3-trxd__dialog-icon m3-trxd__dialog-icon--delete">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </span>
            <h3 class="m3-trxd__dialog-title">
                {{ transaction?.is_draft ? 'Batalkan Draft Transaksi' : $t('transaction.deleteTitle') }}
            </h3>
            <p class="m3-trxd__dialog-text m3-trxd__dialog-text--danger">
                {{ transaction?.is_draft ? 'Apakah Anda yakin ingin membatalkan draft transaksi ini?' : $t('transaction.deleteWarn') }}
            </p>
        </div>

        <template #footer>
            <M3Button variant="text" :disabled="isDeleting" @click="showDeleteConfirm = false">
                {{ $t('btn.no') }}
            </M3Button>
            <M3Button
                variant="filled"
                class="m3-trxd__danger-filled"
                :disabled="isDeleting"
                @click="deleteTransaction"
            >
                {{ isDeleting ? $t('common.deleting') : (transaction?.is_draft ? 'Ya, Batalkan' : $t('btn.yes')) }}
            </M3Button>
        </template>
    </M3Modal>
</template>

<style scoped>
.m3-trxd {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

/* ── Hero ── */
.m3-trxd__hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-trxd__hero-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container);
}

.m3-trxd__ico {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-trxd__cat {
    margin: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-trxd__badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
}

.m3-trxd__badge {
    display: inline-flex;
    align-items: center;
    min-height: var(--m3-space-6);
    padding: 0 var(--m3-space-3);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
}

/* Warna badge = warna semantik finansial (income/expense/transfer/debt/receivable) */
.m3-trxd__badge--income {
    background: var(--m3-color-income-container);
    color: var(--m3-color-on-income-container);
}

.m3-trxd__badge--expense {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-trxd__badge--transfer {
    background: var(--m3-color-transfer-container);
    color: var(--m3-color-on-transfer-container);
}

.m3-trxd__badge--debt,
.m3-trxd__badge--draft {
    background: var(--m3-color-debt-container);
    color: var(--m3-color-on-debt-container);
}

.m3-trxd__badge--receivable {
    background: var(--m3-color-receivable-container);
    color: var(--m3-color-on-receivable-container);
}

.m3-trxd__badge--neutral {
    background: var(--m3-color-surface-container);
    color: var(--m3-color-on-surface-variant);
}

/* ── Nominal ── */
.m3-trxd__amount-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
}

.m3-trxd__amount-label {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-trxd__amount {
    margin: 0;
    display: flex;
    align-items: baseline;
    gap: var(--m3-space-1);
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
}

.m3-trxd__amount--income { color: var(--m3-color-income); }
.m3-trxd__amount--expense { color: var(--m3-color-expense); }
.m3-trxd__amount--transfer { color: var(--m3-color-transfer); }

.m3-trxd__amount-currency {
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

/* ── Metadata ── */
.m3-trxd__rows {
    display: flex;
    flex-direction: column;
}

.m3-trxd__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    padding: var(--m3-space-3) 0;
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-trxd__row:last-child {
    border-bottom: none;
}

.m3-trxd__label {
    flex-shrink: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-trxd__value {
    min-width: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    text-align: end;
    overflow-wrap: anywhere;
}

.m3-trxd__value--wallet {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1-5);
}

.m3-trxd__wallet-name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-trxd__arrow {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
}

.m3-trxd__value--due {
    color: var(--m3-color-debt);
}

.m3-trxd__value--note {
    font-weight: var(--m3-type-body-medium-weight);
    font-style: italic;
    color: var(--m3-color-on-surface-variant);
}

/* ── Aksi ── */
.m3-trxd__actions {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    width: 100%;
}

.m3-trxd__actions-row {
    display: flex;
    gap: var(--m3-space-3);
}

.m3-trxd__actions-row > * {
    flex: 1;
}

.m3-trxd__danger-text {
    color: var(--m3-color-expense);
}

.m3-trxd__danger-filled {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-expense);
    box-shadow: none;
}

.m3-trxd__draft-btn {
    background: var(--m3-color-debt-container);
    color: var(--m3-color-on-debt-container);
    box-shadow: none;
}

/* ── Dialog konfirmasi ── */
.m3-trxd__dialog {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
    padding-top: var(--m3-space-2);
}

.m3-trxd__dialog-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    margin-bottom: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
}

.m3-trxd__dialog-icon svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-trxd__dialog-icon--draft {
    background: var(--m3-color-debt-container);
    color: var(--m3-color-on-debt-container);
}

.m3-trxd__dialog-icon--delete {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-trxd__dialog-title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-trxd__dialog-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-trxd__dialog-text--danger {
    color: var(--m3-color-expense);
}

.m3-trxd__dialog-warn {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-debt);
}
</style>
