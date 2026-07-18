<script setup>
import { ref, computed } from 'vue'
import TransactionDetailModal from './TransactionDetailModal.vue'

const props = defineProps({
    component: { type: Object, required: true },
    metadata:  { type: Object, default: () => ({}) },
})

const showDetail = ref(false)

const trx = computed(() => props.component.transaction ?? {})

const typeConfig = computed(() => ({
    income:   { label: 'Pemasukan',      icon: '↑', color: 'text-emerald-400', bg: 'bg-emerald-500/8',  border: 'border-emerald-500/15', badge: 'bg-emerald-500/12 text-emerald-300 border-emerald-500/20' },
    expense:  { label: 'Pengeluaran',    icon: '↓', color: 'text-red-400',     bg: 'bg-red-500/8',      border: 'border-red-500/15',     badge: 'bg-red-500/12 text-red-300 border-red-500/20' },
    transfer: { label: 'Transfer',       icon: '⇄', color: 'text-blue-400',    bg: 'bg-blue-500/8',     border: 'border-blue-500/15',    badge: 'bg-blue-500/12 text-blue-300 border-blue-500/20' },
    debt:     { label: 'Hutang/Piutang', icon: '🤝', color: 'text-amber-400',  bg: 'bg-amber-500/8',    border: 'border-amber-500/15',   badge: 'bg-amber-500/12 text-amber-300 border-amber-500/20' },
    other:    { label: 'Transaksi',      icon: '•', color: 'text-gray-400',    bg: 'bg-gray-500/8',     border: 'border-gray-500/15',    badge: 'bg-gray-500/12 text-gray-400 border-gray-500/20' },
}[trx.value.type_key ?? 'other'] ?? { label: 'Transaksi', icon: '•', color: 'text-gray-400', bg: 'bg-gray-500/8', border: 'border-gray-500/15', badge: 'bg-gray-500/12 text-gray-400 border-gray-500/20' }))

const statusLabel = computed(() =>
    trx.value.is_cleared ? 'Berhasil' : 'Draft'
)
</script>

<template>
    <div
        class="rounded-xl border overflow-hidden bg-gray-900/80 cursor-pointer transition-all active:scale-98 hover:border-white/15 hover:bg-gray-900"
        :class="typeConfig.border"
        @click="showDetail = true"
        role="button"
        :aria-label="`${typeConfig.label} ${trx.amount_formatted}`"
    >
        <!-- Header: badge + status di kanan -->
        <div class="flex items-center justify-between px-3.5 pt-3 pb-2" :class="typeConfig.bg">
            <div class="flex items-center gap-2">
                <span v-if="component.index !== null && component.index !== undefined"
                    class="text-2xs font-black text-gray-600 tabular-nums">#{{ component.index }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full border" :class="typeConfig.badge">
                    {{ typeConfig.icon }} {{ trx.type_label ?? typeConfig.label }}
                </span>
            </div>
            <span :class="[
                'text-2xs font-bold px-1.5 py-0.5 rounded-full',
                trx.is_cleared
                    ? 'text-emerald-400 bg-emerald-500/10'
                    : 'text-amber-400 bg-amber-500/10'
            ]">{{ statusLabel }}</span>
        </div>

        <!-- Amount -->
        <div class="px-3.5 py-2.5 border-t border-white/5">
            <p class="text-xl font-black text-white tabular-nums tracking-tight leading-tight">
                {{ trx.amount_formatted }}
            </p>
            <p v-if="trx.notes" class="text-2xs text-gray-500 mt-0.5 truncate">{{ trx.notes }}</p>
        </div>

        <!-- Detail rows (show_details mode) -->
        <template v-if="component.show_details">
            <div class="border-t border-white/5 divide-y divide-white/5">
                <div v-if="trx.category" class="flex items-center gap-2.5 px-3.5 py-2">
                    <span class="text-sm w-4 text-center">📂</span>
                    <span class="text-2xs text-gray-500 w-16 shrink-0">Kategori</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ trx.category }}</span>
                </div>
                <div v-if="trx.source_wallet" class="flex items-center gap-2.5 px-3.5 py-2">
                    <span class="text-sm w-4 text-center">👛</span>
                    <span class="text-2xs text-gray-500 w-16 shrink-0">Dompet</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ trx.source_wallet }}</span>
                </div>
                <div v-if="trx.dest_wallet" class="flex items-center gap-2.5 px-3.5 py-2">
                    <span class="text-sm w-4 text-center">📥</span>
                    <span class="text-2xs text-gray-500 w-16 shrink-0">Ke Dompet</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ trx.dest_wallet }}</span>
                </div>
                <div v-if="trx.subject" class="flex items-center gap-2.5 px-3.5 py-2">
                    <span class="text-sm w-4 text-center">👤</span>
                    <span class="text-2xs text-gray-500 w-16 shrink-0">Subjek</span>
                    <span class="text-xs text-gray-200 font-medium truncate">{{ trx.subject }}</span>
                </div>
                <div v-if="trx.date" class="flex items-center gap-2.5 px-3.5 py-2">
                    <span class="text-sm w-4 text-center">📅</span>
                    <span class="text-2xs text-gray-500 w-16 shrink-0">Tanggal</span>
                    <span class="text-xs text-gray-200 font-medium">{{ trx.date }}</span>
                </div>
            </div>
        </template>

        <!-- Compact mode (multi-transaction list item) -->
        <template v-else>
            <div class="flex items-center gap-1.5 px-3.5 pb-2.5 border-t border-white/5 pt-1.5">
                <span v-if="trx.category" class="text-2xs text-gray-500">{{ trx.category }}</span>
                <span v-if="trx.source_wallet && trx.category" class="text-2xs text-gray-700">·</span>
                <span v-if="trx.source_wallet" class="text-2xs text-gray-500">{{ trx.source_wallet }}</span>
                <!-- Tap to detail hint -->
                <span class="ml-auto text-2xs text-gray-700">Detail →</span>
            </div>
        </template>
    </div>

    <TransactionDetailModal
        v-model="showDetail"
        :transaction="trx"
        :metadata="metadata"
    />
</template>
