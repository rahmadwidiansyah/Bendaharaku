<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import DateModal from '@/Components/DateModal.vue'
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getCategoryIconColor, getWalletIconColor } from '@/Composables/useIcon.js'
import { ref, computed } from 'vue'
import { formatNumber, formatDate, formatLocalYMD } from '@/utils/format.js'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
	wallet: Object,
	transactions: Object,
	startDate: String,
	endDate: String,
})

const formatDateRange = () => {
	return `${formatDate(props.startDate)} – ${formatDate(props.endDate)}`
}

const activeDurationLabel = computed(() => {
	const today = new Date()
	const checks = {
		thisYear: [
			formatLocalYMD(new Date(today.getFullYear(), 0, 1)),
			formatLocalYMD(new Date(today.getFullYear(), 11, 31)),
		],
		thisMonth: [
			formatLocalYMD(new Date(today.getFullYear(), today.getMonth(), 1)),
			formatLocalYMD(new Date(today.getFullYear(), today.getMonth() + 1, 0)),
		],
		lastMonth: [
			formatLocalYMD(new Date(today.getFullYear(), today.getMonth() - 1, 1)),
			formatLocalYMD(new Date(today.getFullYear(), today.getMonth(), 0)),
		],
	}
	for (const [key, [s, e]] of Object.entries(checks)) {
		if (props.startDate === s && props.endDate === e) return t('common.' + key)
	}
	return formatDateRange()
})

const isModalOpen = ref(false)
const selectedTransaction = ref({})

const formatTime = (timeString) => {
	if (!timeString) return ''
	const date = new Date(timeString)
	return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':')
}

const openDetailModal = (trx) => {
	selectedTransaction.value = {
		...trx,
		date: formatDate(trx.date),
		time: formatTime(trx.created_at),
	}
	isModalOpen.value = true
}

const closeDetailModal = () => {
	isModalOpen.value = false
}

const getTypeColor = (typeName) => {
	return (
		{
			Income: 'text-[var(--color-income-text)] bg-[var(--color-income-bg)] border-[var(--color-income-border)]',
			Expense: 'text-[var(--color-expense-text)] bg-[var(--color-expense-bg)] border-[var(--color-expense-border)]',
			Transfer: 'text-[var(--color-transfer-text)] bg-[var(--color-transfer-bg)] border-[var(--color-transfer-border)]',
			Debt: 'text-[var(--color-debt-text)] bg-[var(--color-debt-bg)] border-[var(--color-debt-border)]',
			Receivable: 'text-[var(--color-receivable-text)] bg-[var(--color-receivable-bg)] border-[var(--color-receivable-border)]',
		}[typeName] || 'text-[var(--color-text-muted)]'
	)
}

const getTypeName = (name) => ({
	Income: t('types.income'), Expense: t('types.expense'),
	Transfer: t('types.transfer'), Debt: t('types.debt'),
	Receivable: t('types.receivable'),
}[name] ?? t('types.other'))
</script>

<template>
	<AuthenticatedLayout :fullWidth="true">
		<Head :title="wallet.name" />
		<div class="p-3 sm:p-5 w-full lg:max-w-7xl mx-auto lg:px-8 relative">
			<header class="hidden lg:block mb-6 pt-4">
				<h1 class="text-2xl font-bold text-[var(--color-text-primary)] tracking-tight">{{ wallet.name }}</h1>
			</header>

			<div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-3 sm:p-5 lg:p-7 text-center mb-6 sm:mb-8 shadow-card relative overflow-hidden group">
				<div class="absolute -top-10 -right-10 w-32 h-32 bg-[var(--color-brand)] opacity-5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>

				<AppIcon :icon="wallet.icon" fallback="wallet" :class="['w-12 h-12 lg:w-16 lg:h-16 mx-auto mb-3 lg:mb-4', getWalletIconColor()]" />

				<p class="text-2xs font-bold text-[var(--color-text-muted)] uppercase tracking-[0.2em] mb-1">{{ wallet.name }}</p>
				<h2 class="text-2xl lg:text-3xl font-black tracking-tight mb-5 lg:mb-6"
					:class="parseFloat(wallet.balance) < 0 ? 'text-[var(--color-expense-text)]' : 'text-[var(--color-text-primary)]'">
					<span class="text-base lg:text-lg font-medium text-[var(--color-text-muted)] mr-1">Rp</span>{{ formatNumber(wallet.balance) }}
				</h2>

				<Link
					:href="route('wallets.edit', wallet.id)"
					class="inline-block bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] text-[var(--color-brand)] text-2xs font-bold px-5 lg:px-6 py-2 rounded-xl uppercase tracking-widest active:scale-95 transition-all hover:border-[var(--color-brand-border)]">
					{{ $t('wallet.titleEdit') }}
				</Link>
			</div>

			<div class="flex items-center justify-between gap-3 mb-3 px-1">
				<div class="min-w-0">
					<h2 class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">{{ $t('wallet.recentMutation') }}</h2>
				</div>
				<DateModal :action="route('wallets.show', wallet.id)" :start-date="startDate" :end-date="endDate" />
			</div>

			<div class="flex items-center gap-2 mb-4 lg:mb-5 px-1">
				<span class="text-[9px] font-bold text-[var(--color-brand)] bg-[var(--color-brand-subtle)] px-2 py-0.5 rounded-full border border-[var(--color-brand-border)] truncate">{{ activeDurationLabel }}</span>
				<span class="text-[9px] text-[var(--color-text-muted)] font-medium truncate">{{ formatDateRange() }}</span>
			</div>

			<div class="space-y-2 lg:space-y-3">
				<template v-if="transactions.data && transactions.data.length > 0">
					<button
						v-for="trx in transactions.data"
						:key="trx.id"
						type="button"
						@click="openDetailModal(trx)"
						class="w-full text-left bg-[var(--color-surface-raised)] p-2.5 sm:p-3 rounded-xl border border-[var(--color-border-default)] hover:border-[var(--color-brand-border)] active:scale-[0.98] transition-all relative overflow-hidden group">
						<div class="absolute inset-0 bg-[var(--color-surface-muted)]/50 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>

						<div class="flex items-center gap-2.5 lg:gap-3 relative z-10">
							<AppIcon :icon="trx.category?.icon" fallback="file-text" :class="['w-5 h-5 lg:w-6 lg:h-6 shrink-0', getCategoryIconColor(trx.type?.name)]" />

							<div class="flex-1 min-w-0 pr-2">
								<p class="text-xs font-bold text-[var(--color-text-primary)] leading-tight mb-1 lg:mb-2">
									{{ trx.category?.category_name || 'Transfer' }}
								</p>
								<div class="flex items-center gap-1.5 min-w-0">
									<span class="text-[var(--color-text-secondary)] text-2xs tracking-wide font-bold whitespace-nowrap truncate">{{ trx.source_wallet?.name }}</span>
									<svg class="w-2.5 h-2.5 text-[var(--color-brand)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
										<path d="M13 7l5 5m0 0l-5 5m5-5H6" />
									</svg>
									<span class="text-[var(--color-text-secondary)] text-2xs tracking-wide font-bold whitespace-nowrap truncate">{{ trx.destination_wallet?.name }}</span>
								</div>
							</div>

							<div class="text-right shrink-0">
								<p
									class="text-2xs font-black"
									:class="trx.destination_wallet_id === wallet.id ? 'text-[var(--color-income-text)]' : 'text-[var(--color-expense-text)]'">
									{{ trx.destination_wallet_id === wallet.id ? '+' : '-' }}{{ formatNumber(trx.amount) }}
								</p>
								<div class="flex items-center justify-end gap-1.5 mt-1">
									<span class="text-2xs text-[var(--color-text-muted)] font-medium italic">
										{{ formatDate(trx.date) }} • {{ formatTime(trx.created_at) }}
									</span>
									<span class="text-2xs uppercase tracking-widest font-black px-1 py-0.5 rounded border" :class="getTypeColor(trx.type?.name)">
										{{ getTypeName(trx.type?.name) }}
									</span>
								</div>
							</div>
						</div>
					</button>
				</template>
				<div v-else class="text-center py-10 lg:py-12 bg-[var(--color-surface-raised)] rounded-xl border-2 border-dashed border-[var(--color-border-default)]">
					<p class="text-2xs font-bold text-[var(--color-text-muted)] uppercase tracking-widest">{{ $t('wallet.emptyMutation') }}</p>
				</div>
			</div>

			<div v-if="transactions.links && transactions.links.length > 3" class="mt-6 lg:mt-8 flex justify-center gap-1 flex-wrap">
				<template v-for="(link, k) in transactions.links" :key="k">
					<Link
						v-if="link.url"
						:href="link.url"
						v-html="link.label"
						:class="['px-3 py-1 text-sm rounded-md', link.active ? 'bg-[var(--color-brand)] text-white font-bold' : 'bg-[var(--color-surface-raised)] text-[var(--color-text-secondary)] border border-[var(--color-border-default)] hover:text-[var(--color-text-primary)]']" />
					<span v-else v-html="link.label" class="px-3 py-1 text-sm rounded-md bg-[var(--color-surface-raised)] text-[var(--color-text-muted)] border border-[var(--color-border-default)]" />
				</template>
			</div>
		</div>

		<TransactionDetailModal :show="isModalOpen" :transaction="selectedTransaction" @close="closeDetailModal" />
	</AuthenticatedLayout>
</template>
