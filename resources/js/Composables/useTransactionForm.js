/**
 * useTransactionForm.js
 *
 * Composable yang mengenkapsulasi semua logic bersama antara
 * Transactions/Create.vue dan Transactions/Edit.vue.
 *
 * Logic yang diekstrak (identik di kedua file):
 *   - rawAmount state + handleKeypad + handleDesktopInput + formattedAmount
 *   - walletFrequency (localStorage cache)
 *   - mainTab / activeType / debtSubTab state
 *   - activeCategories, selectedCategory, activeSubjects computed
 *   - isMoneyIn computed
 *   - showKeypad / showBottomPanel / showDateModal / dateModalTarget state
 *   - Calendar date picker state & logic (currentMonth/Year, prevMonth, nextMonth, selectDate, setDate)
 *   - selectedSourceWallet / selectedDestWallet computed
 *   - showSourceWallet / showDestWallet computed (Edit only, harmless di Create)
 *   - setMainTab / setType / setDebtSubTab
 *   - availableWallets computed
 *   - openWalletModal / selectWallet
 *   - selectCategory
 *
 * Parameter:
 *   form      — Inertia useForm instance (berbeda initial value di Create vs Edit)
 *   props     — Props dari parent (wallets, categories, systemWallets, dsb.)
 *   options   — { isDesktopLayout: Ref<boolean> }
 *
 * Usage:
 *   import { useTransactionForm } from '@/Composables/useTransactionForm.js'
 *
 *   // Di Create.vue:
 *   const tx = useTransactionForm(form, props, { isDesktopLayout })
 *
 *   // Di Edit.vue (dengan initialType dari existing transaction):
 *   const tx = useTransactionForm(form, props, { isDesktopLayout, initialType: 'Expense' })
 */

import { ref, computed, watch } from 'vue'

export function useTransactionForm(form, props, options = {}) {
    const { isDesktopLayout } = options

    // ─── Amount / Keypad ──────────────────────────────────────────────
    const rawAmount = ref(options.initialAmount ?? '0')

    const formattedAmount = computed(() => {
        if (!rawAmount.value || rawAmount.value === '0') return ''
        const clean = rawAmount.value.toString().replace(/\D/g, '')
        if (!clean) return ''
        return parseInt(clean, 10).toLocaleString('id-ID')
    })

    const handleKeypad = (key) => {
        if (key === 'del') {
            rawAmount.value = rawAmount.value.slice(0, -1) || '0'
        } else if (key === 'C') {
            rawAmount.value = '0'
        } else if (key === '000') {
            if (rawAmount.value !== '0') rawAmount.value += '000'
        } else {
            if (rawAmount.value === '0') rawAmount.value = key
            else rawAmount.value += key
        }
        if (rawAmount.value.length > 15) rawAmount.value = rawAmount.value.slice(0, 15)
        form.amount = parseInt(rawAmount.value, 10) || 0
    }

    const handleDesktopInput = (e) => {
        let clean = e.target.value.replace(/\D/g, '')
        if (clean.length > 15) clean = clean.slice(0, 15)
        e.target.value = clean ? parseInt(clean, 10).toLocaleString('id-ID') : ''
        rawAmount.value = clean || '0'
        form.amount = parseInt(clean, 10) || 0
    }

    // ─── Wallet frequency cache ───────────────────────────────────────
    const walletFrequency = ref({})

    const loadWalletFrequency = () => {
        try {
            const stored = localStorage.getItem('wallet_frequency')
            if (stored) walletFrequency.value = JSON.parse(stored)
        } catch {
            // localStorage tidak tersedia
        }
    }

    // ─── Type / Tab state ─────────────────────────────────────────────
    const mainTab    = ref(options.initialMainTab ?? 'Expense')
    const activeType = ref(options.initialType    ?? 'Expense')
    const debtSubTab = ref(options.initialDebtSubTab ?? 'income')

    // ─── Modal state ──────────────────────────────────────────────────
    const showCategoryModal = ref(false)
    const showWalletModal   = ref(false)
    const walletModalMode   = ref('source')
    const showKeypad        = ref(isDesktopLayout ? !isDesktopLayout.value : true)
    const showBottomPanel   = ref(true)
    const showDateModal     = ref(false)
    const dateModalTarget   = ref('transaction')

    // ─── Calendar date picker ─────────────────────────────────────────
    const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
    const currentMonth = ref(new Date().getMonth())
    const currentYear  = ref(new Date().getFullYear())

    const daysInMonth = computed(() =>
        new Date(currentYear.value, currentMonth.value + 1, 0).getDate()
    )
    const firstDayOfMonth = computed(() => {
        const day = new Date(currentYear.value, currentMonth.value, 1).getDay()
        return day === 0 ? 6 : day - 1 // Mon=0, Sun=6
    })

    const prevCalendarMonth = () => {
        if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value-- }
        else currentMonth.value--
    }

    const nextCalendarMonth = () => {
        if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++ }
        else currentMonth.value++
    }

    // Sync calendar display saat modal terbuka
    watch(showDateModal, (val) => {
        if (val) {
            const d = dateModalTarget.value === 'due_date' && form.due_date
                ? new Date(form.due_date)
                : new Date(form.date)
            currentMonth.value = d.getMonth()
            currentYear.value  = d.getFullYear()
        }
    })

    // Format tanggal lokal menghindari UTC shift
    const toLocalYMD = (d) => {
        const offset = d.getTimezoneOffset() * 60000
        return new Date(d - offset).toISOString().slice(0, 10)
    }

    const selectSpecificDate = (day) => {
        const dateStr = toLocalYMD(new Date(currentYear.value, currentMonth.value, day))
        if (dateModalTarget.value === 'due_date') form.due_date = dateStr
        else                                       form.date    = dateStr
        showDateModal.value = false
    }

    const setDate = (offsetDays) => {
        const d = new Date()
        d.setDate(d.getDate() + offsetDays)
        const dateStr = toLocalYMD(d)
        if (dateModalTarget.value === 'due_date') form.due_date = dateStr
        else                                       form.date    = dateStr
        showDateModal.value = false
    }

    // ─── Wallet computed ──────────────────────────────────────────────
    const allWallets = computed(() => [...(props.wallets ?? []), ...(props.systemWallets ?? [])])

    const selectedSourceWallet = computed(() =>
        allWallets.value.find(w => w.id == form.source_wallet_id)
    )
    const selectedDestWallet = computed(() =>
        allWallets.value.find(w => w.id == form.destination_wallet_id)
    )

    // Resolved category object based on current category_id
    const selectedCategory = computed(() =>
        (props.categories ?? []).find(c => c.id === form.category_id)
    )

    // ─── Visibility computed (untuk Edit — mana wallet yang perlu ditampilkan) ──
    const showSourceWallet = computed(() => {
        if (activeType.value === 'Income') return false
        if (['Debt', 'Receivable'].includes(activeType.value)) {
            const key = selectedCategory.value?.system_key
            if (key === 'LOAN' || key === 'RECEIVABLE_PAYMENT') return false
        }
        return true
    })

    const showDestWallet = computed(() => {
        if (activeType.value === 'Expense') return false
        if (['Debt', 'Receivable'].includes(activeType.value)) {
            const key = selectedCategory.value?.system_key
            if (key === 'DEBT_PAYMENT' || key === 'RECEIVABLE') return false
        }
        return true
    })

    // Dompet yang tersedia di wallet picker (exclude dompet yang sudah dipilih di sisi lain)
    const availableWallets = computed(() => {
        let list = (props.wallets ?? []).filter(w => ['Asset', 'Liquid'].includes(w.group_type))
        const other = walletModalMode.value === 'source' ? form.destination_wallet_id : form.source_wallet_id
        if (other) list = list.filter(w => w.id !== other)
        return list.sort((a, b) => (walletFrequency.value[b.id] || 0) - (walletFrequency.value[a.id] || 0))
    })

    // ─── Category computed ────────────────────────────────────────────
    const activeCategories = computed(() => {
        let cats = (props.categories ?? []).filter(cat => cat.type.name === activeType.value)
        if (activeType.value === 'Debt') {
            if (debtSubTab.value === 'expense') cats = cats.filter(c => c.system_key === 'DEBT_PAYMENT')
            else                                cats = cats.filter(c => c.system_key === 'LOAN')
        } else if (activeType.value === 'Receivable') {
            if (debtSubTab.value === 'expense') cats = cats.filter(c => c.system_key === 'RECEIVABLE')
            else                                cats = cats.filter(c => c.system_key === 'RECEIVABLE_PAYMENT')
        }
        return cats
    })

    const activeSubjects = computed(() => {
        if (activeType.value === 'Debt')       return props.debtSubjects       ?? []
        if (activeType.value === 'Receivable') return props.receivableSubjects ?? []
        return []
    })

    const isMoneyIn = computed(() => {
        if (activeType.value === 'Income') return true
        if (activeType.value === 'Debt'       && debtSubTab.value === 'income') return true
        if (activeType.value === 'Receivable' && debtSubTab.value === 'income') return true
        return false
    })

    // ─── Actions ──────────────────────────────────────────────────────
    const selectCategory = (cat) => {
        form.category_id = cat.id
        showCategoryModal.value = false
        form.clearErrors('category_id')
    }

    const openWalletModal = (mode) => {
        walletModalMode.value = mode
        showWalletModal.value = true
    }

    const selectWallet = (w) => {
        walletFrequency.value[w.id] = (walletFrequency.value[w.id] || 0) + 1
        localStorage.setItem('wallet_frequency', JSON.stringify(walletFrequency.value))

        if (walletModalMode.value === 'source') {
            form.source_wallet_id = w.id
            localStorage.setItem('last_source_wallet', w.id)
            form.clearErrors('source_wallet_id')
        } else {
            form.destination_wallet_id = w.id
            localStorage.setItem('last_dest_wallet', w.id)
            form.clearErrors('destination_wallet_id')
        }
        showWalletModal.value = false
    }

    const setType = (type) => {
        activeType.value = type
        form.category_id = null
        form.subject = (type === 'Debt' || type === 'Receivable') ? '' : '-'
        form.clearErrors()

        const lastSource = localStorage.getItem('last_source_wallet')
        const lastDest   = localStorage.getItem('last_dest_wallet')

        if (type === 'Expense') {
            form.source_wallet_id      = lastSource || lastDest || null
            const merchant = (props.systemWallets ?? []).find(w => w.name.toLowerCase().includes('merchant'))
            form.destination_wallet_id = merchant?.id ?? null
        } else if (type === 'Income') {
            const external = (props.systemWallets ?? []).find(w => w.name.toLowerCase().includes('external'))
            form.source_wallet_id      = external?.id ?? null
            form.destination_wallet_id = lastDest || lastSource || null
        } else {
            form.source_wallet_id      = lastSource || lastDest || null
            form.destination_wallet_id = lastDest  || lastSource || null
        }

        let filteredCats = (props.categories ?? []).filter(cat => cat.type.name === type)

        if (['Debt', 'Receivable'].includes(type) && filteredCats.length > 0) {
            // Lookup by system_key — tidak bergantung pada category_name yang bisa diubah user
            let targetSystemKey = ''
            if (type === 'Debt') {
                targetSystemKey = debtSubTab.value === 'expense' ? 'DEBT_PAYMENT' : 'LOAN'
            } else {
                targetSystemKey = debtSubTab.value === 'expense' ? 'RECEIVABLE' : 'RECEIVABLE_PAYMENT'
            }

            const cat = filteredCats.find(c => c.system_key === targetSystemKey)
            if (cat) {
                form.category_id = cat.id
                form.clearErrors('category_id')
            }

            const syH = (props.systemWallets ?? []).find(w => w.name.toLowerCase().includes('hutang'))
            const syP = (props.systemWallets ?? []).find(w => w.name.toLowerCase().includes('piutang'))

            if (type === 'Debt') {
                if (debtSubTab.value === 'expense') {
                    // Bayar hutang: dompet user → system hutang
                    form.source_wallet_id      = lastSource || null
                    form.destination_wallet_id = syH?.id ?? null
                } else {
                    // Dapat hutang: system hutang → dompet user
                    form.source_wallet_id      = syH?.id ?? null
                    form.destination_wallet_id = lastDest || null
                }
            } else {
                if (debtSubTab.value === 'expense') {
                    // Kasih piutang: dompet user → system piutang
                    form.source_wallet_id      = lastSource || null
                    form.destination_wallet_id = syP?.id ?? null
                } else {
                    // Terima bayar piutang: system piutang → dompet user
                    form.source_wallet_id      = syP?.id ?? null
                    form.destination_wallet_id = lastDest || null
                }
            }
        } else if (filteredCats.length === 1) {
            selectCategory(filteredCats[0])
        }
    }

    const setMainTab = (t) => {
        mainTab.value = t
        if (['Debt', 'Receivable'].includes(t)) debtSubTab.value = 'income'
        setType(t)
    }

    const setDebtSubTab = (subTab) => {
        debtSubTab.value = subTab
        setType(mainTab.value)
    }

    // ─── Return semua yang dibutuhkan parent ──────────────────────────
    return {
        // Amount
        rawAmount,
        formattedAmount,
        handleKeypad,
        handleDesktopInput,

        // Wallet cache
        walletFrequency,
        loadWalletFrequency,

        // Tabs / Type
        mainTab,
        activeType,
        debtSubTab,
        setMainTab,
        setType,
        setDebtSubTab,

        // Modals
        showCategoryModal,
        showWalletModal,
        walletModalMode,
        showKeypad,
        showBottomPanel,
        showDateModal,
        dateModalTarget,

        // Calendar
        monthNames,
        currentMonth,
        currentYear,
        daysInMonth,
        firstDayOfMonth,
        prevCalendarMonth,
        nextCalendarMonth,
        selectSpecificDate,
        setDate,

        // Wallets
        selectedSourceWallet,
        selectedDestWallet,
        showSourceWallet,
        showDestWallet,
        availableWallets,
        openWalletModal,
        selectWallet,

        // Categories
        selectedCategory,
        activeCategories,
        activeSubjects,
        isMoneyIn,
        selectCategory,
    }
}
