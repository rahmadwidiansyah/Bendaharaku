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

import { ref, onMounted, watch } from 'vue'
import ChatMessage    from './ChatMessage.vue'
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
})

const emit = defineEmits(['loadMore'])

const containerRef = ref(null)
let ticking = false

function onScroll() {
    if (ticking) return
    ticking = true
    requestAnimationFrame(() => {
        // Trigger load more saat scroll mendekati atas (100px threshold)
        if (containerRef.value && containerRef.value.scrollTop < 100 && props.hasMore && !props.isLoadingMore) {
            emit('loadMore')
        }
        ticking = false
    })
}

// Expose containerRef ke parent (useChat scrollToBottom membutuhkannya)
defineExpose({ el: containerRef })
</script>

<template>
    <div
        ref="containerRef"
        class="flex-1 overflow-y-auto overscroll-contain"
        style="scroll-behavior: auto;"
        @scroll="onScroll"
        role="log"
        aria-label="Riwayat percakapan"
        aria-live="polite"
    >
        <!-- Load more indicator -->
        <div v-if="isLoadingMore" class="flex justify-center py-4">
            <svg class="animate-spin w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>

        <!-- Spacer atas -->
        <div class="h-3" aria-hidden="true"></div>

        <!-- Slot untuk empty state (diisi dari parent) -->
        <slot />

        <!-- Message list -->
        <TransitionGroup
            tag="div"
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            class="flex flex-col gap-3 pb-3"
        >
            <ChatMessage
                v-for="msg in messages"
                :key="msg.id ?? msg._localId"
                :message="msg"
                :bot-avatar="botAvatar"
                :bot-name="botName"
                :user-avatar="userAvatar"
                :user-name="userName"
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

        <!-- Spacer bawah agar pesan tidak ketutup composer -->
        <div class="h-2" aria-hidden="true"></div>
    </div>
</template>
