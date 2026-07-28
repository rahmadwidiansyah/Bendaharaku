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
import BaseModal from '@/Components/BaseModal.vue'
import Badge from '@/Components/Badge.vue'
import AppIcon from '@/Components/AppIcon.vue'
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

const dueDateLabel = (type) => ({ fixed: 'Sekali', monthly: 'Bulanan', daily: 'Per Hari' }[type] ?? type)
const dueDateDetail = (trx) => {
    if (trx.due_date_type === 'fixed')   return formatDate(trx.due_date)
    if (trx.due_date_type === 'monthly') return `Tgl ${trx.due_date_interval}`
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
        <div v-if="transaction" class="px-1">
            <!-- Category icon + name -->
            <div class="flex flex-col items-center mb-5 mt-1">
                <AppIcon :icon="transaction.category?.icon" fallback="file-text" class="w-14 h-14 text-purple-400 mb-3" />
                <p class="text-lg font-black text-white text-center leading-tight">
                    {{ transaction.category?.category_name || 'Transfer' }}
                </p>
                <div class="flex items-center gap-2 mt-1.5">
                    <Badge :variant="typeVariant(transaction.type?.name)" size="sm">
                        {{ getTypeName(transaction.type?.name) }}
                    </Badge>
                    <!-- Draft badge -->
                    <span
                        v-if="!transaction.is_cleared"
                        class="inline-flex items-center gap-1 text-2xs font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-400 border border-amber-500/30">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                        {{ $t('transaction.draft') }}
                    </span>
                </div>
            </div>

            <!-- Amount -->
            <div class="bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 rounded-xl p-4 text-center mb-4">
                <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest mb-1">{{ $t('transaction.amount') }}</p>
                <h2 :class="['text-3xl font-black tracking-tight', amountColor(transaction)]">
                    {{ amountPrefix(transaction) }}
                    <span class="text-xl text-gray-500 mr-0.5">Rp</span>{{ formatNumber(transaction.amount) }}
                </h2>
            </div>

            <!-- Detail rows -->
            <div class="space-y-3 mb-5 text-2xs">
                <!-- ID transaksi -->
                <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="text-gray-500 font-bold uppercase tracking-widest">{{ $t('transaction.detail.transactionId') }}</span>
                    <span class="font-bold text-gray-300">#{{ transaction.id }}</span>
                </div>

                <!-- Tanggal & waktu -->
                <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="text-gray-500 font-bold uppercase tracking-widest">{{ $t('transaction.detail.date') }}</span>
                    <span class="font-bold text-gray-300">{{ transaction.date }} • {{ transaction.time }}</span>
                </div>

                <!-- Dompet -->
                <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="text-gray-500 font-bold uppercase tracking-widest">{{ $t('transaction.detail.wallet') }}</span>
                    <div class="flex items-center gap-1.5 font-bold text-gray-300">
                        <span>{{ transaction.source_wallet?.name }}</span>
                        <svg class="w-3 h-3 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <span>{{ transaction.destination_wallet?.name }}</span>
                    </div>
                </div>

                <!-- Pelaku (subject) -->
                <div v-if="transaction.subject && transaction.subject !== '-'" class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="text-gray-500 font-bold uppercase tracking-widest">{{ $t('transaction.detail.party') }}</span>
                    <span class="font-bold text-gray-300">{{ transaction.subject }}</span>
                </div>

                <!-- Jatuh tempo -->
                <div v-if="transaction.due_date_type" class="flex justify-between items-start py-2 border-b border-white/5">
                    <span class="text-gray-500 font-bold uppercase tracking-widest">{{ $t('transaction.detail.dueDate') }}</span>
                    <div class="text-right">
                        <p class="font-bold text-yellow-400">{{ dueDateLabel(transaction.due_date_type) }}</p>
                        <p class="text-gray-500 mt-0.5">{{ dueDateDetail(transaction) }}</p>
                    </div>
                </div>

                <!-- Catatan -->
                <div class="flex justify-between items-start py-2">
                    <span class="text-gray-500 font-bold uppercase tracking-widest">{{ $t('transaction.detail.note') }}</span>
                    <span class="text-right italic text-gray-400 max-w-[60%]">
                        {{ transaction.notes || $t('transaction.detail.noNote') }}
                    </span>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-col gap-2">
                <!-- Konfirmasi Draft — hanya muncul saat is_cleared = false -->
                <button
                    v-if="!transaction.is_cleared"
                    type="button"
                    @click="showConfirmDraft = true"
                    class="w-full py-3 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400 text-2xs font-black uppercase tracking-widest hover:bg-amber-500 hover:text-gray-900 hover:border-amber-500 transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $t('transaction.confirmDraft') }}
                </button>

                <!-- Edit & Hapus -->
                <div class="flex gap-2">
                    <Link
                        :href="route('transactions.edit', { transaction: transaction.id, is_draft: transaction.is_draft })"
                        class="flex-1 py-3 rounded-xl bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 text-gray-300 text-2xs font-bold uppercase tracking-widest hover:border-purple-500/40 hover:text-white transition-all active:scale-[0.98] flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                        </svg>
                        {{ $t('transaction.detail.editBtn') }}
                    </Link>
                    <button
                        type="button"
                        @click="showDeleteConfirm = true"
                        class="flex-1 py-3 rounded-xl bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 text-red-500 text-2xs font-bold uppercase tracking-widest hover:bg-red-500/10 hover:border-red-500/30 transition-all active:scale-[0.98] flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ transaction.is_draft ? 'Batalkan Draft' : $t('transaction.detail.deleteBtn') }}
                    </button>
                </div>
            </div>
        </div>
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
