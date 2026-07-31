<script setup>
/**
 * Button.vue
 *
 * Komponen button reusable untuk seluruh aplikasi.
 * Menggantikan semua hardcode button class yang tersebar di setiap halaman.
 *
 * Props:
 *   variant   — Varian visual button:
 *               'primary'   → bg purple gradient (CTA utama)
 *               'secondary' → bg gray gradient, border subtle (aksi sekunder)
 *               'danger'    → bg red gradient (destructive/hapus)
 *               'ghost'     → transparan, border, teks saja
 *               'link'      → tanpa background/border, seperti teks link
 *               (default: 'primary')
 *
 *   size      — Ukuran button:
 *               'xs'  → sangat kecil, untuk badge-like button
 *               'sm'  → kecil
 *               'md'  → default
 *               'lg'  → besar (form submit utama)
 *               (default: 'md')
 *
 *   type      — Native button type: 'button' | 'submit' | 'reset'
 *               (default: 'button')
 *
 *   disabled  — Nonaktifkan button (default: false)
 *
 *   loading   — Tampilkan spinner loading & nonaktifkan button (default: false)
 *
 *   fullWidth — Melebar penuh (w-full) (default: false)
 *
 *   as        — Render sebagai elemen lain: 'button' | 'a' | Link
 *               Gunakan 'a' untuk external link.
 *               Untuk Inertia Link, pakai :href + as="a" atau gunakan slot.
 *               (default: 'button')
 *
 * Slots:
 *   default   — Konten button (teks, icon, dsb)
 *   icon-left — Icon di sebelah kiri teks (opsional)
 *   icon-right— Icon di sebelah kanan teks (opsional)
 *
 * Emits:
 *   click     — Dipancarkan saat button diklik (hanya jika tidak disabled/loading)
 *
 * Usage:
 *   <Button variant="primary" size="lg" :loading="form.processing" @click="submit">
 *     Simpan
 *   </Button>
 *
 *   <Button variant="secondary" size="sm">
 *     <template #icon-left><IconEdit /></template>
 *     Edit
 *   </Button>
 *
 *   <Button variant="danger" @click="hapus">Hapus</Button>
 */

import { computed } from 'vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'danger', 'ghost', 'link'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v),
    },
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    fullWidth: {
        type: Boolean,
        default: false,
    },
    as: {
        type: String,
        default: 'button',
    },
})

const emit = defineEmits(['click'])

const isDisabled = computed(() => props.disabled || props.loading)

const handleClick = (e) => {
    if (isDisabled.value) {
        e.preventDefault()
        return
    }
    emit('click', e)
}

// ─── Variant classes ──────────────────────────────────────────────
const variantClasses = computed(() => ({
    primary: [
        'bg-gradient-to-br from-brand-deep to-brand-soft',
        'text-white shadow-lg shadow-purple-500/20',
        'hover:from-brand-mid hover:to-brand-tint',
        'border border-transparent',
        'disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100',
    ].join(' '),

    secondary: [
        'bg-gradient-to-br from-gray-900 to-gray-800',
        'text-gray-300 hover:text-white',
        'border border-white/10 hover:border-white/20',
        'disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100',
    ].join(' '),

    danger: [
        'bg-gradient-to-br from-danger-mid to-danger-soft',
        'text-white shadow-lg shadow-red-500/20',
        'hover:from-danger-mid hover:to-danger-tint',
        'border border-transparent',
        'disabled:opacity-70 disabled:cursor-not-allowed disabled:active:scale-100',
    ].join(' '),

    ghost: [
        'bg-transparent',
        'text-gray-400 hover:text-white',
        'border border-white/10 hover:border-white/20',
        'disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100',
    ].join(' '),

    link: [
        'bg-transparent border-transparent',
        'text-purple-400 hover:text-purple-300 underline-offset-2 hover:underline',
        'disabled:opacity-50 disabled:cursor-not-allowed',
    ].join(' '),
}[props.variant]))

// ─── Size classes ─────────────────────────────────────────────────
const sizeClasses = computed(() => ({
    xs: 'text-2xs px-2 py-1 gap-1 rounded-lg',
    sm: 'text-2xs px-3 py-2 gap-1.5 rounded-xl',
    md: 'text-xs px-4 py-3 gap-2 rounded-xl',
    lg: 'text-sm px-5 py-4 gap-2 rounded-xl',
}[props.size]))

// ─── Assembled classes ────────────────────────────────────────────
const buttonClasses = computed(() => [
    // Base
    'inline-flex items-center justify-center',
    'font-bold uppercase tracking-widest',
    'transition-all duration-200',
    'active:scale-95 focus:outline-none',
    'focus-visible:ring-2 focus-visible:ring-purple-400 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900',
    // Conditional
    props.fullWidth ? 'w-full' : '',
    isDisabled.value ? 'pointer-events-none' : '',
    variantClasses.value,
    sizeClasses.value,
].filter(Boolean).join(' '))
</script>

<template>
    <component
        :is="as"
        :type="as === 'button' ? type : undefined"
        :disabled="as === 'button' ? isDisabled : undefined"
        :aria-disabled="isDisabled ? 'true' : undefined"
        :aria-busy="loading ? 'true' : undefined"
        :class="buttonClasses"
        @click="handleClick"
        v-bind="$attrs"
    >
        <!-- Loading spinner -->
        <svg
            v-if="loading"
            class="animate-spin shrink-0"
            :class="size === 'xs' || size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4'"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>

        <!-- Icon kiri -->
        <span v-else-if="$slots['icon-left']" class="shrink-0" aria-hidden="true">
            <slot name="icon-left" />
        </span>

        <!-- Konten utama -->
        <slot />

        <!-- Icon kanan -->
        <span v-if="$slots['icon-right'] && !loading" class="shrink-0" aria-hidden="true">
            <slot name="icon-right" />
        </span>
    </component>
</template>
