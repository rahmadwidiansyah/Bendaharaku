<script setup>
/**
 * ChatMessage.vue
 *
 * Wrapper bubble untuk satu pesan (user atau bot).
 *
 * - User  → bubble di kanan (justify-end), avatar di paling kanan (setelah bubble di DOM)
 * - Bot   → bubble di kiri, avatar di paling kiri (sebelum bubble di DOM)
 *
 * Setiap pesan bisa memiliki banyak komponen (content array).
 * MessageRenderer.vue menangani render tiap komponen.
 */

import { ref, computed } from 'vue'
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

// Reactive state untuk track avatar load error
const userAvatarFailed = ref(false)
const botAvatarFailed  = ref(false)

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
    <!-- Bot message: avatar kiri, bubble kanan dari kiri -->
    <div
        v-if="isBot"
        class="flex items-end gap-2.5 px-4 animate-fade-in"
    >
        <!-- Avatar Bot (paling kiri) -->
        <div
            class="w-7 h-7 rounded-full shrink-0 overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center self-end mb-1"
            :aria-label="botName"
        >
            <img
                v-if="botAvatar && !botAvatarFailed"
                :src="botAvatar"
                :alt="botName"
                class="w-full h-full object-cover"
                @error="botAvatarFailed = true"
            />
            <span v-else class="text-xs font-black text-purple-400 select-none">{{ botInitials }}</span>
        </div>

        <!-- Bubble Bot -->
        <div class="flex flex-col gap-1 min-w-0 items-start" style="max-width: 75%">
            <div class="flex flex-col gap-2 w-full">
                <template v-for="(comp, i) in message.content" :key="i">
                    <!-- Text, divider, suggestion langsung di dalam bubble -->
                    <div
                        v-if="comp.type === 'text' || comp.type === 'divider' || comp.type === 'suggestion'"
                        class="bg-gray-800 border border-white/10 px-4 py-2.5 rounded-2xl rounded-bl-sm"
                    >
                        <MessageRenderer :component="comp" />
                    </div>
                    <!-- Cards dan error di luar bubble -->
                    <MessageRenderer v-else :component="comp" />
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
    </div>

    <!-- User message: bubble kanan, avatar paling kanan -->
    <div
        v-else-if="isUser"
        class="flex items-end justify-end gap-2.5 px-4 animate-fade-in"
    >
        <!-- Bubble User (di kiri dari avatar, right-aligned karena justify-end) -->
        <div class="flex flex-col gap-1 min-w-0 items-end" style="max-width: 75%">
            <div
                class="bg-gradient-to-br from-purple-800 to-purple-600 text-white text-sm leading-relaxed px-4 py-2.5 rounded-2xl rounded-br-sm shadow-sm break-words"
            >
                {{ userText }}
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

        <!-- Avatar User (paling kanan, setelah bubble di DOM) -->
        <div
            class="w-7 h-7 rounded-full shrink-0 overflow-hidden bg-gray-800 border border-white/10 flex items-center justify-center self-end mb-1"
            :aria-label="userName"
        >
            <img
                v-if="userAvatar && !userAvatarFailed"
                :src="userAvatar"
                :alt="userName"
                class="w-full h-full object-cover"
                @error="userAvatarFailed = true"
            />
            <span v-else class="text-xs font-black text-gray-400 select-none">{{ userInitials }}</span>
        </div>
    </div>
</template>
