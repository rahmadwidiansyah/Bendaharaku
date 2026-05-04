<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    transaction: Object,
});

const emit = defineEmits(['close']);

const formatAmount = (amount) => {
    return new Intl.NumberFormat('id-ID').format(amount);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity" @click.self="emit('close')">
        <div class="w-full max-w-sm bg-[#121212] rounded-xl border border-[#262626] p-6 animate-pop-in relative">
            
            <button @click="emit('close')" class="absolute top-4 right-4 w-8 h-8 bg-[#1A1A1A] border border-[#333] rounded-full flex items-center justify-center text-gray-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>

            <div class="flex flex-col items-center mb-6 mt-2">
                <div class="w-16 h-16 rounded-xl bg-[#1A1A1A] flex items-center justify-center text-3xl border border-[#333] mb-3 overflow-hidden p-1">
                    <img v-if="transaction.category?.icon?.includes('.')" :src="'/storage/' + transaction.category.icon" class="w-full h-full object-cover rounded-xl">
                    <span v-else>{{ transaction.category?.icon || '📝' }}</span>
                </div>
                <p class="text-xl font-bold text-white text-center">{{ transaction.category?.category_name || 'Transfer' }}</p>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-1 text-center">
                    {{ transaction.date }} • {{ transaction.time }}
                </p>
            </div>

            <div class="bg-[#1A1A1A] border border-[#262626] rounded-xl p-5 text-center mb-5">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Nominal</p>
                <h2 :class="['text-3xl font-bold tracking-tight', transaction.type?.name === 'Income' ? 'text-green-400' : 'text-white']">
                    {{ transaction.type?.name === 'Income' ? '+' : '-' }} Rp {{ formatAmount(transaction.amount) }}
                </h2>
            </div>

            <div class="space-y-4 mb-6 px-1 text-xs">
                <div class="flex justify-between items-center border-b border-[#262626] pb-3 text-white">
                    <span class="text-gray-500 uppercase font-bold tracking-widest" style="font-size: 8px;">Dompet</span>
                    <div class="text-right flex items-center gap-2">
                        <span class="font-bold text-gray-300">{{ transaction.source_wallet?.name }}</span>
                        <svg class="w-3 h-3 text-[#FCA5FF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        <span class="font-bold">{{ transaction.destination_wallet?.name }}</span>
                    </div>
                </div>
                <div v-if="transaction.subject && transaction.subject !== '-'" class="flex justify-between items-center border-b border-[#262626] pb-3 text-white">
                    <span class="text-gray-500 uppercase font-bold tracking-widest" style="font-size: 8px;">Pelaku</span>
                    <span class="font-bold">{{ transaction.subject }}</span>
                </div>
                <div class="flex justify-between items-start text-white">
                    <span class="text-gray-500 uppercase font-bold tracking-widest" style="font-size: 8px;">Catatan</span>
                    <span class="text-right italic text-gray-400">{{ transaction.notes || 'Tidak ada catatan.' }}</span>
                </div>
            </div>

            <div class="flex gap-3 mt-4">
                <Link :href="route('transactions.edit', transaction.id)" class="flex-1 bg-[#1A1A1A] border border-[#333] py-3 rounded-xl flex items-center justify-center gap-2 text-gray-300 text-xs font-bold uppercase">
                    Edit
                </Link>
                <form class="flex-1" @submit.prevent="">
                    <button type="submit" class="w-full bg-[#1A1A1A] border border-[#333] py-3 rounded-xl text-red-500 text-xs font-bold uppercase">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.animate-pop-in { animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>
