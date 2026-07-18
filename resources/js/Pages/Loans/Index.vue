<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility';
import { formatNumber, formatDate } from '@/utils/format.js';

const { t } = useI18n();
const { isBalanceVisible } = useBalanceVisibility();

const props = defineProps({
    loanDetails: Array,
    title: String,
    isDebt: Boolean,
    total: Number,
});

const maskedAmount = '••••••';
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head :title="t('loan.title')" />

        <div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8 relative animate-slide-up">

            <!-- Header -->
            <header class="flex justify-between items-center mb-8 pt-4">
                <div class="hidden lg:block">
                    <p class="text-2xs font-black text-purple-500 uppercase tracking-[0.2em] mb-1 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                        {{ $t('loan.title') }}
                    </p>
                    <h1 class="text-3xl font-black text-white tracking-tight leading-none">
                        {{ isDebt ? $t('loan.titleDebt') : $t('loan.titleReceivable') }}
                    </h1>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 active:scale-90 transition-all hover:text-white hover:border-gray-500"
                    :aria-label="$t('btn.back')">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </Link>
            </header>

            <!-- Tab: Hutang / Piutang -->
            <div class="flex gap-2 mb-6 p-1 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl">
                <Link
                    :href="route('loans.index', { type: 'debt' })"
                    :class="[
                        'flex-1 py-2.5 rounded-lg text-2xs font-black uppercase tracking-widest text-center transition-all',
                        isDebt
                            ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'
                            : 'text-gray-500 hover:text-gray-300 border border-transparent',
                    ]">
                    💸 {{ $t('types.debt') }}
                </Link>
                <Link
                    :href="route('loans.index', { type: 'piutang' })"
                    :class="[
                        'flex-1 py-2.5 rounded-lg text-2xs font-black uppercase tracking-widest text-center transition-all',
                        !isDebt
                            ? 'bg-pink-500/20 text-pink-400 border border-pink-500/30'
                            : 'text-gray-500 hover:text-gray-300 border border-transparent',
                    ]">
                    🤝 {{ $t('types.receivable') }}
                </Link>
            </div>

            <!-- Total Card -->
            <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-5 text-center mb-6">
                <p :class="['text-2xs font-bold uppercase tracking-widest mb-1', isDebt ? 'text-yellow-500' : 'text-pink-500']">
                    {{ isDebt ? $t('loan.totalDebt') : $t('loan.totalReceivable') }}
                </p>
                <h2 class="text-3xl font-black text-white tracking-tight">
                    <span class="text-lg text-gray-500 mr-1">Rp</span>
                    <span v-if="isBalanceVisible">{{ formatNumber(total) }}</span>
                    <span v-else class="tracking-widest text-gray-400">{{ maskedAmount }}</span>
                </h2>
                <p class="text-2xs text-gray-600 mt-1">
                    {{ isDebt ? $t('loan.activeDebtors', { n: loanDetails.length }) : $t('loan.activeCreditors', { n: loanDetails.length }) }}
                </p>
            </div>

            <!-- Section label -->
            <div class="flex items-center gap-3 mb-4">
                <h2 class="text-2xs font-black text-gray-500 uppercase tracking-[0.2em]">
                    {{ isDebt ? $t('loan.fromWhom') : $t('loan.toWhom') }}
                </h2>
                <div class="flex-1 h-px bg-white/5"></div>
            </div>

            <!-- List -->
            <div class="space-y-3">
                <template v-if="loanDetails.length > 0">
                    <div
                        v-for="loan in loanDetails"
                        :key="loan.subject"
                        class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 p-4 rounded-xl hover:border-white/20 transition-colors">

                        <div class="flex justify-between items-start mb-3">
                            <div class="min-w-0">
                                <p class="text-base font-black text-white leading-tight truncate">{{ loan.subject }}</p>
                                <p class="text-2xs text-gray-500 mt-0.5">
                                    {{ $t('loan.since') }} <span class="font-bold text-gray-400">{{ formatDate(loan.latest_date) }}</span>
                                </p>
                            </div>
                            <!-- Usia hutang/piutang -->
                            <div class="shrink-0 ml-3 bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 px-3 py-1.5 rounded-xl text-center">
                                <p class="text-sm font-black text-white leading-tight">{{ loan.age }}</p>
                                <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">{{ $t('loan.days') }}</p>
                            </div>
                        </div>

                        <!-- Sisa nominal -->
                        <div :class="[
                            'flex items-center justify-between px-3 py-2 rounded-lg',
                            isDebt ? 'bg-yellow-500/8 border border-yellow-500/15' : 'bg-pink-500/8 border border-pink-500/15'
                        ]">
                            <span class="text-2xs font-bold text-gray-500 uppercase tracking-widest">{{ $t('loan.remaining') }}</span>
                            <span v-if="isBalanceVisible" :class="['text-sm font-black', isDebt ? 'text-yellow-400' : 'text-pink-400']">
                                Rp {{ formatNumber(loan.balance) }}
                            </span>
                            <span v-else class="font-bold text-gray-500 tracking-widest">{{ maskedAmount }}</span>
                        </div>
                    </div>
                </template>

                <!-- Empty state -->
                <div v-else class="text-center py-14 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl">
                    <p class="text-3xl mb-3">🎉</p>
                    <p class="text-sm font-black text-white uppercase tracking-widest">{{ $t('loan.clean') }}</p>
                    <p class="text-2xs text-gray-500 mt-1.5">
                        {{ isDebt ? $t('loan.cleanMsg') : $t('loan.cleanMsgRcv') }}
                    </p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
