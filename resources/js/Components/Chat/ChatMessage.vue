<script setup>
/**
 * ChatMessage.vue
 *
 * Wrapper bubble untuk satu pesan (user atau bot).
 *
 * - User  → bubble di kanan, warna purple, tanpa avatar
 * - Bot   → bubble di kiri, warna gray, dengan avatar bot
 *
 * Setiap pesan bisa memiliki banyak komponen (content array).
 * MessageRenderer.vue menangani render tiap komponen.
 */

import { computed } from 'vue'
import MessageRenderer from './Messages/MessageRenderer.vue'

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    botAvatar: { type: String, default: null },
    botName:   { type: String, default: 'Ken-Chan' },
    userAvatar: { type: String, default: null },
    userName:   { type: String, default: 'Kamu' },
})

const isUser = computed(() => props.message.role === 'user')
const isBot  = computed(() => props.message.role === 'assistant')

// Inisial fallback untuk avatar
const botInitials = computed(() =>
    props.botName.trim().split(/\s+/).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)
const userInitials = computed(() =>
    props.userName.trim().split(/\s+/).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)

// Format timestamp: "14:35"
const timeLabel = computed(() => {
    if (!props.message.created_at) return ''
    try {
        return new Date(props.message.created_at).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        })
    } catch {
        return ''
    }
})

// Konten utama dari pesan user adalah teks saja
const userText = computed(() => {
    const first = props.message.content?.[0]
    return first?.type === 'text' ? first.text : ''
})
</script>

<template>
    <div
        :class="[
            'flex items-end gap-2.5 px-4 animate-fade-in',
            isUser ? 'flex-row-reverse' : 'flex-row',
        ]"
    >
        <!-- Avatar Bot (kiri, hanya untuk pesan bot) -->
        <div v-if="isBot"
            class="w-7 h-7 rounded-full shrink-0 overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center self-end mb-1"
            :aria-label="botName"
        >
            <img v-if="botAvatar" :src="botAvatar" :alt="botName" class="w-full h-full object-cover" />
            <span v-else class="text-xs font-black text-purple-400 select-none">{{ botInitials }}</span>
        </div>

        <!-- Message bubble -->
        <div :class="['flex flex-col gap-1 min-w-0', isUser ? 'items-end' : 'items-start']"
            style="max-width: 75%">

            <!-- USER: simple text bubble -->
            <div v-if="isUser"
                class="bg-gradient-to-br from-purple-800 to-purple-600 text-white text-sm leading-relaxed px-4 py-2.5 rounded-2xl rounded-br-sm shadow-sm break-words"
            >
                {{ userText }}
            </div>

            <!-- BOT: iterate komponen -->
            <div v-else class="flex flex-col gap-2 w-full">
                <template v-for="(comp, i) in message.content" :key="i">
                    <!-- Text dan divider langsung di dalam bubble -->
                    <div
                        v-if="comp.type === 'text' || comp.type === 'divider' || comp.type === 'suggestion'"
                        class="bg-gray-800 border border-white/10 px-4 py-2.5 rounded-2xl rounded-bl-sm"
                    >
                        <MessageRenderer :component="comp" />
                    </div>

                    <!-- Cards dan error di luar bubble untuk visual lebih lebar -->
                    <MessageRenderer
                        v-else
                        :component="comp"
                    />
                </template>
            </div>

            <!-- Timestamp -->
            <time
                v-if="timeLabel"
                :datetime="message.created_at"
                class="text-2xs text-gray-600 px-1"
            >
                {{ timeLabel }}
            </time>
        </div>

        <!-- Avatar User (kanan, hanya untuk pesan user) -->
        <div v-if="isUser"
            class="w-7 h-7 rounded-full shrink-0 overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center self-end mb-1"
            :aria-label="userName"
        >
            <img v-if="userAvatar" :src="userAvatar" :alt="userName" class="w-full h-full object-cover" />
            <span v-else class="text-xs font-black text-gray-400 select-none">{{ userInitials }}</span>
        </div>
    </div>
</template>
