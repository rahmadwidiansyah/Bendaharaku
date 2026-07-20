<script setup>
/**
 * ChatMessage.vue
 *
 * Bubble tunggal dalam riwayat percakapan.
 * Bot message: avatar kiri + bubble abu + kartu.
 * User message: bubble ungu kanan + avatar kanan.
 *
 * Delegasi:
 *   BotAvatar          — render avatar dengan fallback initials
 *   ChatTimestamp      — render timestamp <time>
 *   MessageRenderer    — dispatch ke komponen per tipe konten
 *   ResponseMeta       — latency · token · model
 *   MessageToolbar     — copy / regenerate / retry (hover)
 *   useMessageContent  — parse struktur konten pesan
 */

import MessageRenderer  from './Messages/MessageRenderer.vue'
import ResponseMeta     from './Messages/ResponseMeta.vue'
import MessageToolbar   from './Messages/MessageToolbar.vue'
import BotAvatar        from './BotAvatar.vue'
import ChatTimestamp    from './ChatTimestamp.vue'
import { useMessageContent } from '@/Composables/useMessageContent.js'

const props = defineProps({
    message:    { type: Object,  required: true },
    botAvatar:  { type: String,  default: null },
    botName:    { type: String,  default: 'Ken-Chan' },
    userAvatar: { type: String,  default: null },
    userName:   { type: String,  default: 'Kamu' },
    showAvatar: { type: Boolean, default: true },
})

const emit = defineEmits(['retry', 'regenerate'])

// ── Content parsing (delegasi ke composable) ──────────────────────
const {
    isUser,
    isBot,
    isErrorMessage,
    userText,
    filteredInline,
    cardComponents,
} = useMessageContent(() => props.message)
</script>

<template>
    <!-- ── BOT MESSAGE ─────────────────────────────────────────── -->
    <div v-if="isBot" class="flex items-end gap-1.5 px-3 py-0.5 animate-fade-in group">

        <!-- Avatar bot -->
        <div class="w-6 h-6 shrink-0 self-end mb-0.5">
            <BotAvatar v-if="showAvatar" :src="botAvatar" :name="botName" size="sm" variant="bot" shape="circle" />
        </div>

        <!-- CONTAINER UTAMA BARU: Menggabungkan Card + Text + Footer -->
        <div :class="['flex flex-col min-w-0 border rounded-2xl rounded-tl-md shadow-sm overflow-hidden', isErrorMessage ? 'bg-red-950/40 border-red-900/50' : 'bg-gray-800/90 border-white/8']" style="max-width: 80%">

            <!-- 1. Card components -->
            <template v-for="(comp, i) in cardComponents" :key="'card-' + i">
                <MessageRenderer :component="comp" :metadata="message.metadata ?? {}" />
            </template>

            <!-- 2. Inline Text (Bubble) -->
            <div v-if="filteredInline.length > 0" class="px-3.5 pt-2.5 pb-1 space-y-1">
                <template v-for="(comp, i) in filteredInline" :key="i">
                    <MessageRenderer :component="comp" />
                </template>
            </div>

            <!-- 3. Footer (Jam dan Response Time) -->
            <div class="flex items-center justify-between gap-2 px-3.5 py-1.5">
                <ResponseMeta :metadata="message.metadata" :content="message.content ?? []" />
                <ChatTimestamp :datetime="message.created_at" class="text-gray-500 select-none shrink-0" />
            </div>
        </div>

        <!-- Toolbar: muncul saat hover -->
        <div v-if="filteredInline.length > 0 || cardComponents.length > 0" class="flex items-center opacity-0 group-hover:opacity-100 transition-opacity duration-150 ml-1">
            <MessageToolbar :message="message" :is-error="isErrorMessage" @retry="emit('retry', message)" @regenerate="emit('regenerate', message)" />
        </div>
    </div>

    <!-- ── USER MESSAGE ────────────────────────────────────────── -->
    <div v-else-if="isUser" class="flex items-end justify-end gap-1.5 px-3 py-0.5 animate-fade-in">

        <!-- Bubble user -->
        <div class="flex flex-col gap-1 min-w-0 items-end" style="max-width: 80%">
            <div class="bg-gradient-to-br from-purple-600 to-purple-500 text-white text-sm leading-relaxed px-3.5 pt-2 pb-1.5 rounded-2xl rounded-tr-md shadow-sm shadow-purple-500/20 break-words">
                <p class="whitespace-pre-wrap break-words">{{ userText }}</p>
                <div class="flex justify-end mt-0.5">
                    <ChatTimestamp
                        :datetime="message.created_at"
                        class="text-white/40 select-none"
                    />
                </div>
            </div>
        </div>

        <!-- Avatar user -->
        <div class="w-6 h-6 shrink-0 self-end mb-0.5">
            <BotAvatar
                v-if="showAvatar"
                :src="userAvatar"
                :name="userName"
                size="sm"
                variant="user"
                shape="circle"
            />
        </div>
    </div>
</template>
