<script setup>
/**
 * FormLabel.vue
 *
 * Label form yang konsisten di seluruh aplikasi.
 * Menggantikan pola class yang diulang:
 *   "block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1"
 *
 * Props:
 *   for      — Nilai `for` / `htmlFor` yang menghubungkan label ke input (wajib)
 *   required — Tampilkan tanda bintang merah sebagai penanda wajib isi (default: false)
 *
 * Slots:
 *   default  — Teks label
 *
 * Usage:
 *   <FormLabel for="email">Email</FormLabel>
 *   <FormLabel for="name" :required="true">Nama Lengkap</FormLabel>
 */

defineProps({
    for: {
        type: String,
        required: true,
    },
    required: {
        type: Boolean,
        default: false,
    },
})
</script>

<template>
    <label
        :for="$props.for"
        class="block text-2xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1"
    >
        <slot />
        <span
            v-if="required"
            class="text-red-400 ml-0.5"
            aria-hidden="true"
        >*</span>
    </label>
</template>
