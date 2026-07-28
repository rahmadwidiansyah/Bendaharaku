<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import DateModal from '@/Components/DateModal.vue'
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
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
			Income: 'text-green-400 bg-green-400/10 border-green-400/20',
			Expense: 'text-red-400 bg-red-400/10 border-red-400/20',
			Transfer: 'text-blue-400 bg-blue-400/10 border-blue-400/20',
			Debt: 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
			Receivable: 'text-purple-400 bg-purple-400/10 border-purple-400/20',
		}[typeName] || 'text-gray-500'
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
		<div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8 relative">
			<header class="hidden lg:block mb-6 pt-4">
				<h1 class="text-2xl font-bold text-white tracking-tight">{{ wallet.name }}</h1>
			</header>

			<div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-5 lg:p-7 text-center mb-8 shadow-2xl relative overflow-hidden group">
				<div class="absolute -top-10 -right-10 w-32 h-32 bg-purple-500 opacity-5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>

				<AppIcon :icon="wallet.icon" fallback="wallet" class="w-12 h-12 lg:w-16 lg:h-16 text-purple-400 mx-auto mb-3 lg:mb-4" />

				<p class="text-2xs font-bold text-gray-500 uppercase tracking-[0.2em] mb-1">{{ wallet.name }}</p>
				<h2 class="text-2xl lg:text-3xl font-black tracking-tight mb-5 lg:mb-6"
					:class="parseFloat(wallet.balance) < 0 ? 'text-red-400' : 'text-white'">
					<span class="text-base lg:text-lg font-medium text-gray-500 mr-1">Rp</span>{{ formatNumber(wallet.balance) }}
				</h2>

				<Link
					:href="route('wallets.edit', wallet.id)"
					class="inline-block bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 text-purple-500 text-2xs font-bold px-5 lg:px-6 py-2 rounded-xl uppercase tracking-widest active:scale-95 transition-all hover:border-purple-500/30">
					{{ $t('wallet.titleEdit') }}
				</Link>
			</div>

			<div class="flex items-center justify-between gap-3 mb-3 px-1">
				<div class="min-w-0">
					<h2 class="text-2xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('wallet.recentMutation') }}</h2>
				</div>
				<DateModal :action="route('wallets.show', wallet.id)" :start-date="startDate" :end-date="endDate" />
			</div>

			<div class="flex items-center gap-2 mb-4 lg:mb-5 px-1">
				<span class="text-[9px] font-bold text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded-full border border-purple-500/20 truncate">{{ activeDurationLabel }}</span>
				<span class="text-[9px] text-gray-500 font-medium truncate">{{ formatDateRange() }}</span>
			</div>

			<div class="space-y-2 lg:space-y-3">
				<template v-if="transactions.data && transactions.data.length > 0">
					<button
						v-for="trx in transactions.data"
						:key="trx.id"
						type="button"
						@click="openDetailModal(trx)"
						class="w-full text-left bg-linear-to-br from-gray-800 to-gray-900 p-3 rounded-xl border border-white/10 hover:border-purple-400/30 active:scale-[0.98] transition-all relative overflow-hidden group">
						<div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>

						<div class="flex items-center gap-2.5 lg:gap-3 relative z-10">
							<AppIcon :icon="trx.category?.icon" fallback="file-text" class="w-5 h-5 lg:w-6 lg:h-6 text-purple-400 shrink-0" />

							<div class="flex-1 min-w-0 pr-2">
								<p class="text-xs font-bold text-white leading-tight mb-1 lg:mb-2">
									{{ trx.category?.category_name || 'Transfer' }}
								</p>
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
									:class="trx.destination_wallet_id === wallet.id ? 'text-green-400' : 'text-red-400'">
									{{ trx.destination_wallet_id === wallet.id ? '+' : '-' }}{{ formatNumber(trx.amount) }}
								</p>
								<div class="flex items-center justify-end gap-1.5 mt-1">
									<span class="text-2xs text-gray-600 font-medium italic">
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
				<div v-else class="text-center py-10 lg:py-12 bg-linear-to-br from-gray-800 to-gray-900 rounded-xl border-2 border-dashed border-white/10">
					<p class="text-2xs font-bold text-gray-500 uppercase tracking-widest">{{ $t('wallet.emptyMutation') }}</p>
				</div>
			</div>

			<div v-if="transactions.links && transactions.links.length > 3" class="mt-6 lg:mt-8 flex justify-center gap-1 flex-wrap">
				<template v-for="(link, k) in transactions.links" :key="k">
					<Link
						v-if="link.url"
						:href="link.url"
						v-html="link.label"
						:class="['px-3 py-1 text-sm rounded-md', link.active ? 'bg-purple-500 text-white font-bold' : 'bg-linear-to-br from-gray-800 to-gray-900 text-gray-400 border border-white/10 hover:text-white']" />
					<span v-else v-html="link.label" class="px-3 py-1 text-sm rounded-md bg-linear-to-br from-gray-800 to-gray-900 text-gray-400 border border-white/10" />
				</template>
			</div>
		</div>

		<TransactionDetailModal :show="isModalOpen" :transaction="selectedTransaction" @close="closeDetailModal" />
	</AuthenticatedLayout>
</template>
