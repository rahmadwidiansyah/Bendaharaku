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

        <!-- Avatar bot: tampilkan atau kosong (spacer alignment) -->
        <div class="w-6 h-6 shrink-0 self-end mb-0.5">
            <BotAvatar
                v-if="showAvatar"
                :src="botAvatar"
                :name="botName"
                size="sm"
                variant="bot"
                shape="circle"
            />
        </div>

        <!-- Bubble + card stack -->
        <div class="flex flex-col gap-1 min-w-0" style="max-width: 80%">

            <!-- Bubble: semua inline components (text, divider, suggestion) -->
            <div
                v-if="filteredInline.length > 0"
                class="bg-gray-800/90 border border-white/8 rounded-2xl rounded-tl-md overflow-hidden shadow-sm"
            >
                <div class="px-3.5 pt-2.5 pb-2 space-y-1">
                    <template v-for="(comp, i) in filteredInline" :key="i">
                        <MessageRenderer :component="comp" />
                    </template>
                </div>

                <!-- Footer: meta + timestamp -->
                <div class="flex items-center justify-between gap-2 px-3.5 pb-2 pt-0">
                    <ResponseMeta
                        :metadata="message.metadata"
                        :content="message.content ?? []"
                    />
                    <ChatTimestamp
                        :datetime="message.created_at"
                        class="text-gray-600 select-none shrink-0"
                    />
                </div>
            </div>

            <!-- Card components (transaction_card, summary_card, error) -->
            <template v-for="(comp, i) in cardComponents" :key="'card-' + i">
                <MessageRenderer :component="comp" :metadata="message.metadata ?? {}" />
            </template>

            <!-- Timestamp standalone jika tidak ada inline bubble -->
            <div v-if="filteredInline.length === 0 && message.created_at" class="flex justify-start">
                <ChatTimestamp
                    :datetime="message.created_at"
                    class="text-gray-600 px-1 select-none"
                />
            </div>

            <!-- Toolbar: muncul saat hover -->
            <div
                v-if="filteredInline.length > 0 || cardComponents.length > 0"
                class="flex justify-start pl-0.5 -mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
            >
                <MessageToolbar
                    :message="message"
                    :is-error="isErrorMessage"
                    @retry="emit('retry', message)"
                    @regenerate="emit('regenerate', message)"
                />
            </div>
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
