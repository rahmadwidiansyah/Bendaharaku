<script setup>
/**
 * PageContainer.vue
 *
 * Standar kontainer untuk SELURUH halaman aplikasi.
 * Satu-satunya komponen yang menentukan padding horizontal & max-width halaman.
 *
 * Prinsip:
 *   - Mobile first: konten penuh, padding kecil.
 *   - Desktop: konten di-center dengan max-width proporsional,
 *     DESKTOP MEMAKAI RUANG LAYAR, bukan terkunci 448px.
 *
 * Prop `size` mengontrol max-width pada breakpoint lg+:
 *   - 'full'       → w-full, tidak ada max-width. Untuk halaman yang benar-benar
 *                    memakai seluruh layar (Chat, Dashboard grid lebar).
 *   - 'fluid'      → sm:max-w-2xl md:max-w-3xl lg:max-w-5xl xl:max-w-6xl 2xl:max-w-7xl [DEFAULT]
 *                    Konten biasa: artikel, form, halaman daftar.
 *   - 'narrow'     → sm:max-w-xl md:max-w-2xl lg:max-w-3xl xl:max-w-4xl
 *                    Konten terbatas: halaman auth, halaman kosong, detail sempit.
 *
 * Prop `padding` mengontrol padding horizontal:
 *   - 'responsive' → px-4 sm:px-6 lg:px-8 [DEFAULT]
 *   - 'none'       → tanpa padding (untuk container yang punya padding sendiri)
 *   - 'compact'    → px-4 sm:px-6
 *
 * Usage:
 *   <!-- Halaman standar (Dashboard, Wallets, Categories, dst) -->
 *   <PageContainer>
 *     <PageHeader title="Dashboard" />
 *     <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
 *       <StatCard v-for="s in stats" :key="s.label" v-bind="s" />
 *     </div>
 *   </PageContainer>
 *
 *   <!-- Chat: full lebar, pakai layar penuh -->
 *   <PageContainer size="full" padding="none">
 *     <ChatWindow />
 *   </PageContainer>
 *
 *   <!-- Halaman form sempit -->
 *   <PageContainer size="narrow">
 *     <TransactionForm />
 *   </PageContainer>
 */

import { computed } from 'vue'

const props = defineProps({
    size: {
        type: String,
        default: 'fluid',
        validator: (v) => ['full', 'fluid', 'narrow'].includes(v),
    },
    padding: {
        type: String,
        default: 'responsive',
        validator: (v) => ['responsive', 'none', 'compact'].includes(v),
    },
    as: {
        type: String,
        default: 'div',
    },
})

const sizeClasses = {
    full: 'w-full',
    fluid: 'w-full sm:max-w-2xl md:max-w-3xl lg:max-w-5xl xl:max-w-6xl 2xl:max-w-7xl',
    narrow: 'w-full sm:max-w-xl md:max-w-2xl lg:max-w-3xl xl:max-w-4xl',
}

const paddingClasses = {
    responsive: 'px-4 sm:px-6 lg:px-8',
    none: '',
    compact: 'px-4 sm:px-6',
}

const containerClass = computed(() => [
    'mx-auto w-full',
    sizeClasses[props.size],
    paddingClasses[props.padding],
])
</script>

<template>
    <component :is="as" :class="containerClass">
        <slot />
    </component>
</template>