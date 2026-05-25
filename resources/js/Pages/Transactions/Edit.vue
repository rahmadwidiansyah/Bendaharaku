<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';

const { isDesktopLayout } = useLayoutPreference();

const props = defineProps({
    transaction: Object,
    wallets: Array,
    categories: Array,
    systemWallets: Array,
    debtSubjects: Array,
});

const form = useForm({
    category_id: props.transaction.category_id,
    source_wallet_id: props.transaction.source_wallet_id,
    destination_wallet_id: props.transaction.destination_wallet_id,
    amount: props.transaction.amount,
    date: props.transaction.date,
    subject: props.transaction.subject || '-',
    notes: props.transaction.notes || '',
    due_date: props.transaction.due_date,
    due_date_type: props.transaction.due_date_type,
    due_date_interval: props.transaction.due_date_interval,
});

const showDeleteConfirm = ref(false);

const mainTab = ref('Expense');
const activeType = ref('Expense');
const debtSubTab = ref('income');
const showCategoryModal = ref(false);
const showWalletModal = ref(false);
const walletModalMode = ref('source');
const rawAmount = ref(props.transaction.amount.toString());

const formattedAmount = computed(() => {
    if (!rawAmount.value || rawAmount.value === '0') return '';
    const clean = rawAmount.value.toString().replace(/\D/g, '');
    if (!clean) return '';
    return parseInt(clean, 10).toLocaleString('id-ID');
});

const handleDesktopInput = (e) => {
    let clean = e.target.value.replace(/\D/g, '');
    if (clean.length > 15) {
        clean = clean.slice(0, 15);
    }

    // Selalu paksa kembalikan value menjadi format angka agar teks terhapus otomatis
    e.target.value = clean ? parseInt(clean, 10).toLocaleString('id-ID') : '';

    rawAmount.value = clean || '0';
    form.amount = parseInt(clean, 10) || 0;
};

const handleKeypad = (key) => {
    if (key === 'del') {
        rawAmount.value = rawAmount.value.slice(0, -1) || '0';
    } else if (key === 'C') {
        rawAmount.value = '0';
    } else if (key === '000') {
        if (rawAmount.value !== '0') rawAmount.value += '000';
    } else {
        if (rawAmount.value === '0') rawAmount.value = key;
        else rawAmount.value += key;
    }

    // limit max length
    if (rawAmount.value.length > 15) rawAmount.value = rawAmount.value.slice(0, 15);

    form.amount = parseInt(rawAmount.value, 10) || 0;
};
const displayAmount = ref('');
const walletFrequency = ref({});

onMounted(() => {
    const cat = props.categories.find(c => c.id === props.transaction.category_id);
    if (cat) {
        activeType.value = cat.type.name;
        if (['Expense', 'Income', 'Transfer'].includes(cat.type.name)) {
            mainTab.value = cat.type.name;
        } else {
            mainTab.value = 'Debt/Receivable';
            if (cat.category_name === 'Bayar Cicilan Hutang' || cat.category_name === 'Ngasih Piutang') {
                debtSubTab.value = 'expense';
            } else {
                debtSubTab.value = 'income';
            }
        }
    }

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
        if (debtSubTab.value === 'expense') cats = cats.filter(c => c.category_name === 'Bayar Cicilan Hutang');
        else if (debtSubTab.value === 'income') cats = cats.filter(c => c.category_name === 'Dapat Hutangan');
    } else if (activeType.value === 'Receivable') {
        if (debtSubTab.value === 'expense') cats = cats.filter(c => c.category_name === 'Ngasih Piutang');
        else if (debtSubTab.value === 'income') cats = cats.filter(c => c.category_name === 'Terima Bayar Piutang');
    }

    return cats;
});

const selectedCategory = computed(() => {
    return props.categories.find(c => c.id === form.category_id);
});

const showKeypad = ref(true);
const showBottomPanel = ref(true);
const showDateModal = ref(false);
const dateModalTarget = ref('transaction'); // 'transaction' or 'due_date'

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
        const d = dateModalTarget.value === 'due_date' && form.due_date ? new Date(form.due_date) : new Date(form.date);
        currentMonth.value = d.getMonth();
        currentYear.value = d.getFullYear();
    }
});

const selectSpecificDate = (day) => {
    const d = new Date(currentYear.value, currentMonth.value, day);
    const offset = d.getTimezoneOffset() * 60000;
    const dateStr = (new Date(d - offset)).toISOString().slice(0, 10);
    
    if (dateModalTarget.value === 'due_date') {
        form.due_date = dateStr;
    } else {
        form.date = dateStr;
    }
    showDateModal.value = false;
};

const setDate = (offsetDays) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const offset = d.getTimezoneOffset() * 60000;
    const dateStr = (new Date(d - offset)).toISOString().slice(0, 10);
    
    if (dateModalTarget.value === 'due_date') {
        form.due_date = dateStr;
    } else {
        form.date = dateStr;
    }
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
    if (['Debt', 'Receivable'].includes(t)) {
        debtSubTab.value = 'income';
    }
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
        let targetCatName = '';
        if (type === 'Debt') {
            targetCatName = debtSubTab.value === 'expense' ? 'Bayar Cicilan Hutang' : 'Dapat Hutangan';
        } else {
            targetCatName = debtSubTab.value === 'expense' ? 'Ngasih Piutang' : 'Terima Bayar Piutang';
        }

        const cat = filteredCats.find(c => c.category_name === targetCatName);
        if (cat) {
            form.category_id = cat.id;
            form.clearErrors('category_id');
        }

        const syH = props.systemWallets.find(w => w.name.toLowerCase().includes('hutang'));
        const syP = props.systemWallets.find(w => w.name.toLowerCase().includes('piutang'));

        if (type === 'Debt') {
            if (debtSubTab.value === 'expense') {
                form.source_wallet_id = lastSource || null;
                form.destination_wallet_id = syH?.id;
            } else {
                form.source_wallet_id = syH?.id;
                form.destination_wallet_id = lastDest || null;
            }
        } else {
            if (debtSubTab.value === 'expense') {
                form.source_wallet_id = lastSource || null;
                form.destination_wallet_id = syP?.id;
            } else {
                form.source_wallet_id = syP?.id;
                form.destination_wallet_id = lastDest || null;
            }
        }
    } else if (filteredCats.length === 1) {
        selectCategory(filteredCats[0]);
    }
};

const setDebtSubTab = (subTab) => {
    debtSubTab.value = subTab;
    setType(mainTab.value);
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

    form.put(route('transactions.update', props.transaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (closeAfter) {
                handleBack();
            }
        },
    });
};

const destroy = () => {
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    showDeleteConfirm.value = false;
    router.delete(route('transactions.destroy', props.transaction.id));
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

        <Head title="Edit Transaksi" />

        <div :class="[
            'flex flex-col bg-gray-800 w-full text-white overflow-hidden',
            'fixed inset-0 z-[60] h-[100dvh] max-h-[100dvh]',
            isDesktopLayout ? 'lg:relative lg:inset-auto lg:z-0 lg:h-screen lg:max-h-[100vh]' : ''
        ]" style="padding-bottom: env(safe-area-inset-bottom)">

            <div class="flex flex-col h-full w-full max-w-md mx-auto relative bg-gray-800 overflow-hidden">
                <form @submit.prevent="submit" class="flex flex-col h-full min-h-0 overflow-hidden relative lg:pt-8">

                    <!-- TOP RIGHT ACTIONS -->
                    <div class="absolute top-12 right-4 z-50 flex items-center gap-2">
                        <!-- DELETE BUTTON -->
                        <button type="button" @click="destroy"
                            class="p-2 text-red-500 active:scale-95 transition-transform bg-gradient-to-br from-red-900/30 to-gray-800 rounded-full border border-red-500/30 hover:border-red-500/50"
                            title="Hapus Transaksi">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        
                        <!-- CLOSE BUTTON -->
                        <button type="button" @click="handleBack"
                            class="p-2 text-gray-400 active:scale-95 transition-transform bg-gradient-to-br from-gray-900 to-gray-800 rounded-full border border-white/10 hover:border-white/50"
                            title="Tutup">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- TABS UTAMA -->
                    <div class="px-4 pt-28 pb-2 shrink-0">
                        <div
                            class="flex bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-2 border border-white/10 overflow-x-auto no-scrollbar gap-1">
                            <button v-for="t in ['Expense', 'Income', 'Transfer', 'Debt', 'Receivable']" :key="t" @click="setMainTab(t)"
                                type="button"
                                :class="['flex-1 px-3 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap', mainTab === t ? 'bg-gray-800 text-purple-500 border border-white/10' : 'text-gray-500 hover:text-white']">
                                {{ t === 'Expense' ? 'Keluar' : (t === 'Income' ? 'Masuk' : (t === 'Transfer' ? 'Transfer' : (t === 'Debt' ? 'Hutang' : 'Piutang'))) }}
                            </button>
                        </div>
                    </div>

                    <!-- SUB TABS -->
                    <div v-if="['Expense', 'Income'].includes(mainTab)" class="px-4 py-1 flex flex-col gap-2 shrink-0">
                        <div class="flex gap-2">
                            <Link :href="route('categories.create', { type: mainTab })"
                                class="flex-1 flex items-center justify-center py-3 rounded-xl text-xs font-bold transition-all whitespace-nowrap bg-transparent text-purple-500 border border-white/10 hover:bg-gray-900">
                                + Kategori
                            </Link>
                        </div>
                    </div>

                    <div v-if="mainTab === 'Debt'" class="px-4 py-1 flex flex-col gap-2 shrink-0">
                        <div class="flex gap-2 transition-all">
                            <button type="button" @click="setDebtSubTab('income')"
                                :class="['flex-1 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap', debtSubTab === 'income' ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10 hover:text-gray-400']">
                                Dapat Hutang
                            </button>
                            <button type="button" @click="setDebtSubTab('expense')"
                                :class="['flex-1 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap', debtSubTab === 'expense' ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10 hover:text-gray-400']">
                                Bayar Hutang
                            </button>
                        </div>
                    </div>

                    <div v-if="mainTab === 'Receivable'" class="px-4 py-1 flex flex-col gap-2 shrink-0">
                        <div class="flex gap-2 transition-all">
                            <button type="button" @click="setDebtSubTab('expense')"
                                :class="['flex-1 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap', debtSubTab === 'expense' ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10 hover:text-gray-400']">
                                Beri Piutang
                            </button>
                            <button type="button" @click="setDebtSubTab('income')"
                                :class="['flex-1 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap', debtSubTab === 'income' ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10 hover:text-gray-400']">
                                Terima Piutang
                            </button>
                        </div>
                    </div>

                    <div v-if="mainTab === 'Transfer'" class="px-4 mt-12 pb-4 flex items-center justify-center gap-10 shrink-0">
                        <!-- Dompet Sumber -->
                        <div class="flex flex-col items-center gap-3">
                            <button type="button" @click="openWalletModal('source')"
                                class="flex items-center justify-center w-20 h-20 active:scale-95 transition-transform"
                                title="Pilih Dompet Sumber">
                                <template v-if="selectedSourceWallet">
                                    <img v-if="selectedSourceWallet.icon.includes('.')"
                                        :src="'/storage/' + selectedSourceWallet.icon"
                                        class="w-12 h-12 object-cover rounded-xl">
                                    <span v-else class="text-4xl">{{ selectedSourceWallet.icon }}</span>
                                </template>
                                <svg v-else class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                            </button>
                            <span
                                class="text-xs font-bold text-gray-500 uppercase tracking-widest truncate max-w-fit text-center">{{
                                    selectedSourceWallet ? selectedSourceWallet.name : 'Sumber' }}</span>
                        </div>

                        <!-- Arrow -->
                        <div class="flex flex-col items-center justify-center text-gray-500 pb-7">
                            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>

                        <!-- Dompet Tujuan -->
                        <div class="flex flex-col items-center gap-3">
                            <button type="button" @click="openWalletModal('dest')"
                                class="flex items-center justify-center w-20 h-20 active:scale-95 transition-transform"
                                title="Pilih Dompet Tujuan">
                                <template v-if="selectedDestWallet">
                                    <img v-if="selectedDestWallet.icon.includes('.')"
                                        :src="'/storage/' + selectedDestWallet.icon"
                                        class="w-12 h-12 object-cover rounded-xl">
                                    <span v-else class="text-4xl">{{ selectedDestWallet.icon }}</span>
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
                    <div class="flex-1 min-h-0 overflow-y-auto px-4 py-4 no-scrollbar">

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
                            class="flex flex-col justify-start h-full pb-10 gap-4">
                            
                            <div class="flex flex-col items-center">
                                <label class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 text-center">Pihak / Nama Terkait</label>
                                <input type="text" v-model="form.subject" placeholder="Masukkan nama..."
                                    class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 focus:border-purple-500 rounded-xl px-4 py-3 text-center text-lg font-bold text-white focus:ring-0 placeholder-gray-700 transition-colors outline-none">
                                
                                <div v-if="debtSubjects && debtSubjects.length > 0 && ((activeType === 'Debt' && debtSubTab === 'expense') || (activeType === 'Receivable' && debtSubTab === 'income'))" class="flex flex-wrap gap-2 justify-center mt-3">
                                    <button type="button" v-for="sub in debtSubjects" :key="sub" @click="form.subject = sub"
                                        class="px-3 py-1.5 rounded-full text-[10px] font-bold border transition-all active:scale-95"
                                        :class="form.subject === sub ? 'bg-purple-600/20 text-purple-400 border-purple-500/50' : 'bg-gray-800 text-gray-400 border-white/5 hover:bg-gray-700'">
                                        {{ sub }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="(activeType === 'Debt' && debtSubTab === 'income') || (activeType === 'Receivable' && debtSubTab === 'expense')" class="flex flex-col items-center p-4 bg-gray-900/50 rounded-xl border border-white/5">
                                <div class="flex items-center gap-2 mb-3 w-full justify-center">
                                    <input type="checkbox" id="has_due" :checked="form.due_date_type !== null" @change="form.due_date_type = $event.target.checked ? 'fixed' : null" class="rounded bg-gray-800 border-white/10 text-purple-600 focus:ring-purple-600">
                                    <label for="has_due" class="text-xs font-bold text-purple-400 uppercase tracking-widest cursor-pointer">Ada Jatuh Tempo?</label>
                                </div>
                                
                                <template v-if="form.due_date_type !== null">
                                    <div class="w-full flex gap-2 mb-3">
                                        <button type="button" @click="form.due_date_type = 'fixed'" :class="['flex-1 py-2 text-[10px] font-bold uppercase rounded-lg transition-all', form.due_date_type === 'fixed' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-500']">Tgl Pasti</button>
                                        <button type="button" @click="form.due_date_type = 'monthly'" :class="['flex-1 py-2 text-[10px] font-bold uppercase rounded-lg transition-all', form.due_date_type === 'monthly' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-500']">Tiap Bulan</button>
                                        <button type="button" @click="form.due_date_type = 'daily'" :class="['flex-1 py-2 text-[10px] font-bold uppercase rounded-lg transition-all', form.due_date_type === 'daily' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-500']">Per Hari</button>
                                    </div>

                                    <div v-if="form.due_date_type === 'fixed'" class="w-full flex flex-col gap-2">
                                        <div @click="dateModalTarget = 'due_date'; showDateModal = true"
                                            class="w-full bg-gray-800 border border-white/10 transition-colors rounded-lg flex items-center justify-center gap-2 text-sm font-bold text-white relative overflow-hidden cursor-pointer py-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="pointer-events-none tracking-wide">
                                                {{ form.due_date ? (new Date(form.due_date).toDateString() === new Date().toDateString() ? 'Hari Ini' : new Date(form.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })) : 'Pilih Tanggal' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="form.due_date_type === 'monthly'" class="w-full flex flex-col gap-2 items-center">
                                        <label class="text-xs text-gray-500">Tanggal Jatuh Tempo (1-31)</label>
                                        <input type="number" min="1" max="31" v-model="form.due_date_interval" placeholder="15" class="w-full bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 focus:border-purple-500 text-center">
                                    </div>

                                    <div v-if="form.due_date_type === 'daily'" class="w-full flex flex-col gap-2 items-center">
                                        <label class="text-xs text-gray-500">Siklus Per Berapa Hari?</label>
                                        <input type="number" min="1" v-model="form.due_date_interval" placeholder="7" class="w-full bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 focus:border-purple-500 text-center">
                                    </div>
                                </template>
                            </div>

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
                        </div>
                    </div>

                    <!-- SHOW PANEL BUTTON -->
                    <button v-if="!showBottomPanel" type="button" @click="showBottomPanel = true"
                        class="flex absolute bottom-8 left-1/2 -translate-x-1/2 z-50 px-2 py-3 bg-gradient-to-br from-gray-900 to-gray-800 text-gray-500 border border-white/10 font-bold rounded-xl active:scale-95 transition-transform items-center gap-2 hover:text-white shadow-xl"
                        title="Tampilkan Panel">

                        <span>Tampilkan Panel Input</span>
                    </button>

                    <!-- BOTTOM KEYPAD AREA -->
                    <div v-show="showBottomPanel"
                        class="bg-gradient-to-br from-gray-800 to-gray-900 border-t border-white/10 rounded-t-3xl md:border md:rounded-xl md:mb-10 md:mx-4 p-4 z-20 shrink-0 relative transition-all shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.5)]">
                        <!-- NOTE & AMOUNT ROW -->
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-4 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-2 pr-4 border border-white/10">
                            <!-- WALLET ICON (CLICKABLE) -->
                            <button type="button" @click="openWalletModal(mainTab === 'Income' ? 'dest' : 'source')"
                                class="w-12 h-12 1flex items-center justify-center shrink-0 active:scale-95 transition-transform overflow-hidden relative"
                                title="Pilih Dompet">
                                <template v-if="mainTab === 'Income' ? selectedDestWallet : selectedSourceWallet">
                                    <img v-if="(mainTab === 'Income' ? selectedDestWallet : selectedSourceWallet).icon.includes('.')"
                                        :src="'/storage/' + (mainTab === 'Income' ? selectedDestWallet : selectedSourceWallet).icon"
                                        class="w-full h-full object-cover">
                                    <span v-else class="text-2xl">{{ (mainTab === 'Income' ? selectedDestWallet :
                                        selectedSourceWallet).icon }}</span>
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
                                    class="col-start-1 row-start-1 w-full h-full bg-transparent border-none focus:ring-0 text-md text-gray-500 placeholder-gray-700 border-r-2 border-white p-0 resize-none overflow-hidden break-all whitespace-pre-wrap"></textarea>
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
                            <div @click="dateModalTarget = 'transaction'; showDateModal = true"
                                class="flex-1 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl flex items-center justify-center gap-2 text-xs font-bold text-gray-500 relative overflow-hidden cursor-pointer">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="pointer-events-none tracking-wide">{{
                                    new Date(form.date).toDateString() === new Date().toDateString() ? 'Hari Ini' : new
                                        Date(form.date).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'short', year: 'numeric'
                                        }) }}</span>
                            </div>
                            <button type="button" @click="showKeypad = !showKeypad"
                                class="flex w-12 h-12 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl items-center justify-center shrink-0 cursor-pointer active:scale-95 transition-transform"
                                :title="showKeypad ? 'Sembunyikan Keypad' : 'Tampilkan Keypad'">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                    width="24px" fill="#ad46ff"
                                    :class="['transition-transform duration-300', { 'rotate-180': !showKeypad }]">
                                    <path d="M440-800v487L216-537l-56 57 320 320 320-320-56-57-224 224v-487h-80Z" />
                                </svg>
                            </button>
                            <button type="button" @click="showBottomPanel = false"
                                class="flex w-12 h-12 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl items-center justify-center shrink-0 cursor-pointer active:scale-95 transition-transform"
                                title="Sembunyikan Panel">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                    width="24px" fill="#ff6467">
                                    <path
                                        d="M480-40 320-200h320L480-40ZM160-280q-33 0-56.5-23.5T80-360v-400q0-33 23.5-56.5T160-840h640q33 0 56.5 23.5T880-760v400q0 33-23.5 56.5T800-280H160Zm0-80h640v-400H160v400Zm160-40h320v-80H320v80ZM200-520h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80ZM200-640h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80ZM160-360v-400 400Z" />
                                </svg>
                            </button>
                            <button type="button" @click="submit(true)"
                                class="w-[72px] h-12 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl flex items-center justify-center text-green-500 shrink-0 active:scale-95 transition-transform"
                                title="Simpan Perubahan">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>

                        </div>

                        <!-- KEYPAD GRID -->
                        <div v-show="showKeypad" class="grid grid-cols-3 gap-2">
                            <button @click="handleKeypad('7')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">7</button>
                            <button @click="handleKeypad('8')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">8</button>
                            <button @click="handleKeypad('9')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">9</button>
                            <button @click="handleKeypad('4')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">4</button>
                            <button @click="handleKeypad('5')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">5</button>
                            <button @click="handleKeypad('6')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">6</button>
                            <button @click="handleKeypad('1')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">1</button>
                            <button @click="handleKeypad('2')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">2</button>
                            <button @click="handleKeypad('3')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">3</button>
                            <button @click="handleKeypad('0')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl text-lg font-bold text-gray-500 flex items-center justify-center">0</button>
                            <button @click="handleKeypad('del')" type="button"
                                class="h-[52px] bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl flex items-center justify-center relative">
                                <!-- Custom back delete button -->
                                <div class="w-8 h-8 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- DATE MODALS (Overlay) -->
            <div v-if="showDateModal"
                class="fixed inset-0 z-[100] flex flex-col justify-end bg-black/70 backdrop-blur-sm"
                @click.self="showDateModal = false">
                <div
                    class="w-full max-w-md mx-auto bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-t-2xl p-5 pb-safe animate-slide-up">
                    <div class="w-12 h-1.5 bg-white/20 rounded-xl mx-auto mb-4 cursor-pointer"
                        @click="showDateModal = false">
                    </div>
                    <h3 class="text-sm font-bold text-gray-500 mb-4 text-center tracking-widest uppercase">Pilih
                        Tanggal
                    </h3>
                    <div class="flex flex-col gap-3 pb-6">
                        <div class="flex gap-2">
                            <button @click="setDate(0)"
                                class="flex-1 p-3 bg-gray-900 border border-white/10 rounded-xl font-bold text-white hover:bg-gray-800 transition-colors active:scale-95 text-sm">
                                Hari Ini
                            </button>
                            <button @click="setDate(-1)"
                                class="flex-1 p-3 bg-gray-900 border border-white/10 rounded-xl font-bold text-white hover:bg-gray-800 transition-colors active:scale-95 text-sm">
                                Kemarin
                            </button>
                        </div>

                        <!-- CUSTOM CALENDAR -->
                        <div
                            class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 shadow-inner">
                            <!-- Header -->
                            <div class="flex justify-between items-center mb-4">
                                <button type="button" @click="prevMonth"
                                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors active:scale-95">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span class="text-sm font-bold text-white tracking-wide">{{ monthNames[currentMonth] }}
                                    {{
                                        currentYear }}</span>
                                <button type="button" @click="nextMonth"
                                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors active:scale-95">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Days of week -->
                            <div class="grid grid-cols-7 mb-2">
                                <span v-for="d in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="d"
                                    class="text-center text-[10px] font-black uppercase text-gray-500">{{ d }}</span>
                            </div>

                            <!-- Grid -->
                            <div class="grid grid-cols-7 gap-1">
                                <!-- Empty slots -->
                                <div v-for="n in firstDayOfMonth" :key="'empty-' + n" class="h-8"></div>
                                <!-- Days -->
                                <button v-for="day in daysInMonth" :key="day" @click="selectSpecificDate(day)" :class="[
                                    'h-8 w-full flex items-center justify-center text-sm font-bold rounded-lg transition-all active:scale-90',
                                    (dateModalTarget === 'due_date' ? form.due_date : form.date) === [currentYear, String(currentMonth + 1).padStart(2, '0'), String(day).padStart(2, '0')].join('-')
                                        ? 'bg-gradient-to-br from-purple-600 to-purple-800 text-white shadow-md border border-purple-400/50'
                                        : 'text-gray-300 hover:bg-gray-800 border border-transparent'
                                ]">
                                    {{ day }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WALLET MODALS (Overlay) -->
            <div v-if="showWalletModal"
                class="fixed inset-0 z-[100] flex flex-col justify-end bg-black/70 backdrop-blur-sm"
                @click.self="showWalletModal = false">
                <div
                    class="w-full max-w-md mx-auto bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-5 pb-safe animate-slide-up">
                    <div class="w-12 h-1.5 bg-white/20 rounded-xl mx-auto mb-4 cursor-pointer"
                        @click="showWalletModal = false">
                    </div>
                    <h3 class="text-sm font-bold text-gray-500 mb-4 text-center tracking-widest uppercase">Pilih
                        Dompet
                    </h3>
                    <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[60vh] pb-6">
                        <div v-for="w in availableWallets" :key="w.id" @click="selectWallet(w)"
                            class="bg-gray-900 border border-white/10 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 rounded-xl flex items-center justify-center text-xl overflow-hidden">
                                <img v-if="w.icon.includes('.')" :src="'/storage/' + w.icon"
                                    class="w-full h-full object-cover">
                                <span v-else>{{ w.icon }}</span>
                            </div>
                            <div class="flex-1">
                                <span class="text-sm font-bold text-white block">{{ w.name }}</span>
                                <p v-if="['Asset', 'Liquid'].includes(w.group_type)"
                                    class="text-xs text-purple-500 font-bold tracking-widest mt-0.5">
                                    Rp {{ new Intl.NumberFormat('id-ID').format(w.balance) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- DELETE CONFIRMATION TOAST/MODAL -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm transition-opacity" @click.self="showDeleteConfirm = false">
            <div class="w-full max-w-sm bg-gradient-to-br from-red-900 to-gray-900 rounded-2xl border border-red-500/30 p-6 animate-pop-in relative shadow-2xl">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-red-500/20 text-red-400 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white tracking-tight mb-2">Hapus Transaksi?</h3>
                    <p class="text-sm text-red-200">Yakin mau menghapus transaksi ini? Data yang dihapus tidak bisa dikembalikan.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteConfirm = false" class="flex-1 bg-gray-800 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all">
                        Batal
                    </button>
                    <button type="button" @click="confirmDelete" class="flex-1 bg-gradient-to-br from-red-600 to-red-500 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-lg shadow-red-500/20 active:scale-95 transition-all">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes pop-in { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.animate-pop-in { animation: pop-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }

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