<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AppIcon from '@/Components/AppIcon.vue'
import { formatNumber } from '@/utils/format.js'


const { t } = useI18n()

const props = defineProps({
    query: { type: String, default: '' },
})

const searchQuery = ref(props.query)
const results = ref([])
const loading = ref(false)
const searched = ref(false)

const showModal = ref(false)
const selectedTransaction = ref(null)

const grouped = computed(() => {
    const groups = {}
    for (const item of results.value) {
        const key = item.type
        if (!groups[key]) groups[key] = []
        groups[key].push(item)
    }
    return groups
})

const doSearch = async () => {
    const q = searchQuery.value.trim()
    if (!q) { results.value = []; searched.value = false; return }
    loading.value = true
    searched.value = true
    try {
        const res = await axios.get(route('search.global'), { params: { q } })
        results.value = res.data.results || []
    } catch {
        results.value = []
    } finally {
        loading.value = false
    }
}

const openTransaction = (item) => {
    selectedTransaction.value = {
        id: item.transaction_id,
        amount: item.amount,
        date: item.date,
        is_cleared: item.is_cleared,
        type: item.transaction_type,
        category: item.category,
        source_wallet: item.source_wallet,
        destination_wallet: item.destination_wallet,
        subject: item.label,
        notes: item.notes,
    }
    showModal.value = true
}

onMounted(() => {
    if (props.query.trim()) doSearch()
})

const typeMeta = {
    Wallet: { label: 'Dompet', icon: 'wallet' },
    Kategori: { label: 'Kategori', icon: 'folder' },
    Transaksi: { label: 'Transaksi', icon: 'receipt' },
}
const typeDot = {
    Wallet: 'bg-[var(--color-transfer-text)]',
    Kategori: 'bg-[var(--color-income-text)]',
    Transaksi: 'bg-[var(--color-brand)]',
}

const getWalletName = (item) => {
    const t = item.transaction_type?.name
    if (t === 'Transfer') {
        return [item.source_wallet?.name, item.destination_wallet?.name].filter(Boolean).join(' → ')
    }
    const isIncomeLike = t === 'Income'
        || (['Debt', 'Receivable'].includes(t) && item.source_wallet?.group_type === 'System')
    return isIncomeLike ? (item.destination_wallet?.name || '') : (item.source_wallet?.name || '')
}

const amountClass = (item) => {
    const n = item.transaction_type?.name
    if (n === 'Income' || (['Debt', 'Receivable'].includes(n) && item.source_wallet?.group_type === 'System')) return 'text-[var(--color-income-text)]'
    if (n === 'Transfer' && !['Debt', 'Receivable'].includes(n)) return 'text-[var(--color-transfer-text)]'
    return 'text-[var(--color-expense-text)]'
}

const amountPrefix = (item) => {
    const n = item.transaction_type?.name
    if (n === 'Income' || (['Debt', 'Receivable'].includes(n) && item.source_wallet?.group_type === 'System')) return '+'
    if (n === 'Transfer' && !['Debt', 'Receivable'].includes(n)) return ''
    return '-'
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="'Cari: ' + searchQuery" />

        <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">
            <!-- Search input -->
            <div class="relative mb-6 animate-fade-in-up">
                <div class="flex items-center gap-3 bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-2xl px-4 py-3.5 shadow-2xl shadow-black/50">
                    <svg class="w-5 h-5 text-[var(--color-text-secondary)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="M21 21l-4.35-4.35" />
                    </svg>
                    <input v-model="searchQuery" type="search"
                        placeholder="Cari dompet, kategori, transaksi..."
                        class="flex-1 bg-transparent text-[var(--color-text-primary)] text-sm sm:text-base placeholder:text-[var(--color-text-muted)] outline-none caret-[var(--color-brand)]"
                        autocomplete="off" @keydown.enter="doSearch" />
                    <button @click="doSearch"
                        class="shrink-0 px-4 py-1.5 rounded-xl bg-[var(--color-brand)] text-white text-2xs font-bold uppercase tracking-widest hover:bg-[var(--color-brand-hover)] transition-colors active:scale-95">
                        Cari
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-12 sm:py-16">
                <svg class="w-6 h-6 text-[var(--color-brand)] animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>

            <!-- No results -->
            <div v-if="searched && !loading && results.length === 0" class="text-center py-12 sm:py-16 px-3 sm:px-4">
                <p class="text-[var(--color-text-muted)] text-sm sm:text-base">Tidak ada hasil untuk "<span class="text-[var(--color-text-secondary)]">{{ searchQuery }}</span>"</p>
            </div>

            <!-- Results -->
            <div v-if="results.length" class="space-y-8 animate-fade-in-up">
                <div v-for="(items, type) in grouped" :key="type">
                    <div class="flex items-center gap-2 mb-3 px-1">
                        <div :class="['w-1.5 h-1.5 rounded-full', typeDot[type] || 'bg-[var(--color-text-muted)]']" />
                        <h2 class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">
                            {{ typeMeta[type]?.label || type }}
                        </h2>
                        <span class="text-2xs text-[var(--color-text-muted)] font-bold">({{ items.length }})</span>
                        <div class="flex-1 h-px bg-gradient-to-r from-[var(--color-border-default)] to-transparent" />
                    </div>

                    <!-- Wallet & Category results -->
                    <div v-if="type !== 'Transaksi'" class="space-y-1.5">
                        <Link v-for="item in items" :key="item.id" :href="item.route"
                            class="flex items-center gap-3 p-2.5 sm:p-3 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] transition-all active:scale-[0.98] group hover:border-[var(--color-brand-border)]">
                            <AppIcon :icon="item.icon" fallback="search"
                                class="w-6 h-6 shrink-0 text-[var(--color-text-secondary)]" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-[var(--color-text-primary)] truncate group-hover:text-[var(--color-brand)] transition-colors">
                                    {{ item.label }}
                                </p>
                                <p v-if="item.description" class="text-2xs text-[var(--color-text-muted)] truncate mt-0.5">
                                    {{ item.description }}
                                </p>
                            </div>
                            <svg class="w-4 h-4 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-secondary)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Transaction results (like dashboard) -->
                    <div v-if="type === 'Transaksi'" class="space-y-1.5">
                        <button v-for="item in items" :key="item.id" @click="openTransaction(item)"
                            class="w-full text-left bg-[var(--color-surface-raised)] p-2.5 sm:p-3 rounded-xl border border-[var(--color-border-default)] hover:border-[var(--color-brand-border)] active:scale-[0.98] transition-all relative overflow-hidden group">
                            <div class="absolute inset-0 bg-[var(--color-surface-muted)]/50 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none" />

                            <div class="flex items-center gap-3 relative z-10">
                                <AppIcon :icon="item.category?.icon" fallback="file-text"
                                    class="w-6 h-6 shrink-0 text-[var(--color-brand)]" />

                                <div class="flex-1 min-w-0 pr-2">
                                    <p class="text-xs font-bold text-[var(--color-text-primary)] leading-tight truncate">
                                        {{ item.label || item.category?.category_name || t('types.transfer') }}
                                    </p>
                                    <p v-if="item.notes" class="text-2xs text-[var(--color-text-muted)] truncate mt-0.5 italic">
                                        {{ item.notes }}
                                    </p>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="text-[var(--color-text-secondary)] text-2xs tracking-wide font-medium">
                                            {{ item.category?.category_name }}
                                        </span>
                                        <span class="text-[var(--color-text-muted)]">•</span>
                                        <span class="text-[var(--color-text-secondary)] text-2xs tracking-wide font-medium truncate">
                                            {{ getWalletName(item) }}
                                        </span>
                                        <span v-if="!item.is_cleared"
                                            class="shrink-0 inline-flex items-center gap-0.5 text-2xs font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-[var(--color-debt-bg)] text-[var(--color-debt-text)] border border-[var(--color-debt-border)]">
                                            DRAFT
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-2xs font-black" :class="amountClass(item)">
                                        {{ amountPrefix(item) }}{{ formatNumber(item.amount) }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <TransactionDetailModal :show="showModal" :transaction="selectedTransaction" @close="showModal = false" />
    </AuthenticatedLayout>
</template>
