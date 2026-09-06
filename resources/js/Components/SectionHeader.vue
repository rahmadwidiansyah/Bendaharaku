<script setup>
/**
 * SectionHeader.vue
 *
 * Page header reusable untuk seluruh halaman aplikasi.
 * Menggantikan pola h1 + subtitle yang diulang di setiap page:
 *
 *   <p class="text-2xs text-purple-500 font-black uppercase tracking-[0.3em] mb-1 opacity-80">Subtitle</p>
 *   <h1 class="text-2xl font-black text-white tracking-tight leading-none">Judul</h1>
 *
 * Mendukung:
 *   - Dot indikator sebelum subtitle
 *   - Slot action untuk tombol/elemen di sisi kanan
 *   - Ukuran heading yang bisa dikonfigurasi
 *
 * Props:
 *   title      — Teks heading utama (wajib)
 *   subtitle   — Teks kecil di atas judul, biasanya konteks/kategori (opsional)
 *   size       — Ukuran heading:
 *                'sm'  → text-xl  (untuk subpage/modal header)
 *                'md'  → text-2xl (default)
 *                'lg'  → text-3xl (untuk halaman utama seperti Dashboard)
 *   dot        — Tampilkan dot indikator ungu sebelum subtitle (default: false)
 *   as         — Elemen heading: 'h1' (default) | 'h2' | 'h3'
 *
 * Slots:
 *   default    — Konten tambahan di bawah judul (opsional)
 *   action     — Elemen di sisi kanan header (tombol, badge, dsb.) (opsional)
 *
 * Usage:
 *   <!-- Minimal -->
 *   <SectionHeader title="Aset & Dompet" subtitle="Portfolio" />
 *
 *   <!-- Dengan tombol aksi di kanan -->
 *   <SectionHeader title="Kategori" subtitle="Collection">
 *     <template #action>
 *       <Button variant="primary" size="sm" :as="Link" :href="route('categories.create')">
 *         Tambah
 *       </Button>
 *     </template>
 *   </SectionHeader>
 *
 *   <!-- Dengan dot indikator -->
 *   <SectionHeader title="Pengaturan" subtitle="Preferences" :dot="true" />
 *
 *   <!-- Dengan konten tambahan di bawah judul -->
 *   <SectionHeader title="Dashboard" subtitle="Overview" size="lg">
 *     <p class="text-sm text-gray-400 mt-1">Selamat datang kembali!</p>
 *   </SectionHeader>
 */

import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    dot: {
        type: Boolean,
        default: false,
    },
    as: {
        type: String,
        default: 'h1',
        validator: (v) => ['h1', 'h2', 'h3'].includes(v),
    },
})

const headingSizeClasses = computed(() => ({
    sm: 'text-lg sm:text-xl font-black text-[var(--color-text-primary)] tracking-tight leading-none',
    md: 'text-xl sm:text-2xl font-black text-[var(--color-text-primary)] tracking-tight leading-none',
    lg: 'text-2xl sm:text-3xl font-black text-[var(--color-text-primary)] tracking-tighter leading-none',
}[props.size]))
</script>

<template>
    <header class="flex items-start justify-between gap-4 animate-fade-in-up">
        <!-- Left: subtitle + title + slot default -->
        <div class="flex-1 min-w-0">
            <!-- Subtitle -->
            <p
                v-if="subtitle"
                class="flex items-center gap-1.5 text-2xs text-[var(--color-brand)] font-black uppercase tracking-[0.3em] mb-1 opacity-80"
            >
                <span
                    v-if="dot"
                    class="w-1.5 h-1.5 rounded-full bg-[var(--color-brand)] shrink-0"
                    aria-hidden="true"
                />
                {{ subtitle }}
            </p>

            <!-- Heading -->
            <component
                :is="as"
                :class="headingSizeClasses"
            >
                {{ title }}
            </component>

            <!-- Slot default — konten opsional di bawah judul -->
            <slot />
        </div>

        <!-- Right: slot action -->
        <div
            v-if="$slots.action"
            class="shrink-0 flex items-center gap-2 pt-1"
        >
            <slot name="action" />
        </div>
    </header>
</template>
