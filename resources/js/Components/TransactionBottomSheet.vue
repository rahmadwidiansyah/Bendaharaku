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
 */

import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { route } from 'ziggy-js'
import { useTransactionForm } from '@/Composables/useTransactionForm'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import AmountKeypad from './AmountKeypad.vue'

const { t } = useI18n()

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
const step = ref(1)
const selectedType = ref(null)
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

// === Transaction Types Definition (computed for i18n reactivity) ===
const transactionTypes = computed(() => [
    { id: 'Income',     label: t('types.income'),     icon: '📥', color: 'from-success-mid to-success-deep' },
    { id: 'Expense',    label: t('types.expense'),    icon: '📤', color: 'from-danger-mid to-danger-deep' },
    { id: 'Transfer',   label: t('types.transfer'),   icon: '🔄', color: 'from-transfer-mid to-transfer-deep' },
    { id: 'Debt',       label: t('types.debt'),       icon: '📊', color: 'from-brand-mid to-brand-deep' },
    { id: 'Receivable', label: t('types.receivable'), icon: '💰', color: 'from-warning-mid to-warning-deep' },
])

// === Computed Properties ===
const selectedTypeData = computed(() => {
    return transactionTypes.value.find(t => t.id === selectedType.value)
})

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

const sheetHeight = computed(() => {
    if (step.value === 1) return '40vh'
    return 'calc(100vh - 4rem)'
})

// === Methods ===
const selectTransactionType = (typeId) => {
    selectedType.value = typeId
    form.clearErrors()
    setType(typeId)
    step.value = 2
}

const goBackToTypeSelection = () => {
    step.value = 1
}

const handleSubmit = async () => {
    if (!form.amount || form.amount <= 0) {
        form.setError('amount', t('validation.amountRequired'))
        return
    }

    if (new Date(form.date) > new Date()) {
        form.setError('date', t('validation.futureDateNotAllowed'))
        return
    }

    if (['Debt', 'Receivable'].includes(selectedType.value) && (!form.subject || form.subject === '-')) {
        form.setError('subject', t('validation.subjectRequired'))
        return
    }

    isSubmitting.value = true

    form.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('submit')
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
