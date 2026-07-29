<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility';
import { formatNumber } from '@/utils/format.js';
import { getWalletIconColor } from '@/Composables/useIcon.js';
import AppIcon from '@/Components/AppIcon.vue';
import PortfolioCard from '@/Pages/Dashboard/PortfolioCard.vue';

const { t } = useI18n();
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility();

const props = defineProps({
    wallets: Array,
    totalHutang: Number,
    totalPiutang: Number,
});

const liquidWallets = computed(() => props.wallets.filter(w => w.group_type === 'Liquid'));
const assetWallets = computed(() => props.wallets.filter(w => w.group_type === 'Asset'));

const totalLiquidBalance = computed(() => {
    return liquidWallets.value.reduce((sum, w) => sum + parseFloat(w.balance), 0);
});

const totalAssetBalance = computed(() => {
    return assetWallets.value.reduce((sum, w) => sum + parseFloat(w.balance), 0);
});

// Karena backend sudah memfilter hanya group_type Liquid/Asset (System diblokir),
// totalBalance selalu sama dengan liquid + asset — tidak perlu loop ulang seluruh
// array wallets untuk ketiga kalinya.
const totalBalance = computed(() => totalLiquidBalance.value + totalAssetBalance.value);


</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="t('wallet.title')" />

        <div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8">
            <div class="hidden lg:block mb-4 pt-4 animate-fade-in-up">
                <p class="text-2xs text-purple-500 font-black uppercase tracking-[0.3em] mb-1 opacity-80">Portfolio</p>
                <h1 class="text-2xl font-black text-white tracking-tight leading-none">{{ $t('wallet.title') }}</h1>
            </div>

            <div class="animate-fade-in-up">
                <PortfolioCard :total-portfolio="totalBalance" :is-visible="isBalanceVisible"
                    :total-hutang="totalHutang" :total-piutang="totalPiutang"
                    :wallets-expanded="false" @toggle-visibility="toggleVisibility" />
                <Link :href="route('wallets.create')"
                    class="w-full flex items-center justify-center gap-2 p-3 rounded-xl border border-dashed border-white/10 hover:border-purple-500/50 text-gray-500 hover:text-purple-400 transition-colors text-2xs font-bold uppercase tracking-widest mb-5 active:scale-[0.98]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ $t('wallet.addNew') }}
                </Link>
            </div>

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
                            <AppIcon :icon="wallet.icon" fallback="wallet"
                                     :class="['w-6 h-6 shrink-0', getWalletIconColor()]" />
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs font-bold text-white leading-tight whitespace-nowrap truncate">{{ wallet.name }}</h3>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2.5">
                            <p class="text-xs font-bold tracking-tight"
                                :class="parseFloat(wallet.balance) < 0 ? 'text-red-400' : 'text-white'">
                                <span class="text-[9px] text-gray-500 mr-0.5">Rp</span>{{ isBalanceVisible ?
                                formatNumber(wallet.balance) : '••••' }}</p>

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
                            <AppIcon :icon="wallet.icon" fallback="wallet"
                                     :class="['w-6 h-6 shrink-0', getWalletIconColor()]" />
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs font-bold text-white leading-tight whitespace-nowrap truncate">{{ wallet.name }}</h3>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2.5">
                            <p class="text-xs font-bold tracking-tight"
                                :class="parseFloat(wallet.balance) < 0 ? 'text-red-400' : 'text-white'">
                                <span class="text-[9px] text-gray-500 mr-0.5">Rp</span>{{ isBalanceVisible ?
                                formatNumber(wallet.balance) : '••••' }}</p>

                        </div>
                    </Link>

                    <!-- Empty state asset -->
                    <div v-if="assetWallets.length === 0"
                        class="col-span-full text-center py-6 bg-linear-to-br from-gray-900 to-gray-800 border border-dashed border-white/10 rounded-xl">
                        <p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">{{ t('wallet.emptyAsset') }}</p>
                    </div>
                </div>
            </section>
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