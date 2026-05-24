<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import BottomNav from '@/Components/BottomNav.vue';
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue';
import { ref } from 'vue';

const props = defineProps({
    wallet: Object,
    transactions: Object,
});

const isModalOpen = ref(false);
const selectedTransaction = ref({});

const formatAmount = (amount) => {
    return new Intl.NumberFormat('id-ID').format(amount);
};

const getIcon = (icon) => {
    return icon && (icon.includes('.') || icon.includes('/')) ? true : false;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};

const formatTime = (timeString) => {
    if (!timeString) return '';
    const date = new Date(timeString);
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
};

const openDetailModal = (trx) => {
    selectedTransaction.value = {
        ...trx,
        date: formatDate(trx.date),
        time: formatTime(trx.created_at)
    };
    isModalOpen.value = true;
};

const closeDetailModal = () => {
    isModalOpen.value = false;
};

const getTypeColor = (typeName) => {
    switch(typeName) {
        case 'Income': return 'text-green-400 bg-green-400/10 border-green-400/20';
        case 'Expense': return 'text-gray-400 bg-gray-400/10 border-gray-400/20';
        case 'Transfer': return 'text-blue-400 bg-blue-400/10 border-blue-400/20';
        case 'Debt': return 'text-red-400 bg-red-400/10 border-red-400/20';
        case 'Receivable': return 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20';
        default: return 'text-gray-500';
    }
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head title="Detail Dompet" />
        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative">
            <header class="flex justify-between items-center mb-6 pt-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Detail Dompet</h1>
                <Link :href="route('dashboard')" class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </Link>
            </header> 

            <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-7 text-center mb-10 shadow-2xl relative overflow-hidden group">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-purple-500 opacity-[0.05] rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                
                <div class="w-20 h-20 bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl mx-auto flex items-center justify-center text-4xl border border-white/10 mb-4 shadow-inner overflow-hidden p-1">
                    <img v-if="getIcon(wallet.icon)" :src="'/storage/' + wallet.icon" class="w-full h-full object-cover rounded-xl">
                    <span v-else>{{ wallet.icon || '💳' }}</span>
                </div>

                <p class="text-xs font-bold text-gray-500 uppercase tracking-[0.2em] mb-1">{{ wallet.name }}</p>
                <h2 class="text-3xl font-black text-white tracking-tight mb-6">Rp {{ formatAmount(wallet.balance) }}</h2>
                
                <Link :href="route('wallets.edit', wallet.id)" class="inline-block bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-purple-500 text-xs font-bold px-6 py-2.5 rounded-xl hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                    Edit Dompet
                </Link>
            </div>

            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1 text-center">Mutasi Terakhir</h2>

            <div class="space-y-4">
                <template v-if="transactions.data && transactions.data.length > 0">
                    <button v-for="trx in transactions.data" :key="trx.id" type="button" @click="openDetailModal(trx)"
                        class="w-full text-left bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 p-4 rounded-xl flex justify-between items-center active:scale-[0.98] transition-all shadow-sm relative overflow-hidden group">
                        
                        <div class="flex items-center gap-3 flex-1 min-w-0 relative z-10">
                            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 flex items-center justify-center text-xl shrink-0 overflow-hidden p-0.5">
                                <img v-if="getIcon(trx.category?.icon)" :src="'/storage/' + trx.category.icon" class="w-full h-full object-cover rounded-xl">
                                <span v-else>{{ trx.category?.icon || '📄' }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-sm font-bold text-white truncate">{{ trx.category?.category_name }}</p>
                                </div>
                                
                                <div class="flex items-center gap-1.5 opacity-60">
                                    <span class="text-xs font-bold text-gray-400 uppercasehover:-translate-y-0.5 active:scale-95 transition-all duration-200 truncate max-w-[70px]">{{ trx.source_wallet?.name }}</span>
                                    <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                    <span class="text-xs font-bold text-white uppercase truncate max-w-[70px]">{{ trx.destination_wallet?.name }}</span>
                                </div>
                                
                                <p class="text-xs text-gray-600 font-bold uppercase mt-1">
                                    {{ formatDate(trx.date) }} • {{ formatTime(trx.created_at) }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right shrink-0 relative z-10 ml-2">
                            <p v-if="trx.destination_wallet_id === wallet.id" class="text-sm font-black text-green-400">+{{ formatAmount(trx.amount) }}</p>
                            <p v-else class="text-sm font-black text-white">-{{ formatAmount(trx.amount) }}</p>
                            
                            <span :class="['inline-block text-xs uppercase tracking-widest font-bold px-1.5 py-0.5 rounded border mt-1', getTypeColor(trx.type?.name)]">
                                {{ trx.type?.name }}
                            </span>
                        </div>
                    </button>
                </template>
                <div v-else class="text-center py-12 bg-gradient-to-br from-gray-900 to-gray-800 border rounded-xl border-dashed border-white/10">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Belum ada mutasi</p>
                </div>
            </div>

            <div v-if="transactions.links && transactions.links.length > 3" class="mt-8 flex justify-center gap-1 flex-wrap">
                <template v-for="(link, k) in transactions.links" :key="k">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" :class="['px-3 py-1 text-sm rounded-md', link.active ? 'bg-gradient-to-br from-purple-600 to-purple-500 text-white font-bold' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-400 border border-white/10 hover:text-white']" />
                    <span v-else v-html="link.label" class="px-3 py-1 text-sm rounded-md bg-gradient-to-br from-gray-900 to-gray-800 text-gray-400 border border-white/10" />
                </template>
            </div>
        </div>

        <TransactionDetailModal :show="isModalOpen" :transaction="selectedTransaction" @close="closeDetailModal" />

        <BottomNav />
    </AuthenticatedLayout>
</template>
