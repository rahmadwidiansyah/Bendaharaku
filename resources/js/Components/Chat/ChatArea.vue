<script setup>
/**
 * ChatArea.vue
 *
 * Scrollable container untuk daftar pesan.
 * Menangani:
 * - Infinite scroll ke atas (load history lebih lama)
 * - Expose ref ke parent via defineExpose
 * - Transition masuk untuk setiap bubble
 */

import { ref } from 'vue'
import ChatMessage     from './ChatMessage.vue'
import TypingIndicator from './Messages/TypingIndicator.vue'

const props = defineProps({
    messages:     { type: Array,   required: true },
    isTyping:     { type: Boolean, default: false },
    isLoadingMore:{ type: Boolean, default: false },
    hasMore:      { type: Boolean, default: false },
    botAvatar:    { type: String,  default: null },
    botName:      { type: String,  default: 'Ken-Chan' },
    userAvatar:   { type: String,  default: null },
    userName:     { type: String,  default: 'Kamu' },
    showJumpBtn:  { type: Boolean, default: false },
})

const emit = defineEmits(['loadMore', 'scrollUpdate', 'jumpLatest', 'retry', 'regenerate'])

/**
 * Avatar grouping: tampilkan avatar hanya pada pesan pertama
 * dalam satu grup pesan dari pihak yang sama.
 */
function shouldShowAvatar(messages, index) {
    if (index === 0) return true
    const prev = messages[index - 1]
    const curr = messages[index]
    return prev.role !== curr.role
}

const containerRef = ref(null)
let ticking = false

function onScroll() {
    if (ticking) return
    ticking = true
    requestAnimationFrame(() => {
        const el = containerRef.value
        if (!el) return
        if (el.scrollTop < 100 && props.hasMore && !props.isLoadingMore) {
            emit('loadMore')
        }
        emit('scrollUpdate', el.scrollTop, el.scrollHeight, el.clientHeight)
        ticking = false
    })
}

// Expose containerRef ke parent (useChat scrollToBottom membutuhkannya)
defineExpose({ el: containerRef })
</script>

<template>
    <div
        ref="containerRef"
        class="flex-1 overflow-y-auto overscroll-contain relative"
        style="scroll-behavior: auto;"
        @scroll="onScroll"
        role="log"
        aria-label="Riwayat percakapan"
        aria-live="polite"
    >
        <!-- Load more indicator -->
        <div v-if="isLoadingMore" class="flex justify-center py-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/60 border border-white/8 text-2xs text-gray-500">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Memuat...
            </div>
        </div>

        <!-- Spacer atas -->
        <div class="h-2" aria-hidden="true"></div>

        <!-- Slot untuk empty state -->
        <slot />

        <!-- Message list -->
        <TransitionGroup
            tag="div"
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            class="flex flex-col pb-2"
        >
            <ChatMessage
                v-for="(msg, idx) in messages"
                :key="msg.id ?? msg._localId"
                :message="msg"
                :bot-avatar="botAvatar"
                :bot-name="botName"
                :user-avatar="userAvatar"
                :user-name="userName"
                :show-avatar="shouldShowAvatar(messages, idx)"
                @retry="emit('retry', $event)"
                @regenerate="emit('regenerate', $event)"
            />
        </TransitionGroup>

        <!-- Typing indicator -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <TypingIndicator
                v-if="isTyping"
                :bot-avatar="botAvatar"
                :bot-name="botName"
            />
        </Transition>

        <!-- Spacer bawah agar tidak ketutup composer -->
        <div class="h-2" aria-hidden="true"></div>

        <!-- Jump to latest button -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-y-2"
        >
            <button
                v-if="showJumpBtn"
                @click="$emit('jumpLatest')"
                class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-800 border border-white/12 text-2xs text-gray-300 shadow-lg hover:bg-gray-700 hover:text-white transition-all active:scale-95"
                aria-label="Scroll ke pesan terbaru"
            >
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                Pesan terbaru
            </button>
        </Transition>
    </div>
</template>
