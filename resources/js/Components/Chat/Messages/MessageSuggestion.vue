<script setup>
/**
 * MessageSuggestion.vue
 *
 * Chip contoh transaksi atau saran yang muncul di dalam bubble bot.
 *
 * Klik → emit 'suggest' dengan insert_text (teks bersih tanpa label emoji)
 * sehingga teks langsung masuk ke composer — user bisa edit sebelum kirim.
 *
 * Props:
 *   component.message     — teks tampilan lengkap (misal: '💸 Pengeluaran: "Beli nasi goreng 15k bca"')
 *   component.insert_text — teks yang dimasukkan ke composer (misal: 'Beli nasi goreng 15k bca')
 *   component.action_url  — opsional, URL navigasi (belum diimplementasi)
 */

const props = defineProps({
    component: { type: Object, required: true },
})

const emit = defineEmits(['suggest'])

function handleClick() {
    // Gunakan insert_text jika ada, fallback ke message penuh
    const text = props.component.insert_text || props.component.message || ''
    if (text) emit('suggest', text)
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="w-full text-left px-3 py-2 mt-0.5 rounded-xl bg-white/4 border border-white/8 text-sm text-gray-300 hover:text-white hover:border-white/16 hover:bg-white/8 transition-all active:scale-[0.98] cursor-pointer"
    >
        {{ component.message }}
    </button>
</template>
