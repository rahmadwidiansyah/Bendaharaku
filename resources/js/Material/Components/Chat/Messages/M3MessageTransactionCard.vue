<script setup>
/**
 * M3MessageTransactionCard — kartu transaksi di bubble bot (paritas
 * MessageTransactionCard Default; logika fetch/patch identik).
 * Termasuk: quick wallet picker (needs_wallet), draft actions, detail modal.
 */
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'
import { markStale } from '@/utils/stale.js'
import AppIcon from '@/Components/AppIcon.vue'
import M3ChatTransactionDetailModal from './M3ChatTransactionDetailModal.vue'
import M3DraftActions from './M3DraftActions.vue'
import M3QuickWalletPicker from './M3QuickWalletPicker.vue'

const { t } = useI18n()
const { showToast } = useToast()

const props = defineProps({
    component: { type: Object, required: true },
    metadata:  { type: Object, default: () => ({}) },
})

const showDetail = ref(false)
const detailInitialTab = ref('detail')

function openDetail() {
    if (localTrx.value.is_cancelled) return
    detailInitialTab.value = 'detail'
    showDetail.value = true
}

const trx = computed(() => props.component.transaction ?? {})

const localTrx    = ref({ ...trx.value })
const isAssigning = ref(false)
const isConfirmed = ref(false)

watch(trx, (newVal) => {
    if (!isConfirmed.value) {
        Object.assign(localTrx.value, newVal)
    }
}, { deep: true })

const apiId = computed(() => {
    if (props.component.is_draft && localTrx.value.draft_id) {
        return localTrx.value.draft_id
    }
    return localTrx.value.id
})

const typeConfig = computed(() => {
    const key = localTrx.value.type_key ?? 'other'
    const map = {
        income:     { label: t('types.income'),      icon: 'trending-up',     badge: 'm3-txn__badge--income' },
        expense:    { label: t('types.expense'),     icon: 'trending-down',   badge: 'm3-txn__badge--expense' },
        transfer:   { label: t('types.transfer'),    icon: 'arrow-left-right', badge: 'm3-txn__badge--transfer' },
        debt:       { label: t('types.debt'),        icon: 'hand-coins',      badge: 'm3-txn__badge--debt' },
        receivable: { label: t('types.receivable'),  icon: 'handshake',       badge: 'm3-txn__badge--receivable' },
        other:      { label: t('transaction.title'), icon: 'file-text',       badge: 'm3-txn__badge--neutral' },
    }
    return map[key] ?? map.other
})

const amountClass = computed(() => {
    const key = localTrx.value.type_key ?? 'other'
    const map = {
        income:     'm3-txn__amount--income',
        expense:    'm3-txn__amount--expense',
        transfer:   'm3-txn__amount--transfer',
        debt:       'm3-txn__amount--debt',
        receivable: 'm3-txn__amount--receivable',
    }
    return map[key] ?? ''
})

const needsWallet = computed(() =>
    !localTrx.value.is_cancelled
    && !localTrx.value.is_cleared
    && (localTrx.value.needs_wallet ?? props.component.needs_wallet)
)

const canShowDraftActions = computed(() =>
    !localTrx.value.is_cancelled
    && !localTrx.value.is_cleared
    && !needsWallet.value
)

function applyTransactionPatch(transactionPatch) {
    Object.assign(localTrx.value, transactionPatch)
}

function markCancelled() {
    applyTransactionPatch({
        is_cancelled: true,
        is_cleared: false,
        needs_wallet: false,
    })
}

async function checkStatus() {
    if (!apiId.value || localTrx.value.is_cancelled) return

    try {
        const routeName = props.component.is_draft ? 'chat.draft.status' : 'chat.transaction.status'
        const { data } = await axios.get(route(routeName, { id: apiId.value }))
        if (props.component.is_draft) {
            if (data.exists === false) {
                markCancelled()
                return
            }
            if (data.draft) {
                if (data.draft.is_cleared && !data.draft.is_draft) {
                    applyTransactionPatch({
                        ...data.draft,
                        id: data.draft.id,
                        draft_id: null,
                        is_draft: false,
                        is_cleared: true,
                    })
                    isConfirmed.value = true
                } else if (data.draft.is_cancelled) {
                    markCancelled()
                } else {
                    applyTransactionPatch(data.draft)
                }
            }
        } else {
            if (data.exists === false) {
                markCancelled()
                return
            }
            if (data.transaction) applyTransactionPatch(data.transaction)
        }
    } catch (e) {
        if (e.response?.status === 404) markCancelled()
    }
}

async function assignWallet({ walletId }) {
    isAssigning.value = true
    try {
        const { data } = await axios.patch(
            route('chat.transaction.assign-wallet', { id: apiId.value }),
            { wallet_id: walletId }
        )
        if (data.success) {
            applyTransactionPatch(data.transaction)
            isConfirmed.value = true
            markStale()
            showToast(t('toast.updated'), 'success')
        }
    } catch (e) {
        if (e.response?.status === 404) markCancelled()
        showToast(t('toast.error'), 'error')
        console.error('assignWallet error', e)
    } finally {
        isAssigning.value = false
    }
}

async function confirmDraft() {
    try {
        const { data } = await axios.patch(route('chat.transaction.confirm', { id: apiId.value }))
        if (data.success && data.transaction) {
            applyTransactionPatch({
                ...data.transaction,
                id:        data.transaction.id,
                draft_id:  undefined,
                is_draft:  false,
                is_cleared: true,
            })
            isConfirmed.value = true
            markStale()
            showToast(t('toast.saved'), 'success')
        }
    } catch (e) {
        if (e.response?.status === 404) markCancelled()
        showToast(t('toast.error'), 'error')
        console.error('confirmDraft error', e)
    }
}

async function cancelDraft() {
    try {
        const { data } = await axios.delete(route('chat.transaction.cancel', { id: apiId.value }))
        if (data.success) {
            markCancelled()
            showToast(t('toast.deleted'), 'success')
        }
    } catch (e) {
        if (e.response?.status === 404) markCancelled()
        showToast(t('toast.error'), 'error')
        console.error('cancelDraft error', e)
    }
}

function isMeaningful(v) {
    return v !== null && v !== undefined && String(v).trim() !== '-' && String(v).trim() !== ''
}

onMounted(checkStatus)
</script>

<template>
    <div
        class="m3-txn"
        role="button"
        tabindex="0"
        :aria-label="`${typeConfig.label} ${localTrx.amount_formatted}`"
        @click="openDetail"
        @keydown.enter.prevent="openDetail"
        @keydown.space.prevent="openDetail"
    >
        <div class="m3-txn__head">
            <div class="m3-txn__head-left">
                <span v-if="component.index !== null && component.index !== undefined" class="m3-txn__index">#{{ component.index }}</span>
                <span class="m3-txn__badge" :class="typeConfig.badge">
                    <AppIcon :icon="typeConfig.icon" iconClass="m3-txn__badge-icon" />
                    {{ localTrx.type_label ?? typeConfig.label }}
                </span>
            </div>
            <span
                class="m3-txn__status"
                :class="localTrx.is_cancelled
                    ? 'm3-txn__status--cancelled'
                    : localTrx.is_cleared
                        ? 'm3-txn__status--cleared'
                        : 'm3-txn__status--draft'"
            >
                <AppIcon v-if="localTrx.is_cancelled" icon="x" iconClass="m3-txn__status-icon" />
                <AppIcon v-else-if="localTrx.is_cleared" icon="check-circle-2" iconClass="m3-txn__status-icon" />
                <AppIcon v-else icon="clock-3" iconClass="m3-txn__status-icon" />
                {{ localTrx.is_cancelled ? t('transaction.cancelled') : (localTrx.is_cleared ? t('common.success') : t('transaction.draft')) }}
            </span>
        </div>

        <div class="m3-txn__amount-block">
            <p class="m3-txn__amount" :class="amountClass">{{ localTrx.amount_formatted }}</p>
            <p v-if="localTrx.notes" class="m3-txn__notes">{{ localTrx.notes }}</p>
        </div>

        <template v-if="component.show_details">
            <div class="m3-txn__rows">
                <div v-if="isMeaningful(localTrx.category)" class="m3-txn__row">
                    <AppIcon icon="folder" iconClass="m3-txn__row-icon" />
                    <span class="m3-txn__row-label">{{ t('transaction.detail.category') }}</span>
                    <span class="m3-txn__row-value">{{ localTrx.category }}</span>
                </div>
                <div v-if="isMeaningful(localTrx.source_wallet)" class="m3-txn__row">
                    <AppIcon icon="wallet" iconClass="m3-txn__row-icon" />
                    <span class="m3-txn__row-label">{{ t('transaction.detail.wallet') }}</span>
                    <span class="m3-txn__row-value">{{ localTrx.source_wallet }}</span>
                </div>
                <div v-if="isMeaningful(localTrx.dest_wallet)" class="m3-txn__row">
                    <AppIcon icon="arrow-down-to-line" iconClass="m3-txn__row-icon" />
                    <span class="m3-txn__row-label">{{ t('transaction.detail.to') }} {{ t('transaction.detail.wallet') }}</span>
                    <span class="m3-txn__row-value">{{ localTrx.dest_wallet }}</span>
                </div>
                <div v-if="isMeaningful(localTrx.subject)" class="m3-txn__row">
                    <AppIcon icon="user" iconClass="m3-txn__row-icon" />
                    <span class="m3-txn__row-label">{{ t('transaction.detail.party') }}</span>
                    <span class="m3-txn__row-value">{{ localTrx.subject }}</span>
                </div>
                <div v-if="isMeaningful(localTrx.date)" class="m3-txn__row">
                    <AppIcon icon="calendar" iconClass="m3-txn__row-icon" />
                    <span class="m3-txn__row-label">{{ t('transaction.detail.date') }}</span>
                    <span class="m3-txn__row-value">{{ localTrx.date }}</span>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="m3-txn__compact">
                <template v-if="isMeaningful(localTrx.category)">
                    <AppIcon icon="folder" iconClass="m3-txn__compact-icon" />
                    <span class="m3-txn__compact-text m3-txn__compact-text--strong">{{ localTrx.category }}</span>
                </template>
                <span
                    v-if="isMeaningful(localTrx.category) && isMeaningful(localTrx.source_wallet)"
                    class="m3-txn__compact-sep"
                    aria-hidden="true"
                />
                <template v-if="isMeaningful(localTrx.source_wallet)">
                    <AppIcon icon="wallet" iconClass="m3-txn__compact-icon" />
                    <span class="m3-txn__compact-text">{{ localTrx.source_wallet }}</span>
                </template>
                <span class="m3-txn__compact-hint">{{ t('transaction.detail.title') }} →</span>
            </div>
        </template>
    </div>

    <M3QuickWalletPicker
        v-if="needsWallet"
        :transaction-id="localTrx.id"
        :loading="isAssigning"
        @select="assignWallet"
    />

    <M3DraftActions
        v-if="canShowDraftActions"
        :transaction-id="localTrx.id"
        :edit-url="route('transactions.edit', { transaction: localTrx.id })"
        @confirm="confirmDraft"
        @cancel="cancelDraft"
    />

    <M3ChatTransactionDetailModal
        v-model="showDetail"
        :transaction="localTrx"
        :metadata="metadata"
        :initial-tab="detailInitialTab"
        @deleted="markCancelled"
    />
</template>

<style scoped>
.m3-txn {
    margin: var(--m3-space-1-5) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-lowest);
    overflow: hidden;
    cursor: pointer;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-txn:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 5%, var(--m3-color-surface-container-lowest));
}

.m3-txn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

/* Header */
.m3-txn__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2-5) var(--m3-space-3);
    border-bottom: 1px solid var(--m3-color-outline-variant);
    background: color-mix(in srgb, var(--m3-color-on-surface) 4%, transparent);
}

.m3-txn__head-left {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-txn__index {
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__badge {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    white-space: nowrap;
}

.m3-txn__badge-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
}

.m3-txn__badge--income {
    background: var(--m3-color-income-container);
    color: var(--m3-color-on-income-container);
}

.m3-txn__badge--expense {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-txn__badge--transfer {
    background: var(--m3-color-transfer-container);
    color: var(--m3-color-on-transfer-container);
}

.m3-txn__badge--debt {
    background: var(--m3-color-debt-container);
    color: var(--m3-color-on-debt-container);
}

.m3-txn__badge--receivable {
    background: var(--m3-color-receivable-container);
    color: var(--m3-color-on-receivable-container);
}

.m3-txn__badge--neutral {
    background: var(--m3-color-surface-container);
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__status {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    flex-shrink: 0;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    white-space: nowrap;
}

.m3-txn__status-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
}

.m3-txn__status--cancelled {
    background: var(--m3-color-surface-container);
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__status--cleared {
    background: var(--m3-color-income-container);
    color: var(--m3-color-on-income-container);
}

.m3-txn__status--draft {
    background: var(--m3-color-debt-container);
    color: var(--m3-color-on-debt-container);
}

/* Amount */
.m3-txn__amount-block {
    padding: var(--m3-space-2-5) var(--m3-space-3);
}

.m3-txn__amount {
    margin: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.01em;
    color: var(--m3-color-on-surface);
}

.m3-txn__amount--income { color: var(--m3-color-income); }
.m3-txn__amount--expense { color: var(--m3-color-expense); }
.m3-txn__amount--transfer { color: var(--m3-color-transfer); }
.m3-txn__amount--debt { color: var(--m3-color-debt); }
.m3-txn__amount--receivable { color: var(--m3-color-receivable); }

.m3-txn__notes {
    margin: var(--m3-space-0-5) 0 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Detail rows */
.m3-txn__rows > * + * {
    border-top: 1px solid color-mix(in srgb, var(--m3-color-outline-variant) 55%, transparent);
}

.m3-txn__row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2-5);
    padding: var(--m3-space-2) var(--m3-space-3);
}

.m3-txn__row-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__row-label {
    flex-shrink: 0;
    width: 4rem;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__row-value {
    min-width: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Compact */
.m3-txn__compact {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2-5) var(--m3-space-3);
    border-top: 1px solid var(--m3-color-outline-variant);
    background: color-mix(in srgb, var(--m3-color-on-surface) 3%, transparent);
}

.m3-txn__compact-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
}

.m3-txn__compact-text {
    min-width: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-txn__compact-text--strong {
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-txn__compact-sep {
    width: 0.25rem;
    height: 0.25rem;
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-on-surface-variant);
}

.m3-txn__compact-hint {
    margin-inline-start: auto;
    flex-shrink: 0;
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    color: var(--m3-color-primary);
}
</style>
