<script setup>
/**
 * ChatArea.vue
 *
 * Scrollable container untuk daftar pesan.
 * Tidak mengandung jump-to-latest button — itu ada di Chat/Index.vue
 * sebagai floating overlay di luar scroll container.
 */

import { ref } from 'vue'
import ChatMessage     from './ChatMessage.vue'
import TypingIndicator from './Messages/TypingIndicator.vue'

const props = defineProps({
    messages:      { type: Array,   required: true },
    isTyping:      { type: Boolean, default: false },
    isLoadingMore: { type: Boolean, default: false },
    hasMore:       { type: Boolean, default: false },
    botAvatar:     { type: String,  default: null },
    botName:       { type: String,  default: 'Ken-Chan' },
    userAvatar:    { type: String,  default: null },
    userName:      { type: String,  default: 'Kamu' },
})

const emit = defineEmits(['loadMore', 'scrollUpdate', 'retry', 'regenerate'])

/** Avatar grouping: tampilkan avatar hanya di awal grup role yang sama */
function shouldShowAvatar(messages, index) {
    if (index === 0) return true
    return messages[index - 1].role !== messages[index].role
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
        <div v-if="isLoadingMore" class="flex justify-center py-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/60 border border-white/8 text-2xs text-gray-500">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Memuat...
            </div>
        </div>

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

        <!-- Spacer bawah -->
        <div class="h-2" aria-hidden="true"></div>
    </div>
</template>
