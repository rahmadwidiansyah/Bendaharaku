<script setup>
/**
 * TransactionDetailModal.vue
 *
 * Modal detail transaksi — dipakai di Dashboard dan halaman lain.
 *
 * Features:
 *   - Menampilkan semua detail transaksi (nominal, kategori, wallet, tanggal, catatan, due date)
 *   - Tombol Edit & Hapus
 *   - Tombol "Konfirmasi Transaksi" — hanya muncul jika is_cleared === false (Draft)
 *   - Dialog konfirmasi sebelum hapus & sebelum konfirmasi draft
 *   - Pakai BaseModal → Teleport ke body, tidak terjebak stacking context
 */

import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { route } from 'ziggy-js'
import BaseModal from '@/Components/BaseModal.vue'
import Badge from '@/Components/Badge.vue'
import AppIcon from '@/Components/AppIcon.vue'
import DetailRow from '@/Components/DetailRow.vue'
import { getCategoryIconColor } from '@/Composables/useIcon.js'
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

// ─── Helpers ─────────────────────────────────────────────────────
const typeVariant = (name) => ({
    Income:     'income',
    Expense:    'expense',
    Transfer:   'transfer',
    Debt:       'debt',
    Receivable: 'receivable',
}[name] ?? 'neutral')

const getTypeName = (name) => ({
    Income:     t('types.income'),
    Expense:    t('types.expense'),
    Transfer:   t('types.transfer'),
    Debt:       t('types.debt'),
    Receivable: t('types.receivable'),
}[name] ?? name)

const amountColor = (trx) => {
    if (!trx?.type) return 'text-white'
    const name = trx.type.name
    if (name === 'Income') return 'text-green-400'
    if (name === 'Transfer') return 'text-blue-400'
    if (['Debt', 'Receivable'].includes(name) && trx.source_wallet?.group_type === 'System') return 'text-green-400'
    return 'text-red-400'
}

const amountPrefix = (trx) => {
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
    <!-- Main detail modal -->
    <BaseModal
        :show="show && !showDeleteConfirm && !showConfirmDraft"
        :title="null"
        :show-close-btn="true"
        max-width="sm"
        @close="emit('close')"
    >
        <!-- ===== Slot #default: Header + Hero + Metadata ===== -->
        <template #default>
            <div v-if="transaction" class="w-full font-mono text-gray-200">
                <div class="overflow-y-auto w-full max-h-[calc(100dvh-260px)]">
                    <!-- ── Header: Icon + Title + Badge ─────────────────── -->
                    <div class="flex flex-col items-center mt-2 mb-6">
                        <AppIcon :icon="transaction.category?.icon" fallback="file-text" :class="['w-11 h-11 mb-3', getCategoryIconColor(transaction.type?.name)]" />
                        <h2 class="text-xl font-bold tracking-wide text-white mb-2 text-center break-words leading-tight">
                            {{ transaction.category?.category_name || 'Transfer' }}
                        </h2>
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <Badge :variant="typeVariant(transaction.type?.name)" size="md" pill>
                                {{ getTypeName(transaction.type?.name) }}
                            </Badge>
                            <Badge v-if="!transaction.is_cleared" variant="warning" size="md" pill>
                                {{ $t('transaction.draft') }}
                            </Badge>
                        </div>
                    </div>

                    <!-- ===== Amount — Hero Section ===== -->
                    <div class="border border-white/10 bg-white/5 rounded-2xl px-4 py-5 mb-6 text-center">
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-[0.2em] mb-2">{{ $t('transaction.amount') }}</p>
                        <p :class="['text-3xl font-bold tracking-tight tabular-nums', amountColor(transaction)]">
                            <span class="flex items-center justify-center gap-2">
                                <span>{{ amountPrefix(transaction) }}</span>
                                <span class="whitespace-nowrap">
                                    <span class="text-lg text-gray-500 mr-1 font-semibold">Rp</span>{{ formatNumber(transaction.amount) }}
                                </span>
                            </span>
                        </p>
                    </div>

                    <!-- ===== Metadata Section (tanpa box, garis membentang penuh) ===== -->
                    <div class="flex flex-col text-sm divide-y divide-white/10 border-t border-white/10">
                        <!-- Tanggal & waktu -->
                        <DetailRow :label="$t('transaction.detail.date')">
                            <span class="font-semibold text-gray-300">{{ transaction.date }} • {{ transaction.time }}</span>
                        </DetailRow>

                        <!-- Dompet -->
                        <DetailRow :label="$t('transaction.detail.wallet')">
                            <div class="flex items-center justify-end gap-1.5 min-w-0">
                                <span class="truncate font-semibold text-gray-300">{{ transaction.source_wallet?.name }}</span>
                                <svg v-if="transaction.destination_wallet?.name" class="w-3.5 h-3.5 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span v-if="transaction.destination_wallet?.name" class="truncate font-semibold text-gray-300">{{ transaction.destination_wallet.name }}</span>
                            </div>
                        </DetailRow>

                        <!-- Pelaku (subject) -->
                        <DetailRow v-if="transaction.subject && transaction.subject !== '-'" :label="$t('transaction.detail.party')">
                            <span class="font-semibold text-gray-300">{{ transaction.subject }}</span>
                        </DetailRow>

                        <!-- Jatuh tempo -->
                        <DetailRow v-if="transaction.due_date_type" :label="$t('transaction.detail.dueDate')">
                            <span class="font-bold text-yellow-400">{{ dueDateText(transaction) }}</span>
                        </DetailRow>

                        <!-- Catatan -->
                        <DetailRow :label="$t('transaction.detail.note')">
                            <span class="font-medium text-gray-400 italic truncate">{{ transaction.notes || $t('transaction.detail.noNote') }}</span>
                        </DetailRow>

                        <!-- ID transaksi -->
                        <DetailRow :label="$t('transaction.detail.transactionId')">
                            <span class="font-semibold text-gray-300">#{{ transaction.id }}</span>
                        </DetailRow>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== Slot #footer: Action Buttons (selalu terlihat) ===== -->
        <template #footer>
            <div v-if="transaction" class="flex flex-col gap-3 w-full border-t border-white/10 font-mono">
                <!-- Konfirmasi Draft -->
                <button
                    v-if="!transaction.is_cleared"
                    type="button"
                    @click="showConfirmDraft = true"
                    class="w-full py-3.5 rounded-2xl border border-amber-500/30 text-amber-400 text-xs font-bold uppercase tracking-widest hover:bg-amber-500/10 transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $t('transaction.confirmDraft') }}
                </button>

                <!-- Edit & Hapus -->
                <div class="flex gap-3">
                    <Link
                        :href="route('transactions.edit', { transaction: transaction.id, is_draft: transaction.is_draft })"
                        class="flex-1 py-3.5 rounded-2xl border border-white/10 text-gray-300 text-xs font-bold uppercase tracking-widest hover:bg-white/5 transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                        </svg>
                        {{ $t('transaction.detail.editBtn') }}
                    </Link>
                    <button
                        type="button"
                        @click="showDeleteConfirm = true"
                        class="flex-1 py-3.5 rounded-2xl border border-red-500/15 text-red-500 text-xs font-bold uppercase tracking-widest hover:bg-red-500/10 hover:border-red-500/30 transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ transaction.is_draft ? 'Batalkan Draft' : $t('transaction.detail.deleteBtn') }}
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>

    <!-- ─── Dialog: Konfirmasi Draft ──────────────────────────────── -->
    <BaseModal
        :show="showConfirmDraft"
        :closeable="!isConfirming"
        :show-close-btn="!isConfirming"
        max-width="sm"
        @close="showConfirmDraft = false"
    >
        <div class="text-center px-1">
            <div class="w-14 h-14 rounded-full bg-amber-500/15 text-amber-400 mx-auto flex items-center justify-center mb-4 border border-amber-500/20">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-base font-black text-white mb-2 tracking-tight">{{ $t('transaction.confirmDraftQ') }}</h3>
            <p class="text-2xs text-gray-400 leading-relaxed mb-1">
                {{ $t('transaction.confirmDraftDetail') }}
            </p>
            <p class="text-2xs text-amber-400/80 mb-6">
                {{ $t('transaction.confirmDraftWarn') }}
            </p>
        </div>

        <template #footer>
            <button
                type="button"
                :disabled="isConfirming"
                @click="showConfirmDraft = false"
                class="flex-1 py-3 rounded-xl bg-gray-800 border border-white/10 text-gray-300 text-2xs font-bold uppercase tracking-widest hover:border-white/20 transition-all disabled:opacity-50">
                {{ $t('btn.no') }}
            </button>
            <button
                type="button"
                :disabled="isConfirming"
                @click="confirmDraft"
                class="flex-1 py-3 rounded-xl bg-amber-500 text-gray-900 text-2xs font-black uppercase tracking-widest hover:bg-amber-400 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2">
                <svg v-if="isConfirming" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ isConfirming ? $t('common.processing') : $t('transaction.yesConfirm') }}
            </button>
        </template>
    </BaseModal>

    <!-- ─── Dialog: Hapus ─────────────────────────────────────────── -->
    <BaseModal
        :show="showDeleteConfirm"
        :closeable="!isDeleting"
        :show-close-btn="!isDeleting"
        max-width="sm"
        @close="showDeleteConfirm = false"
    >
        <div class="text-center px-1">
            <div class="w-14 h-14 rounded-full bg-red-500/15 text-red-400 mx-auto flex items-center justify-center mb-4 border border-red-500/20">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-base font-black text-white mb-2 tracking-tight">
                {{ transaction.is_draft ? 'Batalkan Draft Transaksi' : $t('transaction.deleteTitle') }}
            </h3>
            <p class="text-sm text-red-200/80 leading-relaxed mb-6">
                {{ transaction.is_draft ? 'Apakah Anda yakin ingin membatalkan draft transaksi ini?' : $t('transaction.deleteWarn') }}
            </p>
        </div>

        <template #footer>
            <button
                type="button"
                :disabled="isDeleting"
                @click="showDeleteConfirm = false"
                class="flex-1 py-3 rounded-xl bg-gray-800 border border-white/10 text-gray-300 text-2xs font-bold uppercase tracking-widest hover:border-white/20 transition-all disabled:opacity-50">
                {{ $t('btn.no') }}
            </button>
            <button
                type="button"
                :disabled="isDeleting"
                @click="deleteTransaction"
                class="flex-1 py-3 rounded-xl bg-red-600 text-white text-2xs font-black uppercase tracking-widest hover:bg-red-500 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2">
                <svg v-if="isDeleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ isDeleting ? (transaction.is_draft ? 'Membatalkan...' : $t('common.deleting')) : (transaction.is_draft ? 'Ya, Batalkan' : $t('btn.yes')) }}
            </button>
        </template>
    </BaseModal>
</template>
