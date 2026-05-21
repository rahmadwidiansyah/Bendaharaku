<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    transaction: Object,
    wallets: Array,
    categories: Array,
    systemWallets: Array,
});

const form = useForm({
    date: props.transaction.date,
    category_id: props.transaction.category_id,
    source_wallet_id: props.transaction.source_wallet_id,
    destination_wallet_id: props.transaction.destination_wallet_id,
    amount: props.transaction.amount.toString(),
    subject: props.transaction.subject || '-',
    notes: props.transaction.notes || '',
});

const activeType = ref('Expense');
const showCategoryModal = ref(false);
const showWalletModal = ref(false);
const showDeleteConfirm = ref(false);
const walletModalMode = ref('source');
const displayAmount = ref(new Intl.NumberFormat('id-ID').format(props.transaction.amount));
const walletFrequency = ref({});

onMounted(() => {
    const cat = props.categories.find(c => c.id === props.transaction.category_id);
    if (cat) activeType.value = cat.type.name;

    try {
        const storedFreq = localStorage.getItem('wallet_frequency');
        if (storedFreq) {
            walletFrequency.value = JSON.parse(storedFreq);
        }
    } catch (e) {
        console.error("Gagal meload wallet frequency", e);
    }
});

const formatAmountInput = (e) => {
    let val = e.target.value.replace(/\D/g, '');
    form.amount = val;
    displayAmount.value = val ? new Intl.NumberFormat('id-ID').format(parseInt(val)) : '';
};

const activeCategories = computed(() => {
    return props.categories.filter(cat => cat.type.name === activeType.value);
});

const selectedCategory = computed(() => {
    return props.categories.find(c => c.id === form.category_id);
});

const selectedSourceWallet = computed(() => {
    const all = [...props.wallets, ...props.systemWallets];
    return all.find(w => w.id === form.source_wallet_id);
});

const selectedDestWallet = computed(() => {
    const all = [...props.wallets, ...props.systemWallets];
    return all.find(w => w.id === form.destination_wallet_id);
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

const setType = (type) => {
    activeType.value = type;
    form.category_id = null;
    form.source_wallet_id = null;
    form.destination_wallet_id = null;
    form.subject = (type === 'Debt' || type === 'Receivable') ? '' : '-';
    form.clearErrors();

    if (type === 'Expense') {
        const merchant = props.systemWallets.find(w => w.name.toLowerCase().includes('merchant'));
        form.destination_wallet_id = merchant?.id;
    } else if (type === 'Income') {
        const external = props.systemWallets.find(w => w.name.toLowerCase().includes('external'));
        form.source_wallet_id = external?.id;
    }

    const typeCats = props.categories.filter(cat => cat.type.name === type);
    if (typeCats.length === 1) {
        selectCategory(typeCats[0]);
    }
};

const selectCategory = (cat) => {
    form.category_id = cat.id;
    showCategoryModal.value = false;
    form.clearErrors('category_id');

    if (['Debt', 'Receivable'].includes(activeType.value)) {
        form.source_wallet_id = null;
        form.destination_wallet_id = null;
    }

    const syH = props.systemWallets.find(w => w.name.toLowerCase().includes('hutang'));
    const syP = props.systemWallets.find(w => w.name.toLowerCase().includes('piutang'));

    if (cat.category_name === 'Dapat Hutangan') {
        form.source_wallet_id = syH?.id;
    } else if (cat.category_name === 'Bayar Cicilan Hutang') {
        form.destination_wallet_id = syH?.id;
    } else if (cat.category_name === 'Ngasih Piutang') {
        form.destination_wallet_id = syP?.id;
    } else if (cat.category_name === 'Terima Bayar Piutang') {
        form.source_wallet_id = syP?.id;
    }
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
        form.clearErrors('source_wallet_id');
    } else {
        form.destination_wallet_id = w.id;
        form.clearErrors('destination_wallet_id');
    }
    showWalletModal.value = false;
};

const submit = () => {
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
    });
};

const handleBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('dashboard'));
    }
};

const destroy = () => {
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    showDeleteConfirm.value = false;
    router.delete(route('transactions.destroy', props.transaction.id));
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head title="Edit Transaksi" />

        <div class="p-5 pb-32 w-full lg:max-w-4xl mx-auto lg:px-8 relative">
            <header class="flex justify-between items-center mb-6 pt-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Edit Transaksi</h1>
                <button type="button" @click="handleBack" class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </header>

            <!-- TABS -->
            <div class="grid grid-cols-3 gap-2 mb-2">
                <button v-for="t in ['Expense', 'Income', 'Transfer']" :key="t" @click="setType(t)" type="button" :class="['w-full text-[10px] font-bold uppercase tracking-widest py-3 rounded-xl transition-all border', activeType === t ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border-white/10 shadow-md' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-500 border-transparent']">
                    {{ t === 'Expense' ? 'Pengeluaran' : (t === 'Income' ? 'Pemasukan' : 'Transfer') }}
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2 mb-6">
                <button v-for="t in ['Debt', 'Receivable']" :key="t" @click="setType(t)" type="button" :class="['w-full text-[10px] font-bold uppercase tracking-widest py-3 rounded-xl transition-all border', activeType === t ? 'bg-gradient-to-br from-gray-800 to-gray-900 text-purple-500 border-white/10 shadow-md' : 'bg-gradient-to-br from-gray-900 to-gray-800 text-gray-500 border-transparent']">
                    {{ t === 'Debt' ? 'Hutang' : 'Piutang' }}
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- NOMINAL -->
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5 text-center relative shadow-inner group">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Nominal (Rp)</label>
                    <input type="text" :value="displayAmount" @input="formatAmountInput" inputmode="numeric" required placeholder="0"
                        class="w-full bg-transparent border-none text-white text-center text-4xl font-bold placeholder-gray-600 focus:ring-0 p-0 focus:outline-none caret-purple-500">
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-12 h-1 bg-purple-500 rounded-full opacity-0 group-focus-within:opacity-100 transition-opacity"></div>
                    <div v-if="form.errors.amount" class="text-red-500 text-xs mt-2 font-bold">{{ form.errors.amount }}</div>
                </div>

                <!-- TANGGAL & KATEGORI -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm relative">
                        <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Tanggal</label>
                        <input type="date" v-model="form.date" required class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0" style="color-scheme: dark;">
                        <div v-if="form.errors.date" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.date }}</div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm cursor-pointer active:scale-95 transition-transform" @click="showCategoryModal = true">
                        <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Kategori</label>
                        <div class="flex items-center justify-between px-1">
                            <div class="flex items-center gap-2 truncate">
                                <template v-if="selectedCategory">
                                    <img v-if="selectedCategory.icon.includes('.')" :src="'/storage/' + selectedCategory.icon" class="w-5 h-5 object-cover rounded-md">
                                    <span v-else>{{ selectedCategory.icon }}</span>
                                    <span class="text-sm font-bold text-white truncate">{{ selectedCategory.category_name }}</span>
                                </template>
                                <span v-else class="text-sm font-bold text-gray-600 truncate">-- Pilih --</span>
                            </div>
                            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                        <div v-if="form.errors.category_id" class="text-red-500 text-[10px] mt-2 font-bold">{{ form.errors.category_id }}</div>
                    </div>
                </div>

                <!-- WALLETS -->
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-4 shadow-inner flex flex-col gap-3">
                    <div v-if="showSourceWallet">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Dari Dompet</label>
                        <div @click="openWalletModal('source')" class="w-full bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-white/10">
                            <div class="flex items-center gap-2 truncate">
                                <template v-if="selectedSourceWallet">
                                    <img v-if="selectedSourceWallet.icon.includes('.')" :src="'/storage/' + selectedSourceWallet.icon" class="w-5 h-5 object-cover rounded-md">
                                    <span v-else>{{ selectedSourceWallet.icon }}</span>
                                    <span class="font-bold truncate">{{ selectedSourceWallet.name }}</span>
                                </template>
                                <span v-else class="font-bold text-gray-600">-- Pilih Dompet --</span>
                            </div>
                            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                        <div v-if="form.errors.source_wallet_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.source_wallet_id }}</div>
                    </div>

                    <div v-if="showDestWallet">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Ke Dompet</label>
                        <div @click="openWalletModal('dest')" class="w-full bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-white/10">
                            <div class="flex items-center gap-2 truncate">
                                <template v-if="selectedDestWallet">
                                    <img v-if="selectedDestWallet.icon.includes('.')" :src="'/storage/' + selectedDestWallet.icon" class="w-5 h-5 object-cover rounded-md">
                                    <span v-else>{{ selectedDestWallet.icon }}</span>
                                    <span class="font-bold truncate">{{ selectedDestWallet.name }}</span>
                                </template>
                                <span v-else class="font-bold text-gray-600">-- Pilih Dompet --</span>
                            </div>
                            <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                        <div v-if="form.errors.destination_wallet_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.destination_wallet_id }}</div>
                    </div>
                </div>

                <!-- PIHAK TERKAIT -->
                <div v-if="['Debt', 'Receivable'].includes(activeType)" class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 shadow-sm">
                    <label class="block text-[9px] font-bold text-purple-500 uppercase tracking-widest mb-1.5 ml-1">Pihak Terkait</label>
                    <input type="text" v-model="form.subject" placeholder="Nama..." class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0">
                    <div v-if="form.errors.subject" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.subject }}</div>
                </div>

                <div class="pt-2">
                    <input type="text" v-model="form.notes" placeholder="Catatan tambahan (opsional)" class="w-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-4 text-sm placeholder-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                    <div v-if="form.errors.notes" class="text-red-500 text-[10px] mt-1">{{ form.errors.notes }}</div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" @click="destroy" class="flex-1 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-red-500 font-bold text-sm uppercase tracking-widest py-4 rounded-xl active:scale-95 transition-all">
                        Hapus
                    </button>
                    <button type="submit" :disabled="form.processing" class="flex-[2] bg-gradient-to-br from-purple-600 to-purple-500 text-white font-bold text-sm uppercase tracking-widest py-4 rounded-xl shadow-lg shadow-purple-500/20 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ form.processing ? 'Menyimpan...' : 'Update' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- CATEGORY MODAL -->
        <div v-if="showCategoryModal" class="fixed inset-0 z-[100] flex flex-col justify-end bg-black/70 backdrop-blur-sm" @click.self="showCategoryModal = false">
            <div class="w-full w-full lg:max-w-4xl mx-auto lg:px-8 bg-gray-900 rounded-t-2xl border-t border-x border-white/10 p-5 pb-safe animate-slide-up">
                <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer" @click="showCategoryModal = false"></div>
                <h3 class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Kategori</h3>
                <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[60vh] pb-6">
                    <div v-for="cat in activeCategories" :key="cat.id" @click="selectCategory(cat)" class="bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all">
                        <div class="w-12 h-12 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl flex items-center justify-center text-xl border border-white/10 overflow-hidden">
                            <img v-if="cat.icon.includes('.')" :src="'/storage/' + cat.icon" class="w-full h-full object-cover">
                            <span v-else>{{ cat.icon }}</span>
                        </div>
                        <span class="text-sm font-bold text-white">{{ cat.category_name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- WALLET MODAL -->
        <div v-if="showWalletModal" class="fixed inset-0 z-[100] flex flex-col justify-end bg-black/70 backdrop-blur-sm" @click.self="showWalletModal = false">
            <div class="w-full w-full lg:max-w-4xl mx-auto lg:px-8 bg-gray-900 rounded-t-2xl border-t border-x border-white/10 p-5 pb-safe animate-slide-up">
                <div class="w-12 h-1.5 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer" @click="showWalletModal = false"></div>
                <h3 class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Dompet</h3>
                <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[60vh] pb-6">
                    <div v-for="w in availableWallets" :key="w.id" @click="selectWallet(w)" class="bg-gradient-to-br from-gray-800 to-gray-900 border border-white/10 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all group hover:border-purple-500/30">
                        <div class="w-12 h-12 bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl flex items-center justify-center text-xl border border-white/10 overflow-hidden shadow-inner group-hover:scale-105 transition-transform">
                            <img v-if="w.icon.includes('.')" :src="'/storage/' + w.icon" class="w-full h-full object-cover">
                            <span v-else>{{ w.icon }}</span>
                        </div>
                        <div class="flex-1">
                            <span class="text-sm font-bold text-white block">{{ w.name }}</span>
                            <p v-if="['Asset', 'Liquid'].includes(w.group_type)" class="text-xs text-gray-500 font-bold tracking-widest mt-0.5 uppercase">
                                Saldo: <span class="text-purple-500">Rp {{ new Intl.NumberFormat('id-ID').format(w.balance) }}</span>
                            </p>
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
@keyframes slide-up { 0% { transform: translateY(100%); } 100% { transform: translateY(0); } }
.animate-slide-up { animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>