<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import BottomNav from '@/Components/BottomNav.vue'
import TransactionDetailModal from '@/Components/TransactionDetailModal.vue'
import { ref } from 'vue'
import { formatNumber, formatDate } from '@/utils/format.js'

const props = defineProps({
	wallet: Object,
	transactions: Object,
})

const isModalOpen = ref(false)
const selectedTransaction = ref({})

const getIcon = (icon) => {
	return icon && (icon.includes('.') || icon.includes('/')) ? true : false
}

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

const getTypeName = (typeName) => {
	return (
		{
			Income: 'Pemasukan',
			Expense: 'Pengeluaran',
			Transfer: 'Transfer',
			Debt: 'Hutang',
			Receivable: 'Piutang',
		}[typeName] || 'Lainnya'
	)
}
</script>

<template>
	<AuthenticatedLayout :fullWidth="true">
		<Head title="Detail Dompet" />
		<div class="p-5 w-full lg:max-w-4xl mx-auto lg:px-8 relative">
			<header class="flex justify-between items-center mb-6 pt-2">
				<h1 class="text-2xl font-bold text-white tracking-tight hidden lg:block">Detail Dompet</h1>
				<Link
					:href="route('dashboard')"
					class="w-10 h-10 rounded-full bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white active:scale-95 transition-all shadow-md">
					<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</Link>
			</header>

			<div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-7 text-center mb-10 shadow-2xl relative overflow-hidden group">
				<div class="absolute -top-10 -right-10 w-32 h-32 bg-purple-500 opacity-5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>

				<div
					class="w-20 h-20 bg-linear-to-br from-gray-800 to-gray-900 rounded-xl mx-auto flex items-center justify-center text-4xl border border-white/10 mb-4 shadow-inner overflow-hidden p-1">
					<img v-if="getIcon(wallet.icon)" :src="'/storage/' + wallet.icon" class="w-full h-full object-cover rounded-xl" />
					<span v-else>{{ wallet.icon || '💳' }}</span>
				</div>

				<p class="text-2xs font-bold text-gray-500 uppercase tracking-[0.2em] mb-1">{{ wallet.name }}</p>
				<h2 class="text-3xl font-black text-white tracking-tight mb-6">Rp {{ formatNumber(wallet.balance) }}</h2>

				<Link
					:href="route('wallets.edit', wallet.id)"
					class="inline-block bg-linear-to-br from-gray-900 to gray-800 border border-white/10 text-purple-500 text-2xs font-bold px-6 py-2.5 rounded-xl uppercase tracking-widest active:scale-95 transition-all">
					Edit Dompet
				</Link>
			</div>

			<h2 class="text-2xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1 text-center">Mutasi Terakhir</h2>

			<div class="space-y-4">
				<template v-if="transactions.data && transactions.data.length > 0">
					<button
						v-for="trx in transactions.data"
						:key="trx.id"
						type="button"
						@click="openDetailModal(trx)"
						class="w-full text-left bg-linear-to-br from-gray-800 to-gray-900 p-3 rounded-xl border border-white/10 hover:border-purple-400/30 active:scale-[0.98] transition-all relative overflow-hidden group">
						<div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

						<div class="flex items-center gap-3 relative z-10">
							<div class="w-10 h-10 rounded-xl bg-linear-to-br from-gray-900 to-gray-800 flex items-center justify-center text-lg border border-white/10 shrink-0 overflow-hidden p-0.5">
								<img
									v-if="trx.category?.icon?.includes('.')"
									:src="trx.category.icon.startsWith('http') ? trx.category.icon : '/storage/' + trx.category.icon"
									class="w-full h-full object-cover rounded-xl" />
								<span v-else>{{ trx.category?.icon || '📄' }}</span>
							</div>

							<div class="flex-1 min-w-0 pr-2">
								<p class="text-xs font-bold text-white leading-tight mb-2">
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
									<span class="text-xs text-gray-600 font-medium italic">
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
				<div v-else class="text-center py-12 bg-linear-to-br from-gray-800 to-gray-900 rounded-xl border-2 border-dashed border-white/10">
					<p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Belum ada mutasi</p>
				</div>
			</div>

			<div v-if="transactions.links && transactions.links.length > 3" class="mt-8 flex justify-center gap-1 flex-wrap">
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

		<BottomNav />
	</AuthenticatedLayout>
</template>
