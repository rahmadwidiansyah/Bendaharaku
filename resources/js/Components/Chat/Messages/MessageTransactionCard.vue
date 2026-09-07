<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'
import { markStale } from '@/utils/stale.js'
import AppIcon from '@/Components/AppIcon.vue'
import TransactionDetailModal from './TransactionDetailModal.vue'
import DraftActions from './DraftActions.vue'
import QuickWalletPicker from './QuickWalletPicker.vue'

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

// Local mutable copy of trx so UI can update reactively after API calls
const localTrx    = ref({ ...trx.value })
const isAssigning = ref(false)
const isConfirmed = ref(false)

// Keep localTrx in sync if parent data changes (e.g. history reload)
watch(trx, (newVal) => {
    if (!isConfirmed.value) {
        Object.assign(localTrx.value, newVal)
    }
}, { deep: true })

/**
 * ID yang digunakan untuk API calls.
 * - Jika transaksi masih draft (is_draft=true), gunakan draft_id
 * - Jika sudah dikonfirmasi / bukan draft, gunakan transaction_log id (backward compat)
 */
const apiId = computed(() => {
    if (props.component.is_draft && localTrx.value.draft_id) {
        return localTrx.value.draft_id
    }
    return localTrx.value.id
})

const typeConfig = computed(() => ({
    income:   { label: t('types.income'),      icon: 'trending-up',     color: 'text-income-text', bg: 'bg-income-bg',      border: 'border-income-border',    badge: 'bg-income-bg text-income-text border-income-border' },
    expense:  { label: t('types.expense'),     icon: 'trending-down',   color: 'text-expense-text', bg: 'bg-expense-bg',    border: 'border-expense-border',   badge: 'bg-expense-bg text-expense-text border-expense-border' },
    transfer: { label: t('types.transfer'),    icon: 'arrow-left-right', color: 'text-transfer-text', bg: 'bg-transfer-bg',  border: 'border-transfer-border',  badge: 'bg-transfer-bg text-transfer-text border-transfer-border' },
    debt:     { label: t('types.debt'),        icon: 'hand-coins',      color: 'text-debt-text',   bg: 'bg-debt-bg',        border: 'border-debt-border',      badge: 'bg-debt-bg text-debt-text border-debt-border' },
    receivable: { label: t('types.receivable'), icon: 'handshake',       color: 'text-receivable-text', bg: 'bg-receivable-bg', border: 'border-receivable-border', badge: 'bg-receivable-bg text-receivable-text border-receivable-border' },
    other:    { label: t('transaction.title'), icon: 'file-text',       color: 'text-[var(--color-text-secondary)]',    bg: 'bg-gray-500/8',     border: 'border-gray-500/15',    badge: 'bg-gray-500/12 text-[var(--color-text-secondary)] border-gray-500/20' },
}[localTrx.value.type_key ?? 'other'] ?? { label: t('transaction.title'), icon: 'file-text', color: 'text-[var(--color-text-secondary)]', bg: 'bg-gray-500/8', border: 'border-gray-500/15', badge: 'bg-gray-500/12 text-[var(--color-text-secondary)] border-gray-500/20' }))

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

onMounted(checkStatus)
</script>

<template>
    <div class="overflow-hidden cursor-pointer transition-all active:scale-98 hover:bg-white/5 border-b border-[var(--color-border-default)] last:border-none" @click="openDetail" role="button" :aria-label="`${typeConfig.label} ${localTrx.amount_formatted}`">
        <!-- Header: badge + status -->
        <div class="flex items-center justify-between px-3.5 pt-3 pb-2 bg-white/5 border-b border-white/5">
            <div class="flex items-center gap-2">
                <span v-if="component.index !== null && component.index !== undefined"
                    class="text-2xs font-black text-[var(--color-text-muted)] tabular-nums">#{{ component.index }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full border inline-flex items-center gap-1" :class="typeConfig.badge">
                    <AppIcon :icon="typeConfig.icon" iconClass="w-3 h-3 shrink-0" />
                    {{ localTrx.type_label ?? typeConfig.label }}
                </span>
            </div>
            <span :class="[
                'text-2xs font-bold px-1.5 py-0.5 rounded-full border inline-flex items-center gap-1 shrink-0',
                localTrx.is_cancelled
                    ? 'text-[var(--color-text-secondary)] bg-gray-500/10 border-gray-500/20'
                    : localTrx.is_cleared
                    ? 'text-income-text bg-income-bg border-income-border'
                    : 'text-debt-text bg-debt-bg border-debt-border'
            ]">
                <AppIcon v-if="localTrx.is_cancelled" icon="x" iconClass="w-3 h-3 shrink-0" />
                <AppIcon v-else-if="localTrx.is_cleared" icon="check-circle-2" iconClass="w-3 h-3 shrink-0" />
                <AppIcon v-else icon="clock-3" iconClass="w-3 h-3 shrink-0" />
                {{ localTrx.is_cancelled ? t('transaction.cancelled') : (localTrx.is_cleared ? t('common.success') : t('transaction.draft')) }}
            </span>
        </div>

        <!-- Amount -->
        <div class="px-3.5 py-2.5 border-t border-white/5">
            <p class="text-xl font-black text-[var(--color-text-primary)] tabular-nums tracking-tight leading-tight">
                {{ localTrx.amount_formatted }}
            </p>
            <p v-if="localTrx.notes" class="text-xs text-[var(--color-text-secondary)] mt-0.5 truncate">{{ localTrx.notes }}</p>
        </div>

        <!-- Detail rows (show_details mode) -->
        <template v-if="component.show_details">
            <div class="border-t border-white/5 divide-y divide-white/5">
                <div v-if="localTrx.category && localTrx.category !== '-' && String(localTrx.category).trim() !== '-' && String(localTrx.category).trim() !== ''" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="folder" iconClass="w-4 h-4 shrink-0 text-[var(--color-text-muted)]" />
                    <span class="text-2xs text-[var(--color-text-muted)] w-16 shrink-0">{{ $t('transaction.detail.category') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.category }}</span>
                </div>
                <div v-if="localTrx.source_wallet && localTrx.source_wallet !== '-' && String(localTrx.source_wallet).trim() !== '-' && String(localTrx.source_wallet).trim() !== ''" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="wallet" iconClass="w-4 h-4 shrink-0 text-[var(--color-text-muted)]" />
                    <span class="text-2xs text-[var(--color-text-muted)] w-16 shrink-0">{{ $t('transaction.detail.wallet') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.source_wallet }}</span>
                </div>
                <div v-if="localTrx.dest_wallet && localTrx.dest_wallet !== '-' && String(localTrx.dest_wallet).trim() !== '-' && String(localTrx.dest_wallet).trim() !== ''" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="arrow-down-to-line" iconClass="w-4 h-4 shrink-0 text-[var(--color-text-muted)]" />
                    <span class="text-2xs text-[var(--color-text-muted)] w-16 shrink-0">{{ $t('transaction.detail.to') }} {{ $t('transaction.detail.wallet') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.dest_wallet }}</span>
                </div>
                <div v-if="localTrx.subject && localTrx.subject !== '-' && String(localTrx.subject).trim() !== '-' && String(localTrx.subject).trim() !== ''" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="user" iconClass="w-4 h-4 shrink-0 text-[var(--color-text-muted)]" />
                    <span class="text-2xs text-[var(--color-text-muted)] w-16 shrink-0">{{ $t('transaction.detail.party') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.subject }}</span>
                </div>
                <div v-if="localTrx.date && localTrx.date !== '-' && String(localTrx.date).trim() !== '-' && String(localTrx.date).trim() !== ''" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="calendar" iconClass="w-4 h-4 shrink-0 text-[var(--color-text-muted)]" />
                    <span class="text-2xs text-[var(--color-text-muted)] w-16 shrink-0">{{ $t('transaction.detail.date') }}</span>
                    <span class="text-xs text-gray-200 font-medium">{{ localTrx.date }}</span>
                </div>
            </div>
        </template>

        <!-- Compact mode (multi-transaction list item) -->
        <template v-else>
            <div class="flex items-center gap-2 px-3.5 py-2.5 bg-[var(--color-surface-muted)]/20 border-t border-white/5">
                <AppIcon v-if="localTrx.category && localTrx.category !== '-' && String(localTrx.category).trim() !== '-' && String(localTrx.category).trim() !== ''" icon="folder" iconClass="w-3.5 h-3 shrink-0 text-[var(--color-text-muted)]" />
                <span v-if="localTrx.category && localTrx.category !== '-' && String(localTrx.category).trim() !== '-' && String(localTrx.category).trim() !== ''" class="text-xs font-medium text-[var(--color-text-secondary)] truncate">{{ localTrx.category }}</span>
                <span v-if="localTrx.source_wallet && localTrx.source_wallet !== '-' && String(localTrx.source_wallet).trim() !== '-' && String(localTrx.source_wallet).trim() !== '' && localTrx.category && localTrx.category !== '-' && String(localTrx.category).trim() !== '-' && String(localTrx.category).trim() !== ''" class="w-1 h-1 rounded-full bg-[var(--color-text-muted)] shrink-0"></span>
                <AppIcon v-if="localTrx.source_wallet && localTrx.source_wallet !== '-' && String(localTrx.source_wallet).trim() !== '-' && String(localTrx.source_wallet).trim() !== ''" icon="wallet" iconClass="w-3.5 h-3 shrink-0 text-[var(--color-text-muted)]" />
                <span v-if="localTrx.source_wallet && localTrx.source_wallet !== '-' && String(localTrx.source_wallet).trim() !== '-' && String(localTrx.source_wallet).trim() !== ''" class="text-xs text-[var(--color-text-secondary)] truncate">{{ localTrx.source_wallet }}</span>
                <!-- Tap to detail hint -->
                <span class="ml-auto text-xs font-semibold text-[var(--color-brand)] shrink-0">Detail →</span>
            </div>
        </template>
    </div>

    <!-- Quick wallet picker — tampil jika needs_wallet dan belum confirmed -->
    <QuickWalletPicker
        v-if="needsWallet"
        :transaction-id="localTrx.id"
        :loading="isAssigning"
        @select="assignWallet"
    />

    <!-- Draft actions — tampil hanya jika sudah ada wallet (needs_wallet=false) dan masih draft -->
    <DraftActions
        v-if="canShowDraftActions"
        :transaction-id="localTrx.id"
        :edit-url="route('transactions.edit', { transaction: localTrx.id })"
        @confirm="confirmDraft"
        @cancel="cancelDraft"
    />

    <TransactionDetailModal
        v-model="showDetail"
        :transaction="localTrx"
        :metadata="metadata"
        :initial-tab="detailInitialTab"
        @deleted="markCancelled"
    />
</template>
