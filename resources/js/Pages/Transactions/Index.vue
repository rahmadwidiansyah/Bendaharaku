<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateModal from '@/Components/DateModal.vue';
import CreateTransactionFab from '@/Components/CreateTransactionFab.vue';
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    transactions: Object,
    startDate: String,
    endDate: String,
    filters: Object,
});

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const showSortModal = ref(false);
const showDetailModal = ref(false);
const selectedTransaction = ref(null);
const collapsedDates = ref({});

const toggleDate = (dateKey) => {
    collapsedDates.value[dateKey] = !collapsedDates.value[dateKey];
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const formattedPeriod = computed(() => {
    const start = new Date(props.startDate);
    const end = new Date(props.endDate);
    if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear() && start.getDate() === 1 && end.getDate() >= 28) {
        return start.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    }
    return `${start.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} - ${end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}`;
});

const groupedTransactions = computed(() => {
    const groups = {};
    props.transactions.data.forEach(trx => {
        if (!groups[trx.raw_date]) groups[trx.raw_date] = { date: trx.date, transactions: [], income: 0, expense: 0 };
        groups[trx.raw_date].transactions.push(trx);
        if (trx.type.name === 'Income') groups[trx.raw_date].income += trx.amount;
        if (trx.type.name === 'Expense') groups[trx.raw_date].expense += trx.amount;
    });
    return groups;
});

const openDetail = (trx) => {
    selectedTransaction.value = trx;
    showDetailModal.value = true;
};

const setType = (newType) => {
    type.value = newType;
    showSortModal.value = false;
};

let timeout = null;
watch([search, type], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('transactions.index'), {
            search: search.value,
            type: type.value,
            start_date: props.startDate,
            end_date: props.endDate
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const getTypeColor = (typeName) => {
    return {
        'Income': 'text-green-400 bg-green-400/10 border-green-400/20',
        'Expense': 'text-gray-400 bg-gray-400/10 border-gray-400/20',
        'Transfer': 'text-blue-400 bg-blue-400/10 border-blue-400/20',
        'Debt': 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
        'Receivable': 'text-purple-400 bg-purple-400/10 border-purple-400/20'
    }[typeName] || 'text-gray-500';
};

const getTypeName = (typeName) => {
    return { 'Income': 'Pemasukan', 'Expense': 'Pengeluaran', 'Transfer': 'Transfer', 'Debt': 'Hutang', 'Receivable': 'Piutang' }[typeName] || 'Lainnya';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Histori Transaksi" />

        <div class="p-5 pb-32 max-w-md mx-auto relative">
            
            <header class="mb-4 pt-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Histori Transaksi</h1>
            </header>

            <div class="flex gap-2 mb-6">
                <div class="relative flex-1">
                    <input type="text" v-model="search" placeholder="Cari catatan..." 
                        class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3.5 pl-11 text-xs focus:ring-1 focus:ring-purple-500 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>

                <DateModal :action="route('transactions.index')" :start-date="startDate" :end-date="endDate" />
            </div>

            <div class="flex justify-between items-center mb-4">
                <p class="text-xs font-bold text-purple-500 uppercase tracking-widest flex flex-col">
                    <span class="text-xs">Periode Aktif</span>
                    <span class="text-white text-sm tracking-tight">{{ formattedPeriod }}</span>
                </p>
                
                <button type="button" @click="showSortModal = true" class="flex items-center gap-1.5 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest active:scale-95 transition-all" :class="type ? 'text-purple-500 border-purple-500/50' : 'text-gray-500'">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25" /></svg>
                    {{ type ? getTypeName(type) : 'Semua Tipe' }}
                </button>
            </div>

            <div class="space-y-4">
                <div v-for="(group, dateKey) in groupedTransactions" :key="dateKey" class="bg-gradient-to-br from-gray-900 to-gray-800 p-3 rounded-xl border border-white/5 transition-all duration-300">
                    <div @click="toggleDate(dateKey)" class="flex justify-between items-center px-1 border-b pb-2 transition-colors cursor-pointer group/header" :class="collapsedDates[dateKey] ? 'border-transparent' : 'border-purple-500/30'">
                        <h3 class="text-xs font-bold text-purple-500 uppercase tracking-widest flex items-center gap-1.5 group-hover/header:text-purple-400 transition-colors"> 
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="!collapsedDates[dateKey] ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            {{ group.date }} 
                        </h3>
                        <div class="text-xs font-bold flex gap-2.5 tracking-wide">
                            <span v-if="group.income > 0" class="text-green-400/90">+{{ formatNumber(group.income) }}</span>
                            <span v-if="group.expense > 0" class="text-white/90">-{{ formatNumber(group.expense) }}</span>
                        </div>
                    </div>

                    <div class="grid transition-all duration-300 ease-in-out" :style="{ gridTemplateRows: collapsedDates[dateKey] ? '0fr' : '1fr' }">
                        <div class="overflow-hidden transition-all duration-300" :class="collapsedDates[dateKey] ? 'opacity-0' : 'opacity-100'">
                            <div class="space-y-2.5 pt-3">
                                <button v-for="trx in group.transactions" :key="trx.id" @click="openDetail(trx)"
                                    class="w-full text-left bg-gradient-to-br from-gray-800 to-gray-900 p-3 rounded-xl border border-white/10 hover:border-purple-400/30 active:scale-[0.98] transition-all relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                            <div class="flex items-center gap-3 relative z-10">
                                <div class="w-10 h-10 rounded-xl bg-gradient flex items-center justify-center text-lg border border-white/10 shrink-0 overflow-hidden p-0.5">
                                    <img v-if="trx.category?.icon?.includes('.')" :src="'/storage/' + trx.category.icon" class="w-full h-full object-cover rounded-xl">
                                    <span v-else>{{ trx.category?.icon || '📄' }}</span>
                                </div>

                                <div class="flex-1 min-w-0 pr-2">
                                    <p class="text-xs font-bold text-white leading-tight mb-1.5 truncate">{{ trx.category?.category_name || 'Transfer' }}</p>
                                    <div class="flex items-center gap-1.5 opacity-80 min-w-0">
                                        <span class="text-xs text-gray-400 font-bold uppercase tracking-tight truncate">{{ trx.source_wallet?.name }}</span>
                                        <svg class="w-2.5 h-2.5 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                        <span class="text-xs text-white font-bold uppercase tracking-tight truncate">{{ trx.destination_wallet?.name }}</span>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-xs font-black" :class="trx.type.name === 'Income' ? 'text-green-400' : 'text-white'">
                                        {{ trx.type.name === 'Income' ? '+' : '-' }}{{ formatNumber(trx.amount) }}
                                    </p>
                                    <div class="flex items-center justify-end gap-1.5 mt-1">
                                        <span class="text-xs text-gray-600 font-medium italic"> {{ trx.time }} </span>
                                        <span class="text-xs uppercase tracking-widest font-black px-1 py-0.5 rounded border" :class="getTypeColor(trx.type.name)"> 
                                            {{ getTypeName(trx.type.name) }} 
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="transactions.data.length === 0" class="text-center py-12 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 animate-fade-in-up relative overflow-hidden group">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest relative z-10">Data Kosong</p>
                </div>
            </div>
        </div>

        <!-- SORT MODAL -->
        <div v-if="showSortModal" class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showSortModal = false">
            <div class="w-full max-w-sm bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 p-6 animate-pop-in relative">
                <button type="button" @click="showSortModal = false" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <h3 class="text-sm font-bold text-white mb-6 text-center uppercase tracking-widest">Filter Tipe</h3>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="setType('')" class="col-span-2 py-3 rounded-xl border border-white/10 text-xs font-bold uppercase tracking-widest transition-all" :class="!type ? 'bg-purple-600 text-white' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-300'">Semua Tipe</button>
                    <button v-for="(label, key) in { 'Income': 'Pemasukan', 'Expense': 'Pengeluaran', 'Transfer': 'Transfer', 'Debt': 'Hutang', 'Receivable': 'Piutang' }" 
                        @click="setType(key)" 
                        class="py-3 rounded-xl border border-white/10 text-xs font-bold uppercase tracking-widest transition-all"
                        :class="type === key ? 'bg-gradient-to-br from-purple-800 to-purple-500 text-white' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-300'">
                        {{ label }}
                    </button>
                </div>
            </div>
        </div>

        <CreateTransactionFab />
        <TransactionDetailModal :show="showDetailModal" :transaction="selectedTransaction" @close="showDetailModal = false" />
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.animate-pop-in { animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
@keyframes fade-in-up { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
