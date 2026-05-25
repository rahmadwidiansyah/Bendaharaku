<script setup>
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    transaction: Object,
});

const emit = defineEmits(['close']);
import { ref } from 'vue';

const showDeleteConfirm = ref(false);

const formatAmount = (amount) => {
    return new Intl.NumberFormat('id-ID').format(amount);
};

const showConfirm = () => {
    showDeleteConfirm.value = true;
};

const deleteTransaction = () => {
    showDeleteConfirm.value = false;
    router.delete(route('transactions.destroy', props.transaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close'); // Tutup modal otomatis kalau sukses dihapus
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] bg-black/60 flex items-center justify-center p-4 transition-opacity" @click.self="emit('close')">
        <div class="w-full max-w-sm bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 p-6 animate-pop-in relative">
            
            <button @click="emit('close')" class="absolute top-4 right-4 w-8 h-8 bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 rounded-full flex items-center justify-center text-gray-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>

            <div class="flex flex-col items-center mb-6 mt-2">
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-3xl mb-3 overflow-hidden p-1">
                    <img v-if="transaction.category?.icon?.includes('.')" :src="'/storage/' + transaction.category.icon" class="w-full h-full object-cover rounded-xl">
                    <span v-else>{{ transaction.category?.icon || '📝' }}</span>
                </div>
                <p class="text-xl font-bold text-white text-center">{{ transaction.category?.category_name || 'Transfer' }}</p>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-1 text-center">
                    {{ transaction.date }} • {{ transaction.time }}
                </p>
            </div>

            <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 rounded-xl p-5 text-center mb-5">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Nominal</p>
                <h2 :class="['text-3xl font-bold tracking-tight', transaction.type?.name === 'Income' ? 'text-green-500' : 'text-red-500']">
                    {{ transaction.type?.name === 'Income' ? '+' : '-' }} Rp {{ formatAmount(transaction.amount) }}
                </h2>
            </div>

            <div class="space-y-4 mb-6 px-1 text-xs">
                <div class="flex justify-between items-center border-b border-white/30 pb-3 text-white">
                    <span class="text-gray-500 uppercase font-bold tracking-widest text-xs">Dompet</span>
                    <div class="text-right flex items-center gap-2">
                        <span class="font-bold text-gray-300 text-xs">{{ transaction.source_wallet?.name }}</span>
                        <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        <span class="font-bold text-gray-300 text-xs">{{ transaction.destination_wallet?.name }}</span>
                    </div>
                </div>
                <div v-if="transaction.subject && transaction.subject !== '-'" class="flex justify-between items-center border-b border-white/30 pb-3 text-white">
                    <span class="text-gray-500 uppercase font-bold tracking-widest text-xs">Pelaku</span>
                    <span class="font-bold text-xs">{{ transaction.subject }}</span>
                </div>
                <div v-if="transaction.due_date_type" class="flex justify-between items-center border-b border-white/30 pb-3 text-white">
                    <span class="text-gray-500 uppercase font-bold tracking-widest text-xs">Jatuh Tempo</span>
                    <div class="text-right flex flex-col items-end">
                        <span class="font-bold text-xs text-yellow-400">
                            {{ transaction.due_date_type === 'fixed' ? 'Sekali' : (transaction.due_date_type === 'monthly' ? 'Bulanan' : 'Per Hari') }}
                        </span>
                        <span class="text-[10px] text-gray-400">
                            {{ transaction.due_date_type === 'fixed' ? transaction.due_date : (transaction.due_date_type === 'monthly' ? `Tgl ${transaction.due_date_interval}` : `Setiap ${transaction.due_date_interval} Hari`) }}
                        </span>
                    </div>
                </div>
                <div class="flex justify-between items-start text-white">
                    <span class="text-gray-500 uppercase font-bold tracking-widest text-xs">Catatan</span>
                    <span class="text-right italic text-gray-400 text-xs">{{ transaction.notes || 'Tidak ada catatan.' }}</span>
                </div>
            </div>

            <div class="flex gap-3 mt-4">
                <Link :href="route('transactions.edit', transaction.id)" class="flex-1 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 py-3 rounded-xl flex items-center justify-center gap-2 text-gray-300 text-xs font-bold uppercase hover:bg-gray-800 transition-colors">
                    Edit
                </Link>
                <!-- Form dihapus, diganti menjadi Button dengan @click -->
                <button type="button" @click="showConfirm" class="flex-1 w-full bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 py-3 rounded-xl text-red-500 text-xs font-bold uppercase hover:bg-red-500/10 transition-colors">
                    Hapus
                </button>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION TOAST/MODAL -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm transition-opacity" @click.self="showDeleteConfirm = false">
        <div class="w-full max-w-sm bg-gradient-to-br from-red-900 to-gray-900 rounded-2xl border border-red-500/30 p-6 animate-pop-in relative shadow-2xl">
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-full bg-red-500/20 text-red-400 mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white tracking-tight mb-2">Hapus Transaksi?</h3>
                <p class="text-sm text-red-200">Yakin mau menghapus transaksi ini? Data yang dihapus tidak bisa dikembalikan.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showDeleteConfirm = false" class="flex-1 bg-gray-800 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all">
                    Batal
                </button>
                <button type="button" @click="deleteTransaction" class="flex-1 bg-gradient-to-br from-red-600 to-red-500 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-lg shadow-red-500/20 active:scale-95 transition-all">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.animate-pop-in { animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>