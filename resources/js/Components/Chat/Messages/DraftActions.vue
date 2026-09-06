<script setup>
/**
 * DraftActions.vue
 * Tombol aksi untuk transaksi Draft.
 * Muncul di bawah card transaksi yang belum dikonfirmasi.
 *
 * Props:
 *   transactionId — ID transaksi
 *   editUrl       — URL halaman edit (route transactions.edit)
 *
 * Emits:
 *   confirm — user klik Konfirmasi (tanpa wallet, jika wallet sudah ada)
 *   cancel  — user klik Batal
 */
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    transactionId: { type: Number, required: true },
    editUrl:       { type: String, required: true },
})

const emit = defineEmits(['confirm', 'cancel'])

const isConfirming = ref(false)
const isCancelling = ref(false)

async function handleConfirm() {
    isConfirming.value = true
    emit('confirm')
    // Parent akan reset state
    setTimeout(() => { isConfirming.value = false }, 2000)
}

async function handleCancel() {
    isCancelling.value = true
    emit('cancel')
    setTimeout(() => { isCancelling.value = false }, 2000)
}
</script>

<template>
    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
        <!-- Edit —→ buka halaman edit yang sudah ada -->
        <Link
            :href="editUrl"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-gray-300 bg-white/6 hover:bg-white/10 border border-[var(--color-border-subtle)] hover:border-white/15 transition-all active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-[var(--color-brand)]"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ t('common.edit') }}
        </Link>

        <!-- Konfirmasi -->
        <button
            type="button"
            :disabled="isConfirming"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-emerald-400 disabled:opacity-50"
            :class="isConfirming ? 'bg-income-bg text-income-text border border-income-border' : 'bg-income-bg text-income-text hover:bg-income-bg-hover border border-income-border hover:border-income-border'"
            @click="handleConfirm"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            {{ isConfirming ? t('common.saving') : t('common.confirm') }}
        </button>

        <!-- Batal -->
        <button
            type="button"
            :disabled="isCancelling"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-red-400 disabled:opacity-50"
            :class="isCancelling ? 'bg-expense-bg text-expense-text border border-expense-border' : 'bg-transparent text-[var(--color-text-muted)] hover:text-expense-text hover:bg-expense-bg-hover border border-transparent hover:border-expense-border'"
            @click="handleCancel"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            {{ t('common.cancel') }}
        </button>
    </div>
</template>
