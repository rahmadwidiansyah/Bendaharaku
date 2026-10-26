<script setup>
/**
 * M3ChatSheet — panel chat kanan (supporting pane, >= 1024px).
 *
 * M3 Expressive Non-Modal Side Sheet:
 * - Paritas penuh dengan halaman chat utama (/chat).
 * - Auto-scroll ke bubble terakhir saat pertama dibuka & saat interaksi.
 * - Tombol jump-to-latest (M3JumpButton) adaptif di atas composer.
 * - Dukungan perintah cepat (M3ChatCommandSheet).
 * - Dukungan upload lampiran struk/bukti transaksi (M3ChatUploadSheet).
 * - Empty state informatif (M3ChatEmptyState).
 * - Penanganan interaksi bubble (suggest, regenerate, retry, retryEvidence).
 * - Navigasi pintas ke halaman chat penuh.
 * - Desain M3 Expressive: rounded 28dp di sudut kiri dalam, state layer,
 *   dan dismiss non-modal (klik luar & Escape).
 */
import { computed, onMounted, onBeforeUnmount, ref, watch, nextTick } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ChatArea from '@/Material/Components/Chat/M3ChatArea.vue'
import M3ChatComposer from '@/Material/Components/Chat/M3ChatComposer.vue'
import M3JumpButton from '@/Material/Components/Chat/M3JumpButton.vue'
import M3ChatCommandSheet from '@/Material/Components/Chat/M3ChatCommandSheet.vue'
import M3ChatUploadSheet from '@/Material/Components/Chat/M3ChatUploadSheet.vue'
import M3ChatEmptyState from '@/Material/Components/Chat/M3ChatEmptyState.vue'

import { useChat } from '@/Composables/useChat.js'
import { useChatPending } from '@/Composables/useChatPending.js'
import { useChatCommands } from '@/Composables/useChatCommands.js'
import { useEvidenceUpload } from '@/Composables/useEvidenceUpload.js'

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

// ── Core Chat Composable ──────────────────────────────────────────
const {
    messages,
    conversationId,
    isLoading,
    isTyping,
    hasMore,
    isLoadingMore,
    chatAreaRef,
    sendMessage,
    sendEvidenceMessage,
    loadMore,
    scrollToBottom,
    showJumpBtn,
    unreadCount,
    jumpToLatest,
    onScrollUpdate,
    retryLastMessage,
    regenerateMessage,
    updateEvidenceStatus,
    resumePending,
} = useChat()

// ── DOM References ────────────────────────────────────────────────
const sheetRef = ref(null)
const chatAreaComp = ref(null)
const composerRef = ref(null)

// ── Composer Height & M3JumpButton Offset ─────────────────────────
const composerHeight = ref(76)
let fabObserver = null

function syncComposerHeight() {
    const el = composerRef.value?.$el ?? composerRef.value
    if (el && el.offsetHeight) {
        composerHeight.value = el.offsetHeight + 16
    }
}

const fabStyle = computed(() => ({
    bottom: composerHeight.value + 'px',
}))

// ── Command Palette ───────────────────────────────────────────────
const {
    commands,
    commandsByCategory,
    categories,
    categoryLabels,
    isSheetOpen: isCommandSheetOpen,
    openSheet: openCommandSheet,
    closeSheet: closeCommandSheet,
    refreshCommands,
} = useChatCommands(page.props.commands ?? [])

const handleOpenCommands = async () => {
    if (commands.value.length === 0) {
        await refreshCommands()
    }
    openCommandSheet()
}

const handleCommandSelect = async (commandText) => {
    closeCommandSheet()
    await handleSend(commandText)
}

// ── Evidence / Receipt Upload ─────────────────────────────────────
const {
    setFile,
    upload,
    localPreviewUrl,
    evidence,
    uploadedFile,
    isUploading,
    reset: resetUpload,
} = useEvidenceUpload()

const isUploadSheetOpen = ref(false)
const attachmentPreview = computed(() => localPreviewUrl.value ?? evidence.value?.url ?? null)
const attachmentName = computed(() => uploadedFile.value?.name ?? evidence.value?.original_name ?? '')

const handleFileSelected = (file) => {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    setFile(file)
    isUploadSheetOpen.value = false
    nextTick(() => {
        composerRef.value?.$el?.querySelector('textarea')?.focus()
    })
}

const handleRemoveAttachment = () => {
    resetUpload()
}

// ── Send Message & Evidence ───────────────────────────────────────
async function handleSend(text) {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }

    const trimmed = (text ?? '').trim()
    const hasAttachmentSend = !!uploadedFile.value || !!evidence.value?.uuid

    if (!trimmed && !hasAttachmentSend) return

    if (hasAttachmentSend) {
        const previewBefore = localPreviewUrl.value

        if (!evidence.value?.uuid) {
            await upload()
        }

        if (!evidence.value?.uuid) {
            return
        }

        const uuid = evidence.value.uuid
        const previewUrl = previewBefore ?? localPreviewUrl.value ?? evidence.value.url
        await sendEvidenceMessage(uuid, previewUrl, trimmed)

        resetUpload()
        return
    }

    await sendMessage(trimmed)
}

// ── Message Action Handlers ───────────────────────────────────────
const handleSuggestionSelect = (text) => {
    composerRef.value?.insertText(text)
    nextTick(() => {
        composerRef.value?.$el?.querySelector('textarea')?.focus()
    })
}

const handleRegenerate = async (botMessage) => {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    await regenerateMessage(botMessage)
}

const handleRetryEvidence = async (uuid) => {
    if (!uuid) return
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    updateEvidenceStatus(uuid, 'PROCESSING')
    await scrollToBottom(true, true)
    try {
        const { data } = await axios.post(route('chat.evidence.retry', { uuid }))
        if (data.success) {
            const pendingBot = messages.value.find(
                (m) => m.role === 'assistant' && m.status === 'pending' && m.metadata?.evidence_uuid === uuid
            )
            if (pendingBot) {
                await scrollToBottom(true)
            }
        }
    } catch (e) {
        updateEvidenceStatus(uuid, 'FAILED')
        console.error('retryEvidence failed', e)
    }
    await scrollToBottom(true, true)
}

// ── History Sync & Auto-scroll ────────────────────────────────────
const normalizeMessage = (msg) => ({
    id:         msg.id,
    _localId:   msg._localId ?? null,
    role:       msg.role,
    status:     msg.status ?? 'completed',
    content:    msg.content ?? [],
    metadata:   msg.metadata ?? {},
    created_at: msg.created_at,
})

const hasLoaded = ref(false)
let loadToken = 0

const loadLatest = async () => {
    const token = ++loadToken
    try {
        const { data } = await axios.get(route('chat.history'), {
            params: { limit: 30 },
        })
        if (token !== loadToken) return

        if (data.conversation_id) {
            conversationId.value = data.conversation_id
        }
        hasMore.value = data.has_more ?? false
        messages.value = (data.messages ?? []).map(normalizeMessage)
        hasLoaded.value = true

        await nextTick()
        if (chatAreaComp.value?.el) {
            chatAreaRef.value = chatAreaComp.value.el
        }
        resumePending()
        await scrollToBottom(false, true)
    } catch (err) {
        console.warn('M3ChatSheet: loadLatest failed', err)
        hasLoaded.value = true
    }
}

// ── Non-Modal Dismiss Handling ────────────────────────────────────
const { setChatSurfaceOpen } = useChatPending()

const onEsc = (e) => {
    if (e.key === 'Escape') {
        if (isCommandSheetOpen.value || isUploadSheetOpen.value) return
        emit('close')
    }
}

const onPointerDownOutside = (e) => {
    if (!props.show) return
    const sheetEl = sheetRef.value
    if (!sheetEl) return
    if (sheetEl.contains(e.target)) return
    if (e.target.closest('.m3-modal') || e.target.closest('.m3-modal__overlay')) return
    if (e.target.closest('.m3-chat-bubble')) return

    emit('close')
}

watch(() => props.show, async (open) => {
    if (open) {
        setChatSurfaceOpen(true)
        await loadLatest()
        await nextTick()
        syncComposerHeight()
        try {
            const target = composerRef.value?.$el
            if (target && typeof ResizeObserver !== 'undefined') {
                fabObserver?.disconnect()
                fabObserver = new ResizeObserver(() => syncComposerHeight())
                fabObserver.observe(target)
            }
        } catch (_) {}
        document.addEventListener('keydown', onEsc)
        setTimeout(() => {
            document.addEventListener('pointerdown', onPointerDownOutside)
        }, 60)
    } else {
        setChatSurfaceOpen(false)
        try { fabObserver?.disconnect() } catch (_) {}
        document.removeEventListener('keydown', onEsc)
        document.removeEventListener('pointerdown', onPointerDownOutside)
    }
}, { immediate: true })

onBeforeUnmount(() => {
    setChatSurfaceOpen(false)
    try { fabObserver?.disconnect() } catch (_) {}
    document.removeEventListener('keydown', onEsc)
    document.removeEventListener('pointerdown', onPointerDownOutside)
})
</script>

<template>
    <Teleport to="body">
        <Transition name="m3-chat-sheet">
            <div
                v-if="show"
                ref="sheetRef"
                class="m3-chat-sheet"
                role="dialog"
                aria-modal="false"
                :aria-label="t('chat.assistant')"
            >
                <!-- Header -->
                <header class="m3-chat-sheet__head">
                    <span class="m3-chat-sheet__icon" aria-hidden="true">
                        <img v-if="botProfile.avatar" :src="botProfile.avatar" alt="" class="m3-chat-sheet__bot-img" />
                        <span v-else aria-hidden="true">{{ botProfile.name.charAt(0) }}</span>
                    </span>
                    <div class="m3-chat-sheet__titles">
                        <p class="m3-chat-sheet__name">{{ botProfile.name }}</p>
                        <p class="m3-chat-sheet__sub" :class="{ 'm3-chat-sheet__sub--typing': isTyping }">
                            {{ isTyping ? t('chat.typing') : t('chat.assistant') }}
                        </p>
                    </div>

                    <div class="m3-chat-sheet__actions">
                        <!-- Buka Halaman Penuh (/chat) -->
                        <Link
                            :href="route('chat.index')"
                            class="m3-chat-sheet__action-btn"
                            :title="t('chat.openFullPage')"
                            :aria-label="t('chat.openFullPage')"
                            @click="emit('close')"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </Link>

                        <!-- Tombol Tutup -->
                        <M3IconButton :aria-label="t('common.close')" @click="emit('close')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </M3IconButton>
                    </div>
                </header>

                <!-- Scrollable Chat Messages -->
                <M3ChatArea
                    ref="chatAreaComp"
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
                    @regenerate="handleRegenerate"
                    @suggest="handleSuggestionSelect"
                    @retry="retryLastMessage"
                    @retryEvidence="handleRetryEvidence"
                >
                    <template v-if="messages.length === 0 && !isLoading && hasLoaded">
                        <M3ChatEmptyState
                            :bot-name="botProfile.name"
                            :bot-avatar="botProfile.avatar"
                            @select="handleSuggestionSelect"
                        />
                    </template>
                </M3ChatArea>

                <!-- Tombol Jump To Latest ("Terbaru") -->
                <M3JumpButton
                    :show="showJumpBtn"
                    :unread-count="unreadCount"
                    :bottom="fabStyle.bottom"
                    @click="jumpToLatest"
                />

                <!-- Chat Composer -->
                <M3ChatComposer
                    ref="composerRef"
                    :is-loading="isLoading"
                    :is-uploading="isUploading"
                    :attachment-preview="attachmentPreview"
                    :attachment-name="attachmentName"
                    @send="handleSend"
                    @open-commands="handleOpenCommands"
                    @open-upload="isUploadSheetOpen = true"
                    @remove-attachment="handleRemoveAttachment"
                />

                <!-- Command Sheet Dialog -->
                <M3ChatCommandSheet
                    v-model="isCommandSheetOpen"
                    :commands-by-category="commandsByCategory"
                    :categories="categories"
                    :category-labels="categoryLabels"
                    @select="handleCommandSelect"
                />

                <!-- Upload Sheet Dialog -->
                <M3ChatUploadSheet
                    v-model="isUploadSheetOpen"
                    @camera="handleFileSelected"
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
    /* M3 Expressive: rounded 28dp di sudut kiri dalam */
    border-top-left-radius: var(--m3-radius-xl);
    border-bottom-left-radius: var(--m3-radius-xl);
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
    transition: color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-chat-sheet__sub--typing {
    color: var(--m3-color-primary);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
}

.m3-chat-sheet__actions {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    flex-shrink: 0;
}

.m3-chat-sheet__action-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    text-decoration: none;
    cursor: pointer;
    overflow: hidden;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-chat-sheet__action-btn::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-chat-sheet__action-btn:hover::after {
    opacity: 0.08;
}

.m3-chat-sheet__action-btn:active::after {
    opacity: 0.12;
}

.m3-chat-sheet__action-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-chat-sheet__action-btn svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
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
