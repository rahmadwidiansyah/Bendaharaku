<script setup>
/**
 * ChatArea.vue
 *
 * Scrollable container untuk daftar pesan.
 * Tidak mengandung jump-to-latest button — itu ada di Chat/Index.vue.
 *
 * "Tampilkan Riwayat Sebelumnya" — tombol eksplisit di paling atas,
 * muncul hanya jika hasMore = true. User harus klik untuk load lebih lama,
 * tidak auto-trigger saat scroll agar tidak mengganggu UX.
 */

import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()

const emit = defineEmits(['loadMore', 'scrollUpdate', 'retry', 'regenerate', 'suggest'])

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
        :aria-label="t('chat.history')"
        aria-live="polite"
    >
        <div class="h-2" aria-hidden="true"></div>

        <!--
            Tombol "Tampilkan Riwayat Sebelumnya"
            Muncul di paling atas saat ada pesan lebih lama (hasMore = true).
            Eksplisit — user yang memilih kapan mau load, tidak auto-trigger.
        -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="hasMore && !isLoadingMore" class="flex justify-center py-3 px-4">
                <button
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 rounded-full
                           bg-gray-800/80 border border-white/10 text-xs font-semibold
                           text-gray-400 hover:text-white hover:bg-gray-700/80
                           hover:border-white/20 active:scale-95
                           transition-all duration-150 shadow-sm"
                    @click="emit('loadMore')"
                >
                    <!-- Clock icon -->
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    {{ t('chat.loadMore') }}
                </button>
            </div>
        </Transition>

        <!-- Loading indicator saat sedang fetch -->
        <div v-if="isLoadingMore" class="flex justify-center py-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/60 border border-white/8 text-2xs text-gray-500">
                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ t('chat.loadingMore') }}
            </div>
        </div>

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
                @suggest="emit('suggest', $event)"
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
