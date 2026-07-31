<script setup>
/**
 * Badge.vue
 *
 * Komponen badge/chip reusable untuk menampilkan status, tipe transaksi,
 * atau label apapun secara konsisten di seluruh aplikasi.
 *
 * Menggantikan pola class string yang diulang-ulang di setiap halaman
 * (getTypeColor, getTypeName pattern di Dashboard, Transactions, dsb).
 *
 * Props:
 *   variant  — Varian warna badge:
 *              'income' | 'expense' | 'transfer' | 'debt' | 'receivable'
 *              | 'brand' | 'neutral' | 'success' | 'warning' | 'danger'
 *              (default: 'neutral')
 *   size     — Ukuran: 'sm' | 'md' (default: 'sm')
 *   dot      — Tampilkan dot indicator sebelum teks (default: false)
 *   pill     — Bentuk pill: rounded-full + uppercase + tracking lebar
 *              (default: false — bentuk default rounded-md)
 *
 * Slots:
 *   default  — Konten teks badge
 */

import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (v) => [
            'income', 'expense', 'transfer', 'debt', 'receivable',
            'brand', 'neutral', 'success', 'warning', 'danger',
        ].includes(v),
    },
    size: {
        type: String,
        default: 'sm',
        validator: (v) => ['sm', 'md'].includes(v),
    },
    dot: {
        type: Boolean,
        default: false,
    },
    pill: {
        type: Boolean,
        default: false,
    },
})

const variantClasses = computed(() => ({
    income:     'text-green-400  bg-green-400/10  border-green-400/20',
    expense:    'text-red-400    bg-red-400/10    border-red-400/20',
    transfer:   'text-blue-400   bg-blue-400/10   border-blue-400/20',
    debt:       'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
    receivable: 'text-fuchsia-400 bg-fuchsia-400/10 border-fuchsia-400/20',
    brand:      'text-purple-400 bg-purple-400/10 border-purple-400/20',
    neutral:    'text-gray-400   bg-gray-400/10   border-gray-400/20',
    success:    'text-green-400  bg-green-400/10  border-green-400/20',
    warning:    'text-yellow-400 bg-yellow-400/10 border-yellow-400/20',
    danger:     'text-red-400    bg-red-400/10    border-red-400/20',
}[props.variant]))

const dotColorClasses = computed(() => ({
    income:     'bg-green-400',
    expense:    'bg-red-400',
    transfer:   'bg-blue-400',
    debt:       'bg-yellow-400',
    receivable: 'bg-fuchsia-400',
    brand:      'bg-purple-400',
    neutral:    'bg-gray-400',
    success:    'bg-green-400',
    warning:    'bg-yellow-400',
    danger:     'bg-red-400',
}[props.variant]))

const sizeClasses = computed(() => ({
    sm: 'text-2xs',
    md: 'text-xs',
}[props.size]))

const shapeClasses = computed(() =>
    props.pill
        ? 'rounded-full uppercase tracking-widest px-3 py-1'
        : props.size === 'md'
            ? 'rounded-md px-2 py-1'
            : 'rounded-md px-1.5 py-0.5'
)

const badgeClasses = computed(() =>
    `inline-flex items-center gap-1 border font-bold ${variantClasses.value} ${sizeClasses.value} ${shapeClasses.value}`
)
</script>

<template>
    <span :class="badgeClasses">
        <!-- Dot indicator -->
        <span
            v-if="dot"
            :class="`w-1.5 h-1.5 rounded-full shrink-0 ${dotColorClasses}`"
            aria-hidden="true"
        />
        <slot />
    </span>
</template>
