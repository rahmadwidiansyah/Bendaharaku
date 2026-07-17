<script setup>
/**
 * EmptyState.vue
 *
 * Komponen empty state yang konsisten di seluruh aplikasi.
 * Digunakan di halaman apapun yang bisa memiliki kondisi kosong:
 * transaksi, wallet, kategori, history, dsb.
 *
 * Props:
 *   icon     — Emoji atau karakter ikon (default: '📭')
 *   title    — Judul utama empty state (wajib)
 *   message  — Deskripsi/keterangan tambahan (opsional)
 *   compact  — Mode compact untuk dipakai dalam card/list (default: false)
 *
 * Slots:
 *   action   — Tombol atau link aksi (opsional, misal: "Tambah Pertama")
 */

defineProps({
    icon: {
        type: String,
        default: '📭',
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: null,
    },
    compact: {
        type: Boolean,
        default: false,
    },
})
</script>

<template>
    <div
        :class="[
            'flex flex-col items-center justify-center text-center',
            compact ? 'py-8 px-4' : 'py-16 px-6',
        ]"
        role="status"
        :aria-label="title"
    >
        <!-- Icon -->
        <span
            :class="[
                'block mb-3 select-none',
                compact ? 'text-3xl' : 'text-5xl',
            ]"
            aria-hidden="true"
        >
            {{ icon }}
        </span>

        <!-- Title -->
        <p
            :class="[
                'font-bold text-white',
                compact ? 'text-sm' : 'text-base',
            ]"
        >
            {{ title }}
        </p>

        <!-- Message -->
        <p
            v-if="message"
            :class="[
                'text-gray-500 mt-1 leading-relaxed',
                compact ? 'text-xs' : 'text-sm',
            ]"
        >
            {{ message }}
        </p>

        <!-- Action slot -->
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
