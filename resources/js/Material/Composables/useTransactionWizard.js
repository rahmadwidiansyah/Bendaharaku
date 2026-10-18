/**
 * useTransactionWizard — alur wizard form transaksi untuk halaman M3.
 *
 * Merangkum logika yang identik antara Material/Pages/Transactions/Create.vue
 * dan Edit.vue (langkah wizard, pemilihan tipe, transfer, hutang/piutang,
 * validasi, submit). Logika ini disalin apa adanya dari Pages/Transactions/*
 * agar perilaku M3 tidak menyimpang dari Default — hanya presentasi yang
 * diganti ke token M3 di halaman.
 *
 * Perbedaan mode yang ditangani:
 *   - mode 'create' : mulai langkah 1 (pilih tipe), mendukung "Simpan &
 *     tambah lagi", submit ke transactions.store.
 *   - mode 'edit'   : mulai langkah 2, submit ke transactions.update.
 *
 * Parameter:
 *   form            — instance useForm dari halaman
 *   props           — props halaman (wallets, categories, systemWallets, ...)
 *   isDesktopLayout — Ref<boolean> dari useLayoutPreference
 *   mode            — 'create' | 'edit'
 *   initial         — nilai awal untuk mode 'edit'
 *                     ({ initialMainTab, initialType, initialDebtSubTab, initialAmount })
 */
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useTransactionForm } from '@/Composables/useTransactionForm.js'
import { useWizardNavigation } from '@/Composables/useWizardNavigation.js'
import { isFutureDateOnly } from '@/utils/format.js'

export function useTransactionWizard({ form, props, isDesktopLayout, mode = 'create', initial = {} }) {
    const { t } = useI18n()
    const isEdit = mode === 'edit'

    const tx = useTransactionForm(form, props, {
        isDesktopLayout,
        initialMainTab: isEdit ? initial.initialMainTab : 'Expense',
        initialType: isEdit ? initial.initialType : 'Expense',
        initialDebtSubTab: isEdit ? initial.initialDebtSubTab : 'income',
        initialAmount: isEdit ? initial.initialAmount : undefined,
    })

    const {
        rawAmount, formattedAmount, handleKeypad, handleDesktopInput, setRawInputAmount,
        walletFrequency, loadWalletFrequency,
        mainTab, activeType, debtSubTab, setMainTab, setType, setDebtSubTab,
        showWalletModal, walletModalMode, showKeypad, showDateModal, dateModalTarget,
        selectedSourceWallet, selectedDestWallet, availableWallets,
        openWalletModal, selectWallet,
        selectedCategory, activeCategories, activeSubjects, isMoneyIn, selectCategory,
    } = tx

    // ─── Langkah wizard ────────────────────────────────────────────────
    const formStep = ref(isEdit ? 2 : 1)
    const typeConfirmed = ref(isEdit)

    const resetToStep = (step) => {
        formStep.value = step
        if (isEdit) {
            if (step === 2 && !['Transfer', 'Debt', 'Receivable'].includes(mainTab.value)) {
                form.category_id = null
            }
            return
        }
        if (step <= 1) {
            typeConfirmed.value = false
            form.category_id = null
            form.transaction_type = null
        }
        if (step <= 2 && !['Transfer', 'Debt', 'Receivable'].includes(mainTab.value)) {
            form.category_id = null
        }
    }

    const { goBack, pushStepState } = useWizardNavigation({
        formStep,
        resetToStep,
        onBackFromFirstStep: () => router.visit(route('dashboard')),
    })

    onMounted(() => {
        if (!isEdit) setType('Expense')
        loadWalletFrequency()
    })

    // Kunci semantik tipe → dipakai halaman untuk warna token M3.
    const typeKey = computed(() => {
        const map = {
            Expense: 'expense',
            Income: 'income',
            Transfer: 'transfer',
            Debt: 'debt',
            Receivable: 'receivable',
        }
        return map[mainTab.value] ?? 'expense'
    })

    const confirmType = (tab) => {
        setMainTab(tab)
        typeConfirmed.value = true
        form.transaction_type = tab.toLowerCase()
        if (['Debt', 'Receivable'].includes(tab)) {
            form.debt_sub_type = debtSubTab.value
        } else {
            form.debt_sub_type = null
        }
        formStep.value = 2
        pushStepState()
        if (!['Transfer', 'Debt', 'Receivable'].includes(tab)) {
            form.category_id = null
        }
    }

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

    // ─── Transfer ──────────────────────────────────────────────────────
    const isSwapping = ref(false)
    const transferAll = ref(false)
    const transferErrors = ref({})

    const syncTransferAllAmount = (balance) => {
        const amount = Math.abs(parseInt(balance, 10) || 0)
        setRawInputAmount(amount > 0 ? String(amount) : '0')
    }

    watch(transferAll, () => {
        syncTransferAllAmount(transferAll.value ? selectedSourceWallet.value?.balance : 0)
    })

    watch(selectedSourceWallet, () => {
        if (transferAll.value) syncTransferAllAmount(selectedSourceWallet.value?.balance)
    })

    const swapWallets = () => {
        if (isSwapping.value) return
        isSwapping.value = true
        const tmp = form.source_wallet_id
        form.source_wallet_id = form.destination_wallet_id
        form.destination_wallet_id = tmp
        setTimeout(() => { isSwapping.value = false }, 400)
    }

    const validateTransfer = () => {
        transferErrors.value = {}
        if (!form.source_wallet_id) transferErrors.value.source = t('transaction.validation.sourceRequired')
        if (!form.destination_wallet_id) transferErrors.value.dest = t('transaction.validation.destRequired')
        if (form.source_wallet_id && form.destination_wallet_id &&
            form.source_wallet_id === form.destination_wallet_id) {
            transferErrors.value.same = t('transaction.validation.sameWallet')
        }
        if (!form.amount || form.amount <= 0) transferErrors.value.amount = t('transaction.validation.amountPositive')
        return Object.keys(transferErrors.value).length === 0
    }

    // ─── Lunasi hutang/piutang ─────────────────────────────────────────
    const lunasi = ref(false)

    const debtBalance = computed(() => {
        if (!['Debt', 'Receivable'].includes(mainTab.value)) return 0
        const sub = activeSubjects.value.find(s => s.name === form.subject)
        return sub?.balance || 0
    })

    watch(lunasi, () => {
        setRawInputAmount(lunasi.value && debtBalance.value > 0 ? String(Math.abs(debtBalance.value)) : '0')
    })

    watch(() => form.subject, () => {
        if (lunasi.value && debtBalance.value > 0) {
            setRawInputAmount(String(Math.abs(debtBalance.value)))
        } else if (lunasi.value) {
            lunasi.value = false
            setRawInputAmount('0')
        }
    })

    // ─── Submit ────────────────────────────────────────────────────────
    const handleBack = () => router.visit(route('dashboard'))

    const resetForNextEntry = ({ isTransfer }) => {
        form.amount = 0
        rawAmount.value = '0'
        form.notes = ''
        if (isTransfer) {
            form.source_wallet_id = null
            form.destination_wallet_id = null
            transferErrors.value = {}
        } else {
            form.category_id = null
        }
    }

    const submitTransfer = (closeAfter = true) => {
        if (!validateTransfer()) return
        form.transaction_type = 'transfer'
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                if (closeAfter) handleBack()
                else resetForNextEntry({ isTransfer: true })
            },
        }
        if (isEdit) {
            form.put(route('transactions.update', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }), options)
        } else {
            form.post(route('transactions.store'), options)
        }
    }

    const submit = (closeAfter = true) => {
        if (!form.amount || form.amount <= 0) {
            form.setError('amount', t('transaction.validation.amountPositive'))
            return
        }
        if (isFutureDateOnly(form.date)) {
            form.setError('date', t('transaction.validation.dateFuture'))
            return
        }
        if (['Debt', 'Receivable'].includes(activeType.value) && (!form.subject || form.subject === '-')) {
            form.setError('subject', t('transaction.validation.subjectRequired'))
            return
        }
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                if (closeAfter) handleBack()
                else resetForNextEntry({ isTransfer: false })
            },
        }
        if (isEdit) {
            form.put(route('transactions.update', { transaction: props.transaction.id, is_draft: props.transaction.is_draft }), options)
        } else {
            form.post(route('transactions.store'), options)
        }
    }

    const submitAndClose = () => submit(true)
    const submitAndStay = () => submit(false)

    return {
        // Amount & keypad
        rawAmount, formattedAmount, handleKeypad, handleDesktopInput, setRawInputAmount,

        // Tipe & kategori
        mainTab, activeType, typeKey, debtSubTab, setDebtSubTab, confirmType, goToNominal,
        activeCategories, selectedCategory, selectCategory, activeSubjects, isMoneyIn,

        // Wizard
        formStep, typeConfirmed, resetToStep, goBack,

        // Transfer
        transferErrors, transferAll, isSwapping, swapWallets, submitTransfer,

        // Dompet
        showWalletModal, walletModalMode, openWalletModal, selectWallet,
        availableWallets, selectedSourceWallet, selectedDestWallet, walletFrequency,

        // Tanggal
        showDateModal, dateModalTarget, showKeypad,

        // Hutang/piutang
        lunasi, debtBalance,

        // Submit
        submit, submitAndClose, submitAndStay, handleBack,
    }
}
