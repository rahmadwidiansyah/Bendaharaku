<script setup>
import { computed } from 'vue'

const props = defineProps({
    botAvatar: { type: String, default: null },
    botName:   { type: String, default: 'Ken-Chan' },
})

const initials = computed(() =>
    props.botName.trim().split(/\s+/).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)
</script>

<template>
    <div class="flex items-end gap-2.5 px-4 py-1">
        <!-- Avatar bot -->
        <div class="w-7 h-7 rounded-full shrink-0 overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center">
            <img v-if="botAvatar" :src="botAvatar" :alt="botName" class="w-full h-full object-cover" />
            <span v-else class="text-xs font-black text-purple-400">{{ initials }}</span>
        </div>

        <!-- Bubble dengan dot animation -->
        <div class="bg-gray-800 border border-white/10 rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0ms;"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 150ms;"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 300ms;"></span>
        </div>
    </div>
</template>
