<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility';
import { formatNumber, formatDate } from '@/utils/format.js';
import AppIcon from '@/Components/AppIcon.vue';

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

        <div class="p-5 w-full lg:max-w-7xl mx-auto lg:px-8 relative animate-slide-up">

            <!-- Header -->
            <header class="mb-6 pt-2 lg:pt-4">
                <div class="hidden lg:block mb-4">
                    <p class="text-2xs text-[var(--color-brand)] font-black uppercase tracking-[0.3em] mb-1 opacity-80">{{ $t('loan.title') }}</p>
                    <h1 class="text-2xl font-black text-[var(--color-text-primary)] tracking-tight leading-none">
                        {{ isDebt ? $t('loan.titleDebt') : $t('loan.titleReceivable') }}
                    </h1>
                </div>

                <!-- Tab: Hutang / Piutang -->
                <div class="grid grid-cols-2 gap-1 bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-1">
                    <Link :href="route('loans.index', { type: 'debt' })"
                        :class="[
                            'flex items-center justify-center gap-2 py-2.5 rounded-lg text-2xs font-black uppercase tracking-widest transition-all',
                            isDebt
                                ? 'bg-linear-to-br from-[var(--color-debt-bg)] to-[var(--color-debt-bg-hover)] text-[var(--color-text-primary)] shadow-sm'
                                : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'
                        ]">
                        <AppIcon icon="circle-dollar-sign" class="w-3.5 h-3.5" />
                        {{ $t('types.debt') }}
                    </Link>
                    <Link :href="route('loans.index', { type: 'piutang' })"
                        :class="[
                            'flex items-center justify-center gap-2 py-2.5 rounded-lg text-2xs font-black uppercase tracking-widest transition-all',
                            !isDebt
                                ? 'bg-linear-to-br from-[var(--color-brand)] to-[var(--color-brand-hover)] text-[var(--color-text-primary)] shadow-sm'
                                : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'
                        ]">
                        <AppIcon icon="hand-coins" class="w-3.5 h-3.5" />
                        {{ $t('types.receivable') }}
                    </Link>
                </div>
            </header>

            <!-- Total Card -->
            <div :class="[
                'rounded-xl p-5 mb-6 border relative overflow-hidden bg-[var(--color-surface-raised)]',
                isDebt ? 'border-[var(--color-debt-border)]' : 'border-[var(--color-receivable-border)]'
            ]">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-1">
                        <div :class="['w-1.5 h-1.5 rounded-full', isDebt ? 'bg-[var(--color-debt-text)]' : 'bg-[var(--color-brand)]']" />
                        <p :class="['text-2xs font-bold uppercase tracking-widest', isDebt ? 'text-[var(--color-debt-text)]' : 'text-[var(--color-brand)]']">
                            {{ isDebt ? $t('loan.totalDebt') : $t('loan.totalReceivable') }}
                        </p>
                    </div>
                    <h2 class="text-3xl font-black text-[var(--color-text-primary)] tracking-tight">
                        <span class="text-lg text-[var(--color-text-muted)] mr-1">Rp</span>
                        <span v-if="isBalanceVisible">{{ formatNumber(total) }}</span>
                        <span v-else class="tracking-widest text-[var(--color-text-secondary)]">{{ maskedAmount }}</span>
                    </h2>
                    <p class="text-2xs text-[var(--color-text-muted)] mt-1">
                        {{ isDebt ? $t('loan.activeDebtors', { n: loanDetails.length }) : $t('loan.activeCreditors', { n: loanDetails.length }) }}
                    </p>
                </div>
            </div>

            <!-- Section label -->
            <div class="flex items-center gap-3 mb-4 px-1">
                <h2 class="text-2xs font-bold text-[var(--color-text-primary)] uppercase tracking-widest">
                    {{ isDebt ? $t('loan.fromWhom') : $t('loan.toWhom') }}
                </h2>
                <div class="flex-1 h-px bg-linear-to-r" :class="isDebt ? 'from-yellow-500/30 to-transparent' : 'from-violet-500/30 to-transparent'"></div>
            </div>

            <!-- List -->
            <div class="space-y-2">
                <template v-if="loanDetails.length > 0">
                    <div
                        v-for="loan in loanDetails"
                        :key="loan.subject"
                        :class="[
                            'flex items-center gap-3 bg-[var(--color-surface-raised)] border p-3 rounded-xl',
                            isDebt
                                ? 'border-yellow-500/10 hover:border-yellow-500/25'
                                : 'border-violet-500/10 hover:border-violet-500/25'
                        ]">
                        <div :class="['w-1 h-10 rounded-full shrink-0', isDebt ? 'bg-[var(--color-debt-text)]' : 'bg-[var(--color-brand)]']" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-black text-[var(--color-text-primary)] truncate">{{ loan.subject }}</p>
                                <div class="shrink-0 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] px-2.5 py-1 rounded-lg text-center leading-none">
                                    <p class="text-xs font-black text-[var(--color-text-primary)]">{{ loan.age }}</p>
                                    <p class="text-[9px] font-bold text-[var(--color-text-muted)] uppercase tracking-widest">{{ $t('loan.days') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-1.5">
                                <p class="text-2xs text-[var(--color-text-muted)]">
                                    {{ $t('loan.since') }} <span class="font-bold text-[var(--color-text-secondary)]">{{ formatDate(loan.latest_date) }}</span>
                                </p>
                                <p :class="['text-xs font-black', isDebt ? 'text-yellow-400' : 'text-violet-400']">
                                    Rp {{ formatNumber(loan.balance) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty state -->
                <div v-else class="text-center py-14 bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl">
                    <p class="text-3xl mb-3">🎉</p>
                    <p class="text-sm font-black text-[var(--color-text-primary)] uppercase tracking-widest">{{ $t('loan.clean') }}</p>
                    <p class="text-2xs text-[var(--color-text-muted)] mt-1.5">
                        {{ isDebt ? $t('loan.cleanMsg') : $t('loan.cleanMsgRcv') }}
                    </p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
