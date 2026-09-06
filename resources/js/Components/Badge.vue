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
    income:     'text-[var(--color-income-text)] bg-[var(--color-income-bg)] border-[var(--color-income-border)]',
    expense:    'text-[var(--color-expense-text)] bg-[var(--color-expense-bg)] border-[var(--color-expense-border)]',
    transfer:   'text-[var(--color-transfer-text)] bg-[var(--color-transfer-bg)] border-[var(--color-transfer-border)]',
    debt:       'text-[var(--color-debt-text)] bg-[var(--color-debt-bg)] border-[var(--color-debt-border)]',
    receivable: 'text-[var(--color-receivable-text)] bg-[var(--color-receivable-bg)] border-[var(--color-receivable-border)]',
    brand:      'text-[var(--color-brand)] bg-[var(--color-brand-subtle)] border-[var(--color-brand-border)]',
    neutral:    'text-[var(--color-text-muted)] bg-[var(--color-surface-muted)] border-[var(--color-border-default)]',
    success:    'text-[var(--color-income-text)] bg-[var(--color-income-bg)] border-[var(--color-income-border)]',
    warning:    'text-[var(--color-debt-text)] bg-[var(--color-debt-bg)] border-[var(--color-debt-border)]',
    danger:     'text-[var(--color-expense-text)] bg-[var(--color-expense-bg)] border-[var(--color-expense-border)]',
}[props.variant]))

const dotColorClasses = computed(() => ({
    income:     'bg-[var(--color-income-text)]',
    expense:    'bg-[var(--color-expense-text)]',
    transfer:   'bg-[var(--color-transfer-text)]',
    debt:       'bg-[var(--color-debt-text)]',
    receivable: 'bg-[var(--color-receivable-text)]',
    brand:      'bg-[var(--color-brand)]',
    neutral:    'bg-[var(--color-text-muted)]',
    success:    'bg-[var(--color-income-text)]',
    warning:    'bg-[var(--color-debt-text)]',
    danger:     'bg-[var(--color-expense-text)]',
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
