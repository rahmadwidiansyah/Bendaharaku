<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateModal from '@/Components/DateModal.vue';
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatNumber, formatDate } from '@/utils/format.js';
import AppIcon from '@/Components/AppIcon.vue';

const { t } = useI18n();

const props = defineProps({
    category: Object,
    transactions: Array,
    totalUsage: Number,
    isSystem: Boolean,
    startDate: String,
    endDate: String,
});

const showDetailModal = ref(false);
const selectedTransaction = ref(null);

const openDetail = (trx) => {
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

const getTypeName = (name) => ({
    Income: t('types.income'),
    Expense: t('types.expense'),
    Transfer: t('types.transfer'),
    Debt: t('types.debt'),
    Receivable: t('types.receivable'),
}[name] ?? name);

const getTypeColor = (typeName) => {
    return {
        'Income': 'text-green-400 bg-green-400/10 border-green-400/20',
        'Expense': 'text-gray-400 bg-gray-400/10 border-gray-400/20',
        'Transfer': 'text-blue-400 bg-blue-400/10 border-blue-400/20',
        'Debt': 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
        'Receivable': 'text-purple-400 bg-purple-400/10 border-purple-400/20'
    }[typeName] || 'text-gray-500';
};

const formatDateRange = () => {
    return `${formatDate(props.startDate)} – ${formatDate(props.endDate)}`;
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="category.category_name" />

        <div class="p-4 sm:p-5 w-full lg:max-w-4xl mx-auto lg:px-8 relative animate-fade-in-up">

            <header class="hidden lg:flex justify-between items-center mb-8 pt-4 relative z-10">
                <div>
                    <p class="text-2xs text-gray-300 font-semibold mb-1 uppercase tracking-wider">Vault</p>
                    <h1 class="text-2xl font-bold text-white tracking-tight">{{ t('category.title') }}</h1>
                </div>

                <Link :href="route('categories.edit', category.id)"
                    class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-purple-500 active:scale-95 transition-all hover:border-purple-500/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </Link>
            </header>

            <div class="flex flex-col items-center mb-8 lg:mb-10 relative z-10">
                <AppIcon :icon="category.icon" fallback="folder" class="w-16 h-16 lg:w-20 lg:h-20 text-purple-400 mb-4 lg:mb-5" />

                <div class="text-center">
                    <h1 class="text-2xl lg:text-3xl font-black text-white tracking-tight leading-none mb-2">{{ category.category_name }}</h1>
                    <div class="flex flex-col items-center gap-2">
                        <div class="inline-flex items-center gap-2 px-3 lg:px-4 py-1 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10">
                            <span class="w-1.5 h-1.5 rounded-full"
                                :class="category.type.name === 'Income' ? 'bg-green-400' : 'bg-purple-500'"></span>
                            <p class="text-2xs font-bold text-gray-400 uppercase tracking-[0.2em]">{{ getTypeName(category.type.name) }}</p>
                        </div>
                        <div v-if="isSystem"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30">
                            <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-2xs font-bold text-amber-400 uppercase tracking-[0.15em]">{{ $t('category.systemCategory') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Mobile: edit button floating -->
                <Link :href="route('categories.edit', category.id)"
                    class="lg:hidden mt-4 w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-purple-500 active:scale-95 transition-all hover:border-purple-500/50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </Link>
            </div>

            <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-5 lg:p-6 text-center mb-8 lg:mb-10 shadow-2xl relative overflow-hidden group z-10">
                <div class="absolute top-0 left-0 w-full h-1 bg-purple-500/20"></div>
                <div class="flex items-center justify-between gap-3 mb-4 lg:mb-5">
                    <div class="text-left min-w-0">
                        <p class="text-2xs font-bold text-white uppercase tracking-[0.2em] mb-1 opacity-60">{{ $t('common.total') + ' ' + $t('common.period') }}</p>
                        <p class="text-2xs font-bold text-purple-400 truncate">{{ formatDateRange() }}</p>
                    </div>
                    <DateModal :action="route('categories.show', category.id)" :start-date="startDate" :end-date="endDate" />
                </div>
                <div class="flex items-baseline justify-center gap-1.5">
                    <span class="text-xs lg:text-sm font-bold text-gray-600">Rp</span>
                    <h2 class="text-2xl lg:text-3xl font-black text-white tracking-tight">{{ formatNumber(totalUsage) }}</h2>
                </div>
            </div>

            <div class="space-y-4 relative z-10">
                <div class="flex items-center justify-between px-1 mb-2">
                    <h3 class="text-2xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('category.show.transactions') }}</h3>
                    <span class="text-2xs font-bold text-gray-400 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 px-2 py-0.5 rounded-xl">{{
                        transactions.length }} {{ $t('category.transaction') }}</span>
                </div>

                <button v-for="trx in transactions" :key="trx.id" type="button" @click="openDetail(trx)"
                    class="w-full text-left bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-3 lg:p-4 rounded-xl flex justify-between items-center active:scale-[0.98] transition-all shadow-sm relative overflow-hidden group">

                    <div class="flex-1 min-w-0 pr-3 relative z-10">
                        <div class="flex items-center gap-2 mb-1.5 lg:mb-2">
                            <p class="text-2xs font-bold text-gray-500">
                                {{ new Date(trx.date).toLocaleDateString('id-ID', {
                                    day: '2-digit', month: 'short',
                                year: 'numeric' }) }}
                            </p>
                            <span class="w-1 h-1 bg-gray-500 rounded-full"></span>
                            <p class="text-2xs text-gray-600 font-bold">{{ new
                                Date(trx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                                }}</p>
                        </div>

                        <div class="mb-1.5 lg:mb-2">
                            <p class="text-sm font-bold text-white truncate">{{ trx.notes ?? $t('transaction.detail.noNote') }}</p>
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
                            <div class="flex items-center gap-1.5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-1.5 lg:p-2 py-0.5 lg:py-1 rounded-xl">
                                <span class="text-2xs font-bold text-gray-400 truncate max-w-[50px] lg:max-w-[60px]">{{ trx.source_wallet?.name }}</span>
                                <svg class="w-2.5 h-2.5 text-purple-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                                <span class="text-2xs font-bold text-white truncate max-w-[50px] lg:max-w-[60px]">{{ trx.destination_wallet?.name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right shrink-0 relative z-10">
                        <p class="text-sm lg:text-base font-black"
                            :class="category.type.name === 'Income' ? 'text-green-400' : 'text-white'">
                            {{ category.type.name === 'Income' ? '+' : '-' }}{{ formatNumber(trx.amount) }}
                        </p>
                        <span class="inline-block text-2xs font-bold text-white uppercase px-1.5 py-0.5 rounded border"
                            :class="getTypeColor(trx.type.name)">
                            {{ getTypeName(trx.type.name) }}
                        </span>
                    </div>
                </button>

                <div v-if="transactions.length === 0"
                    class="text-center py-12 lg:py-16 bg-linear-to-br from-gray-900 to-gray-800 rounded-xl border-2 border-dashed border-white/10">
                    <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">{{ $t('category.show.noTransactions') }}</p>
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
