<script setup>
/**
 * M3ChatSheet — panel chat kanan (supporting pane, >= 1024px).
 *
 * P1 F-pattern: chat bisa dibuka dari halaman mana pun tanpa navigasi.
 * Memakai useChat() sendiri (state independen dari halaman /chat) dan
 * me-reuse M3ChatArea/M3ChatComposer persis seperti halaman chat.
 * < 1024px komponen ini tidak dirender (halaman /chat tetap jalannya).
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ChatArea from '@/Material/Components/Chat/M3ChatArea.vue'
import M3ChatComposer from '@/Material/Components/Chat/M3ChatComposer.vue'
import { useChat } from '@/Composables/useChat.js'
import { useChatPending } from '@/Composables/useChatPending.js'
import axios from 'axios'

const props = defineProps({
    show: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const { t } = useI18n()
const page = usePage()

const authUser = computed(() => page.props.auth?.user ?? {})
const botProfile = computed(() => ({
    name: page.props.bot_name ?? 'Ken-Chan',
    avatar: page.props.bot_avatar ?? null,
}))

const {
    messages,
    isTyping,
    isLoading,
    hasMore,
    isLoadingMore,
    showJumpBtn,
    sendMessage,
    loadMore,
    onScrollUpdate,
    jumpToLatest,
    updateEvidenceStatus,
} = useChat()

// Sheet terbuka → tarik riwayat terbaru (endpoint chat.history).
const hasLoaded = ref(false)
let loadToken = 0

const loadLatest = async () => {
    const token = ++loadToken
    hasLoaded.value = true
    try {
        const { data } = await axios.get(route('chat.history'), {
            params: { limit: 20 },
        })
        if (token !== loadToken) return
        messages.value = (data.messages ?? []).map(m => m)
    } catch {
        /* biarkan area kosong menampilkan empty-state */
    }
}

watch(() => props.show, (open) => {
    if (open) {
        hasLoaded.value = false
        loadLatest()
        document.addEventListener('keydown', onEsc)
    } else {
        document.removeEventListener('keydown', onEsc)
    }
})

const onEsc = (e) => { if (e.key === 'Escape') emit('close') }
onMounted(() => { if (props.show) document.addEventListener('keydown', onEsc) })
onUnmounted(() => document.removeEventListener('keydown', onEsc))

const handleSuggestionSelect = (text) => sendMessage(text)
</script>

<template>
    <Teleport to="body">
        <Transition name="m3-chat-sheet">
            <div v-if="show" class="m3-chat-sheet" role="dialog" aria-modal="false" :aria-label="t('chat.assistant')">
                <header class="m3-chat-sheet__head">
                    <span class="m3-chat-sheet__icon" aria-hidden="true">
                        <img v-if="botProfile.avatar" :src="botProfile.avatar" alt="" class="m3-chat-sheet__bot-img" />
                        <span v-else aria-hidden="true">{{ botProfile.name.charAt(0) }}</span>
                    </span>
                    <div class="m3-chat-sheet__titles">
                        <p class="m3-chat-sheet__name">{{ botProfile.name }}</p>
                        <p class="m3-chat-sheet__sub">{{ t('chat.assistant') }}</p>
                    </div>
                    <M3IconButton :aria-label="t('common.close')" @click="emit('close')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </M3IconButton>
                </header>

                <M3ChatArea
                    class="m3-chat-sheet__area"
                    :messages="messages"
                    :is-typing="isTyping"
                    :is-loading-more="isLoadingMore"
                    :has-more="hasMore"
                    :bot-avatar="botProfile.avatar"
                    :bot-name="botProfile.name"
                    :user-avatar="authUser.avatar_url ?? null"
                    :user-name="authUser.name ?? 'Kamu'"
                    @loadMore="loadMore"
                    @scrollUpdate="onScrollUpdate"
                    @retryEvidence="() => {}"
                />

                <M3ChatComposer
                    :is-loading="isLoading"
                    :is-uploading="false"
                    @send="sendMessage"
                />
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.m3-chat-sheet {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    z-index: 70; /* di atas appbar (40) & wavy (60), di bawah modal (z-300) */
    display: flex;
    flex-direction: column;
    width: min(28rem, calc(100vw - var(--m3-size-rail) - var(--m3-space-8)));
    background: var(--m3-color-surface);
    border-left: 1px solid var(--m3-color-outline-variant);
    box-shadow: var(--m3-elevation-3);
    overflow: hidden;
}

.m3-chat-sheet__head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    min-height: var(--m3-size-appbar);
    padding: var(--m3-space-2) var(--m3-space-3);
    border-bottom: 1px solid var(--m3-color-outline-variant);
    background: var(--m3-color-surface-container);
    flex-shrink: 0;
}

.m3-chat-sheet__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    overflow: hidden;
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    flex-shrink: 0;
}

.m3-chat-sheet__bot-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.m3-chat-sheet__titles {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.m3-chat-sheet__name {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-chat-sheet__sub {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-chat-sheet__area {
    flex: 1;
    min-height: 0;
}

.m3-chat-sheet-enter-active,
.m3-chat-sheet-leave-active {
    transition: transform var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-chat-sheet-enter-from,
.m3-chat-sheet-leave-to {
    transform: translateX(100%);
}

@media (prefers-reduced-motion: reduce) {
    .m3-chat-sheet-enter-active,
    .m3-chat-sheet-leave-active {
        transition: none;
    }
}
</style>
