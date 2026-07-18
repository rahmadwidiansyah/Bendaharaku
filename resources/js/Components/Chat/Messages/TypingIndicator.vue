<script setup>
/**
 * TypingIndicator.vue
 *
 * Tiga titik animasi saat bot sedang memproses.
 * Pakai BotAvatar untuk konsistensi visual.
 */

import BotAvatar from '@/Components/Chat/BotAvatar.vue'

defineProps({
    botAvatar: { type: String, default: null },
    botName:   { type: String, default: 'Ken-Chan' },
})
</script>

<template>
    <div class="flex items-end gap-1.5 px-3 py-0.5">
        <!-- Avatar bot -->
        <BotAvatar :src="botAvatar" :name="botName" size="sm" variant="bot" shape="circle" class="self-end mb-0.5" />

        <!-- Bubble dengan dot animation -->
        <div class="bg-gray-800/90 border border-white/8 rounded-2xl rounded-tl-md px-3.5 py-2.5 flex items-center gap-1 shadow-sm">
            <span class="w-1.5 h-1.5 rounded-full bg-gray-500 animate-bounce" style="animation-delay: 0ms;"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-500 animate-bounce" style="animation-delay: 120ms;"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-500 animate-bounce" style="animation-delay: 240ms;"></span>
        </div>
    </div>
</template>
