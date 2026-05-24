<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';

const { isDesktopLayout } = useLayoutPreference();

const props = defineProps({
    wallets: Array,
    categories: Array,
    systemWallets: Array,
});

const form = useForm({
    category_id: null,
    source_wallet_id: null,
    destination_wallet_id: null,
    amount: 0,
    date: new Date().toISOString().split('T')[0],
    subject: '-',
    notes: '',
});

const mainTab = ref('Expense');
const activeType = ref('Expense');
const showCategoryModal = ref(false);
const showWalletModal = ref(false);
const walletModalMode = ref('source');
const displayAmount = ref('');
const walletFrequency = ref({});

onMounted(() => {
    setType('Expense');
    try {
        const storedFreq = localStorage.getItem('wallet_frequency');
        if (storedFreq) {
            walletFrequency.value = JSON.parse(storedFreq);
        }
    } catch (e) {
        console.error("Gagal meload cache wallet", e);
    }
});

const formatAmountInput = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    form.amount = val;
    displayAmount.value = val ? new Intl.NumberFormat('id-ID').format(parseInt(val)) : '';
};

const activeCategories = computed(() => {
    let cats = props.categories.filter(cat => cat.type.name === activeType.value);

    if (activeType.value === 'Debt') {
        if (mainTab.value === 'Expense') cats = cats.filter(c => c.category_name === 'Bayar Cicilan Hutang');
        else if (mainTab.value === 'Income') cats = cats.filter(c => c.category_name === 'Dapat Hutangan');
    } else if (activeType.value === 'Receivable') {
        if (mainTab.value === 'Expense') cats = cats.filter(c => c.category_name === 'Ngasih Piutang');
        else if (mainTab.value === 'Income') cats = cats.filter(c => c.category_name === 'Terima Bayar Piutang');
    }

    return cats;
});

const selectedCategory = computed(() => {
    return props.categories.find(c => c.id === form.category_id);
});

const showKeypad = ref(true);
const showBottomPanel = ref(true);
const showDateModal = ref(false);

const currentMonth = ref(new Date().getMonth());
const currentYear = ref(new Date().getFullYear());
const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

const daysInMonth = computed(() => new Date(currentYear.value, currentMonth.value + 1, 0).getDate());
const firstDayOfMonth = computed(() => {
    let day = new Date(currentYear.value, currentMonth.value, 1).getDay();
    return day === 0 ? 6 : day - 1; // Mon=0, Sun=6
});

const prevMonth = () => {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
};

const nextMonth = () => {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
};

watch(showDateModal, (val) => {
    if (val) {
        const d = new Date(form.date);
        currentMonth.value = d.getMonth();
        currentYear.value = d.getFullYear();
    }
});

const selectSpecificDate = (day) => {
    const d = new Date(currentYear.value, currentMonth.value, day);
    const offset = d.getTimezoneOffset() * 60000;
    form.date = (new Date(d - offset)).toISOString().slice(0, 10);
    showDateModal.value = false;
};

const setDate = (offsetDays) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const offset = d.getTimezoneOffset() * 60000;
    form.date = (new Date(d - offset)).toISOString().slice(0, 10);
    showDateModal.value = false;
};

const selectedSourceWallet = computed(() => {
    const all = [...props.wallets, ...props.systemWallets];
    return all.find(w => w.id == form.source_wallet_id);
});

const selectedDestWallet = computed(() => {
    const all = [...props.wallets, ...props.systemWallets];
    return all.find(w => w.id == form.destination_wallet_id);
});

const showSourceWallet = computed(() => {
    if (activeType.value === 'Income') return false;
    if (['Debt', 'Receivable'].includes(activeType.value)) {
        const catName = selectedCategory.value?.category_name;
        if (catName === 'Dapat Hutangan' || catName === 'Terima Bayar Piutang') return false;
    }
    return true;
});

const showDestWallet = computed(() => {
    if (activeType.value === 'Expense') return false;
    if (['Debt', 'Receivable'].includes(activeType.value)) {
        const catName = selectedCategory.value?.category_name;
        if (catName === 'Bayar Cicilan Hutang' || catName === 'Ngasih Piutang') return false;
    }
    return true;
});

const setMainTab = (t) => {
    mainTab.value = t;
    setType(t);
};

const setType = (type) => {
    activeType.value = type;
    form.category_id = null;
    form.subject = (type === 'Debt' || type === 'Receivable') ? '' : '-';
    form.clearErrors();

    const lastSource = localStorage.getItem('last_source_wallet');
    const lastDest = localStorage.getItem('last_dest_wallet');

    if (type === 'Expense') {
        form.source_wallet_id = lastSource || lastDest || null;
        const merchant = props.systemWallets.find(w => w.name.toLowerCase().includes('merchant'));
        form.destination_wallet_id = merchant?.id;
    } else if (type === 'Income') {
        const external = props.systemWallets.find(w => w.name.toLowerCase().includes('external'));
        form.source_wallet_id = external?.id;
        form.destination_wallet_id = lastDest || lastSource || null;
    } else {
        form.source_wallet_id = lastSource || lastDest || null;
        form.destination_wallet_id = lastDest || lastSource || null;
    }

    let filteredCats = props.categories.filter(cat => cat.type.name === type);

    if (['Debt', 'Receivable'].includes(type) && filteredCats.length > 0) {
        form.category_id = filteredCats[0].id;
        form.clearErrors('category_id');

        const syH = props.systemWallets.find(w => w.name.toLowerCase().includes('hutang'));
        const syP = props.systemWallets.find(w => w.name.toLowerCase().includes('piutang'));

        if (type === 'Debt') {
            if (mainTab.value === 'Expense') form.destination_wallet_id = syH?.id;
            else form.source_wallet_id = syH?.id;
        } else {
            if (mainTab.value === 'Expense') form.destination_wallet_id = syP?.id;
            else form.source_wallet_id = syP?.id;
        }
    } else if (filteredCats.length === 1) {
        selectCategory(filteredCats[0]);
    }
};

const selectCategory = (cat) => {
    form.category_id = cat.id;
    showCategoryModal.value = false;
    form.clearErrors('category_id');

    // Removed aggressive nulling to preserve the user's personal wallet defaults

    const syH = props.systemWallets.find(w => w.name.toLowerCase().includes('hutang'));
    const syP = props.systemWallets.find(w => w.name.toLowerCase().includes('piutang'));
};

const availableWallets = computed(() => {
    let list = props.wallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));

    const otherValue = walletModalMode.value === 'source' ? form.destination_wallet_id : form.source_wallet_id;
    if (otherValue) {
        list = list.filter(w => w.id !== otherValue);
    }

    return list.sort((a, b) => (walletFrequency.value[b.id] || 0) - (walletFrequency.value[a.id] || 0));
});

const openWalletModal = (mode) => {
    walletModalMode.value = mode;
    showWalletModal.value = true;
};

const selectWallet = (w) => {
    walletFrequency.value[w.id] = (walletFrequency.value[w.id] || 0) + 1;
    localStorage.setItem('wallet_frequency', JSON.stringify(walletFrequency.value));

    if (walletModalMode.value === 'source') {
        form.source_wallet_id = w.id;
        localStorage.setItem('last_source_wallet', w.id);
        form.clearErrors('source_wallet_id');
    } else {
        form.destination_wallet_id = w.id;
        localStorage.setItem('last_dest_wallet', w.id);
        form.clearErrors('destination_wallet_id');
    }
    showWalletModal.value = false;
};

const submit = (closeAfter = true) => {
    if (new Date(form.date) > new Date()) {
        form.setError('date', 'Masa depan tidak diizinkan!');
        return;
    }

    if (['Debt', 'Receivable'].includes(activeType.value) && (!form.subject || form.subject === '-')) {
        form.setError('subject', 'Wajib diisi Bos!');
        return;
    }

    form.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            if (closeAfter) {
                handleBack();
            } else {
                form.amount = 0;
                rawAmount.value = '0';
                form.notes = '';
                form.category_id = null;
            }
        },
    });
};

const submitAndClose = () => submit(true);
const submitAndStay = () => submit(false);

const dateInput = ref(null);
const openDatePicker = () => {
    if (dateInput.value) {
        try {
            if (typeof dateInput.value.showPicker === 'function') {
                dateInput.value.showPicker();
            } else {
                dateInput.value.focus();
                dateInput.value.click();
            }
        } catch (e) {
            console.error('Failed to open date picker', e);
        }
    }
};

const handleBack = () => {
    router.visit(route('dashboard'));
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Catat Transaksi" />

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative">
            <header class="flex justify-between items-center mb-6 pt-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Catat Transaksi</h1>
                <button type="button" @click="handleBack"
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </header>

            <!-- TABS -->
            <div class="grid grid-cols-3 gap-2 mb-2">
                <button v-for="t in ['Expense', 'Income', 'Transfer']" :key="t" @click="setType(t)" type="button"
                    :class="['w-full text-[10px] font-bold uppercase tracking-widest py-3 rounded-xl transition-all border', activeType === t ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border-white/10 shadow-md' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-500 border-transparent']">
                    {{ t === 'Expense' ? 'Pengeluaran' : (t === 'Income' ? 'Pemasukan' : 'Transfer') }}
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2 mb-6">
                <button v-for="t in ['Debt', 'Receivable']" :key="t" @click="setType(t)" type="button"
                    :class="['w-full text-[10px] font-bold uppercase tracking-widest py-3 rounded-xl transition-all border', activeType === t ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border-white/10 shadow-md' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-500 border-transparent']">
                    {{ t === 'Debt' ? 'Hutang' : 'Piutang' }}
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- NOMINAL -->
                <div
                    class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5 text-center relative shadow-inner group">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Nominal
                        (Rp)</label>
                    <input type="text" :value="displayAmount" @input="formatAmountInput" inputmode="numeric" required
                        placeholder="0"
                        class="w-full bg-transparent border-none text-white text-center text-4xl font-bold placeholder-gray-600 focus:ring-0 p-0 focus:outline-none caret-purple-500">
                    <div
                        class="absolute bottom-4 left-1/2 -translate-x-1/2 w-12 h-1 bg-purple-500 rounded-full opacity-0 group-focus-within:opacity-100 transition-opacity">
                    </div>
                    <div v-if="form.errors.amount" class="text-red-500 text-xs mt-2 font-bold">{{ form.errors.amount }}
                    </div>
                </div>

                <!-- TANGGAL & KATEGORI -->
                <div class="grid grid-cols-2 gap-3">
                    <div
                        class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm relative">
                        <label
                            class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Tanggal</label>
                        <input type="date" v-model="form.date" required
                            class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0"
                            style="color-scheme: dark;">
                        <div v-if="form.errors.date" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.date
                        }}</div>
                    </div>

                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm cursor-pointer active:scale-95 transition-transform"
                        @click="showCategoryModal = true">
                        <label
                            class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Kategori</label>
                        <div class="flex items-center justify-between px-1">
                            <div class="flex items-center gap-2 truncate">
                                <template v-if="selectedCategory">
                                    <img v-if="selectedCategory.icon.includes('.')"
                                        :src="'/storage/' + selectedCategory.icon"
                                        class="w-5 h-5 object-cover rounded-md">
                                    <span v-else>{{ selectedCategory.icon }}</span>
                                    <span class="text-sm font-bold text-white truncate">{{
                                        selectedCategory.category_name }}</span>
                                </template>
                                <span v-else class="text-sm font-bold text-gray-600 truncate">-- Pilih --</span>
                            </div>
                            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div v-if="form.errors.category_id" class="text-red-500 text-[10px] mt-2 font-bold">{{
                            form.errors.category_id }}</div>
                    </div>
                </div>

                <!-- WALLETS -->
                <div
                    class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-4 shadow-inner flex flex-col gap-3">
                    <div v-if="showSourceWallet">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Dari
                            Dompet</label>
                        <div @click="openWalletModal('source')"
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-white/10">
                            <div class="flex items-center gap-2 truncate">
                                <template v-if="selectedSourceWallet">
                                    <img v-if="selectedSourceWallet.icon.includes('.')"
                                        :src="'/storage/' + selectedSourceWallet.icon"
                                        class="w-5 h-5 object-cover rounded-md">
                                    <span v-else>{{ selectedSourceWallet.icon }}</span>
                                    <span class="font-bold truncate">{{ selectedSourceWallet.name }}</span>
                                </template>
                                <svg v-else class="w-12 h-12 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                            </button>
                            <span
                                class="text-xs font-bold text-gray-500 uppercase tracking-widest truncate max-w-fit text-center">{{
                                    selectedDestWallet ? selectedDestWallet.name : 'Tujuan' }}</span>
                        </div>
                    </div>

                    <!-- CATEGORY GRID -->
                    <div class="flex-1 overflow-y-auto px-4 py-4 no-scrollbar">

                        <!-- ERROR BANNER -->
                        <div v-if="Object.keys(form.errors).length > 0"
                            class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                            <div v-for="(err, key) in form.errors" :key="key"
                                class="text-red-400 text-xs font-bold flex items-center gap-1.5 mb-1 last:mb-0">
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ err }}
                            </div>
                        </div>

                        <div v-if="['Debt', 'Receivable'].includes(activeType)"
                            class="flex flex-col justify-center h-full pb-10">
                            <label
                                class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4 text-center">Pihak
                                / Nama
                                Terkait</label>
                            <input type="text" v-model="form.subject" placeholder="Masukkan nama..."
                                class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border-xl border-white/10 focus:border-purple-500 rounded-xl px-6 py-5 text-center text-xl font-bold text-white focus:ring-0 placeholder-gray-700 transition-colors outline-none">
                        </div>
                        <div v-else-if="mainTab !== 'Transfer'" class="grid grid-cols-4 gap-x-3 gap-y-4 pb-4">
                            <div v-for="cat in activeCategories" :key="cat.id" @click="selectCategory(cat)"
                                :class="['flex flex-col items-center justify-center p-3 rounded-xl border transition-all cursor-pointer aspect-square',
                                    form.category_id === cat.id ? 'bg-gradient-to-br from-gray-800 to-gray-900 border-purple-500' : 'bg-transparent border-white/10 hover:border-white/20']">
                                <img v-if="cat.icon.includes('.')" :src="'/storage/' + cat.icon"
                                    class="w-8 h-8 object-cover mb-2">
                                <span v-else class="text-2xl mb-1.5">{{ cat.icon }}</span>
                                <span
                                    :class="['text-xs font-bold text-center leading-tight truncate w-full px-1', form.category_id === cat.id ? 'text-white' : 'text-gray-500']">{{
                                        cat.category_name }}</span>
                            </div>
                            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div v-if="form.errors.source_wallet_id" class="text-red-500 text-[10px] mt-1 font-bold">{{
                            form.errors.source_wallet_id }}</div>
                    </div>

                    <div v-if="showDestWallet">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Ke
                            Dompet</label>
                        <div @click="openWalletModal('dest')"
                            class="w-full bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-white/10">
                            <div class="flex items-center gap-2 truncate">
                                <template v-if="selectedDestWallet">
                                    <img v-if="selectedDestWallet.icon.includes('.')"
                                        :src="'/storage/' + selectedDestWallet.icon"
                                        class="w-5 h-5 object-cover rounded-md">
                                    <span v-else>{{ selectedDestWallet.icon }}</span>
                                    <span class="font-bold truncate">{{ selectedDestWallet.name }}</span>
                                </template>
                                <svg v-else class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                            </button>

                            <!-- NOTE INPUT (Auto-expanding) -->
                            <div class="flex-1 min-w-0 grid relative">
                                <span
                                    class="invisible whitespace-pre-wrap break-all text-sm p-0 min-h-[20px] col-start-1 row-start-1">{{
                                        (form.notes || 'Note') + ' ' }}</span>
                                <textarea v-model="form.notes" placeholder="Note" rows="1"
                                    class="col-start-1 row-start-1 w-full h-full bg-transparent border-none focus:ring-0 text-md text-gray-500 placeholder-gray-700 p-0 resize-none overflow-hidden break-all whitespace-pre-wrap"></textarea>
                            </div>

                            <!-- AMOUNT -->
                            <div class="flex items-baseline shrink-0 ml-2 md:hidden max-w-full">
                                <span class="text-xl font-bold text-white break-all">{{ parseInt(rawAmount ||
                                    0).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="hidden md:grid flex-1 min-w-[150px] relative items-center justify-items-end">
                                <span
                                    class="invisible whitespace-pre-wrap break-all text-xl font-bold p-0 min-h-[28px] col-start-1 row-start-1 text-right w-full">{{
                                        (formattedAmount || '0') + ' ' }}</span>
                                <textarea :value="formattedAmount" @input="handleDesktopInput" rows="1"
                                    inputmode="numeric"
                                    class="col-start-1 row-start-1 w-full h-full bg-transparent border-none focus:ring-0 text-xl font-bold text-white p-0 text-right resize-none overflow-hidden break-all whitespace-pre-wrap"
                                    placeholder="0"></textarea>
                            </div>
                        </div>

                        <!-- QUICK ACTIONS ROW -->
                        <div class="flex gap-2 mb-2">
                            <!-- Date Picker -->
                            <div @click="showDateModal = true"
                                class="flex-1 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl flex items-center justify-center gap-2 text-xs font-bold text-gray-500 relative overflow-hidden cursor-pointer">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="pointer-events-none tracking-wide">{{ new Date(form.date).toDateString()
                                    ===
                                    new
                                        Date().toDateString() ? 'Hari Ini' : new Date(form.date).toLocaleDateString('id-ID',
                                            {
                                                day: 'numeric',
                                                month: 'short', year: 'numeric'
                                            }) }}</span>
                            </div>
                            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div v-if="form.errors.destination_wallet_id" class="text-red-500 text-[10px] mt-1 font-bold">{{
                            form.errors.destination_wallet_id }}</div>
                    </div>
                </form>
            </div>

                <!-- PIHAK TERKAIT -->
                <div v-if="['Debt', 'Receivable'].includes(activeType)"
                    class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm">
                    <label
                        class="block text-[9px] font-bold text-purple-500 uppercase tracking-widest mb-1.5 ml-1">Pihak
                        Terkait</label>
                    <input type="text" v-model="form.subject" placeholder="Nama..."
                        class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0">
                    <div v-if="form.errors.subject" class="text-red-500 text-[10px] mt-1 font-bold">{{
                        form.errors.subject }}</div>
                </div>

                <div class="pt-2">
                    <input type="text" v-model="form.notes" placeholder="Catatan tambahan (opsional)"
                        class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-sm placeholder-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                    <div v-if="form.errors.notes" class="text-red-500 text-[10px] mt-1">{{ form.errors.notes }}</div>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full bg-gradient-to-br from-purple-600 to-purple-500 text-white font-bold text-sm tracking-wide py-4 rounded-xl mt-6 disabled:opacity-50 disabled:cursor-not-allowed hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Transaksi' }}
                </button>
            </form>
        </div>

        <!-- CATEGORY MODAL -->
        <div v-if="showCategoryModal"
            class="fixed inset-0 z-[100] flex flex-col justify-end bg-black/70 backdrop-blur-sm"
            @click.self="showCategoryModal = false">
            <div
                class="w-full lg:max-w-4xl mx-auto lg:px-8 bg-gray-900 rounded-t-2xl border-t border-x border-white/10 p-5 pb-safe animate-slide-up">
                <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer"
                    @click="showCategoryModal = false">
                </div>
                <h3 class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Kategori
                </h3>
                <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[60vh] pb-6">
                    <div v-for="cat in activeCategories" :key="cat.id" @click="selectCategory(cat)"
                        class="bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl flex items-center justify-center text-xl border border-white/10 overflow-hidden">
                            <img v-if="cat.icon.includes('.')" :src="'/storage/' + cat.icon"
                                class="w-full h-full object-cover">
                            <span v-else>{{ cat.icon }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- WALLET MODAL -->
        <div v-if="showWalletModal" class="fixed inset-0 z-[100] flex flex-col justify-end bg-black/70 backdrop-blur-sm"
            @click.self="showWalletModal = false">
            <div
                class="w-full lg:max-w-4xl mx-auto lg:px-8 bg-gray-900 rounded-t-2xl border-t border-x border-white/10 p-5 pb-safe animate-slide-up">
                <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer"
                    @click="showWalletModal = false">
                </div>
                <h3 class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Dompet
                </h3>
                <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[60vh] pb-6">
                    <div v-for="w in availableWallets" :key="w.id" @click="selectWallet(w)"
                        class="bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all group hover:border-purple-500/30">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl flex items-center justify-center text-xl border border-white/10 overflow-hidden shadow-inner group-hover:scale-105 transition-transform">
                            <img v-if="w.icon.includes('.')" :src="'/storage/' + w.icon"
                                class="w-full h-full object-cover">
                            <span v-else>{{ w.icon }}</span>
                        </div>
                        <div class="flex-1">
                            <span class="text-sm font-bold text-white block">{{ w.name }}</span>
                            <p v-if="['Asset', 'Liquid'].includes(w.group_type)"
                                class="text-xs text-gray-500 font-bold tracking-widest mt-0.5 uppercase">
                                Saldo: <span class="text-purple-500">Rp {{ new
                                    Intl.NumberFormat('id-ID').format(w.balance)
                                }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes slide-up {
    0% {
        transform: translateY(100%);
    }

    100% {
        transform: translateY(0);
    }
}

.animate-slide-up {
    animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>