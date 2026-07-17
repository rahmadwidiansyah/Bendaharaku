<script setup>
/**
 * AmountKeypad.vue
 *
 * Keypad numerik mobile untuk input nominal transaksi.
 * Diekstrak dari Create.vue dan Edit.vue — grid tombol identik di keduanya.
 *
 * Emits:
 *   key — String key yang ditekan: '0'-'9', '000', 'del', 'C'
 *
 * Usage:
 *   <AmountKeypad @key="handleKeypad" />
 */

defineEmits(['key'])

// Urutan tombol keypad — standar bank & e-wallet (1 di kiri atas, bukan kiri bawah)
const keys = [
    '1', '2', '3',
    '4', '5', '6',
    '7', '8', '9',
    '000', '0', 'del',
]
</script>

<template>
    <div class="grid grid-cols-3 gap-y-2 gap-x-4" role="group" aria-label="Keypad input nominal">
        <button
            v-for="k in keys"
            :key="k"
            type="button"
            :aria-label="k === 'del' ? 'Hapus digit terakhir' : k === '000' ? 'Tambah tiga nol' : `Digit ${k}`"
            class="h-12 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 transition-colors rounded-xl flex items-center justify-center active:scale-95 focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400"
            @click="$emit('key', k)"
        >
            <!-- Delete icon -->
            <template v-if="k === 'del'">
                <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </template>

            <!-- 000 shortcut -->
            <template v-else-if="k === '000'">
                <span class="text-sm font-bold text-purple-400">000</span>
            </template>

            <!-- Digit biasa -->
            <template v-else>
                <span class="text-lg font-bold text-gray-500">{{ k }}</span>
            </template>
        </button>
    </div>
</template>
