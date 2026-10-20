<script setup>
/**
 * M3ChatArea — kontainer scrollable riwayat pesan (paritas ChatArea Default).
 * Jump-to-latest tetap di halaman (M3JumpButton), bukan di sini.
 */
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3ChatMessage from './M3ChatMessage.vue'
import M3TypingIndicator from './M3TypingIndicator.vue'

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

const emit = defineEmits(['loadMore', 'scrollUpdate', 'retry', 'regenerate', 'suggest', 'retryEvidence'])

/** Bot pending/processing dirender sebagai bubble mengetik — hindari indikator ganda */
const hasPendingBotMessage = computed(() =>
    props.messages.some(
        (m) => m?.role === 'assistant' && (m.status === 'pending' || m.status === 'processing'),
    ),
)

/** Avatar grouping: tampilkan avatar hanya di awal grup role yang sama */
function shouldShowAvatar(messages, index) {
    if (index === 0) return true
    return messages[index - 1].role !== messages[index].role
}

/** Indikator mengetik setelah pesan user = awal grup bot (spasi antar-pengirim) */
const lastIsUser = computed(() => {
    const last = props.messages[props.messages.length - 1]
    return !!last && last.role === 'user'
})

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
        class="m3-chat-area"
        @scroll="onScroll"
        role="log"
        :aria-label="t('chat.history')"
        aria-live="polite"
    >
        <div class="m3-chat-area__spacer" aria-hidden="true"></div>

        <!-- Tombol eksplisit "Tampilkan Riwayat Sebelumnya" -->
        <Transition name="m3-area-fade">
            <div v-if="hasMore && !isLoadingMore" class="m3-chat-area__load-row">
                <button type="button" class="m3-chat-area__load-btn" @click="emit('loadMore')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    {{ t('chat.loadMore') }}
                </button>
            </div>
        </Transition>

        <!-- Indikator loading riwayat -->
        <div v-if="isLoadingMore" class="m3-chat-area__loading" role="status">
            <M3CircularProgress class="m3-chat-area__spinner" size="sm" />
            {{ t('chat.loadingMore') }}
        </div>

        <!-- Slot empty state -->
        <slot />

        <!-- Daftar pesan -->
        <TransitionGroup tag="div" name="m3-msg" class="m3-chat-area__list">
            <M3ChatMessage
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
                @retryEvidence="emit('retryEvidence', $event)"
            />
        </TransitionGroup>

        <!-- Indikator mengetik -->
        <Transition name="m3-area-fade">
            <M3TypingIndicator
                v-if="isTyping && !hasPendingBotMessage"
                :bot-avatar="botAvatar"
                :bot-name="botName"
                :class="{ 'm3-typing--group-start': lastIsUser }"
            />
        </Transition>

        <div class="m3-chat-area__spacer" aria-hidden="true"></div>
    </div>
</template>

<style scoped>
.m3-chat-area {
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
    scroll-behavior: auto;
}

.m3-chat-area__spacer {
    height: var(--m3-space-2);
}

.m3-chat-area__load-row {
    display: flex;
    justify-content: center;
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-chat-area__load-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-height: 2rem;
    padding: 0 var(--m3-space-4);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-chat-area__load-btn:hover {
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
}

.m3-chat-area__load-btn:active {
    transform: scale(0.97);
}

.m3-chat-area__load-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-chat-area__load-btn svg {
    width: 0.875rem;
    height: 0.875rem;
    flex-shrink: 0;
}

.m3-chat-area__loading {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-3);
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-chat-area__spinner {
    color: var(--m3-color-primary);
}

.m3-chat-area__list {
    display: flex;
    flex-direction: column;
    padding-bottom: var(--m3-space-2);
}

/* Transisi masuk pesan & load-more (M3 motion, dimatikan reduced-motion global) */
.m3-msg-enter-active {
    transition:
        opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects),
        transform var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-msg-enter-from {
    opacity: 0;
    transform: translateY(0.25rem);
}

.m3-area-fade-enter-active,
.m3-area-fade-leave-active {
    transition: opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-area-fade-enter-from,
.m3-area-fade-leave-to {
    opacity: 0;
}
</style>
