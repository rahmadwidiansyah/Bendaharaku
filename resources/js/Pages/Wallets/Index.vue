<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility';
import { formatNumber } from '@/utils/format.js';
import AppIcon from '@/Components/AppIcon.vue';

const { t } = useI18n();
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility();

const togglePin = (wallet) => {
    const newState = wallet.is_pinned === true ? false : true;
    router.patch(route('wallets.set-pin', wallet.id), { state: newState }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const props = defineProps({
    wallets: Array,
    totalHutang: Number,
    totalPiutang: Number,
});

const liquidWallets = computed(() => props.wallets.filter(w => w.group_type === 'Liquid'));
const assetWallets = computed(() => props.wallets.filter(w => w.group_type === 'Asset'));

const totalBalance = computed(() => {
    return props.wallets.reduce((sum, w) => sum + parseFloat(w.balance), 0);
});

const totalLiquidBalance = computed(() => {
    return liquidWallets.value.reduce((sum, w) => sum + parseFloat(w.balance), 0);
});

const totalAssetBalance = computed(() => {
    return assetWallets.value.reduce((sum, w) => sum + parseFloat(w.balance), 0);
});

const displayAmount = (n) => isBalanceVisible.value ? formatNumber(n) : '••••••••';
const displayShort = (n) => isBalanceVisible.value ? formatNumber(n) : '••••';
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="t('wallet.title')" />

        <div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8">
            <header class="mb-6 pt-4 animate-fade-in-up">
                <div class="hidden lg:block mb-4">
                    <p class="text-2xs text-purple-500 font-black uppercase tracking-[0.3em] mb-1 opacity-80">Portfolio</p>
                    <h1 class="text-2xl font-black text-white tracking-tight leading-none">{{ $t('wallet.title') }}</h1>
                </div>

                <div class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl border border-white/10 overflow-hidden group">
                    <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />

                    <div class="absolute inset-x-0 bottom-0 opacity-20 pointer-events-none h-24">
                        <svg viewBox="0 0 400 150" preserveAspectRatio="none" class="w-full h-full">
                            <defs>
                                <linearGradient id="wealthChartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#fca5ff; stop-opacity:0.4" />
                                    <stop offset="100%" style="stop-color:#fca5ff; stop-opacity:0" />
                                </linearGradient>
                            </defs>
                            <path d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50 L400,150 L0,150 Z" fill="url(#wealthChartGradient)" />
                            <path d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50" stroke="#FCA5FF" stroke-width="3" fill="none" />
                        </svg>
                    </div>

                    <div class="relative z-10 p-6 pb-5">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-purple-500" />
                                <p class="text-2xs text-gray-400 font-bold uppercase tracking-[0.2em]">{{ $t('portfolio.title') }}</p>
                            </div>
                            <button @click="toggleVisibility"
                                class="text-gray-500 hover:text-white transition-colors p-1 -m-1">
                                <svg v-if="isBalanceVisible" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-baseline gap-1.5 mb-4">
                            <span class="text-lg font-medium text-gray-500">Rp</span>
                            <h2 class="text-3xl font-black text-white tracking-tight">{{ displayAmount(totalBalance) }}</h2>
                        </div>

                        <div class="flex items-center gap-4 pt-3 border-t border-white/10">
                            <div class="flex-1">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-blue-400" />
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ $t('portfolio.liquid') }}</p>
                                </div>
                                <p class="text-sm font-bold text-white tracking-tight">
                                    <span class="text-2xs text-gray-500 mr-0.5">Rp</span>{{ displayShort(totalLiquidBalance) }}
                                </p>
                            </div>
                            <div class="w-px h-8 bg-gradient-to-b from-transparent via-white/10 to-transparent" />
                            <div class="flex-1">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-purple-400" />
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ $t('portfolio.investment') }}</p>
                                </div>
                                <p class="text-sm font-bold text-white tracking-tight">
                                    <span class="text-2xs text-gray-500 mr-0.5">Rp</span>{{ displayShort(totalAssetBalance) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- LIQUID WALLETS -->
            <section class="mb-6 animate-fade-in-up delay-100">
                <div class="flex justify-between items-center mb-3 px-1 gap-3">
                    <h2 class="text-2xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> {{ $t('wallet.groupTypes.liquid') }}
                    </h2>
                    <div class="flex-1 h-px bg-linear-to-r from-blue-500/30 to-transparent"></div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <Link v-for="wallet in liquidWallets" :key="wallet.id" :href="route('wallets.show', wallet.id)"
                        class="flex flex-col justify-between p-3 bg-linear-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 active:scale-[0.98] transition-all group">
                        <div class="flex items-center gap-2.5">
                            <AppIcon :icon="wallet.icon" :fallback="wallet.keyword?.substring(0, 1) || 'wallet'"
                                     class="w-6 h-6 text-purple-400 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs font-bold text-white leading-tight whitespace-nowrap truncate">{{ wallet.name }}</h3>
                                <p class="text-[9px] text-gray-500 tracking-wider font-bold whitespace-nowrap truncate">{{
                                    wallet.keyword || $t('wallet.name') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2.5">
                            <p class="text-xs font-bold tracking-tight"
                                :class="parseFloat(wallet.balance) < 0 ? 'text-red-400' : 'text-white'">
                                <span class="text-[9px] text-gray-500 mr-0.5">Rp</span>{{ isBalanceVisible ?
                                formatNumber(wallet.balance) : '••••' }}</p>
                            <button @click.stop.prevent="togglePin(wallet)"
                                class="p-0.5 rounded-full z-10 transition-colors"
                                :class="wallet.is_pinned ? 'text-purple-500' : 'text-gray-500 hover:text-white'">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5v6l1 2 1-2v-6h5v-2l-2-2z" />
                                </svg>
                            </button>
                        </div>
                    </Link>

                    <!-- Empty state liquid -->
                    <div v-if="liquidWallets.length === 0"
                        class="col-span-full text-center py-6 bg-linear-to-br from-gray-900 to-gray-800 border border-dashed border-white/10 rounded-xl">
                        <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">{{ $t('wallet.emptyLiquid') }}</p>
                    </div>
                </div>
            </section>

            <!-- ASSET WALLETS -->
            <section class="mb-6 animate-fade-in-up delay-200">
                <div class="flex justify-between items-center mb-3 px-1 gap-3">
                    <h2 class="text-2xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span> {{ $t('wallet.groupTypes.asset') }}
                    </h2>
                    <div class="flex-1 h-px bg-linear-to-r from-purple-500/30 to-transparent"></div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <Link v-for="wallet in assetWallets" :key="wallet.id" :href="route('wallets.show', wallet.id)"
                        class="flex flex-col justify-between p-3 bg-linear-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 active:scale-[0.98] transition-all group">
                        <div class="flex items-center gap-2.5">
                            <AppIcon :icon="wallet.icon" :fallback="wallet.keyword?.substring(0, 1) || 'wallet'"
                                     class="w-6 h-6 text-purple-400 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs font-bold text-white leading-tight whitespace-nowrap truncate">{{ wallet.name }}</h3>
                                <p class="text-[9px] text-gray-500 tracking-wider font-bold whitespace-nowrap truncate">{{
                                    wallet.keyword || $t('wallet.name') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2.5">
                            <p class="text-xs font-bold tracking-tight"
                                :class="parseFloat(wallet.balance) < 0 ? 'text-red-400' : 'text-white'">
                                <span class="text-[9px] text-gray-500 mr-0.5">Rp</span>{{ isBalanceVisible ?
                                formatNumber(wallet.balance) : '••••' }}</p>
                            <button @click.stop.prevent="togglePin(wallet)"
                                class="p-0.5 rounded-full z-10 transition-colors"
                                :class="wallet.is_pinned ? 'text-purple-500 bg-white/5' : 'text-gray-500 hover:text-white'">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5v6l1 2 1-2v-6h5v-2l-2-2z" />
                                </svg>
                            </button>
                        </div>
                    </Link>

                    <!-- Empty state asset -->
                    <div v-if="assetWallets.length === 0"
                        class="col-span-full text-center py-6 bg-linear-to-br from-gray-900 to-gray-800 border border-dashed border-white/10 rounded-xl">
                        <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">{{ t('wallet.emptyAsset') }}</p>
                    </div>
                </div>
            </section>

            <!-- KEWAJIBAN -->
            <section class="mb-8 animate-fade-in-up delay-300">
                <div class="flex justify-between items-center mb-3 px-1 gap-3">
                    <h2 class="text-2xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span> {{ $t('common.total') }}
                    </h2>
                    <div class="flex-1 h-px bg-linear-to-r from-yellow-500/30 to-transparent"></div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <Link :href="route('loans.index', { type: 'hutang' })"
                        class="active:scale-95 transition-transform group">
                        <div
                            class="bg-linear-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/10 relative overflow-hidden min-h-[110px] hover:border-yellow-400">
                            <div class="relative z-10 flex flex-col justify-between h-full">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-yellow-500"></div>
                                    <h3 class="text-2xs font-bold uppercase tracking-widest text-gray-500">{{ $t('wallet.totalDebt') }}</h3>
                                </div>
                                <p class="text-base font-bold text-white tracking-tight truncate">
                                    <span class="text-2xs text-gray-600 mr-1">Rp</span>{{ isBalanceVisible ?
                                        formatNumber(totalHutang) : '••••' }}
                                </p>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('loans.index', { type: 'piutang' })"
                        class="active:scale-95 transition-transform group">
                        <div
                            class="bg-linear-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/10 relative overflow-hidden min-h-[110px] hover:border-purple-400">
                            <div class="relative z-10 flex flex-col justify-between h-full">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-purple-500"></div>
                                    <h3 class="text-2xs font-bold uppercase tracking-widest text-gray-500">{{ $t('wallet.totalReceivable') }}</h3>
                                </div>
                                <p class="text-base font-bold text-white tracking-tight truncate">
                                    <span class="text-2xs text-gray-600 mr-1">Rp</span>{{ isBalanceVisible ?
                                        formatNumber(totalPiutang) : '••••' }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <Link :href="route('wallets.create')"
                class="w-full flex items-center justify-center gap-2 p-4 rounded-xl bg-linear-to-br from-purple-800 to-purple-600 border border-white/10 text-white hover:border-purple-500/50 font-bold uppercase tracking-widest text-2xs hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ $t('wallet.addNew') }}
            </Link>
        </div>
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
    opacity: 0;
}

.delay-100 {
    animation-delay: 100ms;
}

.delay-200 {
    animation-delay: 200ms;
}

.delay-300 {
    animation-delay: 300ms;
}

.delay-400 {
    animation-delay: 400ms;
}
</style>
