<script setup>
/**
 * ChatEmptyState.vue
 *
 * Welcome screen saat belum ada pesan.
 * Pakai BotAvatar agar konsisten dengan bubble.
 */

import BotAvatar from '@/Components/Chat/BotAvatar.vue'

defineProps({
    botName:   { type: String, default: 'Ken-Chan' },
    botAvatar: { type: String, default: null },
})

defineEmits(['select'])

const suggestions = [
    { label: 'Makan 20 ribu',              hint: 'Catat pengeluaran' },
    { label: 'Gaji 5 juta BCA',            hint: 'Catat pemasukan' },
    { label: 'Transfer BCA ke Dana 100rb', hint: 'Transfer antar dompet' },
    { label: '/saldo',                     hint: 'Lihat saldo semua dompet' },
    { label: '/ringkasan',                 hint: 'Ringkasan keuangan' },
    { label: '/help',                      hint: 'Panduan penggunaan' },
]
</script>

<template>
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 text-center select-none">

        <!-- Bot avatar (lg + online dot) -->
        <div class="mb-5">
            <BotAvatar
                :src="botAvatar"
                :name="botName"
                size="lg"
                variant="bot"
                shape="rounded"
                :online="true"
                class="shadow-lg shadow-purple-500/10"
            />
        </div>

        <!-- Name + tagline -->
        <h2 class="text-base font-bold text-white mb-0.5">{{ botName }}</h2>
        <p class="text-xs text-gray-500 mb-8 leading-relaxed max-w-xs">
            Ceritakan transaksimu dengan bahasa alami,<br>atau gunakan perintah di bawah.
        </p>

        <!-- Suggestion chips -->
        <div class="w-full max-w-sm">
            <p class="text-2xs font-bold text-gray-700 uppercase tracking-widest mb-2.5">Mulai dari sini</p>
            <div class="flex flex-wrap gap-1.5 justify-center">
                <button
                    v-for="s in suggestions"
                    :key="s.label"
                    type="button"
                    @click="$emit('select', s.label)"
                    :title="s.hint"
                    class="px-3 py-1.5 rounded-lg bg-gray-800/80 border border-white/8 text-xs text-gray-400 font-medium hover:border-purple-500/30 hover:text-gray-200 hover:bg-gray-800 transition-all active:scale-95"
                >
                    {{ s.label }}
                </button>
            </div>
        </div>

    </div>
</template>
