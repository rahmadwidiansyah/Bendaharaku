<script setup>
/**
 * ChatEmptyState.vue
 *
 * Welcome screen saat belum ada pesan.
 * Menampilkan:
 * - Avatar & nama bot
 * - Penjelasan singkat
 * - Contoh kalimat + command untuk memulai
 *
 * Emit 'select' saat user menekan chip contoh.
 */

import { ref, computed } from 'vue'

const props = defineProps({
    botName:   { type: String, default: 'Ken-Chan' },
    botAvatar: { type: String, default: null },
})

const emit = defineEmits(['select'])

const avatarFailed = ref(false)

const initials = computed(() =>
    props.botName.trim().split(/\s+/).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)

const suggestions = [
    { label: 'Makan 20 ribu', hint: 'Catat pengeluaran' },
    { label: 'Gaji 5 juta BCA', hint: 'Catat pemasukan' },
    { label: 'Transfer BCA ke Dana 100rb', hint: 'Transfer antar dompet' },
    { label: '/saldo', hint: 'Lihat saldo semua dompet' },
    { label: '/ringkasan', hint: 'Ringkasan keuangan' },
    { label: '/help', hint: 'Panduan penggunaan' },
]
</script>

<template>
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-10 text-center">

        <!-- Bot avatar large -->
        <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-800 border-2 border-purple-500/30 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/10">
            <img v-if="botAvatar && !avatarFailed" :src="botAvatar" :alt="botName" class="w-full h-full object-cover" @error="avatarFailed = true" />
            <span v-else class="text-2xl font-black text-purple-400 select-none">{{ initials }}</span>
        </div>

        <!-- Name + tagline -->
        <h2 class="text-lg font-black text-white mb-1">{{ botName }}</h2>
        <p class="text-sm text-gray-400 mb-1">AI Financial Assistant</p>
        <p class="text-xs text-gray-600 max-w-xs leading-relaxed mb-8">
            Ceritakan transaksimu dengan bahasa alami, atau gunakan perintah di bawah.
        </p>

        <!-- Suggestion chips -->
        <div class="w-full max-w-sm">
            <p class="text-2xs font-black text-gray-600 uppercase tracking-widest mb-3">Mulai dari sini</p>
            <div class="flex flex-wrap gap-2 justify-center">
                <button
                    v-for="s in suggestions"
                    :key="s.label"
                    type="button"
                    @click="$emit('select', s.label)"
                    :title="s.hint"
                    class="px-3.5 py-2 rounded-xl bg-gray-800 border border-white/10 text-xs text-gray-300 font-medium hover:border-purple-500/40 hover:text-white hover:bg-gray-750 transition-all active:scale-95"
                >
                    {{ s.label }}
                </button>
            </div>
        </div>

    </div>
</template>
