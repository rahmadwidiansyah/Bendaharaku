<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Link } from '@inertiajs/vue3'
import { formatNumber } from '@/utils/format.js'
import AppIcon from '@/Components/AppIcon.vue'
import { getWalletIconColor } from '@/Composables/useIcon.js'

const { t } = useI18n()

const props = defineProps({
    totalPortfolio: { type: Number, default: 0 },
    isVisible: { type: Boolean, default: true },
    wallets: { type: Array, default: () => [] },
    totalHutang: { type: Number, default: 0 },
    totalPiutang: { type: Number, default: 0 },
    walletsExpanded: { type: Boolean, default: false },
})

defineEmits(['toggle-visibility'])

const displayAmount = (n) => props.isVisible ? formatNumber(n) : '••••••••'

const collapsedSections = ref({
    wallets: !props.walletsExpanded,
    hutang: true,
    piutang: true,
})
const hutangVisible = ref(localStorage.getItem('dashboard_hutang_visible') !== 'false')
const piutangVisible = ref(localStorage.getItem('dashboard_piutang_visible') !== 'false')

watch(hutangVisible, (val) => {
    localStorage.setItem('dashboard_hutang_visible', String(val))
})

watch(piutangVisible, (val) => {
    localStorage.setItem('dashboard_piutang_visible', String(val))
})

const toggleSection = (key) => {
    collapsedSections.value[key] = !collapsedSections.value[key]
}
const displayLocal = (n, visible) => visible ? formatNumber(n) : '••••••••'
</script>

<template>
<div class="relative bg-gradient-to-br from-[var(--color-surface-overlay)] to-[var(--color-surface-raised)] rounded-xl border border-[var(--color-border-default)] overflow-hidden mb-3 sm:mb-5 group animate-fade-in-up delay-200 shadow-card">
    <div class="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none" aria-hidden="true" />

        <div class="absolute inset-x-0 bottom-0 opacity-20 pointer-events-none h-24" aria-hidden="true">
            <svg viewBox="0 0 400 150" preserveAspectRatio="none" class="w-full h-full">
                <defs>
                    <linearGradient id="portfolioChartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:var(--color-brand); stop-opacity:0.3" />
                        <stop offset="100%" style="stop-color:var(--color-brand); stop-opacity:0" />
                    </linearGradient>
                </defs>
                <path d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50 L400,150 L0,150 Z" fill="url(#portfolioChartGradient)" />
                <path d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50" stroke="var(--color-brand)" stroke-width="2" fill="none" />
            </svg>
        </div>

<div class="relative z-10 p-3 sm:p-5">
    <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-1.5 rounded-full bg-[var(--color-brand)]" aria-hidden="true" />
                <p class="text-2xs text-[var(--color-text-secondary)] font-bold uppercase tracking-[0.18em]">{{ $t('portfolio.title') }}</p>
            </div>
        <button type="button" @click="$emit('toggle-visibility')"
            class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors p-1 -m-1 focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400 rounded"
            :aria-label="$t('header.toggleBalance')">
                    <svg v-if="isVisible" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>

    <div class="flex items-baseline gap-1.5 mb-4">
        <span class="text-sm sm:text-lg font-medium text-[var(--color-text-muted)]">Rp</span>
        <h2 class="text-2xl sm:text-3xl font-black text-[var(--color-text-primary)] tracking-tight leading-none" aria-live="polite">
            {{ displayAmount(totalPortfolio) }}
        </h2>
    </div>

    <!-- Wallet List Section -->
    <div v-if="wallets && wallets.length > 0" class="pt-3 border-t border-[var(--color-border-default)]">
        <button @click="toggleSection('wallets')"
            class="w-full flex items-center justify-between py-2 text-left group/section">
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[var(--color-text-secondary)] transition-transform duration-200"
                    :class="!collapsedSections.wallets ? 'rotate-90' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">
                    {{ $t('wallet.title') }}
                </span>
                <span class="text-2xs text-[var(--color-text-muted)] font-bold">({{ wallets.length }})</span>
                    </div>
                </button>

        <div class="grid transition-all duration-300 ease-in-out"
            :style="{ gridTemplateRows: collapsedSections.wallets ? '0fr' : '1fr' }">
            <div class="overflow-hidden transition-all duration-300"
                :class="collapsedSections.wallets ? 'opacity-0' : 'opacity-100'">
                <div class="space-y-2 pt-2 pb-1">
                    <Link v-for="wallet in wallets" :key="wallet.id"
                        :href="route('wallets.show', wallet.id)"
                        class="flex items-center justify-between p-2 rounded-lg bg-white/5 hover:bg-white/10 transition-colors active:scale-[0.98]">
                        <div class="flex items-center gap-2 min-w-0">
                            <AppIcon :icon="wallet.icon" fallback="wallet"
                                :class="['w-4 h-4 shrink-0', getWalletIconColor()]" />
                            <span class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest truncate">
                                {{ wallet.name }}
                            </span>
                        </div>
                        <span class="text-xs font-bold tracking-tight shrink-0"
                            :class="parseFloat(wallet.balance) < 0 ? 'text-[var(--color-expense-text)]' : 'text-[var(--color-text-primary)]'">
                            <span class="text-2xs text-[var(--color-text-muted)] mr-0.5">Rp</span>{{ isVisible ? formatNumber(wallet.balance) : '••••' }}
                        </span>
                    </Link>
                    <Link :href="route('wallets.create')"
                        class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-dashed border-[var(--color-border-subtle)] hover:border-[var(--color-brand-border)] text-[var(--color-text-muted)] hover:text-[var(--color-brand)] transition-colors text-2xs font-bold uppercase tracking-widest">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ $t('wallet.addNew') }}
                    </Link>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Hutang Section -->
    <div v-if="totalHutang > 0" class="border-t border-[var(--color-border-default)]">
        <button @click="toggleSection('hutang')"
            class="w-full flex items-center justify-between py-2 text-left group/section">
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[var(--color-text-secondary)] transition-transform duration-200"
                    :class="!collapsedSections.hutang ? 'rotate-90' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">
                    {{ $t('wallet.totalDebt') }}
                </span>
            </div>
            <div class="flex items-center gap-1">
                <span class="text-xs font-bold text-[var(--color-debt-text)] tracking-tight">
                    <span class="text-2xs text-[var(--color-text-muted)] mr-0.5">Rp</span>{{ displayLocal(totalHutang, hutangVisible) }}
                </span>
                <button type="button" @click.stop="hutangVisible = !hutangVisible"
                    class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors p-0.5">
                            <svg v-if="hutangVisible" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </button>

        <div class="grid transition-all duration-300 ease-in-out"
            :style="{ gridTemplateRows: collapsedSections.hutang ? '0fr' : '1fr' }">
            <div class="overflow-hidden transition-all duration-300"
                :class="collapsedSections.hutang ? 'opacity-0' : 'opacity-100'">
                <div class="pt-2 pb-1">
                    <Link :href="route('loans.index', { type: 'hutang' })"
                        class="flex items-center justify-between p-2 rounded-lg bg-[var(--color-debt-bg)] hover:bg-[var(--color-debt-bg-hover)] transition-colors active:scale-[0.98] border border-[var(--color-debt-border)]">
                        <span class="text-2xs font-bold text-[var(--color-debt-text)] uppercase tracking-widest">
                            {{ $t('wallet.viewDebtDetail') }}
                        </span>
                        <svg class="w-3.5 h-3.5 text-[var(--color-debt-text)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Piutang Section -->
    <div v-if="totalPiutang > 0" class="border-t border-[var(--color-border-default)]">
        <button @click="toggleSection('piutang')"
            class="w-full flex items-center justify-between py-2 text-left group/section">
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[var(--color-text-secondary)] transition-transform duration-200"
                    :class="!collapsedSections.piutang ? 'rotate-90' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">
                    {{ $t('wallet.totalReceivable') }}
                </span>
            </div>
            <div class="flex items-center gap-1">
                <span class="text-xs font-bold text-[var(--color-receivable-text)] tracking-tight">
                    <span class="text-2xs text-[var(--color-text-muted)] mr-0.5">Rp</span>{{ displayLocal(totalPiutang, piutangVisible) }}
                </span>
                <button type="button" @click.stop="piutangVisible = !piutangVisible"
                    class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors p-0.5">
                            <svg v-if="piutangVisible" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </button>

        <div class="grid transition-all duration-300 ease-in-out"
            :style="{ gridTemplateRows: collapsedSections.piutang ? '0fr' : '1fr' }">
            <div class="overflow-hidden transition-all duration-300"
                :class="collapsedSections.piutang ? 'opacity-0' : 'opacity-100'">
                <div class="pt-2 pb-1">
                    <Link :href="route('loans.index', { type: 'piutang' })"
                        class="flex items-center justify-between p-2 rounded-lg bg-[var(--color-receivable-bg)] hover:bg-[var(--color-receivable-bg-hover)] transition-colors active:scale-[0.98] border border-[var(--color-receivable-border)]">
                        <span class="text-2xs font-bold text-[var(--color-receivable-text)] uppercase tracking-widest">
                            {{ $t('wallet.viewReceivableDetail') }}
                        </span>
                        <svg class="w-3.5 h-3.5 text-[var(--color-receivable-text)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
