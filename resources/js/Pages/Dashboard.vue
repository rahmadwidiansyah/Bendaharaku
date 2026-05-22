<script setup>

import DateModal from '@/Components/DateModal.vue';
import CreateTransactionFab from '@/Components/CreateTransactionFab.vue';
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue';
import GoogleAd from '@/Components/GoogleAd.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';

const { isDesktopLayout } = useLayoutPreference();


const props = defineProps({
    totalPortfolio: Number,
    totalLiquid: Number,
    totalInvest: Number,
    thisMonthIncome: Number,
    thisMonthExpense: Number,
    transactions: Object,
    pinnedWallets: Array,
    startDate: String,
    endDate: String,
    filters: Object,
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

const { isBalanceVisible, toggleVisibility } = useBalanceVisibility();

const user = usePage().props.auth.user;
const showModal = ref(false);
const selectedTransaction = ref(null);
const search = ref(props.filters?.search || '');
const type = ref(props.filters?.type || '');
const showSortModal = ref(false);
const collapsedDates = ref({});
const activeHistoryTab = ref('detail');
const calendarFilter = ref('total');

const getLocalYMD = (d) => {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const selectedCalendarDate = ref(getLocalYMD(new Date()));
const currentCalendarMonth = ref(new Date(props.startDate));

const calendarDays = computed(() => {
    const year = currentCalendarMonth.value.getFullYear();
    const month = currentCalendarMonth.value.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];
    for (let i = 0; i < startingDayOfWeek; i++) {
        days.push({ empty: true });
    }
    for (let i = 1; i <= daysInMonth; i++) {
        const dateObj = new Date(year, month, i);
        const dateStr = getLocalYMD(dateObj);
        let largestType = null;
        let largestAmount = 0;
        const dayData = groupedTransactions.value[dateStr];
        if (dayData) {
            if (calendarFilter.value === 'income') {
                if (dayData.income > 0) {
                    largestType = 'income';
                    largestAmount = dayData.income;
                }
            } else if (calendarFilter.value === 'expense') {
                if (dayData.expense > 0) {
                    largestType = 'expense';
                    largestAmount = dayData.expense;
                }
            } else {
                if (dayData.income > dayData.expense) {
                    largestType = 'income';
                    largestAmount = dayData.income;
                } else if (dayData.expense > dayData.income) {
                    largestType = 'expense';
                    largestAmount = dayData.expense;
                } else if (dayData.income > 0 || dayData.expense > 0) {
                    largestType = dayData.expense > 0 ? 'expense' : 'income';
                    largestAmount = dayData.expense > 0 ? dayData.expense : dayData.income;
                }
            }
        }
        days.push({
            empty: false,
            day: i,
            dateStr: dateStr,
            largestType,
            largestAmount
        });
    }
    return days;
});

const prevMonth = () => {
    const d = new Date(currentCalendarMonth.value.getFullYear(), currentCalendarMonth.value.getMonth() - 1, 1);
    const end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    router.get(route('dashboard'), {
        search: search.value,
        type: type.value,
        start_date: getLocalYMD(d),
        end_date: getLocalYMD(end)
    }, { preserveState: true, replace: true });
};

const canGoNextMonth = computed(() => {
    const today = new Date();
    if (currentCalendarMonth.value.getFullYear() > today.getFullYear()) return false;
    if (currentCalendarMonth.value.getFullYear() === today.getFullYear() && currentCalendarMonth.value.getMonth() >= today.getMonth()) return false;
    return true;
});

const nextMonth = () => {
    if (!canGoNextMonth.value) return;
    const d = new Date(currentCalendarMonth.value.getFullYear(), currentCalendarMonth.value.getMonth() + 1, 1);
    const end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    router.get(route('dashboard'), {
        search: search.value,
        type: type.value,
        start_date: getLocalYMD(d),
        end_date: getLocalYMD(end)
    }, { preserveState: true, replace: true });
};

const selectDate = (dateStr) => {
    selectedCalendarDate.value = dateStr;
};

const calendarMonthName = computed(() => {
    return currentCalendarMonth.value.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
});

watch(() => props.startDate, (newDate) => {
    currentCalendarMonth.value = new Date(newDate);
});

const selectedDateTransactions = computed(() => {
    if (groupedTransactions.value[selectedCalendarDate.value]) {
        return { [selectedCalendarDate.value]: groupedTransactions.value[selectedCalendarDate.value] };
    }
    return {};
});

const selectedDateFormatted = computed(() => {
    if (!selectedCalendarDate.value) return '';
    const d = new Date(selectedCalendarDate.value);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
});

const toggleDate = (dateKey) => {
    collapsedDates.value[dateKey] = !collapsedDates.value[dateKey];
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};

const formatCompactNumber = (num) => {
    return new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(num);
};

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return { text: 'Selamat Pagi', emoji: '☀️' };
    if (hour < 15) return { text: 'Selamat Siang', emoji: '🌤️' };
    if (hour < 18) return { text: 'Selamat Sore', emoji: '🌇' };
    return { text: 'Selamat Malam', emoji: '🌙' };
});

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
    if (props.transactions?.data) {
        props.transactions.data.forEach(trx => {
            if (!groups[trx.raw_date]) groups[trx.raw_date] = { date: trx.date, transactions: [], income: 0, expense: 0 };
            groups[trx.raw_date].transactions.push(trx);
            if (trx.type.name === 'Income') groups[trx.raw_date].income += trx.amount;
            if (trx.type.name === 'Expense') groups[trx.raw_date].expense += trx.amount;
        });
    }
    return groups;
});

const getTypeColor = (typeName) => {
    return {
        'Income': 'text-green-400 bg-green-400/10 border-green-400/20',
        'Expense': 'text-red-400 bg-red-400/10 border-red-400/20',
        'Transfer': 'text-blue-400 bg-blue-400/10 border-blue-400/20',
        'Debt': 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
        'Receivable': 'text-purple-400 bg-purple-400/10 border-purple-400/20'
    }[typeName] || 'text-gray-500';
};

const getTypeName = (typeName) => {
    return { 'Income': 'Pemasukan', 'Expense': 'Pengeluaran', 'Transfer': 'Transfer', 'Debt': 'Hutang', 'Receivable': 'Piutang' }[typeName] || 'Lainnya';
};

const setType = (newType) => {
    type.value = newType;
    showSortModal.value = false;
};

let timeout = null;
watch([search, type], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('dashboard'), {
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

const togglePin = (wallet) => {
    router.patch(route('wallets.set-pin', wallet.id), { state: false }, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Dashboard" />

        <div class="p-5 pb-32 w-full mx-auto">

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

                <Link :href="route('profile.edit')"
                    class="relative block w-12 h-12 rounded-full border-2 border-purple-500 p-0.5 bg-gray-900 active:scale-90 transition-transform">
                    <img :src="avatarSrc" :alt="user.name" class="w-full h-full rounded-full object-cover">
                </Link>
            </header>

            <!-- INSIGHT BOX -->
            <div v-if="showInsight"
                class="mb-6 p-3 rounded-xl border items-center justify-between gap-3 animate-fade-in-up delay-100 text-xs uppercase font-bold tracking-widest flex"
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
                <button @click="dismissInsight"
                    class="text-current opacity-70 hover:opacity-100 transition-opacity p-1 focus:outline-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <div :class="isDesktopLayout ? 'lg:flex lg:gap-8 lg:items-start' : ''">
                <!-- LEFT COLUMN (Desktop) -->
                <div :class="isDesktopLayout ? 'lg:w-1/3 lg:sticky lg:top-5' : ''">
                    <!-- HERO CARD -->
                    <div
                        class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 overflow-hidden mb-5 group animate-fade-in-up delay-200">
                        <div
                            class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>

                        <!-- Background Graph SVG -->
                        <div class="absolute inset-x-0 bottom-0 opacity-20 pointer-events-none h-24">
                            <svg viewBox="0 0 400 150" preserveAspectRatio="none" class="w-full h-full">
                                <path
                                    d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50 L400,150 L0,150 Z"
                                    fill="url(#chartGradient)"></path>
                                <path d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50"
                                    stroke="#FCA5FF" stroke-width="3" fill="none"></path>
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
                                    <p class="text-xs text-gray-400 font-bold uppercase tracking-[0.2em]">Total Kekayaan
                                    </p>
                                    <button @click="toggleVisibility"
                                        class="text-gray-500 hover:text-white transition-colors p-1 -m-1 ml-1">
                                        <svg v-if="isBalanceVisible" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                                <span
                                    class="text-xs font-bold text-green-400 bg-green-400/10 px-2 py-0.5 rounded-full border border-green-400/20">Live</span>
                            </div>

                            <div class="flex items-baseline gap-1.5 mb-4">
                                <span class="text-lg font-medium text-gray-500">Rp</span>
                                <h2 class="text-3xl font-black text-white tracking-tight">
                                    {{ isBalanceVisible ? formatNumber(totalPortfolio) : '••••••••' }}
                                </h2>
                            </div>

                            <div class="flex items-center gap-4 pt-3 border-t border-white/10 mt-1">
                                <div class="flex-1">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-400"></div>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Liquid
                                        </p>
                                    </div>
                                    <p class="text-sm font-bold text-white tracking-tight">
                                        <span class="text-xs text-gray-500 mr-0.5">Rp</span>{{ isBalanceVisible ?
                                            formatNumber(totalLiquid) : '••••' }}
                                    </p>
                                </div>
                                <div class="w-px h-8 bg-gradient-to-b from-transparent via-white/10 to-transparent">
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-400"></div>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">
                                            Investasi</p>
                                    </div>
                                    <p class="text-sm font-bold text-white tracking-tight">
                                        <span class="text-xs text-gray-500 mr-0.5">Rp</span>{{ isBalanceVisible ?
                                            formatNumber(totalInvest) : '••••' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PINNED WALLETS -->
                    <div v-if="pinnedWallets && pinnedWallets.length > 0" class="mb-5 animate-fade-in-up delay-200">
                        <div class="flex justify-between items-center mb-3 px-1 gap-3">
                            <h2
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                <svg class="w-3 h-3 text-purple-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg> Dompet Utama
                            </h2>
                            <div class="flex-1 h-px bg-gradient-to-r from-purple-500/20 to-transparent"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <Link v-for="wallet in pinnedWallets" :key="wallet.id"
                                :href="route('wallets.show', wallet.id)"
                                class="bg-gradient-to-br from-gray-900 to-gray-800 p-3.5 rounded-xl border border-white/10 relative overflow-hidden active:scale-95 transition-transform group hover:border-purple-400/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2 truncate">
                                        <div class="w-6 h-6 rounded-md bg-gray-900 border border-white/10 flex items-center justify-center text-xs overflow-hidden shrink-0"
                                            :class="wallet.icon?.includes('.') ? 'p-1' : ''">
                                            <img v-if="wallet.icon?.includes('.')"
                                                :src="wallet.icon.startsWith('http') ? wallet.icon : '/storage/' + wallet.icon"
                                                class="w-full h-full object-contain"
                                                @error="(e) => handleImageError(e, wallet.keyword?.substring(0, 1) || '💳')">
                                            <span v-else>{{ wallet.icon || '💳' }}</span>
                                        </div>
                                        <h3
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest truncate">
                                            {{ wallet.name }}</h3>
                                    </div>
                                    <button @click.stop.prevent="togglePin(wallet)"
                                        class="text-[#FCA5FF] hover:text-white transition-colors p-1 bg-white/5 rounded-full z-10 shrink-0 -mt-1 -mr-1"
                                        title="Unpin dari Dashboard">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5v6l1 2 1-2v-6h5v-2l-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-sm font-bold text-white tracking-tight truncate">
                                    <span class="text-[10px] text-gray-500 mr-1">Rp</span>{{ isBalanceVisible ?
                                    formatNumber(wallet.balance) : '••••' }}
                                </p>
                            </Link>
                        </div>
                    </div>

                    <!-- MINI CASHFLOW -->
                    <div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-200">
                        <div
                            class="bg-gradient-to-br from-green-950/20 to-gray-800 border border-green-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
                            <div
                                class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center text-green-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Pemasukan</p>
                                <p class="text-sm font-bold text-white tracking-tight mt-0.5"><span
                                        class="text-xs text-gray-500 mr-1">Rp</span><span class="text-green-400">{{
                                            isBalanceVisible ? formatNumber(thisMonthIncome) : '••••' }}</span></p>
                            </div>
                        </div>
                        <div
                            class="bg-gradient-to-br from-red-950/20 to-gray-800 border border-red-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
                            <div
                                class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center text-red-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Pengeluaran</p>
                                <p class="text-sm font-bold text-white tracking-tight mt-0.5"><span
                                        class="text-xs text-gray-500 mr-1">Rp</span><span class="text-red-400">{{
                                            isBalanceVisible ? formatNumber(thisMonthExpense) : '••••' }}</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- GOOGLE ADS -->
                    <div :class="['mb-6', isDesktopLayout ? 'hidden lg:block' : 'hidden']">
                        <GoogleAd ad-slot="2448030513" />
                    </div>
                    <div :class="isDesktopLayout ? 'lg:hidden' : ''">
                        <GoogleAd ad-slot="2448030513" />
                    </div>

                </div> <!-- END LEFT COLUMN -->

                <!-- RIGHT COLUMN (Desktop) -->
                <div
                    :class="isDesktopLayout ? 'lg:w-2/3 lg:bg-gray-900/30 lg:p-6 lg:rounded-2xl lg:border lg:border-white/5' : 'mt-8'">
                    <!-- TRANSACTION HISTORY -->
                    <div class="flex justify-between items-center mb-4 px-1 gap-3 animate-fade-in-up delay-500">
                        <h2
                            class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span> Histori Transaksi
                        </h2>
                        <div class="flex-1 h-px bg-gradient-to-r from-purple-500/50 to-transparent"></div>
                        <div class="flex bg-gray-900 rounded-lg border border-white/10 p-0.5 shrink-0">
                            <button @click="activeHistoryTab = 'detail'"
                                :class="activeHistoryTab === 'detail' ? 'bg-purple-500 text-white' : 'text-gray-400 hover:text-white'"
                                class="px-2 py-1 rounded-lg text-xs font-bold uppercase tracking-widest transition-colors">Detail</button>
                            <button @click="activeHistoryTab = 'calendar'"
                                :class="activeHistoryTab === 'calendar' ? 'bg-purple-500 text-white' : 'text-gray-400 hover:text-white'"
                                class="px-2 py-1 rounded-lg text-xs font-bold uppercase tracking-widest transition-colors">Kalender</button>
                        </div>
                    </div>

                    <div class="flex gap-2 mb-6 animate-fade-in-up delay-500">
                        <div class="relative flex-1">
                            <input type="text" v-model="search" placeholder="Cari catatan..."
                                class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3.5 pl-11 text-xs focus:ring-1 focus:ring-purple-500 transition-colors">
                            <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <DateModal :action="route('dashboard')" :start-date="startDate" :end-date="endDate" />
                    </div>

                    <div class="flex justify-between items-center mb-4 animate-fade-in-up delay-500">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <button @click="prevMonth"
                                class="p-1 sm:p-1.5 rounded-full bg-gray-900 border border-white/10 text-gray-400 hover:text-white transition-colors active:scale-95">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <p
                                class="text-xs font-bold text-purple-500 uppercase tracking-widest flex flex-col items-center">
                                <span class="text-white text-sm tracking-tight">{{ formattedPeriod }}</span>
                            </p>

                            <button @click="nextMonth" :disabled="!canGoNextMonth"
                                class="p-1 sm:p-1.5 rounded-full border transition-colors active:scale-95"
                                :class="canGoNextMonth ? 'bg-gray-900 border-white/10 text-gray-400 hover:text-white' : 'bg-gray-900/50 border-white/5 text-gray-600 cursor-not-allowed'">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <button type="button" @click="showSortModal = true"
                            class="flex items-center gap-1.5 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest active:scale-95 transition-all"
                            :class="type ? 'text-purple-500 border-purple-500/50' : 'text-gray-500'">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25" />
                            </svg>
                            {{ type ? getTypeName(type) : 'Semua Tipe' }}
                        </button>
                    </div>

                    <!-- CALENDAR VIEW -->
                    <div v-if="activeHistoryTab === 'calendar'" class="animate-fade-in-up delay-500 mb-8">
                        <div
                            class="bg-gradient-to-br from-gray-900 to-gray-800 p-3 sm:p-4 rounded-xl border border-white/10 mb-5 shadow-lg w-full">
                            <div class="flex justify-between items-center mb-3">
                                <button @click="prevMonth"
                                    class="p-1 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <h3 class="text-xs font-bold text-white uppercase tracking-widest">{{ calendarMonthName
                                    }}</h3>
                                <button @click="nextMonth" :disabled="!canGoNextMonth"
                                    class="p-1 rounded-lg transition-colors"
                                    :class="canGoNextMonth ? 'bg-white/5 hover:bg-white/10 text-white' : 'text-gray-600 cursor-not-allowed'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex justify-center mb-3">
                                <div class="flex bg-gray-900 rounded-xl border border-white/10 p-0.5">
                                    <button @click="calendarFilter = 'income'"
                                        :class="calendarFilter === 'income' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'text-gray-400 hover:text-white border border-transparent'"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">Pemasukan</button>
                                    <button @click="calendarFilter = 'total'"
                                        :class="calendarFilter === 'total' ? 'bg-purple-500 text-white border border-purple-500' : 'text-gray-400 hover:text-white border border-transparent'"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">Total</button>
                                    <button @click="calendarFilter = 'expense'"
                                        :class="calendarFilter === 'expense' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'text-gray-400 hover:text-white border border-transparent'"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">Pengeluaran</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-7 gap-1 text-center mb-1.5">
                                <div v-for="day in ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']" :key="day"
                                    class="text-[9px] sm:text-[10px] font-bold text-gray-500 uppercase tracking-widest py-1">
                                    {{ day }}
                                </div>
                            </div>

                            <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
                                <div v-for="(day, index) in calendarDays" :key="index" class="h-12 sm:h-14">
                                    <button v-if="!day.empty" @click="selectDate(day.dateStr)"
                                        class="w-full h-full rounded-md flex flex-col items-center justify-center p-0.5 border transition-all relative overflow-visible"
                                        :class="[
                                            selectedCalendarDate === day.dateStr ? 'bg-purple-500/20 border-purple-500 z-10' : 'bg-white/5 border-transparent hover:bg-white/10 z-0 hover:z-10',
                                            day.dateStr === getLocalYMD(new Date()) && selectedCalendarDate !== day.dateStr ? 'border-white/20' : ''
                                        ]">
                                        <span class="text-xs font-bold leading-none mb-0.5"
                                            :class="selectedCalendarDate === day.dateStr ? 'text-purple-400' : 'text-gray-300'">{{
                                            day.day }}</span>
                                        <div v-if="day.largestType" class="flex flex-col items-center overflow-visible">
                                            <span
                                                class="text-xs font-bold leading-none tracking-tight whitespace-nowrap"
                                                :class="day.largestType === 'income' ? 'text-green-400' : 'text-red-400'">
                                                {{ formatCompactNumber(day.largestAmount) }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-3 px-1">
                            <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-xs font-bold text-gray-300 uppercase tracking-widest">{{
                                selectedDateFormatted }}</h3>
                        </div>

                        <div class="space-y-4">
                            <div v-if="Object.keys(selectedDateTransactions).length === 0"
                                class="text-center py-8 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 relative overflow-hidden group shadow-lg">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest relative z-10">Tidak
                                    ada transaksi</p>
                            </div>
                            <div v-for="(group, dateKey) in selectedDateTransactions" :key="dateKey"
                                class="bg-gradient-to-br from-gray-900 to-gray-800 p-3 rounded-xl border border-white/5 transition-all duration-300 shadow-lg">
                                <div
                                    class="flex justify-between items-center px-1 border-b border-purple-500/30 pb-2 transition-colors group/header">
                                    <h3
                                        class="text-xs font-bold text-purple-500 uppercase tracking-widest flex items-center gap-1.5">
                                        Total Hari Ini
                                    </h3>
                                    <div class="text-xs font-bold flex gap-2.5 tracking-wide">
                                        <span v-if="group.income > 0" class="text-green-400/90">+{{
                                            formatNumber(group.income) }}</span>
                                        <span v-if="group.expense > 0" class="text-red-400/90">-{{
                                            formatNumber(group.expense) }}</span>
                                    </div>
                                </div>

                                <div class="space-y-2.5 pt-3">
                                    <button v-for="trx in group.transactions" :key="trx.id" @click="openModal(trx)"
                                        class="w-full text-left bg-gradient-to-br from-gray-800 to-gray-900 p-3 rounded-xl border border-white/10 hover:border-purple-400/30 active:scale-[0.98] transition-all relative overflow-hidden group">
                                        <div
                                            class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none">
                                        </div>

                                        <div class="flex items-center gap-3 relative z-10">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-gradient flex items-center justify-center text-lg border border-white/10 shrink-0 overflow-hidden p-0.5">
                                                <img v-if="trx.category?.icon?.includes('.')"
                                                    :src="trx.category.icon.startsWith('http') ? trx.category.icon : '/storage/' + trx.category.icon"
                                                    class="w-full h-full object-cover rounded-xl">
                                                <span v-else>{{ trx.category?.icon || '📄' }}</span>
                                            </div>

                                            <div class="flex-1 min-w-0 pr-2">
                                                <p class="text-xs font-bold text-white leading-tight mb-1.5 truncate">{{
                                                    trx.category?.category_name || 'Transfer' }}</p>
                                                <div class="flex items-center gap-1.5 opacity-80 min-w-0">
                                                    <span
                                                        class="text-xs text-gray-400 font-bold uppercase tracking-tight truncate">{{
                                                            trx.source_wallet?.name }}</span>
                                                    <svg class="w-2.5 h-2.5 text-purple-400 shrink-0" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                                        <path d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                    </svg>
                                                    <span
                                                        class="text-xs text-white font-bold uppercase tracking-tight truncate">{{
                                                            trx.destination_wallet?.name }}</span>
                                                </div>
                                            </div>

                                            <div class="text-right shrink-0">
                                                <p class="text-xs font-black"
                                                    :class="trx.type.name === 'Income' ? 'text-green-400' : 'text-red-400'">
                                                    {{ trx.type.name === 'Income' ? '+' : '-' }}{{
                                                    formatNumber(trx.amount) }}
                                                </p>
                                                <div class="flex items-center justify-end gap-1.5 mt-1">
                                                    <span class="text-xs text-gray-600 font-medium italic"> {{ trx.time
                                                        }} </span>
                                                    <span
                                                        class="text-xs uppercase tracking-widest font-black px-1 py-0.5 rounded border"
                                                        :class="getTypeColor(trx.type.name)">
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

                    <!-- DETAIL VIEW -->
                    <div v-show="activeHistoryTab === 'detail'" class="space-y-4 animate-fade-in-up delay-500 mb-8">
                        <div v-for="(group, dateKey) in groupedTransactions" :key="dateKey"
                            class="bg-gradient-to-br from-gray-900 to-gray-800 p-3 rounded-xl border border-white/5 transition-all duration-300 shadow-lg">
                            <div @click="toggleDate(dateKey)"
                                class="flex justify-between items-center px-1 border-b pb-2 transition-colors cursor-pointer group/header"
                                :class="collapsedDates[dateKey] ? 'border-transparent' : 'border-purple-500/30'">
                                <h3
                                    class="text-xs font-bold text-purple-500 uppercase tracking-widest flex items-center gap-1.5 group-hover/header:text-purple-400 transition-colors">
                                    <svg class="w-3.5 h-3.5 transition-transform duration-300"
                                        :class="!collapsedDates[dateKey] ? 'rotate-90' : ''" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                    {{ group.date }}
                                </h3>
                                <div class="text-xs font-bold flex gap-2.5 tracking-wide">
                                    <span v-if="group.income > 0" class="text-green-400/90">+{{
                                        formatNumber(group.income)
                                        }}</span>
                                    <span v-if="group.expense > 0" class="text-red-400/90">-{{
                                        formatNumber(group.expense)
                                        }}</span>
                                </div>
                            </div>

                            <div class="grid transition-all duration-300 ease-in-out"
                                :style="{ gridTemplateRows: collapsedDates[dateKey] ? '0fr' : '1fr' }">
                                <div class="overflow-hidden transition-all duration-300"
                                    :class="collapsedDates[dateKey] ? 'opacity-0' : 'opacity-100'">
                                    <div class="space-y-2.5 pt-3">
                                        <button v-for="trx in group.transactions" :key="trx.id" @click="openModal(trx)"
                                            class="w-full text-left bg-gradient-to-br from-gray-800 to-gray-900 p-3 rounded-xl border border-white/10 hover:border-purple-400/30 active:scale-[0.98] transition-all relative overflow-hidden group">
                                            <div
                                                class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none">
                                            </div>

                                            <div class="flex items-center gap-3 relative z-10">
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-gradient flex items-center justify-center text-lg border border-white/10 shrink-0 overflow-hidden p-0.5">
                                                    <img v-if="trx.category?.icon?.includes('.')"
                                                        :src="trx.category.icon.startsWith('http') ? trx.category.icon : '/storage/' + trx.category.icon"
                                                        class="w-full h-full object-cover rounded-xl">
                                                    <span v-else>{{ trx.category?.icon || '📄' }}</span>
                                                </div>

                                                <div class="flex-1 min-w-0 pr-2">
                                                    <p
                                                        class="text-xs font-bold text-white leading-tight mb-1.5 truncate">
                                                        {{
                                                            trx.category?.category_name || 'Transfer' }}</p>
                                                    <div class="flex items-center gap-1.5 opacity-80 min-w-0">
                                                        <span
                                                            class="text-xs text-gray-400 font-bold uppercase tracking-tight truncate">{{
                                                                trx.source_wallet?.name }}</span>
                                                        <svg class="w-2.5 h-2.5 text-purple-400 shrink-0" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                                            <path d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                        </svg>
                                                        <span
                                                            class="text-xs text-white font-bold uppercase tracking-tight truncate">{{
                                                                trx.destination_wallet?.name }}</span>
                                                    </div>
                                                </div>

                                                <div class="text-right shrink-0">
                                                    <p class="text-xs font-black"
                                                        :class="trx.type.name === 'Income' ? 'text-green-400' : 'text-red-400'">
                                                        {{ trx.type.name === 'Income' ? '+' : '-' }}{{
                                                            formatNumber(trx.amount)
                                                        }}
                                                    </p>
                                                    <div class="flex items-center justify-end gap-1.5 mt-1">
                                                        <span class="text-xs text-gray-600 font-medium italic"> {{
                                                            trx.time
                                                            }} </span>
                                                        <span
                                                            class="text-xs uppercase tracking-widest font-black px-1 py-0.5 rounded border"
                                                            :class="getTypeColor(trx.type.name)">
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

                        <div v-if="transactions.data.length === 0"
                            class="text-center py-12 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 relative overflow-hidden group">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest relative z-10">Data
                                Kosong</p>
                        </div>
                    </div>

                </div> <!-- END RIGHT COLUMN -->
            </div>

            <!-- SORT MODAL -->
            <div v-if="showSortModal"
                class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showSortModal = false">
                <div
                    class="w-full max-w-sm bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 p-6 animate-pop-in relative">
                    <button type="button" @click="showSortModal = false"
                        class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <h3 class="text-sm font-bold text-white mb-6 text-center uppercase tracking-widest">Filter Tipe</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="setType('')"
                            class="col-span-2 py-3 rounded-xl border border-white/10 text-xs font-bold uppercase tracking-widest transition-all"
                            :class="!type ? 'bg-purple-600 text-white' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-300'">Semua
                            Tipe</button>
                        <button
                            v-for="(label, key) in { 'Income': 'Pemasukan', 'Expense': 'Pengeluaran', 'Transfer': 'Transfer', 'Debt': 'Hutang', 'Receivable': 'Piutang' }"
                            @click="setType(key)"
                            class="py-3 rounded-xl border border-white/10 text-xs font-bold uppercase tracking-widest transition-all"
                            :class="type === key ? 'bg-gradient-to-br from-purple-800 to-purple-500 text-white' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-300'">
                            {{ label }}
                        </button>
                    </div>
                </div>
            </div>

            <CreateTransactionFab />
        </div>

        <TransactionDetailModal :show="showModal" :transaction="selectedTransaction" @close="showModal = false" />
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes pop-in {
    0% {
        transform: scale(0.9);
        opacity: 0;
    }

    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-pop-in {
    animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

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

.delay-500 {
    animation-delay: 500ms;
}
</style>
