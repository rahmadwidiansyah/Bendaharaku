<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TransactionTypeTab from '@/Components/TransactionTypeTab.vue'
import AmountKeypad from '@/Components/AmountKeypad.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { useTransactionForm } from '@/Composables/useTransactionForm.js'
import { formatNumber } from '@/utils/format.js'

const { isDesktopLayout } = useLayoutPreference()

const props = defineProps({
    wallets: Array,
    categories: Array,
    systemWallets: Array,
    debtSubjects: Array,
    receivableSubjects: Array,
})

const form = useForm({
    category_id: null,
    source_wallet_id: null,
    destination_wallet_id: null,
    amount: 0,
    date: new Date().toISOString().split('T')[0],
    subject: '-',
    notes: '',
    due_date: null,
    due_date_type: null,
    due_date_interval: null,
})

// ─── Semua shared logic dari composable ──────────────────────────
const tx = useTransactionForm(form, props, { isDesktopLayout })

const {
    rawAmount, formattedAmount, handleKeypad, handleDesktopInput,
    walletFrequency, loadWalletFrequency,
    mainTab, activeType, debtSubTab, setMainTab, setType, setDebtSubTab,
    showCategoryModal, showWalletModal, walletModalMode,
    showKeypad, showBottomPanel, showDateModal, dateModalTarget,
    monthNames, currentMonth, currentYear, daysInMonth, firstDayOfMonth,
    prevCalendarMonth, nextCalendarMonth, selectSpecificDate, setDate,
    selectedSourceWallet, selectedDestWallet, availableWallets,
    openWalletModal, selectWallet,
    selectedCategory, activeCategories, activeSubjects, isMoneyIn, selectCategory,
} = tx

onMounted(() => {
    setType('Expense')
    loadWalletFrequency()
})

// ─── Step flow (Create-specific) ─────────────────────────────────
// Step 1: Pilih tipe, Step 2: Pilih kategori, Step 3: Dompet + Nominal
const formStep = ref(1)
const typeConfirmed = ref(false)

const confirmType = (tab) => {
    setMainTab(tab)
    typeConfirmed.value = true
    formStep.value = 2
    // reset kategori dan step lanjutan
    form.category_id = null
}

const confirmCategory = () => {
    if (!form.category_id && !['Transfer', 'Debt', 'Receivable'].includes(mainTab.value)) return
    formStep.value = 3
    showBottomPanel.value = true
}

const resetToStep = (step) => {
    formStep.value = step
    if (step <= 1) {
        typeConfirmed.value = false
        form.category_id = null
        showBottomPanel.value = false
    }
    if (step <= 2) {
        form.category_id = null
        showBottomPanel.value = false
    }
}

// ─── Submit logic (Create-specific) ──────────────────────────────
const submit = (closeAfter = true) => {
    if (!form.amount || form.amount <= 0) {
        form.setError('amount', 'Nominal harus lebih dari 0')
        return
    }
    if (new Date(form.date) > new Date()) {
        form.setError('date', 'Masa depan tidak diizinkan!')
        return
    }
    if (['Debt', 'Receivable'].includes(activeType.value) && (!form.subject || form.subject === '-')) {
        form.setError('subject', 'Wajib diisi Bos!')
        return
    }
    form.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            if (closeAfter) {
                handleBack()
            } else {
                form.amount = 0
                rawAmount.value = '0'
                form.notes = ''
                form.category_id = null
            }
        },
    })
}

const submitAndClose = () => submit(true)
const submitAndStay  = () => submit(false)

const handleBack = () => router.visit(route('dashboard'))
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Catat Transaksi" />

        <div :class="[
            'flex flex-col bg-gray-800 w-full text-white overflow-hidden',
            'fixed inset-0 z-60 h-dvh max-h-dvh',
            isDesktopLayout ? 'lg:relative lg:inset-auto lg:z-0 lg:h-screen lg:max-h-screen' : ''
        ]" style="padding-bottom: max(7rem, calc(3.5rem + env(safe-area-inset-bottom, 0px) + 1rem))">

            <div class="flex flex-col h-full w-full max-w-md mx-auto relative bg-gray-800 overflow-hidden">
                <form @submit.prevent="submit" class="flex flex-col h-full min-h-0 overflow-hidden relative lg:pt-8">

                    <div class="px-4 pt-4 pb-2 shrink-0 flex gap-2 items-stretch">
                        <!-- Step 1: Pilih Tipe — tampil sebagai pill ringkasan setelah dipilih -->
                        <div class="flex-1">
                            <!-- Belum dipilih: tampilkan semua pilihan -->
                            <div v-if="!typeConfirmed" class="grid grid-cols-5 gap-1.5">
                                <button
                                    v-for="(item, idx) in [
                                        { tab: 'Expense',    label: 'Keluar',   icon: '📤', color: 'text-red-400',    active: 'bg-red-500/10 border-red-500/50' },
                                        { tab: 'Income',     label: 'Masuk',    icon: '📥', color: 'text-green-400',  active: 'bg-green-500/10 border-green-500/50' },
                                        { tab: 'Transfer',   label: 'Transfer', icon: '🔄', color: 'text-blue-400',   active: 'bg-blue-500/10 border-blue-500/50' },
                                        { tab: 'Debt',       label: 'Hutang',   icon: '📊', color: 'text-orange-400', active: 'bg-orange-500/10 border-orange-500/50' },
                                        { tab: 'Receivable', label: 'Piutang',  icon: '💰', color: 'text-yellow-400', active: 'bg-yellow-500/10 border-yellow-500/50' },
                                    ]"
                                    :key="idx"
                                    type="button"
                                    @click="confirmType(item.tab)"
                                    :class="[
                                        'flex flex-col items-center justify-center py-2 px-1 rounded-xl border transition-all active:scale-95 gap-1',
                                        mainTab === item.tab ? item.active + ' ' + item.color : 'bg-linear-to-br from-gray-900 to-gray-800 border-white/10 text-gray-400',
                                    ]"
                                >
                                    <span class="text-base leading-none">{{ item.icon }}</span>
                                    <span class="text-[9px] font-bold uppercase tracking-wide leading-none">{{ item.label }}</span>
                                </button>
                            </div>
                            <!-- Sudah dipilih: tampilkan pill ringkasan + tap untuk ubah -->
                            <button
                                v-else
                                type="button"
                                @click="resetToStep(1)"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl border border-purple-500/40 bg-purple-500/10 active:scale-95 transition-all"
                            >
                                <span class="text-base">{{
                                    mainTab === 'Expense' ? '📤' :
                                    mainTab === 'Income' ? '📥' :
                                    mainTab === 'Transfer' ? '🔄' :
                                    mainTab === 'Debt' ? '📊' : '💰'
                                }}</span>
                                <span class="text-xs font-bold text-purple-300 flex-1 text-left">{{
                                    mainTab === 'Expense' ? 'Pengeluaran' :
                                    mainTab === 'Income' ? 'Pemasukan' :
                                    mainTab === 'Transfer' ? 'Transfer' :
                                    mainTab === 'Debt' ? 'Hutang' : 'Piutang'
                                }}</span>
                                <svg class="w-3.5 h-3.5 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                                </svg>
                            </button>
                        </div>
                        <button type="button" @click="handleBack"
                            class="w-[46px] shrink-0 flex items-center justify-center text-red-400 active:scale-95 transition-transform bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 hover:bg-red-500/10 hover:text-red-300"
                            aria-label="Tutup">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Sub-tab Debt: hanya tampil setelah tipe dikonfirmasi -->
                    <div v-if="typeConfirmed && mainTab === 'Debt'" class="px-4 pb-2 flex flex-col gap-2 shrink-0">
                        <div class="flex gap-2 transition-all">
                            <button type="button" @click="setDebtSubTab('income')"
                                :class="['flex-1 py-2 rounded-xl text-2xs font-bold transition-all whitespace-nowrap', debtSubTab === 'income' ? 'bg-linear-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10']">
                                Dapat Hutang
                            </button>
                            <button type="button" @click="setDebtSubTab('expense')"
                                :class="['flex-1 py-2 rounded-xl text-2xs font-bold transition-all whitespace-nowrap', debtSubTab === 'expense' ? 'bg-linear-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10']">
                                Bayar Hutang
                            </button>
                        </div>
                    </div>

                    <div v-if="typeConfirmed && mainTab === 'Receivable'" class="px-4 pb-2 flex flex-col gap-2 shrink-0">
                        <div class="flex gap-2 transition-all">
                            <button type="button" @click="setDebtSubTab('expense')"
                                :class="['flex-1 py-2 rounded-xl text-2xs font-bold transition-all whitespace-nowrap', debtSubTab === 'expense' ? 'bg-linear-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10']">
                                Beri Piutang
                            </button>
                            <button type="button" @click="setDebtSubTab('income')"
                                :class="['flex-1 py-2 rounded-xl text-2xs font-bold transition-all whitespace-nowrap', debtSubTab === 'income' ? 'bg-linear-to-br from-gray-800 to-gray-900 text-purple-500 border border-white/10' : 'bg-transparent text-gray-400 border border-white/10']">
                                Terima Piutang
                            </button>
                        </div>
                    </div>

                    <!-- Transfer: pilih dompet muncul di step 2 -->
                    <div v-if="typeConfirmed && mainTab === 'Transfer'"
                        class="px-4 pb-4 flex items-center justify-center gap-10 shrink-0">
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
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                            </button>
                            <span class="text-2xs font-bold text-gray-500 uppercase tracking-widest truncate max-w-fit text-center">{{ selectedSourceWallet ? selectedSourceWallet.name : 'Sumber' }}</span>
                        </div>
                        <div class="flex flex-col items-center justify-center text-gray-500 pb-7">
                            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>
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
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                            </button>
                            <span class="text-2xs font-bold text-gray-500 uppercase tracking-widest truncate max-w-fit text-center">{{ selectedDestWallet ? selectedDestWallet.name : 'Tujuan' }}</span>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto px-4 py-4 pb-20 no-scrollbar relative">

                        <div v-if="Object.keys(form.errors).length > 0"
                            class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                            <div v-for="(err, key) in form.errors" :key="key"
                                class="text-red-400 text-2xs font-bold flex items-center gap-1.5 mb-1 last:mb-0">
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ err }}
                            </div>
                        </div>

                        <div v-if="['Debt', 'Receivable'].includes(activeType)"
                            class="flex flex-col justify-start h-full gap-4">

                            <div class="flex flex-col items-center">
                                <label class="text-2xs font-bold text-gray-500 uppercase tracking-widest mb-2 text-center">Pihak / Nama Terkait</label>
                                <input type="text" v-model="form.subject" placeholder="Masukkan nama..."
                                    @input="form.subject = $event.target.value.toUpperCase()"
                                    class="w-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 focus:border-purple-500 rounded-xl px-4 py-3 text-center text-lg font-bold text-white focus:ring-0 placeholder-gray-700 transition-colors outline-none uppercase">
                                <div v-if="activeSubjects && activeSubjects.length > 0 && ((activeType === 'Debt' && debtSubTab === 'expense') || (activeType === 'Receivable' && debtSubTab === 'income'))"
                                    class="flex flex-wrap gap-2 justify-center mt-3">
                                    <button type="button" v-for="sub in activeSubjects" :key="sub"
                                        @click="form.subject = sub"
                                        class="px-3 py-1.5 rounded-full text-2xs font-bold border transition-all active:scale-95 uppercase"
                                        :class="form.subject === sub ? 'bg-purple-600/20 text-purple-400 border-purple-500/50' : 'bg-gray-800 text-gray-400 border-white/5 hover:bg-gray-700'">
                                        {{ sub }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="(activeType === 'Debt' && debtSubTab === 'income') || (activeType === 'Receivable' && debtSubTab === 'expense')"
                                class="flex flex-col items-center p-4 bg-gray-900/50 rounded-xl border border-white/5">
                                <div class="flex items-center gap-2 mb-3 w-full justify-center">
                                    <input type="checkbox" id="has_due" :checked="form.due_date_type !== null"
                                        @change="form.due_date_type = $event.target.checked ? 'fixed' : null"
                                        class="rounded bg-gray-800 border-white/10 text-purple-600 focus:ring-purple-600">
                                    <label for="has_due" class="text-2xs font-bold text-purple-400 uppercase tracking-widest cursor-pointer">Ada Jatuh Tempo?</label>
                                </div>
                                <template v-if="form.due_date_type !== null">
                                    <div class="w-full flex gap-2 mb-3">
                                        <button type="button" @click="form.due_date_type = 'fixed'"
                                            :class="['flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all', form.due_date_type === 'fixed' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-500']">Tgl Pasti</button>
                                        <button type="button" @click="form.due_date_type = 'monthly'"
                                            :class="['flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all', form.due_date_type === 'monthly' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-500']">Tiap Bulan</button>
                                        <button type="button" @click="form.due_date_type = 'daily'"
                                            :class="['flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all', form.due_date_type === 'daily' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-500']">Per Hari</button>
                                    </div>
                                    <div v-if="form.due_date_type === 'fixed'" class="w-full flex flex-col gap-2">
                                        <div @click="dateModalTarget = 'due_date'; showDateModal = true"
                                            class="w-full bg-gray-800 border border-white/10 rounded-lg flex items-center justify-center gap-2 text-sm font-bold text-white cursor-pointer py-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="pointer-events-none tracking-wide">{{ form.due_date ? new Date(form.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Pilih Tanggal' }}</span>
                                        </div>
                                    </div>
                                    <div v-if="form.due_date_type === 'monthly'" class="w-full flex flex-col gap-2 items-center">
                                        <label class="text-2xs text-gray-500">Tanggal Jatuh Tempo (1-31)</label>
                                        <input type="number" min="1" max="31" v-model="form.due_date_interval" placeholder="15"
                                            class="w-full bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 focus:border-purple-500 text-center">
                                    </div>
                                    <div v-if="form.due_date_type === 'daily'" class="w-full flex flex-col gap-2 items-center">
                                        <label class="text-2xs text-gray-500">Siklus Per Berapa Hari?</label>
                                        <input type="number" min="1" v-model="form.due_date_interval" placeholder="7"
                                            class="w-full bg-gray-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 focus:border-purple-500 text-center">
                                    </div>
                                </template>
                            </div>
                            <!-- Lanjut ke nominal -->
                            <button type="button" @click="confirmCategory"
                                class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold tracking-wide transition-all active:scale-95">
                                Lanjut → Isi Nominal
                            </button>
                        </div>

                        <!-- Sebelum tipe dipilih -->
                        <div v-else-if="!typeConfirmed" class="flex flex-col items-center justify-center h-40 text-center gap-3">
                            <span class="text-4xl">👆</span>
                            <p class="text-sm font-bold text-gray-400">Pilih jenis transaksi di atas untuk mulai mencatat</p>
                        </div>

                        <!-- Step 2 Transfer: tombol lanjut -->
                        <div v-else-if="typeConfirmed && mainTab === 'Transfer' && formStep <= 2"
                            class="flex flex-col items-center justify-center h-32 gap-3">
                            <p class="text-sm font-bold text-gray-400 text-center">Pilih dompet sumber dan tujuan di atas,<br>lalu lanjut ke nominal</p>
                            <button type="button" @click="confirmCategory"
                                :disabled="!selectedSourceWallet || !selectedDestWallet"
                                class="px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed">
                                Lanjut → Isi Nominal
                            </button>
                        </div>

                        <!-- Step 2: Kategori (Income/Expense) -->
                        <section v-else-if="typeConfirmed && formStep <= 2 && mainTab !== 'Transfer'" class="space-y-3">
                            <div class="flex items-end justify-between px-1">
                                <div>
                                    <p class="text-2xs font-black text-purple-500 uppercase tracking-[0.18em]">Langkah 2</p>
                                    <h2 class="text-sm font-bold text-white">Pilih kategori</h2>
                                </div>
                                <span v-if="selectedCategory" class="text-2xs font-bold text-green-400">✓ {{ selectedCategory.category_name }}</span>
                                <span v-else class="text-2xs font-bold text-gray-600">Wajib dipilih</span>
                            </div>
                            <div v-if="activeCategories.length" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                <button v-for="cat in activeCategories" :key="cat.id" type="button"
                                    @click="selectCategory(cat); confirmCategory()"
                                    :class="['relative flex flex-col items-center justify-center p-2 rounded-xl border transition-all active:scale-95 min-h-[88px]',
                                        form.category_id === cat.id ? 'bg-purple-500/10 border-purple-500 shadow-lg shadow-purple-500/10' : 'bg-linear-to-br from-gray-900 to-gray-800 border-white/10 hover:border-purple-500/40']">
                                    <img v-if="cat.icon.includes('.')" :src="'/storage/' + cat.icon" class="w-7 h-7 object-cover rounded-lg mb-1.5">
                                    <span v-else class="text-lg mb-1">{{ cat.icon }}</span>
                                    <span :class="['text-2xs font-bold text-center leading-tight w-full px-0.5 line-clamp-2', form.category_id === cat.id ? 'text-white' : 'text-gray-500']">{{ cat.category_name }}</span>
                                    <span v-if="form.category_id === cat.id" class="absolute top-2 right-2 w-4 h-4 rounded-full bg-purple-500 text-white text-[10px] flex items-center justify-center">✓</span>
                                </button>
                                <Link v-if="['Expense', 'Income'].includes(mainTab)" :href="route('categories.create', { type: mainTab })"
                                    class="flex flex-col items-center justify-center gap-2 p-2 rounded-xl border border-dashed border-purple-500/50 bg-purple-500/5 text-purple-400 transition-all active:scale-95 min-h-[88px] hover:bg-purple-500/10 hover:border-purple-400">
                                    <span class="w-7 h-7 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-xl leading-none">+</span>
                                    <span class="text-2xs font-black text-center leading-tight">Tambah Kategori</span>
                                </Link>
                            </div>
                            <div v-else class="rounded-xl border border-dashed border-white/10 bg-gray-900/50 p-6 text-center">
                                <p class="text-sm font-bold text-gray-400">Kategori belum tersedia</p>
                                <Link :href="route('categories.create', { type: mainTab })" class="inline-flex mt-2 text-2xs font-black text-purple-400 uppercase tracking-wider">Tambah kategori</Link>
                            </div>
                        </section>

                        <!-- Step 3: pill ringkasan kategori terpilih -->
                        <div v-if="typeConfirmed && formStep >= 3 && !['Debt', 'Receivable', 'Transfer'].includes(mainTab)" class="mb-3">
                            <button type="button" @click="resetToStep(2)"
                                class="flex items-center gap-2 px-3 py-2 rounded-xl border border-white/10 bg-gray-900 active:scale-95 transition-all hover:border-purple-500/40">
                                <span class="text-base">{{ selectedCategory?.icon?.includes('.') ? '' : (selectedCategory?.icon || '📁') }}</span>
                                <img v-if="selectedCategory?.icon?.includes('.')" :src="'/storage/' + selectedCategory.icon" class="w-5 h-5 rounded-md object-cover">
                                <span class="text-2xs font-bold text-gray-300">{{ selectedCategory?.category_name || 'Kategori' }}</span>
                                <svg class="w-3 h-3 text-gray-500 ml-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                                </svg>
                            </button>
                        </div>

                    </div><!-- end scrollable area -->

                    <div v-show="formStep >= 3"
                        class="sticky bottom-0 bg-linear-to-br from-gray-800 to-gray-900 border-t border-white/10 rounded-t-xl md:border md:rounded-xl md:mb-10 md:mx-4 p-4 z-20 shrink-0 relative transition-all"
                        style="box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.5)">
                        <div class="flex items-center justify-between px-1 mb-2">
                            <p class="text-2xs font-black text-purple-500 uppercase tracking-[0.18em] truncate pr-2">Langkah 3 · {{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.name || 'Pilih dompet' }}</p>
                            <span :class="form.amount > 0 ? 'text-green-400' : 'text-gray-600'" class="text-2xs font-bold">{{ form.amount > 0 ? '✓ Nominal terisi' : 'Nominal wajib' }}</span>
                        </div>
                        <div
                            class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-2 bg-linear-to-br from-gray-900 to-gray-800 rounded-xl p-1.5 pr-3 border border-white/10">
                            <button type="button" @click="openWalletModal(isMoneyIn ? 'dest' : 'source')"
                                class="w-10 h-10 flex items-center justify-center shrink-0 active:scale-95 transition-transform overflow-hidden relative rounded-lg"
                                title="Pilih Dompet">
                                <template v-if="isMoneyIn ? selectedDestWallet : selectedSourceWallet">
                                    <img v-if="(isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon.includes('.')"
                                        :src="'/storage/' + (isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon"
                                        class="w-full h-full object-cover">
                                    <span v-else class="text-2xl">{{ (isMoneyIn ? selectedDestWallet :
                                        selectedSourceWallet).icon }}</span>
                                </template>
                                <svg v-else class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                            </button>

                            <div class="flex-1 min-w-0 grid relative">
                                <span
                                    class="invisible whitespace-pre-wrap break-all text-sm p-0 min-h-[20px] col-start-1 row-start-1">{{
                                        (form.notes || 'Note') + ' ' }}</span>
                                <textarea v-model="form.notes" placeholder="Catatan (opsional)" rows="1" aria-label="Catatan transaksi"
                                    class="col-start-1 row-start-1 w-full h-full bg-transparent border-none focus:ring-0 text-sm text-gray-500 placeholder-gray-700 border-r-2 border-white p-0 resize-none overflow-hidden break-all whitespace-pre-wrap"></textarea>
                            </div>

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

                        <div class="flex gap-2 mb-2">
                            <button type="button" @click="dateModalTarget = 'transaction'; showDateModal = true"
                                class="flex-1 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl flex items-center justify-center gap-2 text-2xs font-bold text-gray-500 relative overflow-hidden cursor-pointer h-12" aria-label="Pilih tanggal transaksi">
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
                            </button>
                            <button type="button" @click="showKeypad = !showKeypad"
                                class="flex w-12 h-12 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl items-center justify-center shrink-0 cursor-pointer active:scale-95 transition-transform"
                                :title="showKeypad ? 'Sembunyikan Keypad' : 'Tampilkan Keypad'">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                    width="24px" fill="#ad46ff"
                                    :class="['transition-transform duration-300', { 'rotate-180': !showKeypad }]">
                                    <path d="M440-800v487L216-537l-56 57 320 320 320-320-56-57-224 224v-487h-80Z" />
                                </svg>
                            </button>
                            <button type="button" @click="showBottomPanel = false"
                                class="flex w-12 h-12 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl items-center justify-center shrink-0 cursor-pointer active:scale-95 transition-transform"
                                title="Sembunyikan Panel">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                    width="24px" fill="#ff6467">
                                    <path
                                        d="M480-40 320-200h320L480-40ZM160-280q-33 0-56.5-23.5T80-360v-400q0-33 23.5-56.5T160-840h640q33 0 56.5 23.5T880-760v400q0 33-23.5 56.5T800-280H160Zm0-80h640v-400H160v400Zm160-40h320v-80H320v80ZM200-520h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80ZM200-640h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80Zm120 0h80v-80h-80v80ZM160-360v-400 400Z" />
                                </svg>
                            </button>
                            <button type="button" @click="submitAndStay"
                                class="w-12 h-12 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl flex items-center justify-center shrink-0 cursor-pointer active:scale-95 transition-transform"
                                title="Simpan & Tambah Lagi">
                                <div
                                    class="w-6 h-6 rounded-full border-2 border-green-300 flex items-center justify-center text-green-300 text-xl leading-none font-black pt-0.5">
                                    +</div>
                            </button>
                            <button type="button" @click="submitAndClose"
                                class="w-[60px] h-12 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl flex items-center justify-center text-green-500 shrink-0 active:scale-95 transition-transform"
                                title="Simpan & Tutup">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>

                        <div v-show="showKeypad">
                            <AmountKeypad @key="handleKeypad" />
                        </div>
                    </div>
                </form>
            </div>

            <div v-if="showDateModal"
                class="fixed inset-0 z-100 flex flex-col justify-end bg-black/70 backdrop-blur-sm"
                @click.self="showDateModal = false">
                <div
                    class="w-full max-w-md mx-auto bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-t-2xl p-5 pb-safe animate-slide-up">
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

                        <div
                            class="w-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-4 shadow-inner">
                            <div class="flex justify-between items-center mb-4">
                                <button type="button" @click="prevCalendarMonth"
                                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors active:scale-95">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span class="text-sm font-bold text-white tracking-wide">{{ monthNames[currentMonth] }}
                                    {{
                                        currentYear }}</span>
                                <button type="button" @click="nextCalendarMonth"
                                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors active:scale-95">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-7 mb-2">
                                <span v-for="d in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="d"
                                    class="text-center text-2xs font-black uppercase text-gray-500">{{ d }}</span>
                            </div>

                            <div class="grid grid-cols-7 gap-1">
                                <div v-for="n in firstDayOfMonth" :key="'empty-' + n" class="h-8"></div>
                                <button v-for="day in daysInMonth" :key="day" @click="selectSpecificDate(day)" :class="[
                                    'h-8 w-full flex items-center justify-center text-sm font-bold rounded-lg transition-all active:scale-90',
                                    (dateModalTarget === 'due_date' ? form.due_date : form.date) === [currentYear, String(currentMonth + 1).padStart(2, '0'), String(day).padStart(2, '0')].join('-')
                                        ? 'bg-linear-to-br from-purple-600 to-purple-800 text-white shadow-md border border-purple-400/50'
                                        : 'text-gray-300 hover:bg-gray-800 border border-transparent'
                                ]">
                                    {{ day }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showWalletModal"
                class="fixed inset-0 z-100 flex flex-col justify-end bg-black/70 backdrop-blur-sm"
                @click.self="showWalletModal = false">
                <div
                    class="w-full max-w-md mx-auto bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-5 pb-safe animate-slide-up">
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
                                class="w-12 h-12 bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 rounded-xl flex items-center justify-center text-xl overflow-hidden">
                                <img v-if="w.icon.includes('.')" :src="'/storage/' + w.icon"
                                    class="w-full h-full object-cover">
                                <span v-else>{{ w.icon }}</span>
                            </div>
                            <div class="flex-1">
                                <span class="text-sm font-bold text-white block">{{ w.name }}</span>
                                <p v-if="['Asset', 'Liquid'].includes(w.group_type)"
                                    class="text-2xs text-purple-500 font-bold tracking-widest mt-0.5">
                                    Rp {{ new Intl.NumberFormat('id-ID').format(w.balance) }}
                                </p>
                            </div>
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
