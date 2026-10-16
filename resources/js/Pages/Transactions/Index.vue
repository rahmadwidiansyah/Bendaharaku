<script setup>
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { formatNumber } from '@/utils/format.js'
import { getCategoryIconColor } from '@/Composables/useIcon.js'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'

const { t } = useI18n()
const { isBalanceVisible } = useBalanceVisibility()

const props = defineProps({
	transactions: Object,
	dailyTotals: Object,
	calendarMarkers: Array,
	startDate: String,
	endDate: String,
	filters: Object,
	limit: Number,
})

// ─── State halaman (halaman 1 dari server, sisanya via endpoint JSON) ───
const rows = ref([...(props.transactions?.data || [])])
const nextCursor = ref(props.transactions?.next_cursor || null)
const hasMore = ref(!!props.transactions?.has_more)
const totals = ref({ ...(props.dailyTotals || {}) })
const isLoadingMore = ref(false)
const loadError = ref(null)
let loadAbort = null

const resetFromProps = () => {
	rows.value = [...(props.transactions?.data || [])]
	nextCursor.value = props.transactions?.next_cursor || null
	hasMore.value = !!props.transactions?.has_more
	totals.value = { ...(props.dailyTotals || {}) }
	loadError.value = null
}
watch(() => [props.startDate, props.endDate, props.filters, props.transactions?.data], resetFromProps)
onUnmounted(() => loadAbort?.abort())

// ─── Filter (reload Inertia penuh → kembali ke halaman 1) ───
const type = ref(props.filters?.type || '')
const searchInput = ref(props.filters?.search || '')
const from = ref(props.startDate)
const to = ref(props.endDate)
let searchTimer = null

const applyFilters = (patch = {}) => {
	loadAbort?.abort()
	router.get(
		route('transactions.index'),
		{
			type: type.value,
			search: searchInput.value || undefined,
			start_date: from.value,
			end_date: to.value,
			...patch,
		},
		{ preserveState: true, replace: true },
	)
}
const onSearchInput = () => {
	clearTimeout(searchTimer)
	searchTimer = setTimeout(() => applyFilters(), 350)
}
onUnmounted(() => clearTimeout(searchTimer))

// ─── Muat lebih banyak (axios, dedupe, abort ganda) ───
const rowKey = (trx) => `${trx.is_draft ? 'd' : 't'}:${trx.id}`
const loadMore = async () => {
	if (isLoadingMore.value || !hasMore.value || !nextCursor.value) return
	isLoadingMore.value = true
	loadError.value = null
	loadAbort?.abort()
	loadAbort = new AbortController()
	try {
		const { data } = await axios.get(route('transactions.page'), {
			params: {
				start_date: from.value,
				end_date: to.value,
				type: type.value || undefined,
				search: searchInput.value || undefined,
				cursor: nextCursor.value,
				limit: props.limit || 50,
			},
			signal: loadAbort.signal,
		})
		const seen = new Set(rows.value.map(rowKey))
		for (const row of data.data || []) {
			if (!seen.has(rowKey(row))) {
				seen.add(rowKey(row))
				rows.value.push(row)
			}
		}
		nextCursor.value = data.next_cursor || null
		hasMore.value = !!data.has_more
	} catch (err) {
		if (!axios.isCancel(err)) loadError.value = t('common.loadMoreFailed')
	} finally {
		isLoadingMore.value = false
	}
}

// ─── Pengelompokan per tanggal (subtotal dari server, benar di semua halaman) ───
const groupedTransactions = computed(() => {
	const groups = {}
	for (const trx of rows.value) {
		const dateKey = typeof trx.raw_date === 'string' ? trx.raw_date.slice(0, 10) : trx.raw_date
		if (!groups[dateKey]) {
			const server = totals.value[dateKey] || { income: 0, expense: 0 }
			groups[dateKey] = { date: trx.date, transactions: [], income: server.income || 0, expense: server.expense || 0 }
		}
		groups[dateKey].transactions.push(trx)
	}
	return groups
})

// ─── Modal ───
const showModal = ref(false)
const selectedTransaction = ref(null)
const openModal = (trx) => {
	selectedTransaction.value = trx
	showModal.value = true
}

const TYPE_NAME_KEYS = {
	Income: 'types.income',
	Expense: 'types.expense',
	Transfer: 'types.transfer',
	Debt: 'types.debt',
	Receivable: 'types.receivable',
}
const getTypeName = (typeName) => t(TYPE_NAME_KEYS[typeName] ?? 'types.other')
const masked = (v) => (isBalanceVisible.value ? formatNumber(v) : '••••••••')
const getWalletName = (trx) => {
	const typeName = trx.type?.name
	if (typeName === 'Transfer') {
		return [trx.source_wallet?.name, trx.destination_wallet?.name].filter(Boolean).join(' → ')
	}
	const isIncomeLike = typeName === 'Income'
		|| (['Debt', 'Receivable'].includes(typeName) && trx.source_wallet?.group_type === 'System')
	return isIncomeLike ? (trx.destination_wallet?.name || '') : (trx.source_wallet?.name || '')
}
</script>

<template>
	<AuthenticatedLayout :fullWidth="true">
		<Head :title="t('transaction.title')" />

		<div class="p-5 w-full lg:max-w-7xl mx-auto lg:px-8 relative animate-slide-up">
			<header class="mb-4 pt-2">
				<h1 class="text-2xl font-black text-[var(--color-text-primary)] tracking-tight leading-none">
					{{ t('transaction.title') }}
				</h1>
			</header>

			<!-- Filter -->
			<div class="flex flex-col sm:flex-row gap-2 mb-4">
				<input v-model="searchInput" @input="onSearchInput" type="search"
					:placeholder="t('common.search')"
					class="flex-1 px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-xl text-sm text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none" />
				<select v-model="type" @change="applyFilters()"
					class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-xl text-sm text-[var(--color-text-primary)]">
					<option value="">{{ t('types.all') }}</option>
					<option v-for="key in ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable']" :key="key" :value="key">
						{{ getTypeName(key) }}
					</option>
				</select>
				<div class="flex gap-2">
					<input v-model="from" @change="applyFilters()" type="date"
						class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-xl text-sm text-[var(--color-text-primary)]" />
					<input v-model="to" @change="applyFilters()" type="date"
						class="px-3 py-2 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-xl text-sm text-[var(--color-text-primary)]" />
				</div>
			</div>

			<!-- Daftar grup tanggal -->
			<div v-for="(group, dateKey) in groupedTransactions" :key="dateKey"
				class="border-b border-[var(--color-border-subtle)] last:border-b-0 pb-1 sm:pb-2">
				<div class="flex justify-between items-center py-2.5 px-1">
					<h3 class="text-2xs font-bold text-[var(--color-brand)] uppercase tracking-widest">
						{{ group.date }}
					</h3>
					<div class="text-2xs font-bold flex gap-2.5 tracking-wide">
						<span v-if="group.income > 0" class="text-[var(--color-income-text)]">+{{ masked(group.income) }}</span>
						<span v-if="group.expense > 0" class="text-[var(--color-expense-text)]">-{{ masked(group.expense) }}</span>
					</div>
				</div>
				<div>
					<button v-for="trx in group.transactions" :key="(trx.is_draft ? 'd' : 't') + ':' + trx.id"
						@click="openModal(trx)"
						class="w-full text-left flex items-center gap-2.5 sm:gap-3 py-2.5 sm:py-3 px-1 hover:bg-[var(--color-surface-muted)]/40 border-b border-[var(--color-border-subtle)] last:border-b-0">
						<div class="relative shrink-0">
							<AppIcon :icon="trx.category?.icon" fallback="file-text"
								:class="['w-5 h-5', getCategoryIconColor(trx.type?.name)]" />
							<span v-if="!trx.is_cleared"
								class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-[var(--color-debt-text)] ring-2 ring-[var(--color-surface-raised)]" />
						</div>
						<div class="flex-1 min-w-0 leading-tight">
							<p class="text-xs font-bold text-[var(--color-text-primary)] truncate">
								{{ trx.category?.category_name || t('types.transfer') }}
							</p>
							<p class="text-2xs text-[var(--color-text-muted)] truncate mt-[1px]">
								{{ getWalletName(trx) }}
							</p>
						</div>
						<div class="text-right shrink-0 flex flex-col items-end">
							<p class="text-2xs font-black" :class="trx.type.name === 'Income' || (['Debt', 'Receivable'].includes(trx.type.name) && trx.source_wallet?.group_type === 'System')
									? 'text-[var(--color-income-text)]'
									: trx.type.name === 'Transfer' && !['Debt', 'Receivable'].includes(trx.type.name)
										? 'text-[var(--color-transfer-text)]'
										: 'text-[var(--color-expense-text)]'
								">
								{{
									trx.type.name === 'Income' || (['Debt',
										'Receivable'].includes(trx.type.name) &&
										trx.source_wallet?.group_type === 'System')
										? '+'
										: trx.type.name === 'Transfer' && !['Debt',
											'Receivable'].includes(trx.type.name)
											? ''
									: '-'
								}}{{ isBalanceVisible ? formatNumber(trx.amount) : '••••' }}
							</p>
							<span class="text-2xs text-[var(--color-text-disabled)] mt-[2px]">{{ trx.time }}</span>
						</div>
					</button>
				</div>
			</div>

			<div v-if="Object.keys(groupedTransactions).length === 0"
				class="text-center py-8 sm:py-12 bg-[var(--color-surface-raised)] rounded-xl border border-[var(--color-border-default)]">
				<p class="text-2xs font-bold text-[var(--color-text-muted)] uppercase tracking-widest">
					{{ t('common.dataEmpty') }}
				</p>
			</div>

			<!-- Muat lebih banyak -->
			<div v-if="hasMore" class="flex flex-col items-center gap-2 py-4">
				<button @click="loadMore" :disabled="isLoadingMore"
					class="px-5 py-2.5 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] text-xs font-bold uppercase tracking-widest text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] disabled:opacity-50 active:scale-95 transition-all">
					{{ isLoadingMore ? t('common.loading') : t('common.loadMore') }}
				</button>
				<p v-if="loadError" class="text-2xs text-[var(--color-expense-text)]">{{ loadError }}</p>
			</div>
		</div>

		<TransactionDetailModal :show="showModal" :transaction="selectedTransaction" @close="showModal = false" />
	</AuthenticatedLayout>
</template>
