<script setup>
/**
 * Card.vue
 *
 * Container card reusable untuk seluruh aplikasi.
 * Menggantikan pola hardcode yang diulang di setiap halaman:
 *   "bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl"
 *   "bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl"
 *
 * Props:
 *   padding   — Padding bawaan card:
 *               'none' → tanpa padding (untuk konten yang butuh bleed ke pinggir)
 *               'sm'   → p-4
 *               'md'   → p-5 (default)
 *               'lg'   → p-6
 *               'xl'   → p-7
 *
 *   radius    — Border radius:
 *               'xl'   → rounded-xl (default, untuk card kecil/list item)
 *               '2xl'  → rounded-2xl (untuk section card besar)
 *
 *   hover     — Tampilkan efek hover overlay (subtle glow) (default: false)
 *
 *   as        — Render sebagai elemen HTML berbeda (default: 'div')
 *               Bisa diisi 'section', 'article', 'li', dsb sesuai semantik
 *
 *   overflow  — Kelas overflow, berguna untuk konten yang perlu clip:
 *               'hidden'  → overflow-hidden (default)
 *               'visible' → overflow-visible
 *               'auto'    → overflow-auto
 *
 * Slots:
 *   default   — Konten card
 *
 * Usage:
 *   <!-- Card section biasa -->
 *   <Card padding="lg" radius="2xl">
 *     <h2>Judul</h2>
 *     <p>Konten...</p>
 *   </Card>
 *
 *   <!-- Card dengan hover effect -->
 *   <Card :hover="true" padding="md">
 *     <WalletItem :wallet="wallet" />
 *   </Card>
 *
 *   <!-- Card tanpa padding untuk konten bleed -->
 *   <Card padding="none" radius="2xl">
 *     <img class="w-full rounded-t-2xl" />
 *     <div class="p-5">...</div>
 *   </Card>
 *
 *   <!-- Semantik berbeda -->
 *   <Card as="article" padding="lg">...</Card>
 *   <Card as="li" padding="md" :hover="true">...</Card>
 */

import { computed } from 'vue'

const props = defineProps({
    padding: {
        type: String,
        default: 'md',
        validator: (v) => ['none', 'sm', 'md', 'lg', 'xl'].includes(v),
    },
    radius: {
        type: String,
        default: 'xl',
        validator: (v) => ['xl', '2xl'].includes(v),
    },
    hover: {
        type: Boolean,
        default: false,
    },
    as: {
        type: String,
        default: 'div',
    },
    overflow: {
        type: String,
        default: 'hidden',
        validator: (v) => ['hidden', 'visible', 'auto'].includes(v),
    },
})

const paddingClasses = computed(() => ({
    none: '',
    sm:   'p-3 sm:p-4',
    md:   'p-3 sm:p-5',
    lg:   'p-4 sm:p-6',
    xl:   'p-4 sm:p-6 lg:p-7',
}[props.padding]))

const radiusClasses = computed(() => ({
    xl:  'rounded-xl',
    '2xl': 'rounded-2xl',
}[props.radius]))

const overflowClasses = computed(() => ({
    hidden:  'overflow-hidden',
    visible: 'overflow-visible',
    auto:    'overflow-auto',
}[props.overflow]))

const cardClasses = computed(() => [
    'bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] shadow-card',
    'relative',
    props.hover ? 'group' : '',
    paddingClasses.value,
    radiusClasses.value,
    overflowClasses.value,
].filter(Boolean).join(' '))
</script>

<template>
    <component
        :is="as"
        :class="cardClasses"
        v-bind="$attrs"
    >
        <!-- Hover overlay — hanya render jika hover=true -->
        <div
            v-if="hover"
            class="absolute inset-0 bg-white/[0.02] opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"
            aria-hidden="true"
        />

        <!-- Konten card, perlu z-10 agar di atas hover overlay -->
        <div :class="hover ? 'relative z-10' : undefined">
            <slot />
        </div>
    </component>
</template>
