<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import AmountKeypad from '@/Components/AmountKeypad.vue'
import BaseModal from '@/Components/BaseModal.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { useTransactionForm } from '@/Composables/useTransactionForm.js'
import { useWizardNavigation } from '@/Composables/useWizardNavigation.js'
import AppIcon from '@/Components/AppIcon.vue'
import { getCategoryIconColor } from '@/Composables/useIcon.js'

const { t } = useI18n()
const { isDesktopLayout } = useLayoutPreference()

const props = defineProps({
    transaction: Object,
    wallets: Array,
    categories: Array,
    systemWallets: Array,
    debtSubjects: Array,
    receivableSubjects: Array,
})

const showDeleteConfirm = ref(false)

// ─── Tentukan initialType dari transaksi yang sedang di-edit ─────
const _initCat = props.categories.find(c => c.id === props.transaction.category_id)
const _initType = props.transaction.transaction_type
    ? (props.transaction.transaction_type.charAt(0).toUpperCase() + props.transaction.transaction_type.slice(1))
    : (_initCat?.type?.name ?? 'Expense')
const _initDebtSubTab = props.transaction.debt_sub_type
    ? props.transaction.debt_sub_type
    : ((_initCat?.system_key === 'DEBT_PAYMENT' || _initCat?.system_key === 'RECEIVABLE') ? 'expense' : 'income')

const form = useForm({
    category_id: props.transaction.category_id,
    source_wallet_id: props.transaction.source_wallet_id,
    destination_wallet_id: props.transaction.destination_wallet_id,
    amount: Math.trunc(Number(props.transaction.amount) || 0),
    date: props.transaction.date,
    subject: props.transaction.subject || '-',
    notes: props.transaction.notes || '',
    transaction_type: _initType.toLowerCase(),
    debt_sub_type: ['Debt', 'Receivable'].includes(_initType) ? _initDebtSubTab : null,
    due_date: props.transaction.due_date,
    due_date_type: props.transaction.due_date_type,
    due_date_interval: props.transaction.due_date_interval,
})

// ─── Shared logic dari composable ────────────────────────────────
const tx = useTransactionForm(form, props, {
    isDesktopLayout,
    initialMainTab: _initType,
    initialType: _initType,
    initialDebtSubTab: _initDebtSubTab,
    initialAmount: String(Math.trunc(Number(props.transaction.amount) || 0)),
})

const {
    rawAmount, handleKeypad,
    loadWalletFrequency,
    mainTab, activeType, debtSubTab, setMainTab, setDebtSubTab,
    showWalletModal, showKeypad, showDateModal, dateModalTarget,
    monthNames, currentMonth, currentYear, daysInMonth, firstDayOfMonth,
    prevCalendarMonth, nextCalendarMonth, selectSpecificDate, setDate,
    selectedSourceWallet, selectedDestWallet, availableWallets,
    openWalletModal, selectWallet,
    selectedCategory, activeCategories, activeSubjects, isMoneyIn, selectCategory,
} = tx

// ─── Step flow (Edit mulai dari step 2, bisa kembali ke type selector) ──
const formStep = ref(2)

// Navigate back ke step tertentu — Edit tidak seagresif Create karena sudah ada data
const resetToStep = (step) => {
    formStep.value = step
    if (step <= 2) {
        // Reset category hanya jika kembali ke step 2, biarkan wallet tetap
        if (step === 2 && !['Transfer', 'Debt', 'Receivable'].includes(mainTab.value)) {
            form.category_id = null
        }
    }
}

const { goBack, pushStepState } = useWizardNavigation({
    formStep, resetToStep,
    onBackFromFirstStep: () => router.visit(route('dashboard')),
})

onMounted(() => { loadWalletFrequency() })

// TYPE_ITEMS — sama dengan Create untuk type selector chip
const TYPE_ITEMS = computed(() => [
    { tab: 'Expense',    label: t('types.expense'),    desc: t('types.expenseDesc'),    icon: 'arrow-up-from-line', color: 'from-red-500/20 to-red-900/10',     border: 'border-red-500/40',    text: 'text-red-400'    },
    { tab: 'Income',     label: t('types.income'),     desc: t('types.incomeDesc'),     icon: 'arrow-down-to-line', color: 'from-green-500/20 to-green-900/10',  border: 'border-green-500/40',  text: 'text-green-400'  },
    { tab: 'Transfer',   label: t('types.transfer'),   desc: t('types.transferDesc'),   icon: 'arrow-left-right',   color: 'from-blue-500/20 to-blue-900/10',    border: 'border-blue-500/40',   text: 'text-blue-400'   },
    { tab: 'Debt',       label: t('types.debt'),       desc: t('types.debtDesc'),       icon: 'circle-dollar-sign', color: 'from-amber-500/20 to-amber-900/10', border: 'border-amber-500/40', text: 'text-amber-400' },
    { tab: 'Receivable', label: t('types.receivable'), desc: t('types.receivableDesc'), icon: 'hand-coins',         color: 'from-fuchsia-500/20 to-fuchsia-900/10', border: 'border-fuchsia-500/40', text: 'text-fuchsia-400' },
])

const activeTypeItem = computed(() => TYPE_ITEMS.value.find(i => i.tab === mainTab.value))

const typeTheme = computed(() => {
    const type = mainTab.value
    const isExpense = type === 'Expense'
    const isIncome  = type === 'Income'
    const isTransfer = type === 'Transfer'
    const isDebt    = type === 'Debt'
    const isReceivable = type === 'Receivable'

    if (isIncome) return {
        stepLabel: 'text-green-500', btnActive: 'bg-green-600 text-white shadow',
        btnSolid: 'bg-green-600', accentText: 'text-green-400',
        accentBorder: 'border-green-500/40', focusBorder: 'focus:border-green-500',
        focusRing: 'focus:ring-green-600', bgActive: 'bg-green-600 text-white',
        activePill: 'bg-green-600/20 text-green-400 border-green-500/50',
        keypadToggle: 'text-green-400 border-green-500/30',
        walletHover: 'hover:border-green-500/30', dateSelected: 'bg-green-600 text-white',
        chipStep: 'bg-green-500/20 text-green-300 border-green-500/40',
        selected: 'bg-green-500/15 border-green-500 shadow-lg shadow-green-500/10',
        hover: 'hover:border-green-500/40', check: 'bg-green-500',
        add: 'border-green-500/40 bg-green-500/5 text-green-400',
        categoryLink: 'text-green-400',
    }
    if (isExpense) return {
        stepLabel: 'text-red-500', btnActive: 'bg-red-600 text-white shadow',
        btnSolid: 'bg-red-600', accentText: 'text-red-400',
        accentBorder: 'border-red-500/40', focusBorder: 'focus:border-red-500',
        focusRing: 'focus:ring-red-600', bgActive: 'bg-red-600 text-white',
        activePill: 'bg-red-600/20 text-red-400 border-red-500/50',
        keypadToggle: 'text-red-400 border-red-500/30',
        walletHover: 'hover:border-red-500/30', dateSelected: 'bg-red-600 text-white',
        chipStep: 'bg-red-500/20 text-red-300 border-red-500/40',
        selected: 'bg-red-500/15 border-red-500 shadow-lg shadow-red-500/10',
        hover: 'hover:border-red-500/40', check: 'bg-red-500',
        add: 'border-red-500/40 bg-red-500/5 text-red-400',
        categoryLink: 'text-red-400',
    }
    if (isTransfer) return {
        stepLabel: 'text-blue-500', btnActive: 'bg-blue-600 text-white shadow',
        btnSolid: 'bg-blue-600', accentText: 'text-blue-400',
        accentBorder: 'border-blue-500/40', focusBorder: 'focus:border-blue-500',
        focusRing: 'focus:ring-blue-600', bgActive: 'bg-blue-600 text-white',
        activePill: 'bg-blue-600/20 text-blue-400 border-blue-500/50',
        keypadToggle: 'text-blue-400 border-blue-500/30',
        walletHover: 'hover:border-blue-500/30', dateSelected: 'bg-blue-600 text-white',
        chipStep: 'bg-blue-500/20 text-blue-300 border-blue-500/40',
        selected: 'bg-blue-500/15 border-blue-500 shadow-lg shadow-blue-500/10',
        hover: 'hover:border-blue-500/40', check: 'bg-blue-500',
        add: 'border-blue-500/40 bg-blue-500/5 text-blue-400',
        categoryLink: 'text-blue-400',
    }
    if (isDebt) return {
        stepLabel: 'text-amber-500', btnActive: 'bg-amber-600 text-white shadow',
        btnSolid: 'bg-amber-600', accentText: 'text-amber-400',
        accentBorder: 'border-amber-500/40', focusBorder: 'focus:border-amber-500',
        focusRing: 'focus:ring-amber-600', bgActive: 'bg-amber-600 text-white',
        activePill: 'bg-amber-600/20 text-amber-400 border-amber-500/50',
        keypadToggle: 'text-amber-400 border-amber-500/30',
        walletHover: 'hover:border-amber-500/30', dateSelected: 'bg-amber-600 text-white',
        chipStep: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
        selected: 'bg-amber-500/15 border-amber-500 shadow-lg shadow-amber-500/10',
        hover: 'hover:border-amber-500/40', check: 'bg-amber-500',
        add: 'border-amber-500/40 bg-amber-500/5 text-amber-400',
        categoryLink: 'text-amber-400',
    }
    if (isReceivable) return {
        stepLabel: 'text-fuchsia-500', btnActive: 'bg-fuchsia-600 text-white shadow',
        btnSolid: 'bg-fuchsia-600', accentText: 'text-fuchsia-400',
        accentBorder: 'border-fuchsia-500/40', focusBorder: 'focus:border-fuchsia-500',
        focusRing: 'focus:ring-fuchsia-600', bgActive: 'bg-fuchsia-600 text-white',
        activePill: 'bg-fuchsia-600/20 text-fuchsia-400 border-fuchsia-500/50',
        keypadToggle: 'text-fuchsia-400 border-fuchsia-500/30',
        walletHover: 'hover:border-fuchsia-500/30', dateSelected: 'bg-fuchsia-600 text-white',
        chipStep: 'bg-fuchsia-500/20 text-fuchsia-300 border-fuchsia-500/40',
        selected: 'bg-fuchsia-500/15 border-fuchsia-500 shadow-lg shadow-fuchsia-500/10',
        hover: 'hover:border-fuchsia-500/40', check: 'bg-fuchsia-500',
        add: 'border-fuchsia-500/40 bg-fuchsia-500/5 text-fuchsia-400',
        categoryLink: 'text-fuchsia-400',
    }
    return {
        stepLabel: 'text-purple-500', btnActive: 'bg-purple-600 text-white shadow',
        btnSolid: 'bg-purple-600', accentText: 'text-purple-400',
        accentBorder: 'border-purple-500/40', focusBorder: 'focus:border-purple-500',
        focusRing: 'focus:ring-purple-600', bgActive: 'bg-purple-600 text-white',
        activePill: 'bg-purple-600/20 text-purple-400 border-purple-500/50',
        keypadToggle: 'text-purple-400 border-purple-500/30',
        walletHover: 'hover:border-purple-500/30', dateSelected: 'bg-purple-600 text-white',
        chipStep: 'bg-purple-500/20 text-purple-300 border-purple-500/40',
        selected: 'bg-purple-500/15 border-purple-500 shadow-lg shadow-purple-500/10',
        hover: 'hover:border-purple-500/40', check: 'bg-purple-500',
        add: 'border-purple-500/40 bg-purple-500/5 text-purple-400',
        categoryLink: 'text-purple-400',
    }
})

const selectType = (tab) => {
    setMainTab(tab)
    form.transaction_type = tab.toLowerCase()
    if (['Debt', 'Receivable'].includes(tab)) {
        form.debt_sub_type = debtSubTab.value
    } else {
        form.debt_sub_type = null
    }
    if (!['Transfer', 'Debt', 'Receivable'].includes(tab)) {
        form.category_id = null
    }
    formStep.value = 2
    pushStepState()
}

// Sync debt_sub_type saat user ganti sub-tab (Dapat Hutang ↔ Bayar Hutang)
watch(debtSubTab, (val) => {
    if (['Debt', 'Receivable'].includes(mainTab.value)) {
        form.debt_sub_type = val
    }
})

const goToNominal = () => {
    if (!form.category_id && !['Transfer', 'Debt', 'Receivable'].includes(mainTab.value)) return
    formStep.value = 3
    pushStepState()
}



// ─── Transfer: animasi swap ───────────────────────────────────────
const isSwapping = ref(false)
const transferAll = ref(false)
const transferErrors = ref({})

watch(transferAll, () => {
    if (transferAll.value && selectedSourceWallet.value) {
        rawAmount.value = String(Math.abs(parseInt(selectedSourceWallet.value.balance)))
    } else {
        rawAmount.value = '0'
    }
})

watch(selectedSourceWallet, () => {
    if (transferAll.value && selectedSourceWallet.value) {
        rawAmount.value = String(Math.abs(parseInt(selectedSourceWallet.value.balance)))
    }
})

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

const swapWallets = () => {
    if (isSwapping.value) return
    isSwapping.value = true
    const tmp = form.source_wallet_id
    form.source_wallet_id      = form.destination_wallet_id
    form.destination_wallet_id = tmp
    setTimeout(() => { isSwapping.value = false }, 400)
}

// ─── Lunasi / Settle debt ─────────────────────────────────────────
const lunasi = ref(false)

const debtBalance = computed(() => {
    if (!['Debt', 'Receivable'].includes(mainTab.value)) return 0
    const sub = activeSubjects.value.find(s => s.name === form.subject)
    return sub?.balance || 0
})

watch(lunasi, () => {
    if (lunasi.value && debtBalance.value > 0) {
        rawAmount.value = String(Math.abs(debtBalance.value))
    } else {
        rawAmount.value = '0'
    }
})

watch(() => form.subject, () => {
    if (lunasi.value && debtBalance.value > 0) {
        rawAmount.value = String(Math.abs(debtBalance.value))
    } else if (lunasi.value) {
        lunasi.value = false
        rawAmount.value = '0'
    }
})

const submitTransfer = () => {
    if (!validateTransfer()) return
    form.transaction_type = 'transfer'
    form.put(route('transactions.update', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }), {
        preserveScroll: true,
        onSuccess: () => handleBack(),
    })
}

// ─── Submit / Delete ──────────────────────────────────────────────
const submit = () => {
    if (!form.amount || form.amount <= 0) { form.setError('amount', t('transaction.validation.amountPositive')); return }
    if (new Date(form.date) > new Date()) { form.setError('date', t('transaction.validation.dateFuture')); return }
    if (['Debt', 'Receivable'].includes(activeType.value) && (!form.subject || form.subject === '-')) {
        form.setError('subject', t('transaction.validation.subjectRequired')); return
    }
    form.put(route('transactions.update', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }), {
        preserveScroll: true,
        onSuccess: () => handleBack(),
    })
}

const destroy = () => { showDeleteConfirm.value = true }
const confirmDelete = () => {
    showDeleteConfirm.value = false
    router.delete(route('transactions.destroy', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }))
}

const handleBack = () => router.visit(route('dashboard'))
</script>


<template>
    <AuthenticatedLayout :fullWidth="true" :hideNav="true">
        <Head :title="$t('transaction.titleEdit')" />
        <div class="fixed inset-0 z-60 flex flex-col bg-gray-900 text-white" :class="isDesktopLayout ? 'lg:relative lg:inset-auto lg:z-0' : ''" style="height: 100dvh;">

            <!-- HEADER -->
            <div class="shrink-0 border-b border-white/5">
                <!-- Baris 1: judul + tombol delete + close -->
                <div class="flex items-center justify-between px-4 pt-3 pb-2">
                    <span class="text-xs font-black text-gray-500 uppercase tracking-widest">Edit Transaksi</span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="destroy" class="w-8 h-8 flex items-center justify-center rounded-full bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 active:scale-90 transition-all" :aria-label="$t('transaction.deleteTitle')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        <button type="button" @click="goBack" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-800 border border-white/10 text-gray-400 hover:text-red-400 hover:border-red-500/30 active:scale-90 transition-all" :aria-label="$t('common.close')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <!-- Baris 2: breadcrumb progress steps -->
                <div class="flex items-center gap-2 px-4 pb-2.5 overflow-x-auto no-scrollbar">
                    <button type="button" @click="resetToStep(1)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest transition-all active:scale-95 shrink-0"
                        :class="formStep === 1 ? 'bg-purple-500/20 text-purple-300 border border-purple-500/40' : 'bg-gray-800 text-gray-300 border border-white/10 hover:border-purple-500/30'">
                        <span class="inline-flex items-center gap-1">
                            <AppIcon :icon="activeTypeItem?.icon" class="w-3.5 h-3.5" />
                            {{ activeTypeItem?.label }}
                        </span>
                    </button>
                    <svg class="w-3 h-3 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <!-- Step 2 chip — Transfer -->
                    <div v-if="mainTab === 'Transfer'"
                        class="px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest shrink-0"
                        :class="formStep === 2 ? 'bg-blue-500/20 text-blue-300 border border-blue-500/40' : 'bg-gray-800/50 text-gray-600 border border-white/5'">
                        2 · Transfer
                    </div>
                    <!-- Step 2 chip — non-Transfer -->
                    <button v-else type="button" @click="resetToStep(2)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest transition-all active:scale-95 shrink-0"
                        :class="formStep === 2 ? typeTheme.chipStep : 'bg-gray-800 text-gray-300 border border-white/10 ' + typeTheme.hover">
                        <span v-if="formStep > 2 && selectedCategory">
                            <AppIcon :icon="selectedCategory.icon" :class="['inline w-3.5 h-3.5', getCategoryIconColor(selectedCategory?.type?.name)]" />
                            {{ selectedCategory.category_name }}
                        </span>
                        <span v-else>2 · {{ $t('transaction.category') }}</span>
                    </button>
                    <template v-if="mainTab !== 'Transfer'">
                        <svg v-if="formStep >= 3" class="w-3 h-3 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        <div v-if="formStep >= 3" class="px-3 py-1.5 rounded-full text-2xs font-black uppercase tracking-widest shrink-0" :class="typeTheme.chipStep">3 · {{ $t('transaction.amount') }}</div>
                    </template>
                </div>
            </div>

            <!-- STEP 1: GANTI TIPE -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="formStep === 1" class="flex-1 flex flex-col items-center justify-center px-6 gap-4">
                    <div class="text-center mb-2">
                        <p class="text-2xs font-black text-purple-500 uppercase tracking-[0.25em] mb-1">{{ $t('transaction.stepOf', { step: 1, total: 3 }) }}</p>
                        <h1 class="text-xl font-black text-white">{{ $t('transaction.selectType') }}</h1>
                    </div>
                    <div class="w-full max-w-sm grid grid-cols-1 gap-3">
                        <button v-for="item in TYPE_ITEMS" :key="item.tab" type="button" @click="selectType(item.tab)"
                            class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl border transition-all active:scale-95 text-left"
                            :class="['bg-gradient-to-r ' + item.color, item.border]">
                            <AppIcon :icon="item.icon" class="w-8 h-8 shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black text-white">{{ item.label }}</p>
                                <p class="text-xs mt-0.5" :class="item.text">{{ item.desc }}</p>
                            </div>
                            <span v-if="item.tab === mainTab" class="w-5 h-5 rounded-full bg-purple-500 text-white text-[10px] flex items-center justify-center shrink-0">✓</span>
                            <svg v-else class="w-4 h-4 shrink-0" :class="item.text" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- STEP 2: KATEGORI / KONFIGURASI -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
                <div v-if="formStep === 2" class="flex-1 flex flex-col min-h-0 overflow-hidden">

                    <!-- Sub-tab Debt/Receivable -->
                    <div v-if="mainTab === 'Debt' || mainTab === 'Receivable'" class="shrink-0 px-4 pt-3 pb-2">
                        <div class="flex gap-2 p-1 bg-gray-800/80 rounded-xl border border-white/5">
                            <button type="button" @click="setDebtSubTab(mainTab === 'Debt' ? 'income' : 'expense')"
                                class="flex-1 py-2 rounded-lg text-2xs font-black uppercase tracking-widest transition-all"
                                :class="debtSubTab === (mainTab === 'Debt' ? 'income' : 'expense') ? typeTheme.btnActive : 'text-gray-500'">
                                {{ mainTab === 'Debt' ? $t('transaction.debt.receive') : $t('transaction.receivable.give') }}
                            </button>
                            <button type="button" @click="setDebtSubTab(mainTab === 'Debt' ? 'expense' : 'income')"
                                class="flex-1 py-2 rounded-lg text-2xs font-black uppercase tracking-widest transition-all"
                                :class="debtSubTab === (mainTab === 'Debt' ? 'expense' : 'income') ? typeTheme.btnActive : 'text-gray-500'">
                                {{ mainTab === 'Debt' ? $t('transaction.debt.pay') : $t('transaction.receivable.collect') }}
                            </button>
                        </div>
                    </div>

                    <!-- Transfer: form lengkap 2-step (wallet picker + nominal + catatan + tanggal) -->
                    <div v-if="mainTab === 'Transfer'" class="flex-1 flex flex-col min-h-0 overflow-hidden">

                        <!-- Scrollable form area -->
                        <div class="flex-1 overflow-y-auto no-scrollbar px-4 pt-3 pb-2 space-y-3">

                            <!-- Error banner -->
                            <div v-if="Object.keys(transferErrors).length || Object.keys(form.errors).length"
                                class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                                <p v-for="(err, key) in {...transferErrors, ...form.errors}" :key="key"
                                    class="text-red-400 text-2xs font-bold">{{ err }}</p>
                            </div>

                            <!-- Wallet Picker dengan swap -->
                            <div class="bg-gray-800/60 border border-white/8 rounded-2xl p-4">
                                <p class="text-2xs font-black text-gray-500 uppercase tracking-widest mb-3 text-center">{{ $t('transaction.transferFunds') }}</p>

                                <!-- Source wallet -->
                                <button type="button" @click="openWalletModal('source')"
                                    class="w-full flex items-center gap-3 bg-gray-900/80 border rounded-xl px-4 py-3 active:scale-[0.98] transition-transform text-left"
                                    :class="transferErrors.source ? 'border-red-500/60' : selectedSourceWallet ? 'border-blue-500/40' : 'border-white/10 border-dashed'">
                                    <AppIcon :icon="selectedSourceWallet?.icon" :fallback="selectedSourceWallet ? 'wallet' : 'circle-plus'" class="w-7 h-7 shrink-0" :class="selectedSourceWallet ? typeTheme.accentText : 'text-gray-600'" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-2xs font-black text-blue-400 uppercase tracking-widest">{{ $t('transaction.detail.from') }}</p>
                                        <p class="text-sm font-bold truncate" :class="selectedSourceWallet ? 'text-white' : 'text-gray-600'">
                                            {{ selectedSourceWallet?.name || $t('transaction.chooseSourceWallet') }}
                                        </p>
                                        <p v-if="selectedSourceWallet" class="text-2xs text-gray-500 mt-0.5">
                                            Rp {{ parseInt(selectedSourceWallet.balance).toLocaleString('id-ID') }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>

                                <!-- All-in checkbox -->
                                <label v-if="selectedSourceWallet"
                                    class="flex items-center gap-2 mt-2 px-1 cursor-pointer select-none">
                                    <input type="checkbox" v-model="transferAll"
                                        class="rounded bg-gray-700 border-white/10 text-blue-500 focus:ring-blue-500" />
                                    <span class="text-2xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('transaction.allBalance') }}</span>
                                </label>

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
                                    :class="transferErrors.dest ? 'border-red-500/60' : selectedDestWallet ? typeTheme.accentBorder : 'border-white/10 border-dashed'">
                                    <AppIcon :icon="selectedDestWallet?.icon" :fallback="selectedDestWallet ? 'wallet' : 'circle-plus'" class="w-7 h-7 shrink-0" :class="selectedDestWallet ? typeTheme.accentText : 'text-gray-600'" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-2xs font-black uppercase tracking-widest" :class="typeTheme.accentText">{{ $t('transaction.detail.to') }}</p>
                                        <p class="text-sm font-bold truncate" :class="selectedDestWallet ? 'text-white' : 'text-gray-600'">
                                            {{ selectedDestWallet?.name || $t('transaction.chooseDestWallet') }}
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
                                <input v-model="form.notes" type="text" :placeholder="$t('transaction.notePlaceholder')"
                                    class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-white placeholder-gray-700 outline-none" />
                            </div>

                        </div>

                        <!-- Nominal + action bar -->
                        <div class="shrink-0 px-4 pb-2 pt-1 border-t border-white/5">
                            <div class="text-center py-2">
                                <p class="text-2xs font-black text-gray-600 uppercase tracking-widest mb-0.5">{{ $t('transaction.amount') }}</p>
                                <p class="text-3xl font-black tracking-tight"
                                    :class="transferErrors.amount ? 'text-red-400' : 'text-white'">
                                    <span class="text-lg text-gray-500 mr-1">Rp</span>{{ parseInt(rawAmount || 0).toLocaleString('id-ID') }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <!-- Tanggal -->
                                <button type="button" @click="dateModalTarget = 'transaction'; showDateModal = true"
                                    class="flex-1 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center gap-2 text-2xs font-bold text-gray-400 active:scale-95 transition-transform">
                                    <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ new Date(form.date).toDateString() === new Date().toDateString() ? $t('transaction.today') : new Date(form.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'}) }}
                                </button>
                                <!-- Toggle keypad -->
                                <button type="button" @click="showKeypad = !showKeypad"
                                    class="w-12 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center shrink-0 active:scale-95 transition-all"
                                    :class="showKeypad ? typeTheme.keypadToggle : 'text-gray-500'">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </button>
                                <!-- Simpan -->
                                <button type="button" @click="submitTransfer" :disabled="form.processing"
                                    class="flex-1 h-12 bg-blue-600 hover:bg-blue-500 rounded-xl flex items-center justify-center gap-1.5 text-white font-black text-2xs uppercase tracking-widest active:scale-95 transition-all shadow-lg shadow-blue-600/30 disabled:opacity-50">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ $t('common.save') }}
                                </button>
                            </div>
                        </div>

                        <!-- Keypad -->
                        <div v-show="showKeypad" class="shrink-0 px-4 pb-2 pt-1">
                            <AmountKeypad @key="handleKeypad" />
                        </div>
                    </div>

                    <!-- Debt/Receivable: nama pihak -->
                    <div v-else-if="['Debt', 'Receivable'].includes(mainTab)" class="flex-1 overflow-y-auto no-scrollbar px-4 pt-3 pb-4 space-y-4">
                        <div class="text-center mb-2">
                        <p class="text-2xs font-black uppercase tracking-[0.25em] mb-1" :class="typeTheme.stepLabel">{{ $t('transaction.stepOf', { step: 2, total: 3 }) }}</p>
                        <h2 class="text-lg font-black text-white">{{ $t('transaction.relatedParty') }}</h2>
                    </div>
                    <input type="text" v-model="form.subject" :placeholder="$t('transaction.namePlaceholder')" @input="form.subject = $event.target.value.toUpperCase()" class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-center text-lg font-bold text-white focus:ring-0 placeholder-gray-700 outline-none uppercase" :class="typeTheme.focusBorder" />
                        <div v-if="activeSubjects?.length" class="flex flex-wrap gap-2 justify-center">
                            <button v-for="sub in activeSubjects" :key="sub.name" type="button" @click="form.subject = sub.name" class="px-3 py-1.5 rounded-full text-2xs font-bold border transition-all active:scale-95 uppercase" :class="form.subject === sub.name ? typeTheme.activePill : 'bg-gray-800 text-gray-400 border-white/5'">{{ sub.name }} <span v-if="sub.balance" class="text-2xs text-gray-500 ml-1">Rp{{ parseInt(sub.balance).toLocaleString('id-ID') }}</span></button>
                        </div>
                        <div v-if="(activeType === 'Debt' && debtSubTab === 'income') || (activeType === 'Receivable' && debtSubTab === 'expense')" class="p-4 bg-gray-800/60 rounded-xl border border-white/5 space-y-3">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="has_due" :checked="form.due_date_type !== null" @change="form.due_date_type = $event.target.checked ? 'fixed' : null" class="rounded bg-gray-700 border-white/10" :class="typeTheme.focusRing" />
                                <label for="has_due" class="text-2xs font-bold uppercase tracking-widest cursor-pointer" :class="typeTheme.accentText">{{ $t('transaction.hasDueDate') }}</label>
                            </div>
                            <template v-if="form.due_date_type !== null">
                                <div class="flex gap-2">
                                    <button type="button" @click="form.due_date_type = 'fixed'" class="flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all" :class="form.due_date_type === 'fixed' ? typeTheme.bgActive : 'bg-gray-900 text-gray-500'">{{ $t('transaction.fixedDate') }}</button>
                                    <button type="button" @click="form.due_date_type = 'monthly'" class="flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all" :class="form.due_date_type === 'monthly' ? typeTheme.bgActive : 'bg-gray-900 text-gray-500'">{{ $t('transaction.everyMonth') }}</button>
                                    <button type="button" @click="form.due_date_type = 'daily'" class="flex-1 py-2 text-2xs font-bold uppercase rounded-lg transition-all" :class="form.due_date_type === 'daily' ? typeTheme.bgActive : 'bg-gray-900 text-gray-500'">{{ $t('transaction.everyDay') }}</button>
                                </div>
                                <button v-if="form.due_date_type === 'fixed'" type="button" @click="dateModalTarget = 'due_date'; showDateModal = true" class="w-full py-2 bg-gray-900 border border-white/10 rounded-lg text-sm font-bold text-white flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ form.due_date ? new Date(form.due_date).toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'}) : $t('transaction.chooseDate') }}
                                </button>
                                <input v-if="form.due_date_type === 'monthly'" type="number" min="1" max="31" v-model="form.due_date_interval" :placeholder="$t('transaction.dayPlaceholder')" class="w-full bg-gray-900 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 text-center" :class="typeTheme.focusBorder" />
                                <input v-if="form.due_date_type === 'daily'" type="number" min="1" v-model="form.due_date_interval" :placeholder="$t('transaction.cyclePlaceholder')" class="w-full bg-gray-900 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:ring-0 text-center" :class="typeTheme.focusBorder" />
                            </template>
                        </div>
                        <button type="button" @click="goToNominal" class="w-full py-4 rounded-2xl text-white text-sm font-black uppercase tracking-widest transition-all active:scale-95" :class="typeTheme.btnSolid">{{ $t('transaction.nextNominal') }}</button>
                    </div>

                    <!-- Income/Expense: grid kategori -->
                    <div v-else class="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <div class="shrink-0 px-4 pt-3 pb-2 text-center">
                            <p class="text-2xs font-black uppercase tracking-[0.25em] mb-0.5" :class="typeTheme.stepLabel">{{ $t('transaction.stepOf', { step: 2, total: 3 }) }}</p>
                            <h2 class="text-base font-black text-white">{{ $t('transaction.chooseCategory') }}</h2>
                        </div>
                        <div class="flex-1 overflow-y-auto no-scrollbar px-4 pb-4">
                            <div v-if="activeCategories.length" class="grid grid-cols-3 gap-2.5">
                                <button v-for="cat in activeCategories" :key="cat.id" type="button" @click="selectCategory(cat); goToNominal()"
                                    class="relative flex flex-col items-center justify-center p-3 rounded-2xl border transition-all active:scale-95 min-h-[90px]"
                                    :class="form.category_id === cat.id ? typeTheme.selected : `bg-gray-800 border-white/8 ${typeTheme.hover}`">
                                    <AppIcon :icon="cat.icon" fallback="folder" :class="['w-8 h-8 mb-2', getCategoryIconColor(cat.type?.name)]" />
                                    <span class="text-2xs font-bold text-center leading-tight w-full line-clamp-2" :class="form.category_id === cat.id ? 'text-white' : 'text-gray-400'">{{ cat.category_name }}</span>
                                    <span v-if="form.category_id === cat.id" class="absolute top-2 right-2 w-4 h-4 rounded-full text-white text-[9px] flex items-center justify-center"
                                        :class="typeTheme.check">✓</span>
                                </button>
                                <Link v-if="['Expense','Income'].includes(mainTab)" :href="route('categories.create', { type: mainTab })"
                                    class="flex flex-col items-center justify-center gap-2 p-3 rounded-2xl border border-dashed active:scale-95 min-h-[90px]"
                                    :class="typeTheme.add">
                                    <span class="w-8 h-8 rounded-xl border border-white/10 flex items-center justify-center text-lg">+</span>
                                    <span class="text-2xs font-black text-center leading-tight">{{ $t('transaction.addCategory') }}</span>
                                </Link>
                            </div>
                            <div v-else class="rounded-2xl border border-dashed border-white/10 bg-gray-800/50 p-8 text-center">
                                <p class="text-sm font-bold text-gray-400 mb-2">{{ $t('transaction.noCategory') }}</p>
                                <Link :href="route('categories.create', { type: mainTab })" class="text-2xs font-black uppercase tracking-wider" :class="typeTheme.categoryLink">+ {{ $t('transaction.addCategory') }}</Link>
                            </div>
                        </div>
                    </div>

                </div>
            </Transition>

            <!-- STEP 3: NOMINAL PANEL -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 translate-y-8" enter-to-class="opacity-100 translate-y-0">
                <form v-if="formStep >= 3 && mainTab !== 'Transfer'" @submit.prevent="submit" class="flex-1 flex flex-col min-h-0 overflow-hidden border-t border-white/5">

                    <!-- Error banner -->
                    <div v-if="Object.keys(form.errors).length" class="shrink-0 mx-4 mt-3 p-3 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <p v-for="(err, key) in form.errors" :key="key" class="text-red-400 text-2xs font-bold">{{ err }}</p>
                    </div>

                    <!-- Scrollable: catatan + dompet -->
                    <div class="flex-1 overflow-y-auto no-scrollbar px-4 pt-3 pb-2 space-y-3">
                        <div class="flex items-center gap-3 bg-gray-800 border border-white/8 rounded-xl px-4 py-3">
                            <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <input v-model="form.notes" type="text" :placeholder="$t('transaction.notePlaceholder')" class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-white placeholder-gray-700 outline-none" />
                        </div>
                        <button type="button" @click="openWalletModal(isMoneyIn ? 'dest' : 'source')" class="w-full flex items-center gap-3 bg-gray-800 border border-white/8 rounded-xl px-4 py-3 active:scale-95 transition-transform text-left">
                            <AppIcon :icon="(isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.icon" :fallback="(isMoneyIn ? selectedDestWallet : selectedSourceWallet) ? 'wallet' : 'circle-plus'" class="w-7 h-7 shrink-0" :class="(isMoneyIn ? selectedDestWallet : selectedSourceWallet) ? typeTheme.accentText : 'text-gray-600'" />
                            <div class="flex-1 min-w-0">
                                <p class="text-2xs font-black text-gray-500 uppercase tracking-widest">{{ $t('transaction.wallet') }}</p>
                                <p class="text-sm font-bold text-white truncate">{{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.name || $t('transaction.chooseWallet') }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <!-- Lunasi checkbox untuk Debt/Receivable -->
                        <label v-if="['Debt', 'Receivable'].includes(mainTab) && debtBalance > 0"
                            class="flex items-center gap-2 px-1 py-2 cursor-pointer select-none">
                            <input type="checkbox" v-model="lunasi"
                                class="rounded bg-gray-700 border-white/10 text-amber-500 focus:ring-amber-500" />
                            <span class="text-2xs font-bold text-gray-400 uppercase tracking-widest">
                                {{ mainTab === 'Debt' ? $t('transaction.settle') : $t('transaction.collectAll') }}
                            </span>
                            <span class="text-2xs font-bold text-amber-400 ml-auto">Rp {{ parseInt(debtBalance).toLocaleString('id-ID') }}</span>
                        </label>

                        <!-- Info khusus Edit: waktu dibuat & diperbarui -->
                        <div class="flex gap-2">
                            <div class="flex-1 bg-gray-800/50 border border-white/5 rounded-xl px-3 py-2.5 flex items-center gap-2.5">
                                <svg class="w-3.5 h-3.5 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black text-gray-600 uppercase tracking-widest leading-none mb-0.5">{{ $t('transaction.created') }}</p>
                                    <p class="text-2xs font-bold text-gray-400 truncate">
                                        {{ transaction.created_at ? new Date(transaction.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex-1 bg-gray-800/50 border border-white/5 rounded-xl px-3 py-2.5 flex items-center gap-2.5">
                                <svg class="w-3.5 h-3.5 text-gray-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black text-gray-600 uppercase tracking-widest leading-none mb-0.5">{{ $t('transaction.updated') }}</p>
                                    <p class="text-2xs font-bold text-gray-400 truncate">
                                        {{ transaction.updated_at ? new Date(transaction.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nominal + action row -->
                    <div class="shrink-0 px-4 pb-2 pt-1">
                        <div class="text-center py-2">
                            <p class="text-2xs font-black text-gray-600 uppercase tracking-widest mb-0.5">{{ $t('transaction.amount') }}</p>
                            <p class="text-3xl font-black text-white tracking-tight">
                                <span class="text-lg text-gray-500 mr-1">Rp</span>{{ parseInt(rawAmount || 0).toLocaleString('id-ID') }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="dateModalTarget = 'transaction'; showDateModal = true" class="flex-1 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center gap-2 text-2xs font-bold text-gray-400 active:scale-95 transition-transform">
                                <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ new Date(form.date).toDateString() === new Date().toDateString() ? $t('transaction.today') : new Date(form.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'}) }}
                            </button>
                            <button type="button" @click="showKeypad = !showKeypad" class="w-12 h-12 bg-gray-800 border border-white/8 rounded-xl flex items-center justify-center shrink-0 active:scale-95 transition-all" :class="showKeypad ? typeTheme.keypadToggle : 'text-gray-500'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </button>
                            <button type="submit" :disabled="form.processing" class="flex-1 h-12 bg-green-600 hover:bg-green-500 rounded-xl flex items-center justify-center gap-2 text-white font-black text-sm uppercase tracking-wider active:scale-95 transition-all shadow-lg shadow-green-600/30 disabled:opacity-50">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </div>

                    <!-- Keypad -->
                    <div v-show="showKeypad" class="shrink-0 px-4 pb-2 pt-1">
                        <AmountKeypad @key="handleKeypad" />
                    </div>
                </form>
            </Transition>

        </div>

        <!-- MODAL: TANGGAL -->
        <Teleport to="body">
            <div v-if="showDateModal" class="fixed inset-0 z-[200] flex flex-col justify-end bg-black/70 backdrop-blur-sm" @click.self="showDateModal = false">
                <div class="w-full max-w-md mx-auto bg-gray-900 border border-white/10 rounded-t-2xl p-5 pb-8 animate-slide-up">
                    <div class="w-10 h-1 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer" @click="showDateModal = false"></div>
                    <h3 class="text-sm font-black text-gray-400 mb-4 text-center tracking-widest uppercase">{{ $t('transaction.chooseDate') }}</h3>
                    <div class="flex gap-2 mb-3">
                        <button @click="setDate(0)" class="flex-1 p-3 bg-gray-800 border border-white/10 rounded-xl font-bold text-white active:scale-95 text-sm">{{ $t('transaction.today') }}</button>
                        <button @click="setDate(-1)" class="flex-1 p-3 bg-gray-800 border border-white/10 rounded-xl font-bold text-white active:scale-95 text-sm">{{ $t('transaction.yesterday') }}</button>
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
                                :class="(dateModalTarget === 'due_date' ? form.due_date : form.date) === [currentYear, String(currentMonth+1).padStart(2,'0'), String(day).padStart(2,'0')].join('-') ? typeTheme.dateSelected : 'text-gray-300 hover:bg-gray-700'">{{ day }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: DOMPET -->
            <div v-if="showWalletModal" class="fixed inset-0 z-[200] flex flex-col justify-end bg-black/70 backdrop-blur-sm" @click.self="showWalletModal = false">
                <div class="w-full max-w-md mx-auto bg-gray-900 border border-white/10 rounded-t-2xl p-5 pb-8 animate-slide-up">
                    <div class="w-10 h-1 bg-white/20 rounded-full mx-auto mb-4 cursor-pointer" @click="showWalletModal = false"></div>
                    <h3 class="text-sm font-black text-gray-400 mb-4 text-center tracking-widest uppercase">{{ $t('transaction.chooseWallet') }}</h3>
                    <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[55vh]">
                        <div v-for="w in availableWallets" :key="w.id" @click="selectWallet(w)" class="bg-gray-800 border border-white/8 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all" :class="typeTheme.walletHover">
                            <AppIcon :icon="w.icon" fallback="wallet" class="w-8 h-8 shrink-0" :class="typeTheme.accentText" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white truncate">{{ w.name }}</p>
                                <p class="text-2xs font-bold mt-0.5" :class="typeTheme.accentText">Rp {{ new Intl.NumberFormat('id-ID').format(w.balance) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL: HAPUS -->
        <BaseModal :show="showDeleteConfirm" max-width="sm" @close="showDeleteConfirm = false">
            <div class="text-center px-1">
                <div class="w-14 h-14 rounded-full bg-red-500/15 text-red-400 mx-auto flex items-center justify-center mb-4 border border-red-500/20">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-base font-black text-white mb-2 tracking-tight">{{ $t('transaction.deleteTitle') }}</h3>
                <p class="text-sm text-red-200/80 leading-relaxed mb-6">{{ $t('transaction.deleteWarn') }}</p>
            </div>
            <template #footer>
                <button type="button" @click="showDeleteConfirm = false" class="flex-1 py-3 rounded-xl bg-gray-800 border border-white/10 text-gray-300 text-2xs font-bold uppercase tracking-widest hover:border-white/20 transition-all">{{ $t('btn.no') }}</button>
                <button type="button" @click="confirmDelete" class="flex-1 py-3 rounded-xl bg-red-600 text-white text-2xs font-black uppercase tracking-widest hover:bg-red-500 transition-all active:scale-[0.98]">{{ $t('btn.yes') }}</button>
            </template>
        </BaseModal>

    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
@keyframes slide-up { from { transform: translateY(100%); } to { transform: translateY(0); } }
.animate-slide-up { animation: slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
