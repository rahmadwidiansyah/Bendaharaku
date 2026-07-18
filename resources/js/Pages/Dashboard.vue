<script setup>
import DateModal from '@/Components/DateModal.vue'
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import Badge from '@/Components/Badge.vue'
import InsightBanner from '@/Pages/Dashboard/InsightBanner.vue'
import PortfolioCard from '@/Pages/Dashboard/PortfolioCard.vue'
import UpcomingDebts from '@/Pages/Dashboard/UpcomingDebts.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useBalanceVisibility } from '@/Composables/useBalanceVisibility'
import { useLayoutPreference } from '@/Composables/useLayoutPreference'
import { useCalendar } from '@/Composables/useCalendar'
import { formatNumber, formatCompact } from '@/utils/format.js'

const { t } = useI18n()

const { isDesktopLayout } = useLayoutPreference()
const { isBalanceVisible, toggleVisibility } = useBalanceVisibility()

const props = defineProps({
	totalPortfolio: Number,
	totalLiquid: Number,
	totalInvest: Number,
	thisMonthIncome: Number,
	thisMonthExpense: Number,
	transactions: Object,
	pinnedWallets: Array,
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

// ─── Search + filter ──────────────────────────────────────────────
const search = ref(props.filters?.search || '')
const type   = ref(props.filters?.type   || '')
const showSortModal = ref(false)

const setType = (newType) => {
	type.value = newType
	showSortModal.value = false
}

let searchTimeout = null
watch([search, type], () => {
	clearTimeout(searchTimeout)
	searchTimeout = setTimeout(() => {
		router.get(
			route('dashboard'),
			{ search: search.value, type: type.value, start_date: props.startDate, end_date: props.endDate },
			{ preserveState: true, replace: true },
		)
	}, 300)
})

// ─── History tabs ─────────────────────────────────────────────────
const collapsedDates   = ref({})
const activeHistoryTab = ref('detail')

const toggleDate = (dateKey) => {
	collapsedDates.value[dateKey] = !collapsedDates.value[dateKey]
}

// ─── Transactions grouping ────────────────────────────────────────
const groupedTransactions = computed(() => {
	const groups = {}
	if (!props.transactions?.data) return groups
	props.transactions.data.forEach((trx) => {
		if (!groups[trx.raw_date])
			groups[trx.raw_date] = { date: trx.date, transactions: [], income: 0, expense: 0 }
		groups[trx.raw_date].transactions.push(trx)

		let isIncome  = trx.type.name === 'Income'
		let isExpense = trx.type.name === 'Expense'
		if (['Debt', 'Receivable'].includes(trx.type.name)) {
			if (trx.source_wallet?.group_type === 'System') isIncome  = true
			else                                             isExpense = true
		}
		if (isIncome)  groups[trx.raw_date].income  += trx.amount
		if (isExpense) groups[trx.raw_date].expense += trx.amount
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
	initialDate: props.startDate,
	groupedTransactions,
	onNavigate: (startDate, endDate) => {
		router.get(
			route('dashboard'),
			{ search: search.value, type: type.value, start_date: startDate, end_date: endDate },
			{ preserveState: true, replace: true },
		)
	},
})

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
	const end   = new Date(props.endDate)
	if (start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear()
		&& start.getDate() === 1 && end.getDate() >= 28) {
		return start.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
	}
	return `${start.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} - ${end.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}`
})

// ─── Type helpers ─────────────────────────────────────────────────
const getTypeName = (typeName) => ({
	Income:     t('types.income'),
	Expense:    t('types.expense'),
	Transfer:   t('types.transfer'),
	Debt:       t('types.debt'),
	Receivable: t('types.receivable'),
}[typeName] ?? t('types.other'))

// Badge variant map
const typeVariant = (typeName) => ({
	Income:     'income',
	Expense:    'expense',
	Transfer:   'transfer',
	Debt:       'debt',
	Receivable: 'receivable',
}[typeName] ?? 'neutral')

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
const togglePin = (wallet) => {
	router.patch(route('wallets.set-pin', wallet.id), { state: false }, { preserveScroll: true, preserveState: true })
}

// ─── Wallet image error fallback ──────────────────────────────────
const handleImageError = (e, fallback) => {
	e.target.style.display = 'none'
	const span = document.createElement('span')
	span.innerText = fallback
	span.className = 'text-xl animate-pulse'
	e.target.parentElement?.appendChild(span)
}
</script>

<template>
	<AuthenticatedLayout :fullWidth="true">
		<Head title="Dashboard" />

		<div class="p-5 w-full mx-auto">
			<!-- INSIGHT BOX -->
			<InsightBanner
				:this-month-income="thisMonthIncome"
				:this-month-expense="thisMonthExpense"
			/>

			<!-- dual grid desktop view. (left: asset cards, right: transactions list) -->
			<div :class="isDesktopLayout ? 'lg:flex lg:gap-8 lg:items-start' : ''">
				<!-- left asset cards -->
				<div :class="isDesktopLayout ? 'lg:w-1/3 lg:sticky lg:top-5' : ''">
					<!-- Total Kekayaan Card -->
					<PortfolioCard
						:total-portfolio="totalPortfolio"
						:total-liquid="totalLiquid"
						:total-invest="totalInvest"
						:is-visible="isBalanceVisible"
						@toggle-visibility="toggleVisibility"
					/>
					<!-- PINNED WALLETS -->
					<div v-if="pinnedWallets && pinnedWallets.length > 0" class="mb-5 animate-fade-in-up delay-200">
						<div class="flex justify-between items-center mb-3 px-1 gap-3">
							<h2 class="text-2xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
								<svg class="w-3 h-3 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
								</svg>
								{{ $t('dashboard.mainWallets') }}
							</h2>
							<div class="flex-1 h-px bg-linear-to-r from-purple-500/20 to-transparent"></div>
						</div>
						<div class="grid grid-cols-2 gap-3">
							<Link
								v-for="wallet in pinnedWallets"
								:key="wallet.id"
								:href="route('wallets.show', wallet.id)"
								class="bg-linear-to-br from-gray-900 to-gray-800 p-3.5 rounded-xl border border-white/10 relative overflow-hidden active:scale-95 transition-transform group hover:border-purple-400/50">
								<div class="flex justify-between items-start mb-2">
									<div class="flex items-center gap-2 truncate">
										<div
											class="w-6 h-6 rounded-md bg-gray-900 border border-white/10 flex items-center justify-center text-2xs overflow-hidden shrink-0"
											:class="wallet.icon?.includes('.') ? 'p-1' : ''">
											<img
												v-if="wallet.icon?.includes('.')"
												:src="wallet.icon.startsWith('http') ? wallet.icon : '/storage/' + wallet.icon"
												class="w-full h-full object-contain"
												@error="(e) => handleImageError(e, wallet.keyword?.substring(0, 1) || '💳')" />
											<span v-else>{{ wallet.icon || '💳' }}</span>
										</div>
										<h3 class="text-2xs font-bold text-gray-400 uppercase tracking-widest truncate">
											{{ wallet.name }}
										</h3>
									</div>
									<button
										@click.stop.prevent="togglePin(wallet)"
										class="text-purple-500 hover:text-white transition-colors p-1 bg-white/5 rounded-full z-10 shrink-0 -mt-1 -mr-1"
										:title="$t('dashboard.unpinFromDashboard')">
										<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
											<path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5v6l1 2 1-2v-6h5v-2l-2-2z" />
										</svg>
									</button>
								</div>
								<p class="text-sm font-bold text-white tracking-tight truncate">
									<span class="text-2xs text-gray-500 mr-1">Rp</span>{{ isBalanceVisible ? formatNumber(wallet.balance) : '••••' }}
								</p>
							</Link>
						</div>
					</div>
					<!-- MINI CASHFLOW -->
					<div class="grid grid-cols-2 gap-3 mb-10 animate-fade-in-up delay-200">
						<div class="bg-linear-to-br from-green-950/20 to-gray-800 border border-green-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
							<div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center text-green-400">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
								</svg>
							</div>
							<div>
								<p class="text-2xs text-gray-500 font-bold uppercase tracking-widest">{{ $t('dashboard.income') }}</p>
								<p class="text-sm font-bold text-white tracking-tight mt-0.5">
									<span class="text-2xs text-gray-500 mr-1">Rp</span><span class="text-green-400">{{ isBalanceVisible ? formatNumber(thisMonthIncome) : '••••' }}</span>
								</p>
							</div>
						</div>
						<div class="bg-linear-to-br from-red-950/20 to-gray-800 border border-red-900/30 rounded-xl p-4 flex items-center gap-3 relative overflow-hidden group">
							<div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center text-red-400">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
								</svg>
							</div>
							<div>
								<p class="text-2xs text-gray-500 font-bold uppercase tracking-widest">{{ $t('dashboard.expense') }}</p>
								<p class="text-sm font-bold text-white tracking-tight mt-0.5">
									<span class="text-2xs text-gray-500 mr-1">Rp</span><span class="text-red-400">{{ isBalanceVisible ? formatNumber(thisMonthExpense) : '••••' }}</span>
								</p>
							</div>
						</div>
					</div>
					<!-- UPCOMING DEBTS NOTIFICATION -->
					<UpcomingDebts
						:upcoming-debts="upcomingDebts"
						:is-visible="isBalanceVisible"
					/>
				</div>
				<!-- END LEFT COLUMN -->


				<!-- RIGHT COLUMN (Desktop) -->
				<div :class="isDesktopLayout ? 'lg:w-2/3 lg:bg-gray-900/30 lg:p-6 lg:rounded-2xl lg:border lg:border-white/5' : 'mt-8'">
					<!-- TRANSACTION HISTORY HEADER-->
					<div class="flex justify-between items-center mb-4 px-1 gap-3 animate-fade-in-up delay-500">
						<h2 class="text-2xs font-bold text-white uppercase tracking-widest flex items-center gap-2 shrink-0">
							<span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
							{{ $t('dashboard.transactionHistory') }}
						</h2>
						<div class="flex-1 h-px bg-linear-to-r from-purple-500/50 to-transparent"></div>
						<div class="flex bg-gray-900 rounded-lg border border-white/10 p-0.5 shrink-0">
							<button
								@click="activeHistoryTab = 'detail'"
								:class="activeHistoryTab === 'detail' ? 'bg-purple-500 text-white' : 'text-gray-400 hover:text-white'"
								class="px-2 py-1 rounded-lg text-2xs font-bold uppercase tracking-widest transition-colors">
								{{ $t('dashboard.detailTab') }}
							</button>
							<button
								@click="activeHistoryTab = 'calendar'"
								:class="activeHistoryTab === 'calendar' ? 'bg-purple-500 text-white' : 'text-gray-400 hover:text-white'"
								class="px-2 py-1 rounded-lg text-2xs font-bold uppercase tracking-widest transition-colors">
								{{ $t('dashboard.calendarTab') }}
							</button>
						</div>
					</div>
					<!-- Search -->
					<div class="flex gap-2 mb-6 animate-fade-in-up delay-500">
						<div class="relative flex-1">
							<input
								type="text"
								v-model="search"
								:placeholder="$t('dashboard.searchPlaceholder')"
								class="w-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 text-white rounded-xl p-3.5 pl-11 text-2xs focus:ring-1 focus:ring-purple-500 transition-colors" />
							<svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
								<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
							</svg>
						</div>
						<!-- Date Filter -->
						<DateModal :action="route('dashboard')" :start-date="startDate" :end-date="endDate" />
					</div>

					<!-- Month Navigation & Filter Type-->
					<div class="flex justify-between items-center mb-4 animate-fade-in-up delay-500">
						<div class="flex items-center gap-2 sm:gap-3">
							<button @click="prevMonth" class="p-1 sm:p-1.5 rounded-full bg-gray-900 border border-white/10 text-gray-400 hover:text-white transition-colors active:scale-95">
								<svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
								</svg>
							</button>
							<p class="text-2xs font-bold text-purple-500 uppercase tracking-widest flex flex-col items-center">
								<span class="text-white text-sm tracking-tight">{{ formattedPeriod }}</span>
							</p>
							<button
								@click="nextMonth"
								:disabled="!canGoNextMonth"
								class="p-1 sm:p-1.5 rounded-full border transition-colors active:scale-95"
								:class="canGoNextMonth ? 'bg-gray-900 border-white/10 text-gray-400 hover:text-white' : 'bg-gray-900/50 border-white/5 text-gray-600 cursor-not-allowed'">
								<svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
								</svg>
							</button>
						</div>
						<button
							type="button"
							@click="showSortModal = true"
							class="flex items-center gap-1.5 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 px-3 py-1.5 rounded-full text-2xs font-bold uppercase tracking-widest active:scale-95 transition-all"
							:class="type ? 'text-purple-500 border-purple-500/50' : 'text-gray-500'">
							<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25" />
							</svg>
							{{ type ? getTypeName(type) : $t('types.all') }}
						</button>
					</div>

					<!-- Kalender Tab Active -->
					<div v-if="activeHistoryTab === 'calendar'" class="animate-fade-in-up delay-500 mb-8">
						<div class="bg-linear-to-br from-gray-900 to-gray-800 p-3 sm:p-4 rounded-xl border border-white/10 mb-5 shadow-lg w-full">
							<div class="flex justify-between items-center mb-3">
								<button @click="prevMonth" class="p-1 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors">
									<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
									</svg>
								</button>
								<h3 class="text-2xs font-bold text-white uppercase tracking-widest">
									{{ calendarMonthName }}
								</h3>
								<button
									@click="nextMonth"
									:disabled="!canGoNextMonth"
									class="p-1 rounded-lg transition-colors"
									:class="canGoNextMonth ? 'bg-white/5 hover:bg-white/10 text-white' : 'text-gray-600 cursor-not-allowed'">
									<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
									</svg>
								</button>
							</div>
							<!-- Pemasukan, Total, Pengeluaran filter -->
							<div class="flex justify-center mb-3">
								<div class="flex bg-gray-900 rounded-xl border border-white/10 p-0.5">
									<button
										@click="calendarFilter = 'income'"
										:class="calendarFilter === 'income' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'text-gray-400 hover:text-white border border-transparent'"
										class="px-2.5 py-1 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all">
										{{ $t('dashboard.calendarFilter.income') }}
									</button>
									<button
										@click="calendarFilter = 'total'"
										:class="calendarFilter === 'total' ? 'bg-purple-500 text-white border border-purple-500' : 'text-gray-400 hover:text-white border border-transparent'"
										class="px-2.5 py-1 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all">
										{{ $t('dashboard.calendarFilter.total') }}
									</button>
									<button
										@click="calendarFilter = 'expense'"
										:class="calendarFilter === 'expense' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'text-gray-400 hover:text-white border border-transparent'"
										class="px-2.5 py-1 rounded-lg text-2xs font-bold uppercase tracking-widest transition-all">
										{{ $t('dashboard.calendarFilter.expense') }}
									</button>
								</div>
							</div>
							<!-- Day name headers -->
							<div class="grid grid-cols-7 gap-1 text-center mb-1.5">
								<div v-for="day in calendarDayNames" :key="day" class="text-[9px] sm:text-2xs font-bold text-gray-500 uppercase tracking-widest py-1">
									{{ day }}
								</div>
							</div>
							<!-- Calendar Grid -->
							<div class="grid grid-cols-7 gap-1 sm:gap-1.5">
								<div v-for="(day, index) in calendarDays" :key="index" class="h-12 sm:h-14">
									<button
										v-if="!day.empty"
										@click="selectDate(day.dateStr)"
										class="w-full h-full rounded-md flex flex-col items-center justify-center p-0.5 border transition-all relative overflow-visible"
										:class="[
											selectedCalendarDate === day.dateStr ? 'bg-purple-500/20 border-purple-500 z-10' : 'bg-white/5 border-transparent hover:bg-white/10 z-0 hover:z-10',
											day.dateStr === getLocalYMD(new Date()) && selectedCalendarDate !== day.dateStr ? 'border-white/20' : '',
										]">
										<span class="text-2xs font-bold leading-none mb-0.5" :class="selectedCalendarDate === day.dateStr ? 'text-purple-400' : 'text-gray-300'">{{ day.day }}</span>
										<div v-if="day.largestType" class="flex flex-col items-center overflow-visible">
											<span class="text-2xs font-bold leading-none tracking-tight whitespace-nowrap" :class="day.largestType === 'income' ? 'text-green-400' : 'text-red-400'">
												{{ formatCompact(day.largestAmount) }}
											</span>
										</div>
									</button>
								</div>
							</div>
						</div>
					</div>

					<!-- HISTORY LIST -->
					<div class="space-y-4 animate-fade-in-up delay-500 mb-8">
						<!-- Calendar Tab: Grid Selection Group History-->
						<div v-if="activeHistoryTab === 'calendar'" class="flex items-center gap-2 mb-3 px-1">
							<svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
								<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
							</svg>
							<h3 class="text-2xs font-bold text-gray-300 uppercase tracking-widest">
								{{ selectedDateFormatted }}
							</h3>
						</div>

						<!-- Group Transaction Cards per Date -->
						<div
							v-for="(group, dateKey) in visibleTransactions"
							:key="dateKey"
							class="bg-linear-to-br from-gray-900 to-gray-800 p-3 rounded-xl border border-white/5 transition-all duration-300 shadow-lg">
							<div
								@click="activeHistoryTab === 'detail' && toggleDate(dateKey)"
								class="flex justify-between items-center px-1 border-b pb-2 transition-colors group/header"
								:class="[
									activeHistoryTab === 'detail' ? 'cursor-pointer' : '',
									activeHistoryTab === 'detail' && collapsedDates[dateKey] ? 'border-transparent' : 'border-purple-500/30',
								]">
								<h3 class="text-2xs font-bold text-purple-500 uppercase tracking-widest flex items-center gap-1.5 group-hover/header:text-purple-400 transition-colors">
									<svg
										v-if="activeHistoryTab === 'detail'"
										class="w-3.5 h-3.5 transition-transform duration-300"
										:class="!collapsedDates[dateKey] ? 'rotate-90' : ''"
										fill="none"
										viewBox="0 0 24 24"
										stroke="currentColor"
										stroke-width="3">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
									</svg>
									{{ group.date }}
								</h3>
								<div class="text-2xs font-bold flex gap-2.5 tracking-wide">
									<span v-if="group.income > 0" class="text-green-400/90">+{{ formatNumber(group.income) }}</span>
									<span v-if="group.expense > 0" class="text-red-400/90">-{{ formatNumber(group.expense) }}</span>
								</div>
							</div>

							<!-- Main History List -->
							<div
								class="grid transition-all duration-300 ease-in-out"
								:style="{
									gridTemplateRows: activeHistoryTab === 'detail' && collapsedDates[dateKey] ? '0fr' : '1fr',
								}">
								<div class="overflow-hidden transition-all duration-300" :class="activeHistoryTab === 'detail' && collapsedDates[dateKey] ? 'opacity-0' : 'opacity-100'">
									<div class="space-y-2.5 pt-3">
										<button
											v-for="trx in group.transactions"
											:key="trx.id"
											@click="openModal(trx)"
											class="w-full text-left bg-linear-to-br from-gray-800 to-gray-900 p-3 rounded-xl border border-white/10 hover:border-purple-400/30 active:scale-[0.98] transition-all relative overflow-hidden group">
											<div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

											<div class="flex items-center gap-3 relative z-10">
												<div class="w-10 h-10 rounded-xl bg-gradient flex items-center justify-center text-lg border border-white/10 shrink-0 overflow-hidden p-0.5">
													<img
														v-if="trx.category?.icon?.includes('.')"
														:src="trx.category.icon.startsWith('http') ? trx.category.icon : '/storage/' + trx.category.icon"
														class="w-full h-full object-cover rounded-xl" />
													<span v-else>{{ trx.category?.icon || '📄' }}</span>
												</div>

												<div class="flex-1 min-w-0 pr-2">
													<div class="flex items-center gap-1.5 mb-1">
														<p class="text-xs font-bold text-white leading-tight">
															{{ trx.category?.category_name || 'Transfer' }}
														</p>
														<!-- Indikator DRAFT -->
														<span
															v-if="!trx.is_cleared"
															class="shrink-0 inline-flex items-center gap-0.5 text-2xs font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-amber-500/15 text-amber-400 border border-amber-500/30">
															<svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
																<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
															</svg>
															Draft
														</span>
													</div>
													<div class="flex items-center gap-1.5 min-w-0">
														<span class="text-gray-400 text-2xs tracking-wide font-bold whitespace-nowrap truncate">{{ trx.source_wallet?.name }}</span>
														<svg class="w-2.5 h-2.5 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
															<path d="M13 7l5 5m0 0l-5 5m5-5H6" />
														</svg>
														<span class="text-gray-400 text-2xs tracking-wide font-bold whitespace-nowrap truncate">{{ trx.destination_wallet?.name }}</span>
													</div>
												</div>

												<div class="text-right shrink-0">
													<p
														class="text-2xs font-black"
														:class="
															trx.type.name === 'Income' || (['Debt', 'Receivable'].includes(trx.type.name) && trx.source_wallet?.group_type === 'System')
																? 'text-green-400'
																: trx.type.name === 'Transfer' && !['Debt', 'Receivable'].includes(trx.type.name)
																	? 'text-blue-400'
																	: 'text-red-400'
														">
														{{
															trx.type.name === 'Income' || (['Debt', 'Receivable'].includes(trx.type.name) && trx.source_wallet?.group_type === 'System')
																? '+'
																: trx.type.name === 'Transfer' && !['Debt', 'Receivable'].includes(trx.type.name)
																	? ''
																	: '-'
														}}{{ formatNumber(trx.amount) }}
													</p>
													<div class="flex items-center justify-end gap-1.5 mt-1">
														<span class="text-xs text-gray-600 font-medium italic">
															{{ trx.time }}
														</span>
														<Badge :variant="typeVariant(trx.type.name)" size="sm">
															{{ getTypeName(trx.type.name) }}
														</Badge>
													</div>
												</div>
											</div>
										</button>
									</div>
								</div>
							</div>
						</div>

						<!-- Empty state -->
						<div
							v-if="Object.keys(visibleTransactions).length === 0"
							class="text-center py-12 bg-linear-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 relative overflow-hidden group">
							<p class="text-2xs font-bold text-gray-500 uppercase tracking-widest relative z-10">
								{{ activeHistoryTab === 'calendar' ? $t('dashboard.noTransactions') : $t('common.dataEmpty') }}
							</p>
						</div>
					</div>
				</div>
				<!-- END RIGHT COLUMN -->
			</div>

			<!-- Pop Up Filter Type -->
			<div v-if="showSortModal" class="fixed inset-0 z-60 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showSortModal = false">
				<div class="w-full max-w-sm bg-linear-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 p-6 animate-pop-in relative">
					<button type="button" @click="showSortModal = false" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
						<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
						</svg>
					</button>

					<h3 class="text-sm font-bold text-white mb-6 text-center uppercase tracking-widest">{{ $t('dashboard.filterType') }}</h3>
					<div class="grid grid-cols-2 gap-3">
						<button
							@click="setType('')"
							class="col-span-2 py-3 rounded-xl border border-white/10 text-2xs font-bold uppercase tracking-widest transition-all"
							:class="!type ? 'bg-purple-600 text-white' : 'bg-linear-to-br from-gray-900 to-gray-800 text-gray-300'">
							{{ $t('types.all') }}
						</button>
						<button
							v-for="key in ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable']"
							:key="key"
							@click="setType(key)"
							class="py-3 rounded-xl border border-white/10 text-2xs font-bold uppercase tracking-widest transition-all"
							:class="type === key ? 'bg-linear-to-br from-purple-800 to-purple-500 text-white' : 'bg-linear-to-br from-gray-900 to-gray-800 text-gray-300'">
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
