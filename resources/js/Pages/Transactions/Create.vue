<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    wallets: Array,
    categories: Array,
    systemWallets: Array,
});

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    category_id: null,
    source_wallet_id: null,
    destination_wallet_id: null,
    amount: '',
    subject: '-',
    notes: '',
});

const activeType = ref('Expense');
const showCategoryModal = ref(false);
const showWalletModal = ref(false);
const walletModalMode = ref('source'); // 'source' or 'dest'
const displayAmount = ref('');

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

const setType = (type) => {
    activeType.value = type;
    form.category_id = null;
    form.source_wallet_id = null;
    form.destination_wallet_id = null;
    form.subject = '-';

    if (type === 'Expense') {
        const merchant = props.systemWallets.find(w => w.name.toLowerCase().includes('merchant'));
        form.destination_wallet_id = merchant?.id;
    } else if (type === 'Income') {
        const external = props.systemWallets.find(w => w.name.toLowerCase().includes('external'));
        form.source_wallet_id = external?.id;
    }
};

const selectCategory = (cat) => {
    form.category_id = cat.id;
    showCategoryModal.value = false;

    const syH = props.systemWallets.find(w => w.name.toLowerCase().includes('hutang'));
    const syP = props.systemWallets.find(w => w.name.toLowerCase().includes('piutang'));

    if (cat.category_name === 'Dapat Hutangan') {
        form.source_wallet_id = syH?.id;
        form.destination_wallet_id = null;
    } else if (cat.category_name === 'Bayar Cicilan Hutang') {
        form.destination_wallet_id = syH?.id;
        form.source_wallet_id = null;
    } else if (cat.category_name === 'Ngasih Piutang') {
        form.destination_wallet_id = syP?.id;
        form.source_wallet_id = null;
    } else if (cat.category_name === 'Terima Bayar Piutang') {
        form.source_wallet_id = syP?.id;
        form.destination_wallet_id = null;
    }
};

const availableWallets = computed(() => {
    let list = [];
    if (walletModalMode.value === 'source') {
        if (activeType.value === 'Income') return props.systemWallets.filter(w => w.name.toLowerCase().includes('external'));
        if (selectedCategory.value?.category_name === 'Terima Bayar Piutang') return props.systemWallets.filter(w => w.name.toLowerCase().includes('piutang'));
        if (selectedCategory.value?.category_name === 'Dapat Hutangan') return props.systemWallets.filter(w => w.name.toLowerCase().includes('hutang'));
        list = props.wallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
    } else {
        if (activeType.value === 'Expense') return props.systemWallets.filter(w => w.name.toLowerCase().includes('merchant'));
        if (selectedCategory.value?.category_name === 'Ngasih Piutang') return props.systemWallets.filter(w => w.name.toLowerCase().includes('piutang'));
        if (selectedCategory.value?.category_name === 'Bayar Cicilan Hutang') return props.systemWallets.filter(w => w.name.toLowerCase().includes('hutang'));
        list = props.wallets.filter(w => ['Asset', 'Liquid'].includes(w.group_type));
    }
    return list;
});

const openWalletModal = (mode) => {
    walletModalMode.value = mode;
    showWalletModal.value = true;
};

const selectWallet = (w) => {
    if (walletModalMode.value === 'source') {
        form.source_wallet_id = w.id;
    } else {
        form.destination_wallet_id = w.id;
    }
    showWalletModal.value = false;
};

const submit = () => {
    form.post(route('transactions.store'));
};

const handleBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('dashboard'));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Catat Transaksi" />

        <div class="p-5 pb-32 max-w-md mx-auto relative">
            <header class="flex justify-between items-center mb-6 pt-2">
                <h1 class="text-2xl font-bold text-white tracking-tight">Catat Transaksi</h1>
                <button type="button" @click="handleBack" class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </header>

            <!-- TABS -->
            <div class="grid grid-cols-3 gap-2 mb-2">
                <button v-for="t in ['Expense', 'Income', 'Transfer']" :key="t" @click="setType(t)" type="button" :class="['w-full text-[10px] font-bold uppercase tracking-widest py-3 rounded-2xl transition-all border', activeType === t ? 'bg-[#262626] text-purple-500 border-[#333] shadow-md' : 'bg-[#1A1A1A] text-gray-500 border-transparent']">
                    {{ t === 'Expense' ? 'Pengeluaran' : (t === 'Income' ? 'Pemasukan' : 'Transfer') }}
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2 mb-6">
                <button v-for="t in ['Debt', 'Receivable']" :key="t" @click="setType(t)" type="button" :class="['w-full text-[10px] font-bold uppercase tracking-widest py-3 rounded-2xl transition-all border', activeType === t ? 'bg-[#262626] text-purple-500 border-[#333] shadow-md' : 'bg-[#1A1A1A] text-gray-500 border-transparent']">
                    {{ t === 'Debt' ? 'Hutang' : 'Piutang' }}
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- NOMINAL -->
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-[2rem] p-5 text-center relative shadow-inner group">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Nominal (Rp)</label>
                    <input type="text" :value="displayAmount" @input="formatAmountInput" inputmode="numeric" required placeholder="0"
                        class="w-full bg-transparent border-none text-white text-center text-4xl font-bold placeholder-gray-600 focus:ring-0 p-0 focus:outline-none caret-purple-500">
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-12 h-1 bg-purple-500 rounded-full opacity-0 group-focus-within:opacity-100 transition-opacity"></div>
                </div>

                <!-- TANGGAL & KATEGORI -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[#1A1A1A] border border-[#262626] rounded-[1.5rem] p-3 shadow-sm relative">
                        <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Tanggal</label>
                        <input type="date" v-model="form.date" required class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0" style="color-scheme: dark;">
                    </div>
                    
                    <div class="bg-[#1A1A1A] border border-[#262626] rounded-[1.5rem] p-3 shadow-sm cursor-pointer active:scale-95 transition-transform" @click="showCategoryModal = true">
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
                    </div>
                </div>

                <!-- WALLETS -->
                <div class="bg-[#1A1A1A] border border-[#262626] rounded-[2rem] p-4 shadow-inner flex flex-col gap-3">
                    <div v-if="activeType !== 'Income'">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Dari Dompet</label>
                        <div @click="openWalletModal('source')" class="w-full bg-[#262626] text-white rounded-2xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-[#333]">
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
                    </div>
                    <div v-if="activeType !== 'Expense'">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5 ml-1">Ke Dompet</label>
                        <div @click="openWalletModal('dest')" class="w-full bg-[#262626] text-white rounded-2xl p-3.5 text-sm cursor-pointer flex items-center justify-between active:scale-95 transition-transform border border-[#333]">
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
                    </div>
                </div>

                <div v-if="['Debt', 'Receivable'].includes(activeType)" class="bg-[#1A1A1A] border border-[#262626] rounded-[1.5rem] p-3 shadow-sm">
                    <label class="block text-[9px] font-bold text-purple-400 uppercase tracking-widest mb-1.5 ml-1">Pihak Terkait</label>
                    <input type="text" v-model="form.subject" placeholder="Nama..." class="w-full bg-transparent border-none text-white p-1 text-sm focus:ring-0">
                </div>

                <div class="pt-2">
                    <input type="text" v-model="form.notes" placeholder="Catatan tambahan (opsional)" class="w-full bg-[#1A1A1A] border border-[#262626] text-white rounded-[1.5rem] p-4 text-sm placeholder-gray-600 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                </div>

                <button type="submit" :disabled="form.processing" class="w-full bg-purple-500 text-[#121212] font-bold text-sm uppercase tracking-widest py-4 rounded-[1.5rem] shadow-lg active:scale-95 transition-all mt-6">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Transaksi' }}
                </button>
            </form>
        </div>

        <!-- CATEGORY MODAL -->
        <div v-if="showCategoryModal" class="fixed inset-0 z-[100] flex flex-col justify-end bg-black/70 backdrop-blur-sm" @click.self="showCategoryModal = false">
            <div class="w-full max-w-md mx-auto bg-[#121212] rounded-t-[2rem] border-t border-x border-[#262626] p-5 pb-safe animate-slide-up">
                <div class="w-12 h-1.5 bg-[#333] rounded-full mx-auto mb-4 cursor-pointer" @click="showCategoryModal = false"></div>
                <h3 class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Kategori</h3>
                <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[60vh] pb-6">
                    <div v-for="cat in activeCategories" :key="cat.id" @click="selectCategory(cat)" class="bg-[#1A1A1A] border border-[#262626] p-4 rounded-2xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all">
                        <div class="w-12 h-12 bg-[#262626] rounded-xl flex items-center justify-center text-xl border border-[#333] overflow-hidden">
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
            <div class="w-full max-w-md mx-auto bg-[#121212] rounded-t-[2rem] border-t border-x border-[#262626] p-5 pb-safe animate-slide-up">
                <div class="w-12 h-1.5 bg-[#333] rounded-full mx-auto mb-4 cursor-pointer" @click="showWalletModal = false"></div>
                <h3 class="text-sm font-bold text-purple-500 mb-4 uppercase tracking-widest text-center">Pilih Dompet</h3>
                <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[60vh] pb-6">
                    <div v-for="w in availableWallets" :key="w.id" @click="selectWallet(w)" class="bg-[#1A1A1A] border border-[#262626] p-4 rounded-2xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all group hover:border-purple-500/30">
                        <div class="w-12 h-12 bg-[#262626] rounded-xl flex items-center justify-center text-xl border border-[#333] overflow-hidden shadow-inner group-hover:scale-105 transition-transform">
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

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes slide-up { 0% { transform: translateY(100%); } 100% { transform: translateY(0); } }
.animate-slide-up { animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
