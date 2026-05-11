<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateTransactionFab from '@/Components/CreateTransactionFab.vue';
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue';
import GoogleAd from '@/Components/GoogleAd.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    totalPortfolio: Number,
    totalLiquid: Number,
    totalInvest: Number,
    wallets: Array,
    totalHutang: Number,
    totalPiutang: Number,
    thisMonthIncome: Number,
    thisMonthExpense: Number,
    recentTransactions: Array,
});

const user = usePage().props.auth.user;
const showModal = ref(false);
const selectedTransaction = ref(null);

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return { text: 'Selamat Pagi', emoji: '☀️' };
    if (hour < 15) return { text: 'Selamat Siang', emoji: '🌤️' };
    if (hour < 18) return { text: 'Selamat Sore', emoji: '🌇' };
    return { text: 'Selamat Malam', emoji: '🌙' };
});

const insight = computed(() => {
    let type = 'info';
    let msg = 'Selamat datang di Bendaharaku! Yuk catat keuanganmu hari ini.';
    let icon = '💡';

    if (props.thisMonthExpense > 0 && props.thisMonthIncome > 0) {
        const ratio = (props.thisMonthExpense / props.thisMonthIncome) * 100;
        if (ratio >= 80) {
            type = 'danger';
            msg = 'Awas! Pengeluaran bulan ini sudah mendekati total pemasukanmu.';
            icon = '⚠️';
        } else if (ratio <= 40) {
            type = 'success';
            msg = 'Bagus sekali! Pengeluaranmu bulan ini sangat terjaga.';
            icon = '✨';
        } else {
            msg = 'Arus kas bulan ini berjalan normal. Terus catat pengeluaranmu!';
            icon = '📊';
        }
    } else if (props.thisMonthExpense > 0 && props.thisMonthIncome === 0) {
        type = 'warning';
        msg = 'Belum ada pemasukan bulan ini, tapi pengeluaran terus jalan. Hati-hati!';
        icon = '🚨';
    }
    return { type, msg, icon };
});

const showInsight = ref(!sessionStorage.getItem('insightDismissed'));

const dismissInsight = () => {
    sessionStorage.setItem('insightDismissed', 'true');
    showInsight.value = false;
};

const openModal = (trx) => {
    selectedTransaction.value = trx;
    showModal.value = true;
};

const avatarSrc = computed(() => {
    const avatar = user.avatar;
    if (avatar && (avatar.startsWith('http://') || avatar.startsWith('https://'))) return avatar;
    return avatar ? `/storage/${avatar}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=1A1A1A&color=FCA5FF&bold=true`;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dashboard" />

        <div class="p-5 pb-32 max-w-md mx-auto">
            
            <!-- HEADER -->
            <header class="flex justify-between items-center mb-6 pt-4 animate-fade-in-up">
                <div>
                    <p class="text-xs text-purple-500 font-black uppercase tracking-[0.3em] mb-0.5 opacity-80">Hello</p>
                    <h1 class="text-2xl font-black text-white tracking-tight leading-none">{{ user.name }}</h1>
                    <div class="flex items-center gap-2 mb-1">
                        <p class="text-sm text-gray-400 font-bold uppercase tracking-[0.1em]">{{ greeting.text }}</p>
                        <span class="text-sm">{{ greeting.emoji }}</span>
                    </div>
                </div>

                <Link :href="route('profile.edit')" class="relative block w-12 h-12 rounded-full border-2 border-purple-500 p-0.5 bg-gray-900 active:scale-90 transition-transform">
                    <img :src="avatarSrc" :alt="user.name" class="w-full h-full rounded-full object-cover">
                </Link>
            </header>

            <!-- INSIGHT BOX -->
            <div v-if="showInsight" class="mb-6 p-3 rounded-xl border items-center justify-between gap-3 animate-fade-in-up delay-100 text-xs uppercase font-bold tracking-widest flex"
                :class="{
                    'bg-red-950/40 border-red-900/50 text-red-400': insight.type === 'danger',
                    'bg-green-950/40 border-green-900/50 text-green-400': insight.type === 'success',
                    'bg-yellow-950/40 border-yellow-900/50 text-yellow-400': insight.type === 'warning',
                    'bg-gray-900/80 border-gray-900/50 text-gray-400': insight.type === 'info'
                }">
                <div class="flex items-center gap-3">
                    <span class="text-base">{{ insight.icon }}</span>
                    <p class="leading-relaxed">{{ insight.msg }}</p>
                </div>
                <button @click="dismissInsight" class="text-current opacity-70 hover:opacity-100 transition-opacity p-1 focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- HERO CARD -->
            <div class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 overflow-hidden mb-5 group animate-fade-in-up delay-200">
                <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                <!-- Background Graph SVG -->
                <div class="absolute inset-x-0 bottom-0 opacity-20 pointer-events-none h-24">
                    <svg viewBox="0 0 400 150" preserveAspectRatio="none" class="w-full h-full">
                        <path d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50 L400,150 L0,150 Z" fill="url(#chartGradient)"></path>
                        <path d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50" stroke="#FCA5FF" stroke-width="3" fill="none"></path>
                        <defs>
                            <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#FCA5FF; stop-opacity:0.4" />
                                <stop offset="100%" style="stop-color:#FCA5FF; stop-opacity:0" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>

                <div class="relative z-10 p-7 pb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-xl bg-purple-500"></div>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-[0.2em]">Total Kekayaan</p>
                        </div>
                        <span class="text-xs font-bold text-green-400 bg-green-400/10 px-2 py-0.5 rounded-full border border-green-400/20">Live</span>
                    </div>
                    
                    <div class="flex items-baseline gap-1.5 mb-4">
                        <span class="text-lg font-medium text-gray-500">Rp</span>
                        <h2 class="text-3xl font-black text-white tracking-tight">
                            {{ formatNumber(totalPortfolio) }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-4 pt-3 border-t border-white/10 mt-1">
                        <div class="flex-1">
                            <div class="flex items-center gap-1.5 mb-1">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-400"></div>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Liquid</p>
                            </div>
                            <p class="text-sm font-bold text-white tracking-tight">
                                <span class="text-xs text-gray-500 mr-0.5">Rp</span>{{ formatNumber(totalLiquid) }}
                            </p>
                        </div>
                        <div class="w-px h-8 bg-gradient-to-b from-transparent via-white/10 to-transparent"></div>
                        <div class="flex-1">
                            <div class="flex items-center gap-1.5 mb-1">
                                <div class="w-1.5 h-1.5 rounded-full bg-purple-400"></div>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Investasi</p>
                            </div>
                            <p class="text-sm font-bold text-white tracking-tight">
                                <span class="text-xs text-gray-500 mr-0.5">Rp</span>{{ formatNumber(totalInvest) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MINI CASHFLOW -->
            <div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-200">
                <div class="bg-gradient-to-br from-green-950/20 to-gray-800 border border-green-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
                    <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center text-green-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Pemasukan</p>
                        <p class="text-sm font-bold text-white tracking-tight mt-0.5"><span class="text-xs text-gray-500 mr-1">Rp</span><span class="text-green-400">{{ formatNumber(thisMonthIncome) }}</span></p>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-red-950/20 to-gray-800 border border-red-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
                    <div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center text-red-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Pengeluaran</p>
                        <p class="text-sm font-bold text-white tracking-tight mt-0.5"><span class="text-xs text-gray-500 mr-1">Rp</span><span class="text-red-400">{{ formatNumber(thisMonthExpense) }}</span></p>
                    </div>
                </div>
            </div>
            
            <!-- GOOGLE ADS EXAMPLE -->
            <GoogleAd ad-slot="1234567890" />

            <!-- WALLETS -->
            <div class="flex justify-between items-center mb-5 px-1 gap-3 animate-fade-in-up delay-300">
                <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> Aset Saya
                </h2>
                <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
                <Link :href="route('wallets.create')" class="text-gray-400 hover:text-white transition-colors active:scale-90">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-300">
                <Link v-for="wallet in wallets" :key="wallet.id" :href="route('wallets.show', wallet.id)" class="relative group bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-4 border border-white/10 active:scale-95 transition-all overflow-hidden h-[100px] flex flex-col justify-between hover:border-blue-500/30">
                    <div class="relative z-10 flex items-center gap-2">
                        <div class="w-8 h-8 shrink-0 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 flex items-center justify-center text-base border border-white/10 group-hover:scale-110 transition-transform overflow-hidden">
                            <img v-if="wallet.icon?.includes('.')" :src="'/storage/' + wallet.icon" class="w-full h-full object-cover">
                            <span v-else>{{ wallet.icon || '💳' }}</span>
                        </div>
                        <p class="text-xs font-black text-gray-400 uppercase tracking-tight truncate leading-tight group-hover:text-white transition-colors">{{ wallet.name }}</p>
                    </div>
                    <div class="relative z-10">
                        <p class="text-lg font-bold text-white tracking-tighter truncate"><span class="text-xs text-gray-600 font-medium mr-0.5">Rp</span>{{ formatNumber(wallet.balance) }}</p>
                    </div>
                </Link>
                <div v-if="wallets.length === 0" class="col-span-2 text-center py-6 rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10">
                    <span class="text-2xl mb-2 block">🏦</span>
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-widest">Belum Ada Dompet Aktif</p>
                </div>
            </div>

            <!-- LOANS -->
            <div class="flex justify-between items-center mb-4 px-1 gap-3 animate-fade-in-up delay-400">
                <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span> Kewajiban
                </h2>
                <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
                <Link :href="route('loans.index', { type: 'hutang' })" class="text-xs font-bold text-purple-400 hover:text-white transition-colors uppercase tracking-widest">
                    Semua
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-400">
                <Link :href="route('loans.index', { type: 'hutang' })" class="active:scale-95 transition-transform group">
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/10 relative overflow-hidden h-[100px] hover:border-yellow-400">
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-1.5 h-1.5 rounded-full bg-[#E5D07E]"></div>
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Hutang</h3>
                            </div>
                            <p class="text-base font-bold text-white tracking-tight truncate"><span class="text-xs text-gray-600 mr-1">Rp</span>{{ formatNumber(totalHutang) }}</p>
                        </div>
                    </div>
                </Link>
                <Link :href="route('loans.index', { type: 'piutang' })" class="active:scale-95 transition-transform group">
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-4 rounded-xl border border-white/10 relative overflow-hidden h-[100px] hover:border-purple-400">
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-1.5 h-1.5 rounded-full bg-[#FCA5FF]"></div>
                                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Piutang</h3>
                            </div>
                            <p class="text-base font-bold text-white tracking-tight truncate"><span class="text-xs text-gray-600 mr-1">Rp</span>{{ formatNumber(totalPiutang) }}</p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- RECENT ACTIVITY -->
            <div class="flex justify-between items-center mb-4 px-1 gap-3 animate-fade-in-up delay-500">
                <h2 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Aktivitas Terakhir
                </h2>
                <div class="flex-1 h-px bg-gradient-to-r from-purple-500 to-transparent"></div>
                <Link :href="route('transactions.index')" class="text-xs font-bold text-purple-400 hover:text-white transition-colors uppercase tracking-widest">
                    Semua
                </Link>
            </div>

            <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-2 mb-8 animate-fade-in-up delay-500">
                <button v-for="trx in recentTransactions" :key="trx.id" @click="openModal(trx)" class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-gray-800 active:scale-95 transition-all group text-left relative overflow-hidden">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-10 h-10 shrink-0 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl flex items-center justify-center text-lg overflow-hidden">
                            <img v-if="trx.category?.icon?.includes('.')" :src="'/storage/' + trx.category.icon" class="w-full h-full object-cover">
                            <span v-else>{{ trx.category?.icon || '📝' }}</span>
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-white truncate">{{ trx.category?.category_name || 'Transfer' }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-widest truncate mt-0.5">
                                {{ trx.short_date }} • {{ trx.notes || trx.type.name }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right shrink-0 pl-2">
                        <p class="text-sm font-bold tracking-tight" :class="trx.type.name === 'Income' ? 'text-green-400' : 'text-white'">
                            {{ trx.type.name === 'Income' ? '+' : '-' }}Rp{{ formatNumber(trx.amount) }}
                        </p>
                    </div>
                </button>
                <div v-if="recentTransactions.length === 0" class="text-center py-6">
                    <span class="text-2xl mb-2 block opacity-50">💸</span>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Belum Ada Transaksi</p>
                </div>
            </div>

            <CreateTransactionFab />
        </div>

        <TransactionDetailModal :show="showModal" :transaction="selectedTransaction" @close="showModal = false" />
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up { 0% { opacity: 0; transform: translateY(15px); } 100% { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
.delay-100 { animation-delay: 100ms; }
.delay-200 { animation-delay: 200ms; }
.delay-300 { animation-delay: 300ms; }
.delay-400 { animation-delay: 400ms; }
.delay-500 { animation-delay: 500ms; }
</style>
