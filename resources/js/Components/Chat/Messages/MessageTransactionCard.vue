<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'
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
    other:    { label: t('transaction.title'), icon: 'file-text',       color: 'text-gray-400',    bg: 'bg-gray-500/8',     border: 'border-gray-500/15',    badge: 'bg-gray-500/12 text-gray-400 border-gray-500/20' },
}[localTrx.value.type_key ?? 'other'] ?? { label: t('transaction.title'), icon: 'file-text', color: 'text-gray-400', bg: 'bg-gray-500/8', border: 'border-gray-500/15', badge: 'bg-gray-500/12 text-gray-400 border-gray-500/20' }))

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
    <div class="overflow-hidden cursor-pointer transition-all active:scale-98 hover:bg-white/5 border-b border-white/10 last:border-none" @click="!localTrx.is_cancelled && (showDetail = true)" role="button" :aria-label="`${typeConfig.label} ${localTrx.amount_formatted}`">
        <!-- Header: badge + status di kanan -->
        <div class="flex items-center justify-between px-3.5 pt-3 pb-2 bg-white/5 border-b border-white/5">
            <div class="flex items-center gap-2">
                <span v-if="component.index !== null && component.index !== undefined"
                    class="text-2xs font-black text-gray-600 tabular-nums">#{{ component.index }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full border inline-flex items-center gap-1" :class="typeConfig.badge">
                    <AppIcon :icon="typeConfig.icon" class="w-3 h-3 shrink-0" />
                    {{ localTrx.type_label ?? typeConfig.label }}
                </span>
            </div>
            <span :class="[
                'text-2xs font-bold px-1.5 py-0.5 rounded-full border inline-flex items-center gap-1',
                localTrx.is_cancelled
                    ? 'text-gray-400 bg-gray-500/10 border-gray-500/20'
                    : localTrx.is_cleared
                    ? 'text-income-text bg-income-bg border-income-border'
                    : 'text-debt-text bg-debt-bg border-debt-border'
            ]">
                <AppIcon v-if="localTrx.is_cancelled" icon="x" class="w-3 h-3 shrink-0" />
                <AppIcon v-else-if="localTrx.is_cleared" icon="check-circle-2" class="w-3 h-3 shrink-0" />
                <AppIcon v-else icon="clock-3" class="w-3 h-3 shrink-0" />
                {{ localTrx.is_cancelled ? t('transaction.cancelled') : (localTrx.is_cleared ? t('common.success') : t('transaction.draft')) }}
            </span>
        </div>

        <!-- Amount -->
        <div class="px-3.5 py-2.5 border-t border-white/5">
            <p class="text-xl font-black text-white tabular-nums tracking-tight leading-tight">
                {{ localTrx.amount_formatted }}
            </p>
            <p v-if="localTrx.notes" class="text-2xs text-gray-500 mt-0.5 truncate">{{ localTrx.notes }}</p>
        </div>

        <!-- Detail rows (show_details mode) -->
        <template v-if="component.show_details">
            <div class="border-t border-white/5 divide-y divide-white/5">
                <div v-if="localTrx.category" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="folder" class="w-4 h-4 shrink-0 text-gray-500" />
                    <span class="text-2xs text-gray-500 w-16 shrink-0">{{ $t('transaction.detail.category') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.category }}</span>
                </div>
                <div v-if="localTrx.source_wallet" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="wallet" class="w-4 h-4 shrink-0 text-gray-500" />
                    <span class="text-2xs text-gray-500 w-16 shrink-0">{{ $t('transaction.detail.wallet') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.source_wallet }}</span>
                </div>
                <div v-if="localTrx.dest_wallet" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="arrow-down-to-line" class="w-4 h-4 shrink-0 text-gray-500" />
                    <span class="text-2xs text-gray-500 w-16 shrink-0">{{ $t('transaction.detail.to') }} {{ $t('transaction.detail.wallet') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.dest_wallet }}</span>
                </div>
                <div v-if="localTrx.subject" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="user" class="w-4 h-4 shrink-0 text-gray-500" />
                    <span class="text-2xs text-gray-500 w-16 shrink-0">{{ $t('transaction.detail.party') }}</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ localTrx.subject }}</span>
                </div>
                <div v-if="localTrx.date" class="flex items-center gap-2.5 px-3.5 py-2">
                    <AppIcon icon="calendar" class="w-4 h-4 shrink-0 text-gray-500" />
                    <span class="text-2xs text-gray-500 w-16 shrink-0">{{ $t('transaction.detail.date') }}</span>
                    <span class="text-xs text-gray-200 font-medium">{{ localTrx.date }}</span>
                </div>
            </div>
        </template>

        <!-- Compact mode (multi-transaction list item) -->
        <template v-else>
            <div class="flex items-center gap-1.5 px-3.5 pb-2.5 border-t border-white/5 pt-1.5">
                <span v-if="localTrx.category" class="text-2xs text-gray-500">{{ localTrx.category }}</span>
                <span v-if="localTrx.source_wallet && localTrx.category" class="text-2xs text-gray-700">·</span>
                <span v-if="localTrx.source_wallet" class="text-2xs text-gray-500">{{ localTrx.source_wallet }}</span>
                <!-- Tap to detail hint -->
                <span class="ml-auto text-2xs text-gray-700">{{ $t('transaction.detail.title') }} →</span>
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
        @deleted="markCancelled"
    />
</template>
