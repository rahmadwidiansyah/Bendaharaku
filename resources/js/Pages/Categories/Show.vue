<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    category: Object,
    transactions: Array,
    totalUsage: Number,
    isSystem: Boolean,
});

const showDetailModal = ref(false);
const selectedTransaction = ref(null);

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const openDetail = (trx) => {
    // Controller returns full objects, but we need to match the structure expected by the modal
    // Actually TransactionDetailModal might need some adjustment or I can format it here
    selectedTransaction.value = {
        ...trx,
        amount: Number(trx.amount),
        category: props.category,
        source_wallet: trx.source_wallet,
        destination_wallet: trx.destination_wallet,
        time: new Date(trx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
        date: new Date(trx.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
    };
    showDetailModal.value = true;
};

const handleBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('categories.index'));
    }
};

const getTypeColor = (typeName) => {
    return {
        'Income': 'text-green-400 bg-green-400/10 border-green-400/20',
        'Expense': 'text-gray-400 bg-gray-400/10 border-gray-400/20',
        'Transfer': 'text-blue-400 bg-blue-400/10 border-blue-400/20',
        'Debt': 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
        'Receivable': 'text-purple-400 bg-purple-400/10 border-purple-400/20'
    }[typeName] || 'text-gray-500';
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="category.category_name" />

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative animate-fade-in-up">

            <header class="flex justify-between items-center mb-8 pt-4 relative z-10">
                <button type="button" @click="handleBack"
                    class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <Link v-if="!isSystem" :href="route('categories.edit', category.id)"
                    class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-purple-500 active:scale-95 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </Link>
            </header>

            <div class="flex flex-col items-center mb-10 relative z-10">
                <div
                    class="w-24 h-24 rounded-xl bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-5xl shadow-2xl mb-5 overflow-hidden relative p-1">
                    <img v-if="category.icon?.includes('.')" :src="'/storage/' + category.icon"
                        class="w-full h-full object-cover rounded-xl">
                    <span v-else class="relative z-10">{{ category.icon || '📁' }}</span>
                    <div class="absolute inset-0 bg-linear-to-t from-black/20 to-transparent"></div>
                </div>

                <div class="text-center">
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none mb-2">{{
                        category.category_name }}</h1>
                    <div
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full"
                            :class="category.type.name === 'Income' ? 'bg-green-400' : 'bg-purple-500'"></span>
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-[0.2em]">{{ category.type.name ===
                            'Income' ? 'Pemasukan' : 'Pengeluaran' }}</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-7 text-center mb-10 shadow-2xl relative overflow-hidden group z-10">
                <div class="absolute top-0 left-0 w-full h-1 bg-purple-500/20"></div>
                <p class="text-2xs font-bold text-white uppercase tracking-[0.2em] mb-2 opacity-60">Total Akumulasi
                </p>
                <div class="flex items-baseline justify-center gap-1.5">
                    <span class="text-sm font-bold text-gray-600">Rp</span>
                    <h2 class="text-3xl font-black text-white tracking-tight">{{ formatNumber(totalUsage) }}</h2>
                </div>
            </div>

            <div class="space-y-4 relative z-10">
                <div class="flex items-center justify-between px-1 mb-2">
                    <h3 class="text-2xs font-bold text-gray-400 uppercase tracking-widest">Riwayat Transaksi</h3>
                    <span
                        class="text-2xs font-bold text-gray-400 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 px-2 py-0.5 rounded-xl">{{
                        transactions.length }} Record</span>
                </div>

                <button v-for="trx in transactions" :key="trx.id" type="button" @click="openDetail(trx)"
                    class="w-full text-left bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-4 rounded-xl flex justify-between items-center active:scale-[0.98] transition-all shadow-sm relative overflow-hidden group">

                    <div class="flex-1 min-w-0 pr-3 relative z-10">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="text-2xs font-bold text-gray-500 ">
                                {{ new Date(trx.date).toLocaleDateString('id-ID', {
                                    day: '2-digit', month: 'short',
                                year: 'numeric' }) }}
                            </p>
                            <span class="w-1 h-1 bg-gray-500 rounded-full"></span>
                            <p class="text-2xs text-gray-600 font-bold">{{ new
                                Date(trx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                                }}</p>
                        </div>

                        <div class="mb-2">
                            <p class="text-sm font-bold text-white truncate">{{ trx.notes ?? 'Tanpa catatan' }}</p>
                            <p v-if="trx.subject && trx.subject !== '-'"
                                class="text-2xs text-yellow-500 font-bold mt-0.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ trx.subject }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <div
                                class="flex items-center gap-1.5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-2 py-1 rounded-xl">
                                <span class="text-2xs font-bold text-gray-400 truncate max-w-[60px]">{{
                                    trx.source_wallet?.name }}</span>
                                <svg class="w-2.5 h-2.5 text-purple-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                <span class="text-2xs font-bold text-white truncate max-w-[60px]">{{
                                    trx.destination_wallet?.name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right shrink-0 relative z-10">
                        <p class="text-base font-black"
                            :class="category.type.name === 'Income' ? 'text-green-400' : 'text-white'">
                            {{ category.type.name === 'Income' ? '+' : '-' }}{{ formatNumber(trx.amount) }}
                        </p>
                        <span
                            class="inline-block text-2xs font-bold text-white uppercase px-1.5 py-0.5 rounded border"
                            :class="getTypeColor(trx.type.name)">
                            {{ trx.type.name }}
                        </span>
                    </div>
                </button>

                <div v-if="transactions.length === 0"
                    class="text-center py-16 bg-linear-to-br from-gray-900 to-gray-800 rounded-xl border-2 border-dashed border-white/10">
                    <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">Belum ada transaksi</p>
                </div>
            </div>
        </div>

        <TransactionDetailModal :show="showDetailModal" :transaction="selectedTransaction"
            @close="showDetailModal = false" />
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(15px);
    }

    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
