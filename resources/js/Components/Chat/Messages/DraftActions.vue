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
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-gray-300 bg-white/6 hover:bg-white/10 border border-white/8 hover:border-white/15 transition-all active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
        </Link>

        <!-- Konfirmasi -->
        <button
            type="button"
            :disabled="isConfirming"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-emerald-400 disabled:opacity-50"
            :class="isConfirming ? 'bg-emerald-600/30 text-emerald-400 border border-emerald-500/30' : 'bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600/30 border border-emerald-500/20 hover:border-emerald-500/40'"
            @click="handleConfirm"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            {{ isConfirming ? 'Menyimpan...' : 'Konfirmasi' }}
        </button>

        <!-- Batal -->
        <button
            type="button"
            :disabled="isCancelling"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-red-400 disabled:opacity-50"
            :class="isCancelling ? 'bg-red-600/20 text-red-400 border border-red-500/20' : 'bg-transparent text-gray-500 hover:text-red-400 hover:bg-red-500/10 border border-transparent hover:border-red-500/20'"
            @click="handleCancel"
        >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Batal
        </button>
    </div>
</template>
