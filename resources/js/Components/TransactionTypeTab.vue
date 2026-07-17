<script setup>
/**
 * TransactionTypeTab.vue
 *
 * Tab selector tipe transaksi: Expense · Income · Transfer · Debt · Receivable
 * Diekstrak dari Create.vue dan Edit.vue — identik di keduanya.
 *
 * Props:
 *   modelValue — Tipe yang sedang aktif ('Expense' | 'Income' | 'Transfer' | 'Debt' | 'Receivable')
 *
 * Emits:
 *   update:modelValue — Saat tipe baru dipilih
 */

defineProps({
    modelValue: {
        type: String,
        required: true,
    },
})

defineEmits(['update:modelValue'])

const types = [
    {
        key: 'Expense',
        label: 'Keluar',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />`,
    },
    {
        key: 'Income',
        label: 'Masuk',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />`,
    },
    {
        key: 'Transfer',
        label: 'Transfer',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />`,
    },
    {
        key: 'Debt',
        label: 'Hutang',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`,
    },
    {
        key: 'Receivable',
        label: 'Piutang',
        icon: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />`,
    },
]
</script>

<template>
    <div
        class="flex-1 flex bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-1.5 border border-white/10 overflow-x-auto no-scrollbar gap-1"
        role="tablist"
        aria-label="Tipe transaksi"
    >
        <button
            v-for="t in types"
            :key="t.key"
            type="button"
            role="tab"
            :aria-selected="modelValue === t.key"
            :class="[
                'flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-2xs font-bold uppercase tracking-wider transition-all whitespace-nowrap overflow-hidden',
                modelValue === t.key
                    ? 'bg-gray-800 text-purple-500 border border-white/10 flex-1 px-3'
                    : 'text-gray-500 hover:text-white px-3 border border-transparent',
            ]"
            @click="$emit('update:modelValue', t.key)"
        >
            <svg
                class="w-5 h-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
                v-html="t.icon"
            />
            <span v-show="modelValue === t.key" class="transition-all duration-300">
                {{ t.label }}
            </span>
        </button>
    </div>
</template>
