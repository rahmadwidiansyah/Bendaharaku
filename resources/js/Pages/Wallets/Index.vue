<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility';

const { isBalanceVisible, toggleVisibility } = useBalanceVisibility();

const props = defineProps({
    wallets: Array,
    totalHutang: Number,
    totalPiutang: Number,
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const liquidWallets = computed(() => props.wallets.filter(w => w.group_type === 'Liquid'));
const assetWallets = computed(() => props.wallets.filter(w => w.group_type === 'Asset'));
const otherWallets = computed(() => props.wallets.filter(w => w.group_type !== 'Liquid' && w.group_type !== 'Asset'));

const totalBalance = computed(() => {
    return props.wallets.reduce((sum, w) => sum + parseFloat(w.balance), 0);
});

const handleImageError = (e, fallback) => {
    e.target.style.display = 'none';
    const parent = e.target.parentElement;
    if (parent) {
        const span = document.createElement('span');
        span.innerText = fallback;
        span.className = 'text-xl animate-pulse';
        parent.appendChild(span);
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Aset Saya" />

        <div class="p-5 pb-32 max-w-md mx-auto">
            <header class="mb-8 pt-4 animate-fade-in-up">
                <p class="text-xs text-purple-500 font-black uppercase tracking-[0.3em] mb-1 opacity-80">Portfolio</p>
                <h1 class="text-2xl font-black text-white tracking-tight leading-none mb-4">Aset & Dompet</h1>
                
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl border border-white/10 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    <div class="relative z-10 p-6">
                        <div class="flex justify-between items-center mb-1">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Total Kekayaan</p>
                            <button @click="toggleVisibility" class="text-gray-500 hover:text-white transition-colors p-1 -m-1">
                                <svg v-if="isBalanceVisible" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                            </button>
                        </div>
                        <h2 class="text-3xl font-black text-white tracking-tight">
                            <span class="text-lg text-gray-500 font-medium mr-1">Rp</span>{{ isBalanceVisible ? formatNumber(totalBalance) : '••••••••' }}
                        </h2>
                    </div>
                </div>
            </header>

            <!-- LIQUID WALLETS -->
            <section class="mb-8 animate-fade-in-up delay-100">
                <div class="flex justify-between items-center mb-4 px-1 gap-3">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> Liquid
                    </h2>
                    <div class="flex-1 h-px bg-gradient-to-r from-blue-500/30 to-transparent"></div>
                </div>
                
                <div class="grid grid-cols-1 gap-3">
                    <Link v-for="wallet in liquidWallets" :key="wallet.id" :href="route('wallets.show', wallet.id)" 
                        class="flex items-center justify-between p-4 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 active:scale-[0.98] transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gray-900 border border-white/10 flex items-center justify-center text-xl group-hover:scale-110 transition-transform overflow-hidden" :class="wallet.icon?.includes('.') ? 'p-2' : ''">
                                <img v-if="wallet.icon?.includes('.')" 
                                    :src="wallet.icon.startsWith('http') ? wallet.icon : '/storage/' + wallet.icon" 
                                    class="w-full h-full object-contain" 
                                    @error="(e) => handleImageError(e, wallet.keyword?.substring(0,1) || '💳')">
                                <span v-else>{{ wallet.icon || '💳' }}</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white leading-tight">{{ wallet.name }}</h3>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ wallet.keyword || 'Dompet' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-white tracking-tight">Rp{{ isBalanceVisible ? formatNumber(wallet.balance) : '••••' }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- ASSET WALLETS -->
            <section class="mb-8 animate-fade-in-up delay-200">
                <div class="flex justify-between items-center mb-4 px-1 gap-3">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span> Investasi & Aset
                    </h2>
                    <div class="flex-1 h-px bg-gradient-to-r from-purple-500/30 to-transparent"></div>
                </div>
                
                <div class="grid grid-cols-1 gap-3">
                    <Link v-for="wallet in assetWallets" :key="wallet.id" :href="route('wallets.show', wallet.id)" 
                        class="flex items-center justify-between p-4 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 active:scale-[0.98] transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gray-900 border border-white/10 flex items-center justify-center text-xl group-hover:scale-110 transition-transform overflow-hidden" :class="wallet.icon?.includes('.') ? 'p-2' : ''">
                                <img v-if="wallet.icon?.includes('.')" 
                                    :src="wallet.icon.startsWith('http') ? wallet.icon : '/storage/' + wallet.icon" 
                                    class="w-full h-full object-contain" 
                                    @error="(e) => handleImageError(e, wallet.keyword?.substring(0,1) || '💰')">
                                <span v-else>{{ wallet.icon || '💰' }}</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white leading-tight">{{ wallet.name }}</h3>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{{ wallet.keyword || 'Aset' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-white tracking-tight">Rp{{ isBalanceVisible ? formatNumber(wallet.balance) : '••••' }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- OTHER WALLETS -->
            <section v-if="otherWallets.length > 0" class="mb-8 animate-fade-in-up delay-300">
                <div class="flex justify-between items-center mb-4 px-1 gap-3">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Lainnya
                    </h2>
                    <div class="flex-1 h-px bg-gradient-to-r from-gray-500/30 to-transparent"></div>
                </div>
                
                <div class="grid grid-cols-1 gap-3">
                    <Link v-for="wallet in otherWallets" :key="wallet.id" :href="route('wallets.show', wallet.id)" 
                        class="flex items-center justify-between p-4 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 active:scale-[0.98] transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gray-900 border border-white/10 flex items-center justify-center text-xl group-hover:scale-110 transition-transform overflow-hidden" :class="wallet.icon?.includes('.') ? 'p-2' : ''">
                                <img v-if="wallet.icon?.includes('.')" 
                                    :src="wallet.icon.startsWith('http') ? wallet.icon : '/storage/' + wallet.icon" 
                                    class="w-full h-full object-contain" 
                                    @error="(e) => handleImageError(e, wallet.keyword?.substring(0,1) || '🏦')">
                                <span v-else>{{ wallet.icon || '🏦' }}</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white leading-tight">{{ wallet.name }}</h3>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-white tracking-tight">Rp{{ isBalanceVisible ? formatNumber(wallet.balance) : '••••' }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- KEWAJIBAN (Pindahan dari Dashboard) -->
            <section class="mb-10 animate-fade-in-up delay-400">
                <div class="flex justify-between items-center mb-5 px-1 gap-3">
                    <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span> Kewajiban
                    </h2>
                    <div class="flex-1 h-px bg-gradient-to-r from-yellow-500/30 to-transparent"></div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <Link :href="route('loans.index', { type: 'hutang' })" class="active:scale-95 transition-transform group">
                        <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/10 relative overflow-hidden h-[110px] hover:border-yellow-400">
                            <div class="relative z-10 flex flex-col justify-between h-full">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-[#E5D07E]"></div>
                                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Hutang</h3>
                                </div>
                                <p class="text-base font-bold text-white tracking-tight truncate">
                                    <span class="text-xs text-gray-600 mr-1">Rp</span>{{ isBalanceVisible ? formatNumber(totalHutang) : '••••' }}
                                </p>
                            </div>
                        </div>
                    </Link>
                    <Link :href="route('loans.index', { type: 'piutang' })" class="active:scale-95 transition-transform group">
                        <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/10 relative overflow-hidden h-[110px] hover:border-purple-400">
                            <div class="relative z-10 flex flex-col justify-between h-full">
                                <div class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-[#FCA5FF]"></div>
                                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Piutang</h3>
                                </div>
                                <p class="text-base font-bold text-white tracking-tight truncate">
                                    <span class="text-xs text-gray-600 mr-1">Rp</span>{{ isBalanceVisible ? formatNumber(totalPiutang) : '••••' }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <Link :href="route('wallets.create')" class="w-full flex items-center justify-center gap-2 p-4 rounded-xl border-2 border-dashed border-white/10 text-gray-500 hover:text-white hover:border-purple-500/50 transition-all font-bold uppercase tracking-widest text-xs animate-fade-in-up delay-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Dompet / Aset
            </Link>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
.delay-100 { animation-delay: 100ms; }
.delay-200 { animation-delay: 200ms; }
.delay-300 { animation-delay: 300ms; }
.delay-400 { animation-delay: 400ms; }
</style>
