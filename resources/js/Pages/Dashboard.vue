<script setup>
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import PortfolioCard from '@/Pages/Dashboard/PortfolioCard.vue'
import UpcomingDebts from '@/Pages/Dashboard/UpcomingDebts.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { useCalendar } from '@/Composables/useCalendar'
import { formatNumber, formatCompact } from '@/utils/format.js'
import AppIcon from '@/Components/AppIcon.vue'
import { getCategoryIconColor } from '@/Composables/useIcon.js'

const { t } = useI18n()

const { isDesktopLayout } = useLayoutPreference()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

// Map dipindah keluar dari setup() supaya tidak dibuat ulang tiap kali
// getTypeName/typeVariant dipanggil (dipanggil berkali-kali per transaksi per render).
const TYPE_NAME_KEYS = {
	Income: 'types.income',
	Expense: 'types.expense',
	Transfer: 'types.transfer',
	Debt: 'types.debt',
	Receivable: 'types.receivable',
}

const props = defineProps({
	totalPortfolio: Number,
	totalLiquid: Number,
	totalInvest: Number,
	totalHutang: Number,
	totalPiutang: Number,
	thisMonthIncome: Number,
	thisMonthExpense: Number,
	transactions: Object,
	pinnedWallets: Array,
	wallets: Array,
	startDate: String,
	endDate: String,
	filters: Object,
	upcomingDebts: Array,
})

// ─── Transaction modal ────────────────────────────────────────────
const showModal = ref(false)
const selectedTransaction = ref(null)

const openModal = (trx) => {
	selectedTransaction.value = trx
	showModal.value = true
}

// ─── Filter type ──────────────────────────────────────────────────
const type = ref(props.filters?.type || '')
const showSortModal = ref(false)

const setType = (newType) => {
	type.value = newType
	showSortModal.value = false
}

watch(type, () => {
	router.get(
		route('dashboard'),
		{ type: type.value, start_date: props.startDate, end_date: props.endDate },
		{ preserveState: true, replace: true },
	)
})

// ─── History tabs ─────────────────────────────────────────────────
const collapsedDates = ref({})
const activeHistoryTab = ref('detail')

const toggleDate = (dateKey) => {
	collapsedDates.value[dateKey] = !collapsedDates.value[dateKey]
}

// ─── Transactions grouping ────────────────────────────────────────
// SESUDAH
const groupedTransactions = computed(() => {
	const groups = {}
	if (!props.transactions?.data) return groups
	props.transactions.data.forEach((trx) => {
		// Normalisasi: ambil 10 karakter pertama ("YYYY-MM-DD") supaya konsisten
		// walau raw_date kebawa format ISO dengan jam (mis. dari serialisasi Carbon).
		const dateKey = typeof trx.raw_date === 'string' ? trx.raw_date.slice(0, 10) : trx.raw_date

		if (!groups[dateKey])
			groups[dateKey] = { date: trx.date, transactions: [], income: 0, expense: 0 }
		groups[dateKey].transactions.push(trx)

		let isIncome  = trx.type.name === 'Income'
		let isExpense = trx.type.name === 'Expense'
		if (['Debt', 'Receivable'].includes(trx.type.name)) {
			if (trx.source_wallet?.group_type === 'System') isIncome  = true
			else                                             isExpense = true
		}
		if (isIncome)  groups[dateKey].income  += trx.amount
		if (isExpense) groups[dateKey].expense += trx.amount
	})
	return groups
})

// ─── Calendar composable ──────────────────────────────────────────
const {
	getLocalYMD,
	selectedCalendarDate,
	currentCalendarMonth,
	calendarFilter,
	calendarMonthName,
	canGoNextMonth,
	selectedDateFormatted,
	calendarDays,
	prevMonth,
	nextMonth,
	selectDate,
} = useCalendar({
	initialDate: computed(() => props.startDate),
	groupedTransactions,
	onNavigate: (startDate, endDate) => {
		router.get(
			route('dashboard'),
			{ type: type.value, start_date: startDate, end_date: endDate },
			{ preserveState: true, replace: true },
		)
	},
})

// Dihitung sekali per render lewat computed, bukan dipanggil ulang
// di dalam v-for untuk tiap sel kalender (bisa sampai 42x per render).
const todayYMD = computed(() => getLocalYMD(new Date()))

const selectedDateTransactions = computed(() => {
	const grp = groupedTransactions.value[selectedCalendarDate.value]
	return grp ? { [selectedCalendarDate.value]: grp } : {}
})

const visibleTransactions = computed(() =>
	activeHistoryTab.value === 'calendar' ? selectedDateTransactions.value : groupedTransactions.value
)

// ─── Period label ─────────────────────────────────────────────────
const formattedPeriod = computed(() => {
	const start = new Date(props.startDate)
	const end = new Date(props.endDate)
	if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()
		&& start.getDate() === 1 && end.getDate() >= 28) {
		return start.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
	}
	return `${start.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} - ${end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}`
})

// ─── Type helpers ─────────────────────────────────────────────────
const getTypeName = (typeName) => t(TYPE_NAME_KEYS[typeName] ?? 'types.other')

const masked = (v) => isBalanceVisible.value ? formatNumber(v) : '••••••••'

const getWalletName = (trx) => {
	const typeName = trx.type?.name
	if (typeName === 'Transfer') {
		return [trx.source_wallet?.name, trx.destination_wallet?.name].filter(Boolean).join(' → ')
	}
	const isIncomeLike = typeName === 'Income'
		|| (['Debt', 'Receivable'].includes(typeName) && trx.source_wallet?.group_type === 'System')
	return isIncomeLike ? (trx.destination_wallet?.name || '') : (trx.source_wallet?.name || '')
}

// ─── Calendar day names (reactive to locale) ──────────────────────
const calendarDayNames = computed(() => [
	t('dashboard.calendar.sun'),
	t('dashboard.calendar.mon'),
	t('dashboard.calendar.tue'),
	t('dashboard.calendar.wed'),
	t('dashboard.calendar.thu'),
	t('dashboard.calendar.fri'),
	t('dashboard.calendar.sat'),
])

// ─── Wallet pin toggle ────────────────────────────────────────────
</script>

<template>
	<AuthenticatedLayout :fullWidth="true">

		<Head title="Dashboard" />

		<div class="p-3 sm:p-5 w-full mx-auto">
			<!-- dual grid desktop view. (left: asset cards, right: transactions list) -->
			<div :class="isDesktopLayout ? 'lg:flex lg:gap-8 lg:items-start' : ''">
				<!-- left asset cards -->
				<div :class="isDesktopLayout ? 'lg:w-1/3 lg:sticky lg:top-5' : ''">
					<!-- Total Kekayaan Card -->
					<PortfolioCard :total-portfolio="totalPortfolio" :is-visible="isBalanceVisible"
						:wallets="wallets" :total-hutang="totalHutang" :total-piutang="totalPiutang"
						@toggle-visibility="toggleVisibility" />
					<!-- MINI CASHFLOW -->
					<div class="grid grid-cols-2 gap-3 sm:gap-4 mb-5 sm:mb-8 animate-fade-in-up delay-200">
						<div
							class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-3 sm:p-4 flex items-center gap-3 relative overflow-hidden group">
							<div
								class="w-8 h-8 rounded-full bg-[var(--color-income-bg)] flex items-center justify-center text-[var(--color-income-text)] shrink-0">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
									stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round"
										d="M19 14l-7 7m0 0l-7-7m7 7V3" />
								</svg>
							</div>
							<div class="min-w-0">
								<p class="text-2xs text-[var(--color-text-secondary)] font-bold uppercase tracking-widest">{{
									$t('dashboard.income') }}</p>
								<p class="text-sm font-bold text-[var(--color-text-primary)] tracking-tight mt-0.5 truncate">
									<span class="text-2xs text-[var(--color-text-secondary)] mr-1">Rp</span><span class="text-[var(--color-income-text)]">{{
										isBalanceVisible ? formatNumber(thisMonthIncome) : '••••' }}</span>
								</p>
							</div>
						</div>
						<div
							class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-3 sm:p-4 flex items-center gap-3 relative overflow-hidden group">
							<div
								class="w-8 h-8 rounded-full bg-[var(--color-expense-bg)] flex items-center justify-center text-[var(--color-expense-text)] shrink-0">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
									stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round"
										d="M5 10l7-7m0 0l7 7m-7-7v18" />
								</svg>
							</div>
							<div class="min-w-0">
								<p class="text-2xs text-[var(--color-text-secondary)] font-bold uppercase tracking-widest">{{
									$t('dashboard.expense') }}</p>
								<p class="text-sm font-bold text-[var(--color-text-primary)] tracking-tight mt-0.5 truncate">
									<span class="text-2xs text-[var(--color-text-secondary)] mr-1">Rp</span><span class="text-[var(--color-expense-text)]">{{
										isBalanceVisible ? formatNumber(thisMonthExpense) : '••••' }}</span>
								</p>
							</div>
						</div>
					</div>
					<!-- UPCOMING DEBTS NOTIFICATION -->
					<UpcomingDebts :upcoming-debts="upcomingDebts" :is-visible="isBalanceVisible" />
				</div>
				<!-- END LEFT COLUMN -->


				<!-- RIGHT COLUMN (Desktop) -->
				<div
					:class="isDesktopLayout ? 'lg:w-2/3 lg:bg-[var(--color-surface-muted)]/30 lg:p-6 lg:rounded-2xl lg:border lg:border-[var(--color-border-subtle)]' : 'mt-4 sm:mt-8'">
					<!-- TRANSACTION HISTORY HEADER-->
					<div class="mb-3 sm:mb-4 px-1 animate-fade-in-up delay-500">
						<div class="flex items-center gap-2 mb-2">
							<h2
								class="text-2xs font-bold text-[var(--color-text-primary)] uppercase tracking-widest flex items-center gap-2 shrink-0">
								<span class="w-1.5 h-1.5 rounded-full bg-[var(--color-brand)]"></span>
								{{ $t('dashboard.transactionHistory') }}
							</h2>
							<div class="flex-1 h-px bg-linear-to-r from-[var(--color-brand-border)] to-transparent"></div>
						</div>
						<div class="grid grid-cols-2 gap-1 p-1 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)]/70 shadow-sm shadow-black/20">
							<button @click="activeHistoryTab = 'detail'"
								:class="activeHistoryTab === 'detail' ? 'text-white bg-[var(--color-brand)] shadow-sm' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-white/5'"
								class="px-3 py-2 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all text-center">
								{{ $t('dashboard.detailTab') }}
							</button>
							<button @click="activeHistoryTab = 'calendar'"
								:class="activeHistoryTab === 'calendar' ? 'text-white bg-[var(--color-brand)] shadow-sm' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-white/5'"
								class="px-3 py-2 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all text-center">
								{{ $t('dashboard.calendarTab') }}
							</button>
						</div>
					</div>
					<!-- Month Navigation & Filter Type-->
					<div class="grid grid-cols-[1fr_auto] gap-2 mb-3 sm:mb-4 animate-fade-in-up delay-500 px-1 items-center">
						<div class="flex items-center justify-between gap-2 min-w-0 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)]/70 px-2 py-1.5 shadow-sm shadow-black/20">
							<button @click="prevMonth"
								class="p-1.5 rounded-lg bg-white/5 border border-[var(--color-border-default)] text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors active:scale-95 shrink-0">
								<svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24"
									stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
								</svg>
							</button>
							<p class="min-w-0 flex-1 text-center">
								<span class="block text-[10px] font-bold text-[var(--color-brand)] uppercase tracking-[0.18em]">Periode</span>
								<span class="block text-sm font-bold text-[var(--color-text-primary)] tracking-tight leading-tight truncate">{{ formattedPeriod }}</span>
							</p>
							<button @click="nextMonth" :disabled="!canGoNextMonth"
								class="p-1.5 rounded-lg border transition-colors active:scale-95 shrink-0"
								:class="canGoNextMonth ? 'bg-white/5 border-[var(--color-border-default)] text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]' : 'bg-white/[0.03] border-[var(--color-border-subtle)] text-[var(--color-text-muted)] cursor-not-allowed'">
								<svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24"
									stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
								</svg>
							</button>
						</div>
						<button type="button" @click="showSortModal = true"
							class="flex items-center justify-center gap-1.5 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)]/70 px-3 py-2 text-2xs font-bold uppercase tracking-widest active:scale-95 transition-all shadow-sm shadow-black/20"
							:class="type ? 'text-[var(--color-brand)] border-[var(--color-brand-border)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'">
							<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
								stroke-width="2.5">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25" />
							</svg>
							{{ type ? getTypeName(type) : $t('types.all') }}
						</button>
					</div>

					<!-- Kalender Tab Active -->
					<div v-if="activeHistoryTab === 'calendar'" class="animate-fade-in-up delay-500 mb-4 sm:mb-8 px-1">
						<div
							class="bg-[var(--color-surface-raised)] p-2.5 sm:p-4 rounded-xl border border-[var(--color-border-default)] mb-3 sm:mb-5 shadow-lg shadow-black/20 w-full">
							<!-- Pemasukan, Total, Pengeluaran filter -->
							<div class="flex justify-center mb-2.5 sm:mb-3">
								<div class="flex w-full bg-[var(--color-surface-muted)] rounded-xl border border-[var(--color-border-default)] p-0.5">
									<button @click="calendarFilter = 'income'"
										:class="calendarFilter === 'income' ? 'bg-[var(--color-income-bg)] text-[var(--color-income-text)] border border-[var(--color-income-border)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] border border-transparent'"
										class="flex-1 px-2 py-1.5 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all text-center">
										{{ $t('dashboard.calendarFilter.income') }}
									</button>
									<button @click="calendarFilter = 'total'"
										:class="calendarFilter === 'total' ? 'text-white border bg-[var(--color-brand)] border-[var(--color-brand)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] border border-transparent'"
										class="flex-1 px-2 py-1.5 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all text-center">
										{{ $t('dashboard.calendarFilter.total') }}
									</button>
									<button @click="calendarFilter = 'expense'"
										:class="calendarFilter === 'expense' ? 'bg-[var(--color-expense-bg)] text-[var(--color-expense-text)] border border-[var(--color-expense-border)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] border border-transparent'"
										class="flex-1 px-2 py-1.5 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all text-center">
										{{ $t('dashboard.calendarFilter.expense') }}
									</button>
								</div>
							</div>
							<!-- Day name headers -->
							<div class="grid grid-cols-7 gap-1 text-center mb-1.5">
								<div v-for="day in calendarDayNames" :key="day"
									class="text-[9px] sm:text-2xs font-bold text-[var(--color-text-muted)] uppercase tracking-widest py-1">
									{{ day }}
								</div>
							</div>
							<!-- Calendar Grid -->
							<div class="grid grid-cols-7 gap-1">
								<div v-for="(day, index) in calendarDays" :key="index" class="h-[2.875rem] sm:h-14">
									<button v-if="!day.empty" @click="selectDate(day.dateStr)"
										class="w-full h-full rounded-md flex flex-col items-center justify-center p-0.5 border transition-all relative overflow-visible"
										:class="[
											selectedCalendarDate === day.dateStr ? 'bg-[var(--color-brand-subtle)] border-[var(--color-brand)] z-10' : 'bg-white/5 border-transparent hover:bg-white/10 z-0 hover:z-10',
											day.dateStr === todayYMD && selectedCalendarDate !== day.dateStr ? 'border-[var(--color-border-strong)]' : '',
										]">
										<span class="text-2xs font-bold leading-none mb-0.5"
											:class="selectedCalendarDate === day.dateStr ? 'text-[var(--color-brand)]' : 'text-[var(--color-text-secondary)]'">{{
												day.day }}</span>
										<div v-if="day.largestType" class="flex flex-col items-center overflow-visible">
											<span
												class="text-2xs font-bold leading-none tracking-tight whitespace-nowrap"
												:class="day.largestType === 'income' ? 'text-[var(--color-income-text)]' : 'text-[var(--color-expense-text)]'">
												{{ day.largestType === 'income' ? '+' : '-' }}{{
													formatCompact(day.largestAmount) }}
											</span>
										</div>
									</button>
								</div>
							</div>
						</div>
					</div>

					<!-- HISTORY LIST -->
					<div class="space-y-2 sm:space-y-4 animate-fade-in-up delay-500 mb-4 sm:mb-8">
						<!-- Calendar Tab: Grid Selection Group History-->
						<div v-if="activeHistoryTab === 'calendar'" class="flex items-center gap-2 mb-2 sm:mb-3 px-1">
							<svg class="w-4 h-4 text-[var(--color-brand)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
								stroke-width="2">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
							</svg>
							<h3 class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">
								{{ selectedDateFormatted }}
							</h3>
						</div>

						<!-- Group Transaction per Date -->
						<div v-for="(group, dateKey) in visibleTransactions" :key="dateKey" class="border-b border-[var(--color-border-subtle)] last:border-b-0 pb-1 sm:pb-2">
							<div @click="activeHistoryTab === 'detail' && toggleDate(dateKey)"
								class="flex justify-between items-center py-2.5 px-1 transition-colors group/header"
								:class="[
									activeHistoryTab === 'detail' ? 'cursor-pointer' : '',
								]">
								<h3
									class="text-2xs font-bold text-[var(--color-brand)] uppercase tracking-widest flex items-center gap-1.5 transition-colors group-hover/header:opacity-90">
									<svg v-if="activeHistoryTab === 'detail'"
										class="w-3.5 h-3.5 transition-transform duration-300"
										:class="!collapsedDates[dateKey] ? 'rotate-90' : ''" fill="none"
										viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
									</svg>
									{{ group.date }}
								</h3>
								<div class="text-2xs font-bold flex gap-2.5 tracking-wide">
									<span v-if="group.income > 0" class="text-[var(--color-income-text)]">+{{ masked(group.income) }}</span>
									<span v-if="group.expense > 0" class="text-[var(--color-expense-text)]">-{{ masked(group.expense) }}</span>
								</div>
							</div>

							<!-- Transaction rows -->
							<div class="grid transition-all duration-300 ease-in-out" :style="{
								gridTemplateRows: activeHistoryTab === 'detail' && collapsedDates[dateKey] ? '0fr' : '1fr',
							}">
								<div class="overflow-hidden transition-all duration-300"
									:class="activeHistoryTab === 'detail' && collapsedDates[dateKey] ? 'opacity-0' : 'opacity-100'">
									<div>
										<button v-for="(trx, trxIdx) in group.transactions" :key="trx.id" @click="openModal(trx)"
											class="w-full text-left flex items-center gap-2.5 sm:gap-3 py-2.5 sm:py-3 px-1 transition-colors hover:bg-white/[0.03] active:bg-white/[0.06] border-b border-[var(--color-border-subtle)] last:border-b-0">
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
							</div>
						</div>

						<!-- Empty state -->
						<div v-if="Object.keys(visibleTransactions).length === 0"
							class="text-center py-8 sm:py-12 bg-[var(--color-surface-raised)] rounded-xl border border-[var(--color-border-default)] relative overflow-hidden group">
							<p class="text-2xs font-bold text-[var(--color-text-muted)] uppercase tracking-widest relative z-10">
								{{ activeHistoryTab === 'calendar' ? $t('dashboard.noTransactions') :
									$t('common.dataEmpty') }}
							</p>
						</div>
					</div>
				</div>
				<!-- END RIGHT COLUMN -->
			</div>

			<!-- Pop Up Filter Type -->
			<div v-if="showSortModal"
				class="fixed inset-0 z-60 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
				@click.self="showSortModal = false">
				<div
					class="w-full max-w-md sm:max-w-lg md:max-w-xl bg-[var(--color-surface-raised)] rounded-lg sm:rounded-xl border border-[var(--color-border-default)] p-4 sm:p-6 animate-pop-in relative">
					<button type="button" @click="showSortModal = false"
						class="absolute top-3 sm:top-4 right-3 sm:right-4 text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors">
						<svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
							stroke-width="2.5">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
						</svg>
					</button>

					<h3
						class="text-xs sm:text-sm font-bold text-[var(--color-text-primary)] mb-4 sm:mb-6 text-center uppercase tracking-widest">
						{{ $t('dashboard.filterType') }}</h3>
					<div class="grid grid-cols-2 gap-2 sm:gap-3">
						<button @click="setType('')"
							class="col-span-2 py-2.5 sm:py-3 rounded-lg sm:rounded-xl border border-[var(--color-border-default)] text-2xs font-bold uppercase tracking-widest transition-all"
							:class="!type ? 'bg-[var(--color-brand)] text-white' : 'bg-[var(--color-surface-muted)] text-[var(--color-text-secondary)]'">
							{{ $t('types.all') }}
						</button>
						<button v-for="key in ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable']" :key="key"
							@click="setType(key)"
							class="py-2.5 sm:py-3 rounded-lg sm:rounded-xl border border-[var(--color-border-default)] text-2xs font-bold uppercase tracking-widest transition-all"
							:class="type === key ? 'bg-[var(--color-brand)] text-white' : 'bg-[var(--color-surface-muted)] text-[var(--color-text-secondary)]'">
							{{ getTypeName(key) }}
						</button>
					</div>
				</div>
			</div>

		</div>

		<TransactionDetailModal :show="showModal" :transaction="selectedTransaction" @close="showModal = false" />

	</AuthenticatedLayout>
</template>

<!-- Animasi sudah terdefinisi di app.css — hapus semua duplikasi scoped -->
<style scoped>
/* delay utilities — Tailwind JIT tidak handle ini, tetap perlu lokal */
.delay-100 {
	animation-delay: 100ms;
}

.delay-200 {
	animation-delay: 200ms;
}

.delay-300 {
	animation-delay: 300ms;
}

.delay-400 {
	animation-delay: 400ms;
}

.delay-500 {
	animation-delay: 500ms;
}
</style>