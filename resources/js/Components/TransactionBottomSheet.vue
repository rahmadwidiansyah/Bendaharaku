<script setup>
/**
 * TransactionBottomSheet.vue
 *
 * Modern transaction creation flow menggunakan bottom sheet dengan 2-step pattern:
 *   Step 1: Type selection (minimal sheet dengan 5 tombol)
 *   Step 2: Type collapsed to summary + dynamic form
 *
 * Props:
 *   modelValue     — Boolean untuk show/hide bottom sheet
 *   categories     — Array of categories untuk form
 *   wallets        — Array of wallets untuk form
 *   systemWallets  — Array of system wallets (untuk Transfer)
 *   debtSubjects   — Array of debt subjects
 *   receivableSubjects — Array of receivable subjects
 *
 * Emits:
 *   update:modelValue — Saat sheet dibuka/ditutup
 *   submit          — Saat transaksi berhasil disimpan
 *
 * Features:
 *   - Responsive bottom sheet (mobile vs tablet)
 *   - Smooth type selection with preview
 *   - Type summary collapsible
 *   - Dynamic form fields per transaction type
 *   - Auto-close after submit
 *   - Keyboard safe area handling
 *   - Accessible: ARIA labels, keyboard navigation
 *
 * Design System:
 *   - Uses Bendaharaku Design System tokens
 *   - Colors: purple-500 for brand, gray-800/900 for dark mode
 *   - Typography: text-2xs to text-2xl scale
 *   - Spacing: consistent gap/padding scale
 */

import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useTransactionForm } from '@/Composables/useTransactionForm'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import AmountKeypad from './AmountKeypad.vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    categories: Array,
    wallets: Array,
    systemWallets: Array,
    debtSubjects: Array,
    receivableSubjects: Array,
})

const emit = defineEmits(['update:modelValue', 'submit'])

const { isDesktopLayout } = useLayoutPreference()

// === State Management ===
const step = ref(1) // Step 1: Type selection, Step 2: Form
const selectedType = ref(null) // 'Income', 'Expense', 'Transfer', 'Debt', 'Receivable'
const showKeypad = ref(false)
const showDateModal = ref(false)
const dateModalTarget = ref(null)
const isSubmitting = ref(false)

// === Form Setup ===
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

// === Composable Data ===
const tx = useTransactionForm(form, props, { isDesktopLayout })
const {
    rawAmount, handleKeypad,
    selectedSourceWallet, selectedDestWallet, availableWallets,
    openWalletModal, selectWallet, showWalletModal,
    selectedCategory, activeCategories, activeSubjects, isMoneyIn,
    selectCategory, selectSpecificDate, setDate,
    monthNames, currentMonth, currentYear, daysInMonth, firstDayOfMonth,
    prevCalendarMonth, nextCalendarMonth,
    setType, debtSubTab, setDebtSubTab,
} = tx

// === Transaction Types Definition ===
const transactionTypes = [
    { id: 'Income', label: 'Pemasukan', icon: '📥', color: 'from-green-600 to-green-700' },
    { id: 'Expense', label: 'Pengeluaran', icon: '📤', color: 'from-red-600 to-red-700' },
    { id: 'Transfer', label: 'Transfer', icon: '🔄', color: 'from-blue-600 to-blue-700' },
    { id: 'Debt', label: 'Hutang', icon: '📊', color: 'from-purple-600 to-purple-700' },
    { id: 'Receivable', label: 'Piutang', icon: '💰', color: 'from-yellow-600 to-yellow-700' },
]

// === Computed Properties ===
const selectedTypeData = computed(() => {
    return transactionTypes.find(t => t.id === selectedType.value)
})

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

const sheetHeight = computed(() => {
    if (step.value === 1) return '40vh' // Type selection: minimal
    return 'calc(100vh - 4rem)' // Form: nearly full screen
})

// === Methods ===
const selectTransactionType = (typeId) => {
    selectedType.value = typeId
    form.clearErrors()
    setType(typeId) // Setup wallet & category defaults via composable
    step.value = 2
}

const goBackToTypeSelection = () => {
    step.value = 1
}

const handleSubmit = async () => {
    if (!form.amount || form.amount <= 0) {
        form.setError('amount', 'Nominal harus lebih dari 0')
        return
    }

    if (new Date(form.date) > new Date()) {
        form.setError('date', 'Masa depan tidak diizinkan!')
        return
    }

    if (['Debt', 'Receivable'].includes(selectedType.value) && (!form.subject || form.subject === '-')) {
        form.setError('subject', 'Wajib diisi Bos!')
        return
    }

    isSubmitting.value = true

    form.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('submit')
            // Reset form
            form.reset()
            selectedType.value = null
            step.value = 1
            isOpen.value = false
            isSubmitting.value = false
        },
        onError: () => {
            isSubmitting.value = false
        },
    })
}

const closeSheet = () => {
    if (step.value === 2) {
        goBackToTypeSelection()
    } else {
        isOpen.value = false
        form.reset()
        selectedType.value = null
    }
}
</script>

<template>
    <!-- Backdrop -->
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
                @click="closeSheet"
            />
        </transition>
    </teleport>

    <!-- Bottom Sheet -->
    <teleport to="body">
        <transition
            enter-active-class="transition-transform duration-300 ease-out"
            leave-active-class="transition-transform duration-300 ease-in"
            enter-from-class="translate-y-full"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="isOpen"
                class="fixed bottom-0 left-0 right-0 z-[51] max-h-screen rounded-t-2xl border border-white/10 flex flex-col"
                :class="[
                    'bg-linear-to-br from-gray-800 to-gray-900',
                    'max-w-2xl mx-auto w-full',
                    'lg:fixed lg:inset-x-1/2 lg:-translate-x-1/2 lg:bottom-auto lg:top-1/2 lg:-translate-y-1/2 lg:rounded-2xl'
                ]"
                :style="{ height: isDesktopLayout ? '80vh' : sheetHeight }"
            >
                <!-- Handle Bar -->
                <div class="flex justify-center pt-3 pb-2 shrink-0">
                    <div class="w-12 h-1.5 bg-white/20 rounded-xl cursor-pointer hover:bg-white/30 transition-colors" @click="closeSheet" />
                </div>

                <!-- Step 1: Type Selection -->
                <div v-if="step === 1" class="flex-1 flex flex-col items-center justify-center gap-6 px-6 pb-6">
                    <div class="text-center">
                        <h2 class="text-lg font-black text-white mb-1">Pilih Jenis Transaksi</h2>
                        <p class="text-2xs font-bold text-gray-400 uppercase tracking-wider">Pilih salah satu tipe transaksi</p>
                    </div>

                    <!-- Type Selection Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 w-full max-w-sm">
                        <button
                            v-for="type in transactionTypes"
                            :key="type.id"
                            @click="selectTransactionType(type.id)"
                            class="flex flex-col items-center justify-center p-4 rounded-xl border-2 transition-all active:scale-95 text-center"
                            :class="[
                                'border-white/10 bg-gray-900 hover:border-purple-500/50 hover:bg-gray-800',
                                'focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900',
                                'cursor-pointer'
                            ]"
                            :aria-label="`Pilih tipe ${type.label}`"
                        >
                            <span class="text-3xl mb-2">{{ type.icon }}</span>
                            <span class="text-sm font-bold text-white">{{ type.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Form -->
                <div v-else-if="step === 2" class="flex-1 flex flex-col min-h-0 overflow-hidden">
                    <!-- Type Summary Header (Collapsible) -->
                    <div class="shrink-0 px-6 pt-4 pb-2 border-b border-white/10">
                        <button
                            @click="goBackToTypeSelection"
                            class="flex items-center gap-2 text-sm font-bold text-purple-400 hover:text-purple-300 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ selectedTypeData?.label }}
                        </button>
                    </div>

                    <!-- Scrollable Form Content -->
                    <div class="flex-1 overflow-y-auto px-6 py-4 no-scrollbar space-y-4">
                        <!-- Error Display -->
                        <div
                            v-if="Object.keys(form.errors).length > 0"
                            class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl"
                        >
                            <div
                                v-for="(err, key) in form.errors"
                                :key="key"
                                class="text-red-400 text-2xs font-bold flex items-center gap-1.5 mb-1 last:mb-0"
                            >
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ err }}
                            </div>
                        </div>

                        <!-- Category Selection (for Income/Expense) -->
                        <div v-if="['Income', 'Expense'].includes(selectedType)">
                            <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Kategori</label>
                            <div class="grid grid-cols-4 gap-2">
                                <button
                                    v-for="cat in activeCategories"
                                    :key="cat.id"
                                    @click="selectCategory(cat)"
                                    class="flex flex-col items-center justify-center p-2 rounded-xl border transition-all active:scale-95 min-h-[80px]"
                                    :class="[
                                        form.category_id === cat.id
                                            ? 'bg-purple-500/10 border-purple-500'
                                            : 'bg-gray-900 border-white/10 hover:border-purple-500/40'
                                    ]"
                                >
                                    <img
                                        v-if="cat.icon.includes('.')"
                                        :src="'/storage/' + cat.icon"
                                        class="w-6 h-6 object-cover rounded-lg mb-1"
                                    >
                                    <span v-else class="text-lg mb-1">{{ cat.icon }}</span>
                                    <span
                                        :class="[
                                            'text-2xs font-bold text-center leading-tight w-full px-0.5 line-clamp-2',
                                            form.category_id === cat.id ? 'text-white' : 'text-gray-500'
                                        ]"
                                    >
                                        {{ cat.category_name }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Wallet Selection untuk Income / Expense -->
                        <div v-if="['Expense', 'Income'].includes(selectedType)">
                            <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Dompet</label>
                            <button
                                @click="openWalletModal(isMoneyIn ? 'dest' : 'source')"
                                class="w-full p-3 bg-gray-900 border border-white/10 rounded-xl flex items-center gap-3 hover:border-purple-500/30 transition-colors text-left"
                            >
                                <div
                                    v-if="isMoneyIn ? selectedDestWallet : selectedSourceWallet"
                                    class="w-10 h-10 bg-gray-800 rounded-lg overflow-hidden flex items-center justify-center shrink-0"
                                >
                                    <img
                                        v-if="(isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon.includes('.')"
                                        :src="'/storage/' + (isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon"
                                        class="w-full h-full object-cover"
                                    >
                                    <span v-else class="text-lg">{{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon }}</span>
                                </div>
                                <svg v-else class="w-10 h-10 text-purple-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                                <div class="flex-1">
                                    <span class="text-sm font-bold text-white block">
                                        {{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.name || 'Pilih Dompet' }}
                                    </span>
                                    <span v-if="isMoneyIn ? selectedDestWallet : selectedSourceWallet" class="text-2xs text-purple-400">
                                        Rp {{ new Intl.NumberFormat('id-ID').format((isMoneyIn ? selectedDestWallet : selectedSourceWallet).balance || 0) }}
                                    </span>
                                </div>
                            </button>
                        </div>

                        <!-- Wallet Selection untuk Transfer (source + dest) -->
                        <div v-if="selectedType === 'Transfer'" class="flex items-center gap-3">
                            <!-- Source -->
                            <div class="flex-1">
                                <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Dari</label>
                                <button
                                    @click="openWalletModal('source')"
                                    class="w-full p-3 bg-gray-900 border border-white/10 rounded-xl flex items-center gap-2 hover:border-purple-500/30 transition-colors text-left"
                                >
                                    <div v-if="selectedSourceWallet" class="w-8 h-8 bg-gray-800 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                        <img v-if="selectedSourceWallet.icon.includes('.')" :src="'/storage/' + selectedSourceWallet.icon" class="w-full h-full object-cover">
                                        <span v-else class="text-base">{{ selectedSourceWallet.icon }}</span>
                                    </div>
                                    <svg v-else class="w-8 h-8 text-gray-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                    </svg>
                                    <span class="text-xs font-bold text-white truncate">{{ selectedSourceWallet?.name || 'Pilih' }}</span>
                                </button>
                            </div>
                            <!-- Arrow -->
                            <div class="shrink-0 mt-5">
                                <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                            <!-- Dest -->
                            <div class="flex-1">
                                <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Ke</label>
                                <button
                                    @click="openWalletModal('dest')"
                                    class="w-full p-3 bg-gray-900 border border-white/10 rounded-xl flex items-center gap-2 hover:border-purple-500/30 transition-colors text-left"
                                >
                                    <div v-if="selectedDestWallet" class="w-8 h-8 bg-gray-800 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                        <img v-if="selectedDestWallet.icon.includes('.')" :src="'/storage/' + selectedDestWallet.icon" class="w-full h-full object-cover">
                                        <span v-else class="text-base">{{ selectedDestWallet.icon }}</span>
                                    </div>
                                    <svg v-else class="w-8 h-8 text-gray-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                    </svg>
                                    <span class="text-xs font-bold text-white truncate">{{ selectedDestWallet?.name || 'Pilih' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Subject + Dompet untuk Debt / Receivable -->
                        <div v-if="['Debt', 'Receivable'].includes(selectedType)" class="space-y-4">
                            <!-- Sub-tab: arah transaksi -->
                            <div>
                                <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">
                                    {{ selectedType === 'Debt' ? 'Jenis Hutang' : 'Jenis Piutang' }}
                                </label>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="setDebtSubTab('income')"
                                        :class="['flex-1 py-2.5 rounded-xl text-2xs font-bold transition-all border', debtSubTab === 'income' ? 'bg-purple-500/10 text-purple-400 border-purple-500/50' : 'bg-gray-900 text-gray-400 border-white/10']"
                                    >
                                        {{ selectedType === 'Debt' ? 'Dapat Hutang' : 'Terima Piutang' }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="setDebtSubTab('expense')"
                                        :class="['flex-1 py-2.5 rounded-xl text-2xs font-bold transition-all border', debtSubTab === 'expense' ? 'bg-purple-500/10 text-purple-400 border-purple-500/50' : 'bg-gray-900 text-gray-400 border-white/10']"
                                    >
                                        {{ selectedType === 'Debt' ? 'Bayar Hutang' : 'Beri Piutang' }}
                                    </button>
                                </div>
                            </div>
                            <!-- Nama pihak terkait -->
                            <div>
                                <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Pihak / Nama Terkait</label>
                                <input
                                    type="text"
                                    v-model="form.subject"
                                    placeholder="Masukkan nama..."
                                    @input="form.subject = $event.target.value.toUpperCase()"
                                    class="w-full bg-gray-900 border border-white/10 focus:border-purple-500 rounded-xl px-4 py-3 text-center text-base font-bold text-white focus:ring-0 placeholder-gray-700 transition-colors outline-none uppercase"
                                >
                                <div v-if="activeSubjects && activeSubjects.length > 0" class="flex flex-wrap gap-2 justify-center mt-2">
                                    <button
                                        type="button"
                                        v-for="sub in activeSubjects"
                                        :key="sub"
                                        @click="form.subject = sub"
                                        class="px-3 py-1.5 rounded-full text-2xs font-bold border transition-all active:scale-95 uppercase"
                                        :class="form.subject === sub ? 'bg-purple-600/20 text-purple-400 border-purple-500/50' : 'bg-gray-800 text-gray-400 border-white/5 hover:bg-gray-700'"
                                    >
                                        {{ sub }}
                                    </button>
                                </div>
                            </div>
                            <!-- Dompet -->
                            <div>
                                <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Dompet</label>
                                <button
                                    @click="openWalletModal(isMoneyIn ? 'dest' : 'source')"
                                    class="w-full p-3 bg-gray-900 border border-white/10 rounded-xl flex items-center gap-3 hover:border-purple-500/30 transition-colors text-left"
                                >
                                    <div v-if="isMoneyIn ? selectedDestWallet : selectedSourceWallet" class="w-10 h-10 bg-gray-800 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                        <img v-if="(isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon.includes('.')" :src="'/storage/' + (isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon" class="w-full h-full object-cover">
                                        <span v-else class="text-lg">{{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet).icon }}</span>
                                    </div>
                                    <svg v-else class="w-10 h-10 text-purple-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                    </svg>
                                    <div class="flex-1">
                                        <span class="text-sm font-bold text-white block">{{ (isMoneyIn ? selectedDestWallet : selectedSourceWallet)?.name || 'Pilih Dompet' }}</span>
                                        <span v-if="isMoneyIn ? selectedDestWallet : selectedSourceWallet" class="text-2xs text-purple-400">
                                            Rp {{ new Intl.NumberFormat('id-ID').format((isMoneyIn ? selectedDestWallet : selectedSourceWallet).balance || 0) }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Amount Input -->
                        <div>
                            <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Nominal</label>
                            <div class="text-4xl font-bold text-white mb-2">
                                Rp {{ parseInt(rawAmount || 0).toLocaleString('id-ID') }}
                            </div>
                            <button
                                @click="showKeypad = !showKeypad"
                                class="w-full p-3 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition-colors text-sm"
                            >
                                {{ showKeypad ? 'Sembunyikan' : 'Tampilkan' }} Keypad
                            </button>
                            <div v-if="showKeypad" class="mt-3">
                                <AmountKeypad @key="handleKeypad" />
                            </div>
                        </div>

                        <!-- Date Selection -->
                        <div>
                            <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Tanggal</label>
                            <button
                                @click="showDateModal = true"
                                class="w-full p-3 bg-gray-900 border border-white/10 rounded-xl flex items-center gap-2 hover:border-purple-500/30 transition-colors text-left"
                            >
                                <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-sm font-bold text-white">
                                    {{ new Date(form.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </span>
                            </button>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Catatan (Opsional)</label>
                            <textarea
                                v-model="form.notes"
                                placeholder="Tambahkan catatan..."
                                class="w-full p-3 bg-gray-900 border border-white/10 rounded-xl focus:border-purple-500 focus:ring-0 text-white placeholder-gray-700 resize-none"
                                rows="3"
                            />
                        </div>
                    </div>

                    <!-- Sticky Action Buttons -->
                    <div class="shrink-0 flex gap-2 px-6 pt-4 pb-6 border-t border-white/10 bg-gradient-to-t from-gray-900 to-transparent">
                        <button
                            @click="goBackToTypeSelection"
                            class="flex-1 py-3 px-4 bg-gray-800 hover:bg-gray-700 text-gray-400 font-bold rounded-xl transition-colors"
                            :disabled="isSubmitting"
                        >
                            Batal
                        </button>
                        <button
                            @click="handleSubmit"
                            class="flex-1 py-3 px-4 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600 text-white font-bold rounded-xl transition-all active:scale-95"
                            :disabled="isSubmitting"
                        >
                            <span v-if="isSubmitting" class="inline-block animate-spin mr-2">⏳</span>
                            {{ isSubmitting ? 'Menyimpan...' : 'Simpan Transaksi' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>

    <!-- Date Modal (simple modal overlay) -->
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showDateModal && isOpen"
                class="fixed inset-0 z-[52] flex items-end bg-black/70 backdrop-blur-sm"
                @click.self="showDateModal = false"
            >
                <div class="w-full max-w-md mx-auto bg-gray-900 border-t border-white/10 rounded-t-2xl p-5 pb-safe">
                    <div class="w-12 h-1.5 bg-white/20 rounded-xl mx-auto mb-4 cursor-pointer" @click="showDateModal = false" />

                    <h3 class="text-sm font-bold text-gray-400 mb-4 text-center tracking-widest uppercase">Pilih Tanggal</h3>

                    <div class="flex flex-col gap-3 pb-6">
                        <div class="flex gap-2">
                            <button
                                @click="setDate(0)"
                                class="flex-1 p-3 bg-gray-800 border border-white/10 rounded-xl font-bold text-white hover:bg-gray-700 transition-colors active:scale-95 text-sm"
                            >
                                Hari Ini
                            </button>
                            <button
                                @click="setDate(-1)"
                                class="flex-1 p-3 bg-gray-800 border border-white/10 rounded-xl font-bold text-white hover:bg-gray-700 transition-colors active:scale-95 text-sm"
                            >
                                Kemarin
                            </button>
                        </div>

                        <div class="bg-gray-800 border border-white/10 rounded-xl p-4">
                            <div class="flex justify-between items-center mb-4">
                                <button
                                    @click="prevCalendarMonth"
                                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span class="text-sm font-bold text-white">{{ monthNames[currentMonth] }} {{ currentYear }}</span>
                                <button
                                    @click="nextCalendarMonth"
                                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-7 gap-1">
                                <span class="text-center text-2xs font-bold text-gray-500 py-2">Sen</span>
                                <span class="text-center text-2xs font-bold text-gray-500 py-2">Sel</span>
                                <span class="text-center text-2xs font-bold text-gray-500 py-2">Rab</span>
                                <span class="text-center text-2xs font-bold text-gray-500 py-2">Kam</span>
                                <span class="text-center text-2xs font-bold text-gray-500 py-2">Jum</span>
                                <span class="text-center text-2xs font-bold text-gray-500 py-2">Sab</span>
                                <span class="text-center text-2xs font-bold text-gray-500 py-2">Min</span>

                                <div v-for="n in firstDayOfMonth" :key="'empty-' + n" />
                                <button
                                    v-for="day in daysInMonth"
                                    :key="day"
                                    @click="selectSpecificDate(day); showDateModal = false"
                                    class="h-8 flex items-center justify-center text-sm font-bold rounded-lg transition-all"
                                    :class="[
                                        form.date === [currentYear, String(currentMonth + 1).padStart(2, '0'), String(day).padStart(2, '0')].join('-')
                                            ? 'bg-purple-600 text-white'
                                            : 'text-gray-300 hover:bg-gray-700'
                                    ]"
                                >
                                    {{ day }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>

    <!-- Wallet Modal -->
    <teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showWalletModal && isOpen"
                class="fixed inset-0 z-[52] flex items-end bg-black/70 backdrop-blur-sm"
                @click.self="showWalletModal = false"
            >
                <div class="w-full max-w-md mx-auto bg-gray-900 border-t border-white/10 rounded-t-2xl p-5 pb-safe">
                    <div class="w-12 h-1.5 bg-white/20 rounded-xl mx-auto mb-4 cursor-pointer" @click="showWalletModal = false" />
                    <h3 class="text-sm font-bold text-gray-400 mb-4 text-center tracking-widest uppercase">Pilih Dompet</h3>
                    <div class="overflow-y-auto no-scrollbar space-y-2 max-h-[55vh] pb-6">
                        <div
                            v-for="w in availableWallets"
                            :key="w.id"
                            @click="selectWallet(w)"
                            class="bg-gray-800 border border-white/10 p-4 rounded-xl flex items-center gap-4 cursor-pointer active:scale-95 transition-all hover:border-purple-500/30"
                        >
                            <div class="w-12 h-12 bg-gray-900 border border-white/10 rounded-xl flex items-center justify-center text-xl overflow-hidden shrink-0">
                                <img v-if="w.icon.includes('.')" :src="'/storage/' + w.icon" class="w-full h-full object-cover">
                                <span v-else>{{ w.icon }}</span>
                            </div>
                            <div class="flex-1">
                                <span class="text-sm font-bold text-white block">{{ w.name }}</span>
                                <p class="text-2xs text-purple-500 font-bold tracking-widest mt-0.5">
                                    Rp {{ new Intl.NumberFormat('id-ID').format(w.balance || 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
