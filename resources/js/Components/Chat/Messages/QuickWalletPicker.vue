<script setup>
/**
 * QuickWalletPicker.vue
 *
 * Tampil di bawah card transaksi Draft yang needs_wallet=true.
 * Fetch daftar wallet user, lalu tampilkan sebagai chip.
 * Klik chip → emit 'select' dengan wallet_id.
 *
 * Props:
 *   transactionId — ID transaksi yang akan diassign
 *   loading       — Override loading state dari parent
 *
 * Emits:
 *   select({ walletId }) — user memilih wallet
 */
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    transactionId: { type: Number, required: true },
    loading:       { type: Boolean, default: false },
})

const emit = defineEmits(['select'])

const wallets        = ref([])
const isFetching     = ref(true)
const selectedId     = ref(null)
const fetchError     = ref(null)

onMounted(async () => {
    try {
        const { data } = await axios.get(route('chat.wallets'))
        wallets.value = data.wallets ?? []
    } catch (e) {
        fetchError.value = t('chatTransaction.walletLoadFailed')
    } finally {
        isFetching.value = false
    }
})

function select(wallet) {
    if (props.loading || selectedId.value) return
    selectedId.value = wallet.id
    emit('select', { walletId: wallet.id })
}
</script>

<template>
    <div class="mt-2 space-y-2">
        <!-- Label -->
        <p class="text-2xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            {{ $t('transaction.chooseWallet') }}
        </p>

        <!-- Loading skeleton -->
        <div v-if="isFetching" class="flex flex-wrap gap-1.5">
            <div v-for="i in 4" :key="i"
                class="h-7 w-16 rounded-xl bg-white/6 animate-pulse"
            />
        </div>

        <!-- Error -->
        <p v-else-if="fetchError" class="text-2xs text-red-400">{{ fetchError }}</p>

        <!-- Wallet chips -->
        <div v-else class="flex flex-wrap gap-1.5">
            <button
                v-for="wallet in wallets"
                :key="wallet.id"
                type="button"
                :disabled="loading || !!selectedId"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400 disabled:opacity-50"
                :class="[
                    selectedId === wallet.id
                        ? 'bg-purple-600/40 text-purple-200 border border-purple-500/60 scale-95'
                        : 'bg-white/6 text-gray-300 hover:bg-white/12 hover:text-white border border-white/8 hover:border-white/20'
                ]"
                @click="select(wallet)"
            >
                <span v-if="loading && selectedId === wallet.id" class="inline-block w-2.5 h-2.5 border-2 border-current border-t-transparent rounded-full animate-spin mr-1" />
                {{ wallet.name }}
            </button>
        </div>
    </div>
</template>
