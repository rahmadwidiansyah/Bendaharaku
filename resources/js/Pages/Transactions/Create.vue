<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import AmountKeypad from '@/Components/AmountKeypad.vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { useTransactionForm } from '@/Composables/useTransactionForm.js'

const { t } = useI18n()
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
    transaction_type: null,
    debt_sub_type: null,
    due_date: null,
    due_date_type: null,
    due_date_interval: null,
})

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

// ─── Step flow ────────────────────────────────────────────────────
const formStep = ref(1)
const typeConfirmed = ref(false)

// Transfer: animasi swap
const isSwapping = ref(false)

const TYPE_ITEMS = computed(() => [
    { tab: 'Expense',    label: t('types.expense'),    desc: t('types.expenseDesc'),    icon: '📤', color: 'from-red-500/20 to-red-900/10',     border: 'border-red-500/40',    text: 'text-red-400'    },
    { tab: 'Income',     label: t('types.income'),     desc: t('types.incomeDesc'),     icon: '📥', color: 'from-green-500/20 to-green-900/10',  border: 'border-green-500/40',  text: 'text-green-400'  },
    { tab: 'Transfer',   label: t('types.transfer'),   desc: t('types.transferDesc'),   icon: '🔄', color: 'from-blue-500/20 to-blue-900/10',    border: 'border-blue-500/40',   text: 'text-blue-400'   },
    { tab: 'Debt',       label: t('types.debt'),       desc: t('types.debtDesc'),       icon: '📊', color: 'from-orange-500/20 to-orange-900/10', border: 'border-orange-500/40', text: 'text-orange-400' },
    { tab: 'Receivable', label: t('types.receivable'), desc: t('types.receivableDesc'), icon: '💰', color: 'from-yellow-500/20 to-yellow-900/10', border: 'border-yellow-500/40', text: 'text-yellow-400' },
])

const confirmType = (tab) => {
    setMainTab(tab)
    typeConfirmed.value = true
    form.transaction_type = tab.toLowerCase()
    // Set debt_sub_type: 'income' = dapat hutang / terima piutang, 'expense' = bayar hutang / kasih piutang
    if (['Debt', 'Receivable'].includes(tab)) {
        form.debt_sub_type = debtSubTab.value  // 'income' or 'expense'
    } else {
        form.debt_sub_type = null
    }
    // Transfer langsung ke step 2 (form lengkap), tipe lain tetap ke step 2 (kategori)
    formStep.value = 2
    if (!['Transfer', 'Debt', 'Receivable'].includes(tab)) {
        form.category_id = null
    }
}

// Sync debt_sub_type saat user ganti sub-tab (Dapat Hutang ↔ Bayar Hutang)
watch(debtSubTab, (val) => {
    if (['Debt', 'Receivable'].includes(mainTab.value)) {
        form.debt_sub_type = val
    }
})

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
        form.transaction_type = null
        showBottomPanel.value = false
    }
    if (step <= 2) {
        if (!['Transfer', 'Debt', 'Receivable'].includes(mainTab.value)) {
            form.category_id = null
        }
        showBottomPanel.value = false
    }
}

const activeTypeItem = computed(() => TYPE_ITEMS.value.find(i => i.tab === mainTab.value))

// ─── Transfer: validasi frontend ──────────────────────────────────
const transferErrors = ref({})

const validateTransfer = () => {
    transferErrors.value = {}
    if (!form.source_wallet_id)      transferErrors.value.source = t('transaction.validation.sourceRequired')
    if (!form.destination_wallet_id) transferErrors.value.dest   = t('transaction.validation.destRequired')
    if (form.source_wallet_id && form.destination_wallet_id &&
        form.source_wallet_id === form.destination_wallet_id) {
        transferErrors.value.same = t('transaction.validation.sameWallet')
    }
    if (!form.amount || form.amount <= 0) transferErrors.value.amount = t('transaction.validation.amountPositive')
    return Object.keys(transferErrors.value).length === 0
}

// ─── Transfer: swap source ↔ destination ─────────────────────────
const swapWallets = () => {
    if (isSwapping.value) return
    isSwapping.value = true
    const tmp = form.source_wallet_id
    form.source_wallet_id      = form.destination_wallet_id
    form.destination_wallet_id = tmp
    setTimeout(() => { isSwapping.value = false }, 400)
}

// ─── Submit ───────────────────────────────────────────────────────
const submitTransfer = (closeAfter = true) => {
    if (!validateTransfer()) return
    form.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            if (closeAfter) {
                handleBack()
            } else {
                form.amount = 0
                rawAmount.value = '0'
                form.notes = ''
                form.source_wallet_id = null
                form.destination_wallet_id = null
                transferErrors.value = {}
            }
        },
    })
}

const submit = (closeAfter = true) => {
    if (!form.amount || form.amount <= 0) { form.setError('amount', t('transaction.validation.amountPositive')); return }
    if (new Date(form.date) > new Date()) { form.setError('date', t('transaction.validation.dateFuture')); return }
    if (['Debt', 'Receivable'].includes(activeType.value) && (!form.subject || form.subject === '-')) {
        form.setError('subject', t('transaction.validation.subjectRequired')); return
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
    <AuthenticatedLayout :fullWidth="true" :hideNav="true">
        <Head title="Catat Transaksi" />

        <!-- Fullscreen overlay: fixed inset-0, tinggi 100dvh, tidak ada padding-bottom besar -->
        <div
            class="fixed inset-0 z-60 flex flex-col bg-gray-900 text-white"
            :class="isDesktopLayout ? 'lg:relative lg:inset-auto lg:z-0' : ''"
            style="height: 100dvh;"
        >
            <!-- ── HEADER BAR ─────────────────────────────────────── -->
            <div class="shrink-0 border-b border-white/5">
                <!-- Baris 1: judul + tombol close -->
                <div class="flex items-center justify-between px-4 pt-3 pb-2">
                    <span class="text-xs font-black text-gray-500 uppercase tracking-widest">Catat Transaksi</span>
                    <button
                        type="button"
                        @click="handleBack"
                        class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-gray-800 border border-white/10 text-gray-400 hover:text-red-400 hover:border-red-500/30 active:scale-90 transition-all"
                        aria-label="Tutup"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Baris 2: breadcrumb progress steps -->
                <div class="flex items-center gap-2 px-4 pb-2.5 overflow-x-auto no-scrollbar">
                    <!-- Step 1 chip -->
                    <button
                        type="button"
                        @click="resetToStep(1)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest transition-all active:scale-95 shrink-0"
                        :class="formStep === 1
                            ? 'bg-purple-500/20 text-purple-300 border border-purple-500/40'
                            : typeConfirmed
                                ? 'bg-gray-800 text-gray-300 border border-white/10 hover:border-purple-500/30'
                                : 'bg-gray-800/50 text-gray-600 border border-white/5 cursor-default'"
                    >
                        <span>{{ typeConfirmed ? (activeTypeItem?.icon + ' ' + activeTypeItem?.label) : '1 · Jenis' }}</span>
                    </button>

                    <svg v-if="typeConfirmed" class="w-3 h-3 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>

                    <!-- Step 2 chip — Transfer hanya punya 2 step total -->
                    <div
                        v-if="typeConfirmed && mainTab === 'Transfer'"
                        class="px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest shrink-0"
                        :class="formStep === 2 ? 'bg-blue-500/20 text-blue-300 border border-blue-500/40' : 'bg-gray-800/50 text-gray-600 border border-white/5'"
                    >2 · Transfer</div>

                    <!-- Step 2 chip — tipe lain (Expense, Income, Debt, Receivable) -->
                    <button
                        v-if="typeConfirmed && mainTab !== 'Transfer'"
                        type="button"
                        @click="resetToStep(2)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest transition-all active:scale-95 shrink-0"
                        :class="formStep === 2
                            ? 'bg-purple-500/20 text-purple-300 border border-purple-500/40'
                            : formStep > 2
                                ? 'bg-gray-800 text-gray-300 border border-white/10 hover:border-purple-500/30'
                                : 'bg-gray-800/50 text-gray-600 border border-white/5 cursor-default'"
                    >
                        <span>{{ formStep > 2 && selectedCategory ? (selectedCategory.icon?.includes('.') ? '' : selectedCategory.icon + ' ') + selectedCategory.category_name : '2 · Kategori' }}</span>
                    </button>

                    <svg v-if="formStep >= 3 && mainTab !== 'Transfer'" class="w-3 h-3 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>

                    <!-- Step 3 chip — hanya untuk tipe non-Transfer -->
                    <div
                        v-if="formStep >= 3 && mainTab !== 'Transfer'"
                        class="px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest bg-purple-500/20 text-purple-300 border border-purple-500/40 shrink-0"
                    >3 · Nominal</div>
                </div>
            </div>

            <!-- ── STEP 1: PILIH JENIS — fullscreen centered ──────── -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="formStep === 1" class="flex-1 flex flex-col items-center justify-center px-6 gap-4">
                    <div class="text-center mb-2">
                        <p class="text-2xs font-black text-purple-500 uppercase tracking-[0.25em] mb-1">Langkah 1 dari 3</p>
                        <h1 class="text-xl font-black text-white">Jenis Transaksi</h1>
                        <p class="text-xs text-gray-500 mt-1">Pilih jenis untuk melanjutkan</p>
                    </div>

                    <div class="w-full max-w-sm grid grid-cols-1 gap-3">
                        <button
                            v-for="item in TYPE_ITEMS"
                            :key="item.tab"
                            type="button"
                            @click="confirmType(item.tab)"
                            class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl border transition-all active:scale-95 text-left"
                            :class="['bg-gradient-to-r ' + item.color, item.border]"
                        >
                            <span class="text-3xl shrink-0">{{ item.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black text-white">{{ item.label }}</p>
                                <p class="text-xs mt-0.5" :class="item.text">{{ item.desc }}</p>
                            </div>
                            <svg class="w-4 h-4 shrink-0" :class="item.text" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- ── STEP 2: PILIH KATEGORI / KONFIGURASI ───────────── -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="formStep === 2" class="flex-1 flex flex-col min-h-0 overflow-hidden">

                    <!-- Sub-tab Debt / Receivable -->
                    <div v-if="mainTab === 'Debt' || mainTab === 'Receivable'" class="shrink-0 px-4 pt-3 pb-2">
                        <div class="flex gap-2 p-1 bg-gray-800/80 rounded-xl border border-white/5">
                            <button type="button"
                                @click="setDebtSubTab(mainTab === 'Debt' ? 'income' : 'expense')"
                                class="flex-1 py-2 rounded-lg text-2xs font-black uppercase tracking-widest transition-all"
                                :class="debtSubTab === (mainTab === 'Debt' ? 'income' : 'expense') ? 'bg-purple-600 text-white shadow' : 'text-gray-500'"
                            >{{ mainTab === 'Debt' ? 'Dapat Hutang' : 'Beri Piutang' }}</button>
                            <button type="button"
                                @click="setDebtSubTab(mainTab === 'Debt' ? 'expense' : 'income')"
                                class="flex-1 py-2 rounded-lg text-2xs font-black uppercase tracking-widest transition-all"
                                :class="debtSubTab === (mainTab === 'Debt' ? 'expense' : 'income') ? 'bg-purple-600 text-white shadow' : 'text-gray-500'"
                            >{{ mainTab === 'Debt' ? 'Bayar Hutang' : 'Terima Piutang' }}</button>
                        </div>
                    </div>

                    <!-- ── TRANSFER: Form lengkap 2-step (wallet picker + nominal + catatan + tanggal) ── -->
                    <div v-if="mainTab === 'Transfer'" class="flex-1 flex flex-col min-h-0 overflow-hidden">

                        <!-- Scrollable form area -->
                        <div class="flex-1 overflow-y-auto no-scrollbar px-4 pt-3 pb-2 space-y-3">

                            <!-- Error banner -->
                            <div v-if="Object.keys(transferErrors).length || Object.keys(form.errors).length"
                                class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                                <p v-for="(err, key) in {...transferErrors, ...form.errors}" :key="key"
                                    class="text-red-400 text-2xs font-bold">{{ err }}</p>
                            </div>

                            <!-- ── Wallet Picker dengan tombol Swap ── -->
                            <div class="bg-gray-800/60 border border-white/8 rounded-2xl p-4">
                                <p class="text-2xs font-black text-gray-500 uppercase tracking-widest mb-3 text-center">Pindah Dana</p>

                                <!-- Source wallet -->
                                <button type="button" @click="openWalletModal('source')"
                                    class="w-full flex items-center gap-3 bg-gray-900/80 border rounded-xl px-4 py-3 active:scale-[0.98] transition-transform text-left"
                                    :class="transferErrors.source ? 'border-red-500/60' : selectedSourceWallet ? 'border-blue-500/40' : 'border-white/10 border-dashed'">
                                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center shrink-0">
                                        <template v-if="selectedSourceWallet">
                                            <img v-if="selectedSourceWallet.icon?.includes('.')" :src="'/storage/' + selectedSourceWallet.icon" class="w-full h-full object-cover">
                                            <span v-else class="text-xl">{{ selectedSourceWallet.icon }}</span>
                                        </template>
                                        <svg v-else class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-2xs font-black text-blue-400 uppercase tracking-widest">Dari</p>
                                        <p class="text-sm font-bold truncate" :class="selectedSourceWallet ? 'text-white' : 'text-gray-600'">
                                            {{ selectedSourceWallet?.name || 'Pilih dompet asal...' }}
                                        </p>
                                        <p v-if="selectedSourceWallet" class="text-2xs text-gray-500 mt-0.5">
                                            Rp {{ parseInt(selectedSourceWallet.balance).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>

                                <!-- Swap button -->
                                <div class="flex items-center justify-center my-2">
                                    <button type="button" @click="swapWallets"
                                        class="w-9 h-9 rounded-full bg-gray-700 border border-white/10 flex items-center justify-center active:scale-90 transition-all hover:bg-gray-600 hover:border-blue-500/40"
                                        :class="isSwapping ? 'rotate-180' : ''"
                                        style="transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1)"
                                        aria-label="Tukar dompet asal dan tujuan">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Destination wallet -->
                                <button type="button" @click="openWalletModal('dest')"
                                    class="w-full flex items-center gap-3 bg-gray-900/80 border rounded-xl px-4 py-3 active:scale-[0.98] transition-transform text-left"
                                    :class="transferErrors.dest ? 'border-red-500/60' : selectedDestWallet ? 'border-purple-500/40' : 'border-white/10 border-dashed'">
                                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center shrink-0">
                                        <template v-if="selectedDestWallet">
                                            <img v-if="selectedDestWallet.icon?.includes('.')" :src="'/storage/' + selectedDestWallet.icon" class="w-full h-full object-cover">
                                            <span v-else class="text-xl">{{ selectedDestWallet.icon }}</span>
                                        </template>
                                        <svg v-else class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6"/></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-2xs font-black text-purple-400 uppercase tracking-widest">Ke</p>
                                        <p class="text-sm font-bold truncate" :class="selectedDestWallet ? 'text-white' : 'text-gray-600'">
                                            {{ selectedDestWallet?.name || 'Pilih dompet tujuan...' }}
                                        </p>
                                        <p v-if="selectedDestWallet" class="text-2xs text-gray-500 mt-0.5">
                                            Rp {{ parseInt(selectedDestWallet.balance).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>

                            <!-- Catatan -->
                            <div class="flex items-center gap-3 bg-gray-800 border border-white/8 rounded-xl px-4 py-3">
                                <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <input v-model="form.notes" type="text" placeholder="Catatan (opsional)"
                                    class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-white placeholder-gray-700 outline-none" />
                            </div>

                        </div>

                        <!-- Nominal display + action bar (shrink-0) -->
                        <div class="shrink-0 px-4 pb-2 pt-1 border-t border-white/5">
                            <!-- Nominal besar -->
                            <div class="text-center py-2">
                                <p class="text-2xs font-black text-gray-600 uppercase tracking-widest mb-0.5">Nominal</p>
                                <p class="text-3xl font-black tracking-tight"
                                    :class="transferErrors.amount ? 'text-red-400' : 'text-white'">
                                    <span class="text-lg text-gray-500 mr-1">Rp</span>{{ parseInt(rawAmount || 0).toLocaleString('id-ID') }}
                                </p>
                            </div>

                            <!-- Action row: tanggal + keypad toggle + simpan+lagi + simpan -->
                            <div class="flex gap-2">
                                <!-- Tanggal -->
                                <button type="button" @click="dateModalTarget = 'transaction'; showDateModal = true"
                                    class="flex-1 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center gap-2 text-2xs font-bold text-gray-400 active:scale-95 transition-transform">
                                    <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ new Date(form.date).toDateString() === new Date().toDateString() ? 'Hari Ini' : new Date(form.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'}) }}
                                </button>
                                <!-- Toggle keypad -->
                                <button type="button" @click="showKeypad = !showKeypad"
                                    class="w-12 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center shrink-0 active:scale-95 transition-all"
                                    :class="showKeypad ? 'text-purple-400 border-purple-500/30' : 'text-gray-500'">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </button>
                                <!-- Simpan & tambah lagi -->
                                <button type="button" @click="submitTransfer(false)"
                                    class="w-12 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center shrink-0 active:scale-95 transition-transform"
                                    title="Simpan & tambah lagi">
                                    <div class="w-6 h-6 rounded-full border-2 border-blue-400 flex items-center justify-center text-blue-400 text-base font-black">+</div>
                                </button>
                                <!-- Simpan & tutup -->
                                <button type="button" @click="submitTransfer(true)" :disabled="form.processing"
                                    class="flex-1 h-12 bg-blue-600 hover:bg-blue-500 rounded-xl flex items-center justify-center gap-1.5 text-white font-black text-2xs uppercase tracking-widest active:scale-95 transition-all shadow-lg shadow-blue-600/30 disabled:opacity-50">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Transfer
                                </button>
                            </div>
                        </div>

                        <!-- Keypad -->
                        <div v-show="showKeypad" class="shrink-0 px-4 pb-2 pt-1">
                            <AmountKeypad @key="handleKeypad" />
                        </div>
                    </div>

                    <!-- Debt / Receivable: input nama -->
                    <div v-else-if="['Debt', 'Receivable'].includes(mainTab)" class="flex-1 overflow-y-auto no-scrollbar px-4 pt-3 pb-4 space-y-4">
                        <div class="text-center mb-2">
                            <p class="text-2xs font-black text-purple-500 uppercase tracking-[0.25em] mb-1">Langkah 2 dari 3</p>
                            <h2 class="text-lg font-black text-white">Pihak Terkait</h2>
                        </div>
                        <input type="text" v-model="form.subject" placeholder="Nama..."
                            @input="form.subject = $event.target.value.toUpperCase()"
                            class="w-full bg-gray-800 border border-white/10 focus:border-purple-500 rounded-xl px-4 py-3 text-center text-lg font-bold text-white focus:ring-0 placeholder-gray-700 outline-none uppercase" />
                        <div v-if="activeSubjects?.length" class="flex flex-wrap gap-2 justify-center">
                            <button v-for="sub in activeSubjects" :key="sub" type="button" @click="form.subject = sub"
                                class="px-3 py-1.5 rounded-full text-2xs font-bold border transition-all active:scale-95 uppercase"
                                :class="form.subject === sub ? 'bg-purple-600/20 text-purple-400 border-purple-500/50' : 'bg-gray-800 text-gray-400 border-white/5'">
                                {{ sub }}
                            </button>
                        </div>
                        <!-- Jatuh tempo hanya untuk Dapat/Ngasih -->
                        <div v-if="(activeType === 'Debt' && debtSubTab === 'income') || (activeType === 'Receivable' && debtSubTab === 'expense')"
                            class="p-4 bg-gray-800/60 rounded-xl border border-white/5 space-y-3">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="has_due" :checked="form.due_date_type !== null"
                                    @change="form.due_date_type = $event.target.checked ? 'fixed' : null"
                                    class="rounded bg-gray-700 border-white/10 text-purple-600 focus:ring-purple-600" />
                                <label for="has_due" class="text-2xs font-bold text-purple-400 uppercase tracking-widest cursor-pointer">Ada Jatuh Tempo?</label>
                            </div>
                            <template v-if="form.due_date_type !== null">
                                <div class="flex gap-2">
                                    <button type="button" @click="form.due_date_type = 'fixed'"
                                        class="flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all"
                                        :class="form.due_date_type === 'fixed' ? 'bg-purple-600 text-white' : 'bg-gray-900 text-gray-500'">Tgl Pasti</button>
                                    <button type="button" @click="form.due_date_type = 'monthly'"
                                        class="flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all"
                                        :class="form.due_date_type === 'monthly' ? 'bg-purple-600 text-white' : 'bg-gray-900 text-gray-500'">Tiap Bulan</button>
                                    <button type="button" @click="form.due_date_type = 'daily'"
                                        class="flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all"
                                        :class="form.due_date_type === 'daily' ? 'bg-purple-600 text-white' : 'bg-gray-900 text-gray-500'">Per Hari</button>
                                </div>
                                <button v-if="form.due_date_type === 'fixed'" type="button"
                                    @click="dateModalTarget = 'due_date'; showDateModal = true"
                                    class="w-full py-2 bg-gray-900 border border-white/10 rounded-lg text-sm font-bold text-white flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ form.due_date ? new Date(form.due_date).toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'}) : 'Pilih Tanggal' }}
                                </button>
                                <input v-if="form.due_date_type === 'monthly'" type="number" min="1" max="31" v-model="form.due_date_interval" placeholder="Tgl (1-31)"
                                    class="w-full bg-gray-900 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 focus:border-purple-500 text-center" />
                                <input v-if="form.due_date_type === 'daily'" type="number" min="1" v-model="form.due_date_interval" placeholder="Siklus (hari)"
                                    class="w-full bg-gray-900 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 focus:border-purple-500 text-center" />
                            </template>
                        </div>
                        <button type="button" @click="confirmCategory"
                            class="w-full py-4 rounded-2xl bg-purple-600 text-white text-sm font-black uppercase tracking-widest transition-all active:scale-95">
                            Lanjut → Isi Nominal
                        </button>
                    </div>

                    <!-- Income / Expense: grid kategori -->
                    <div v-else class="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <div class="shrink-0 px-4 pt-3 pb-2 text-center">
                            <p class="text-2xs font-black text-purple-500 uppercase tracking-[0.25em] mb-0.5">Langkah 2 dari 3</p>
                            <h2 class="text-base font-black text-white">Pilih Kategori</h2>
                        </div>
                        <div class="flex-1 overflow-y-auto no-scrollbar px-4 pb-4">
                            <div v-if="activeCategories.length" class="grid grid-cols-3 gap-2.5">
                                <button v-for="cat in activeCategories" :key="cat.id" type="button"
                                    @click="selectCategory(cat); confirmCategory()"
                                    class="relative flex flex-col items-center justify-center p-3 rounded-2xl border transition-all active:scale-95 min-h-[90px]"
                                    :class="form.category_id === cat.id ? 'bg-purple-500/15 border-purple-500 shadow-lg shadow-purple-500/10' : 'bg-gray-800 border-white/8 hover:border-purple-500/40'">
                                    <img v-if="cat.icon?.includes('.')" :src="'/storage/' + cat.icon" class="w-8 h-8 object-cover rounded-xl mb-2">
                                    <span v-else class="text-2xl mb-1.5">{{ cat.icon }}</span>
                                    <span class="text-2xs font-bold text-center leading-tight w-full line-clamp-2"
                                        :class="form.category_id === cat.id ? 'text-white' : 'text-gray-400'">{{ cat.category_name }}</span>
                                    <span v-if="form.category_id === cat.id" class="absolute top-2 right-2 w-4 h-4 rounded-full bg-purple-500 text-white text-[9px] flex items-center justify-center">✓</span>
                                </button>
                                <Link v-if="['Expense','Income'].includes(mainTab)" :href="route('categories.create', { type: mainTab })"
                                    class="flex flex-col items-center justify-center gap-2 p-3 rounded-2xl border border-dashed border-purple-500/40 bg-purple-500/5 text-purple-400 active:scale-95 min-h-[90px]">
                                    <span class="w-8 h-8 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-lg">+</span>
                                    <span class="text-2xs font-black text-center leading-tight">Tambah</span>
                                </Link>
                            </div>
                            <div v-else class="rounded-2xl border border-dashed border-white/10 bg-gray-800/50 p-8 text-center">
                                <p class="text-sm font-bold text-gray-400 mb-2">Belum ada kategori</p>
                                <Link :href="route('categories.create', { type: mainTab })" class="text-2xs font-black text-purple-400 uppercase tracking-wider">+ Tambah kategori</Link>
                            </div>
                        </div>
                    </div>

                </div>
            </Transition>

            <!-- ── STEP 3: PANEL NOMINAL ──────────────────────────── -->
            <!-- Mengisi sisa ruang di bawah step 2, keypad tidak overlap navbar
                 karena container sendiri sudah fixed inset-0 tanpa tambahan padding -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-8"
                enter-to-class="opacity-100 translate-y-0"
            >
                <form v-if="formStep >= 3 && mainTab !== 'Transfer'" @submit.prevent="submitAndClose"
                    class="flex-1 flex flex-col min-h-0 overflow-hidden border-t border-white/5"
                >
                    <!-- Error banner -->
                    <div v-if="Object.keys(form.errors).length"
                        class="shrink-0 mx-4 mt-3 p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <p v-for="(err, key) in form.errors" :key="key"
                            class="text-red-400 text-2xs font-bold">{{ err }}</p>
                    </div>

                    <!-- Scrollable area: catatan + dompet -->
                    <div class="flex-1 overflow-y-auto no-scrollbar px-4 pt-3 pb-2 space-y-3">
                        <!-- Catatan -->
                        <div class="flex items-center gap-3 bg-gray-800 border border-white/8 rounded-xl px-4 py-3">
                            <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <input v-model="form.notes" type="text" placeholder="Catatan (opsional)"
                                class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-white placeholder-gray-700 outline-none" />
                        </div>

                        <!-- Pilih Dompet -->
                        <button type="button" @click="openWalletModal(isMoneyIn ? 'dest' : 'source')"
                            class="w-full flex items-center gap-3 bg-gray-800 border border-white/8 rounded-xl px-4 py-3 active:scale-95 transition-transform text-left">
                            <div class="w-10 h-10 rounded-xl overflow-hidden bg-gray-900 border border-white/10 flex items-center justify-center shrink-0">
                                <template v-if="isMoneyIn ? selectedDestWallet : selectedSourceWallet">
                                    <img v-if="(isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon.includes('.')"
                                        :src="'/storage/' + (isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon"
                                        class="w-full h-full object-cover">
                                    <span v-else class="text-xl">{{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon }}</span>
                                </template>
                                <svg v-else class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-2xs font-black text-gray-500 uppercase tracking-widest">Dompet</p>
                                <p class="text-sm font-bold text-white truncate">{{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.name || 'Pilih dompet...' }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <!-- Nominal display + action bar — shrink-0 agar tidak kepush keypad -->
                    <div class="shrink-0 px-4 pb-2 pt-1">
                        <!-- Nominal besar -->
                        <div class="text-center py-2">
                            <p class="text-2xs font-black text-gray-600 uppercase tracking-widest mb-0.5">Nominal</p>
                            <p class="text-3xl font-black text-white tracking-tight">
                                <span class="text-lg text-gray-500 mr-1">Rp</span>{{ parseInt(rawAmount || 0).toLocaleString('id-ID') }}
                            </p>
                        </div>

                        <!-- Action row: tanggal + toggle keypad + submit -->
                        <div class="flex gap-2">
                            <!-- Tanggal -->
                            <button type="button" @click="dateModalTarget = 'transaction'; showDateModal = true"
                                class="flex-1 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center gap-2 text-2xs font-bold text-gray-400 active:scale-95 transition-transform">
                                <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ new Date(form.date).toDateString() === new Date().toDateString() ? 'Hari Ini' : new Date(form.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'}) }}
                            </button>
                            <!-- Toggle keypad -->
                            <button type="button" @click="showKeypad = !showKeypad"
                                class="w-12 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center shrink-0 active:scale-95 transition-all"
                                :class="showKeypad ? 'text-purple-400 border-purple-500/30' : 'text-gray-500'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </button>
                            <!-- Simpan & tambah lagi -->
                            <button type="button" @click="submitAndStay"
                                class="w-12 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center shrink-0 active:scale-95 transition-transform"
                                title="Simpan & tambah lagi">
                                <div class="w-6 h-6 rounded-full border-2 border-green-400 flex items-center justify-center text-green-400 text-base font-black">+</div>
                            </button>
                            <!-- Simpan & tutup -->
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 h-12 bg-green-600 hover:bg-green-500 rounded-xl flex items-center justify-center gap-2 text-white font-black text-sm uppercase tracking-wider active:scale-95 transition-all shadow-lg shadow-green-600/30 disabled:opacity-50">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Simpan
                            </button>
                        </div>
                    </div>

                    <!-- Keypad — shrink-0 di bawah, tidak overlap karena container sudah fixed -->
                    <div v-show="showKeypad" class="shrink-0 px-4 pb-2 pt-1">
                        <AmountKeypad @key="handleKeypad" />
                    </div>
                </form>
            </Transition>

            <!-- ── MODAL: TANGGAL ─────────────────────────────────── -->
            <Teleport to="body">
                <div v-if="showDateModal"
                    class="fixed inset-0 z-[200] flex flex-col justify-end bg-black/70 backdrop-blur-sm"
                    @click.self="showDateModal = false">
                    <div class="w-full max-w-md mx-auto bg-gray-900 border border-white/10 rounded-t-2xl p-5 pb-8 animate-slide-up">
                        <div class="w-10 h-1 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer" @click="showDateModal = false"></div>
                        <h3 class="text-sm font-black text-gray-400 mb-4 text-center tracking-widest uppercase">Pilih Tanggal</h3>
                        <div class="flex gap-2 mb-3">
                            <button @click="setDate(0)" class="flex-1 p-3 bg-gray-800 border border-white/10 rounded-xl font-bold text-white active:scale-95 text-sm">Hari Ini</button>
                            <button @click="setDate(-1)" class="flex-1 p-3 bg-gray-800 border border-white/10 rounded-xl font-bold text-white active:scale-95 text-sm">Kemarin</button>
                        </div>
                        <div class="bg-gray-800 border border-white/10 rounded-xl p-4">
                            <div class="flex justify-between items-center mb-4">
                                <button type="button" @click="prevCalendarMonth" class="p-2 text-gray-400 hover:text-white rounded-lg active:scale-95">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span class="text-sm font-bold text-white">{{ monthNames[currentMonth] }} {{ currentYear }}</span>
                                <button type="button" @click="nextCalendarMonth" class="p-2 text-gray-400 hover:text-white rounded-lg active:scale-95">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 mb-2">
                                <span v-for="d in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="d" class="text-center text-2xs font-black text-gray-500">{{ d }}</span>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                <div v-for="n in firstDayOfMonth" :key="'e'+n" class="h-8"></div>
                                <button v-for="day in daysInMonth" :key="day" type="button" @click="selectSpecificDate(day)"
                                    class="h-8 w-full flex items-center justify-center text-sm font-bold rounded-lg transition-all active:scale-90"
                                    :class="(dateModalTarget === 'due_date' ? form.due_date : form.date) === [currentYear, String(currentMonth+1).padStart(2,'0'), String(day).padStart(2,'0')].join('-')
                                        ? 'bg-purple-600 text-white'
                                        : 'text-gray-300 hover:bg-gray-700'">{{ day }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── MODAL: PILIH DOMPET ────────────────────────── -->
                <div v-if="showWalletModal"
                    class="fixed inset-0 z-[200] flex flex-col justify-end bg-black/70 backdrop-blur-sm"
                    @click.self="showWalletModal = false">
                    <div class="w-full max-w-md mx-auto bg-gray-900 border border-white/10 rounded-t-2xl p-5 pb-8 animate-slide-up">
                        <div class="w-10 h-1 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer" @click="showWalletModal = false"></div>
                        <h3 class="text-sm font-black text-gray-400 mb-4 text-center tracking-widest uppercase">Pilih Dompet</h3>
                        <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[55vh]">
                            <div v-for="w in availableWallets" :key="w.id" @click="selectWallet(w)"
                                class="bg-gray-800 border border-white/8 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all hover:border-purple-500/30">
                                <div class="w-12 h-12 bg-gray-900 border border-white/10 rounded-xl flex items-center justify-center text-xl overflow-hidden shrink-0">
                                    <img v-if="w.icon?.includes('.')" :src="'/storage/' + w.icon" class="w-full h-full object-cover">
                                    <span v-else>{{ w.icon }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-white truncate">{{ w.name }}</p>
                                    <p class="text-2xs text-purple-400 font-bold mt-0.5">Rp {{ new Intl.NumberFormat('id-ID').format(w.balance) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
@keyframes slide-up { from { transform: translateY(100%); } to { transform: translateY(0); } }
.animate-slide-up { animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes swap-bounce { 0% { transform: scale(1) rotate(0deg); } 40% { transform: scale(0.85) rotate(120deg); } 100% { transform: scale(1) rotate(180deg); } }
</style>
