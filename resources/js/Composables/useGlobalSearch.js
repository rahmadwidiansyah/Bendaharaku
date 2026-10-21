import { computed, onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import { useI18n } from 'vue-i18n'

/**
 * Logika bersama halaman pencarian global (Default & Material 3).
 *
 * - Request lama selalu dibatalkan sebelum request baru.
 * - `requestSeq` mencegah respons lama menimpa hasil terbaru.
 * - Query kosong mengembalikan UI ke keadaan awal, bukan "tidak ada hasil".
 */
export function useGlobalSearch(initialQuery = '') {
    const { t } = useI18n()

    const searchQuery = ref(String(initialQuery ?? ''))
    const results = ref([])
    const loading = ref(false)
    const searched = ref(false)
    const error = ref('')
    const showModal = ref(false)
    const selectedTransaction = ref(null)

    let controller = null
    let requestSeq = 0

    const grouped = computed(() => {
        const groups = {}
        for (const item of results.value) {
            const key = item.type
            if (!groups[key]) groups[key] = []
            groups[key].push(item)
        }
        return groups
    })

    const showNoResults = computed(() =>
        searched.value && !loading.value && !error.value && results.value.length === 0,
    )

    const resetSearch = () => {
        requestSeq++
        controller?.abort()
        controller = null
        results.value = []
        searched.value = false
        loading.value = false
        error.value = ''
    }

    const doSearch = async () => {
        const q = searchQuery.value.trim()
        if (!q) {
            resetSearch()
            return
        }

        controller?.abort()
        const seq = ++requestSeq
        controller = new AbortController()
        loading.value = true
        searched.value = true
        error.value = ''

        try {
            const response = await axios.get(route('search.global'), {
                params: { q },
                signal: controller.signal,
            })

            if (seq !== requestSeq) return
            results.value = response.data.results || []
        } catch (err) {
            if (seq !== requestSeq || axios.isCancel(err)) return
            results.value = []
            error.value = t('common.errors.generic')
        } finally {
            if (seq === requestSeq) loading.value = false
        }
    }

    const onQueryInput = (value) => {
        searchQuery.value = value
        if (!value.trim()) resetSearch()
    }

    const closeTransaction = () => {
        showModal.value = false
    }

    const openTransaction = (item) => {
        selectedTransaction.value = {
            id: item.transaction_id,
            amount: item.amount,
            date: item.date,
            time: item.time,
            is_cleared: item.is_cleared,
            is_draft: !!item.is_draft,
            due_date: item.due_date,
            due_date_type: item.due_date_type,
            due_date_interval: item.due_date_interval,
            type: item.transaction_type,
            category: item.category,
            source_wallet: item.source_wallet,
            destination_wallet: item.destination_wallet,
            subject: item.subject || item.label,
            notes: item.notes,
        }
        showModal.value = true
    }

    const getTypeName = (name) => t({
        Income: 'types.income',
        Expense: 'types.expense',
        Transfer: 'types.transfer',
        Debt: 'types.debt',
        Receivable: 'types.receivable',
    }[name] ?? 'types.other')

    const displayTitle = (item) => {
        if (item.label && item.label !== '-') return item.label
        return item.category?.category_name || getTypeName(item.transaction_type?.name)
    }

    const getWalletName = (item) => {
        const type = item.transaction_type?.name
        if (type === 'Transfer') {
            return [item.source_wallet?.name, item.destination_wallet?.name].filter(Boolean).join(' → ')
        }

        const isIncomeLike = type === 'Income'
            || (['Debt', 'Receivable'].includes(type) && item.source_wallet?.group_type === 'System')

        return isIncomeLike
            ? (item.destination_wallet?.name || '')
            : (item.source_wallet?.name || '')
    }

    const amountTone = (item) => {
        const type = item.transaction_type?.name
        if (type === 'Income' || (['Debt', 'Receivable'].includes(type) && item.source_wallet?.group_type === 'System')) {
            return 'income'
        }
        if (type === 'Transfer') return 'transfer'
        return 'expense'
    }

    const amountPrefix = (item) => {
        if (amountTone(item) === 'income') return '+'
        if (amountTone(item) === 'transfer') return ''
        return '-'
    }

    const groupMeta = (type) => ({
        Wallet: { label: t('transaction.wallet'), icon: 'wallet' },
        Kategori: { label: t('transaction.category'), icon: 'folder' },
        Transaksi: { label: t('category.transaction'), icon: 'receipt' },
    }[type] ?? { label: type, icon: 'search' })

    onMounted(() => {
        if (searchQuery.value.trim()) doSearch()
    })

    onUnmounted(() => {
        requestSeq++
        controller?.abort()
    })

    return {
        searchQuery,
        results,
        loading,
        searched,
        error,
        grouped,
        showNoResults,
        showModal,
        selectedTransaction,
        doSearch,
        resetSearch,
        onQueryInput,
        openTransaction,
        closeTransaction,
        displayTitle,
        getWalletName,
        getTypeName,
        amountTone,
        amountPrefix,
        groupMeta,
    }
}
