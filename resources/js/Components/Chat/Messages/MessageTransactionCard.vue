<script setup>
import { computed } from 'vue'

const props = defineProps({
    component: { type: Object, required: true },
})

const trx = computed(() => props.component.transaction ?? {})

const typeConfig = computed(() => ({
    income:   { label: 'Pemasukan', icon: '↑', color: 'text-emerald-400', bg: 'bg-emerald-500/10', border: 'border-emerald-500/20', badge: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25' },
    expense:  { label: 'Pengeluaran', icon: '↓', color: 'text-red-400',     bg: 'bg-red-500/10',     border: 'border-red-500/20',     badge: 'bg-red-500/15 text-red-400 border-red-500/25' },
    transfer: { label: 'Transfer',    icon: '⇄', color: 'text-blue-400',    bg: 'bg-blue-500/10',    border: 'border-blue-500/20',    badge: 'bg-blue-500/15 text-blue-400 border-blue-500/25' },
    debt:     { label: 'Hutang/Piutang', icon: '🤝', color: 'text-amber-400',  bg: 'bg-amber-500/10',   border: 'border-amber-500/20',   badge: 'bg-amber-500/15 text-amber-400 border-amber-500/25' },
    other:    { label: 'Transaksi',   icon: '•', color: 'text-gray-400',    bg: 'bg-gray-500/10',    border: 'border-gray-500/20',    badge: 'bg-gray-500/15 text-gray-400 border-gray-500/25' },
}[trx.value.type_key ?? 'other'] ?? { label: 'Transaksi', icon: '•', color: 'text-gray-400', bg: 'bg-gray-500/10', border: 'border-gray-500/20', badge: 'bg-gray-500/15 text-gray-400 border-gray-500/25' }))

const statusLabel = computed(() =>
    trx.value.is_cleared ? 'Berhasil' : 'Draft'
)
</script>

<template>
    <div class="rounded-2xl border overflow-hidden bg-gray-950/50"
        :class="typeConfig.border">

        <!-- Header: type badge + status -->
        <div class="flex items-center justify-between px-4 pt-3.5 pb-2.5"
            :class="typeConfig.bg">
            <div class="flex items-center gap-2">
                <!-- Index badge (untuk multi-transaction) -->
                <span v-if="component.index !== null && component.index !== undefined"
                    class="text-2xs font-black text-gray-500 tabular-nums">
                    #{{ component.index }}
                </span>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full border"
                    :class="typeConfig.badge">
                    {{ typeConfig.icon }} {{ trx.type_label ?? typeConfig.label }}
                </span>
            </div>
            <span :class="[
                'text-2xs font-bold px-2 py-0.5 rounded-full',
                trx.is_cleared
                    ? 'text-emerald-400 bg-emerald-500/10'
                    : 'text-amber-400 bg-amber-500/10'
            ]">
                {{ statusLabel }}
            </span>
        </div>

        <!-- Amount — always shown -->
        <div class="px-4 py-3 border-t border-white/5">
            <p class="text-2xl font-black text-white tabular-nums tracking-tight">
                {{ trx.amount_formatted }}
            </p>
            <p v-if="trx.notes" class="text-2xs text-gray-500 mt-0.5 truncate">
                {{ trx.notes }}
            </p>
        </div>

        <!-- Detail rows (show_details mode) -->
        <template v-if="component.show_details">
            <div class="border-t border-white/5 divide-y divide-white/5">
                <!-- Category -->
                <div v-if="trx.category" class="flex items-center gap-3 px-4 py-2.5">
                    <span class="text-gray-500 w-4 shrink-0">📂</span>
                    <span class="text-2xs text-gray-400 w-20 shrink-0">Kategori</span>
                    <span class="text-xs text-white font-medium">{{ trx.category }}</span>
                </div>
                <!-- Source Wallet -->
                <div v-if="trx.source_wallet" class="flex items-center gap-3 px-4 py-2.5">
                    <span class="text-gray-500 w-4 shrink-0">👛</span>
                    <span class="text-2xs text-gray-400 w-20 shrink-0">Dompet</span>
                    <span class="text-xs text-white font-medium">{{ trx.source_wallet }}</span>
                </div>
                <!-- Dest Wallet (transfer) -->
                <div v-if="trx.dest_wallet" class="flex items-center gap-3 px-4 py-2.5">
                    <span class="text-gray-500 w-4 shrink-0">📥</span>
                    <span class="text-2xs text-gray-400 w-20 shrink-0">Ke Dompet</span>
                    <span class="text-xs text-white font-medium">{{ trx.dest_wallet }}</span>
                </div>
                <!-- Subject (debt) -->
                <div v-if="trx.subject" class="flex items-center gap-3 px-4 py-2.5">
                    <span class="text-gray-500 w-4 shrink-0">👤</span>
                    <span class="text-2xs text-gray-400 w-20 shrink-0">Subjek</span>
                    <span class="text-xs text-white font-medium">{{ trx.subject }}</span>
                </div>
                <!-- Date -->
                <div v-if="trx.date" class="flex items-center gap-3 px-4 py-2.5">
                    <span class="text-gray-500 w-4 shrink-0">📅</span>
                    <span class="text-2xs text-gray-400 w-20 shrink-0">Tanggal</span>
                    <span class="text-xs text-white font-medium">{{ trx.date }}</span>
                </div>
            </div>
        </template>

        <!-- Compact mode (multi-transaction list item) -->
        <template v-else>
            <div class="flex items-center gap-2 px-4 py-2 border-t border-white/5">
                <span v-if="trx.category" class="text-2xs text-gray-500">{{ trx.category }}</span>
                <span v-if="trx.source_wallet" class="text-2xs text-gray-600">·</span>
                <span v-if="trx.source_wallet" class="text-2xs text-gray-500">{{ trx.source_wallet }}</span>
            </div>
        </template>
    </div>
</template>
