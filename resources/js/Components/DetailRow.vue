<script setup>
/**
 * DetailRow.vue
 *
 * Baris label–nilai reusable untuk modal detail / daftar informasi.
 * Menjamin alignment, spacing, dan hierarki label/nilai konsisten
 * di seluruh baris — tanpa duplikasi class di tiap pemakaian.
 *
 * Props:
 *   label — Teks label (kiri)
 *
 * Slots:
 *   default — Konten nilai (kanan, rata kanan)
 */

defineProps({
    label: { type: String, required: true },
})
</script>

<template>
    <div class="flex items-center justify-between gap-4 py-4">
        <span class="shrink-0 text-2xs font-bold text-gray-500 uppercase tracking-[0.2em]">{{ label }}</span>
        <div class="min-w-0 text-right">
            <slot />
        </div>
    </div>
</template>
