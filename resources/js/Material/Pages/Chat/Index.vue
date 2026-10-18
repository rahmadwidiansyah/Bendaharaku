<script setup>
/**
 * Material/Pages/Chat/Index.vue — Chat M3 Expressive.
 *
 * Kontrak data & logika IDENTIK dengan Pages/Chat/Index.vue Default:
 * props, route, composables (useChat/useChatCommands/useVisualViewport/
 * useEvidenceUpload/useLayoutPreference), dan semua endpoint chat.*
 * tidak berubah. Adaptasi hanya presentasi (token --m3-*).
 *
 * Shell minimal — komponen chat M3 menyusul (Milestone 2-5).
 */
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3ChatHeader from '@/Material/Components/Chat/M3ChatHeader.vue'
import M3ChatArea from '@/Material/Components/Chat/M3ChatArea.vue'
import M3ChatComposer from '@/Material/Components/Chat/M3ChatComposer.vue'
import M3ChatCommandSheet from '@/Material/Components/Chat/M3ChatCommandSheet.vue'
import M3ChatUploadSheet from '@/Material/Components/Chat/M3ChatUploadSheet.vue'
import M3ChatEmptyState from '@/Material/Components/Chat/M3ChatEmptyState.vue'
import M3JumpButton from '@/Material/Components/Chat/M3JumpButton.vue'
import { useChat } from '@/Composables/useChat.js'
import { useChatCommands } from '@/Composables/useChatCommands.js'
import { useVisualViewport } from '@/Composables/useVisualViewport.js'
import { useEvidenceUpload } from '@/Composables/useEvidenceUpload.js'
import { useLayoutPreference } from '@/Composables/useLayoutPreference.js'

const props = defineProps({
    initialMessages: { type: Array, default: () => [] },
    initialHasMore:  { type: Boolean, default: false },
    conversation:    { type: Object,  default: null },
    botProfile:      { type: Object,  default: () => ({ name: 'Ken-Chan', avatar: null }) },
    commands:        { type: Array,  default: () => [] },
})

const page     = usePage()
const authUser = page.props.auth?.user ?? {}

const { t } = useI18n()

const { isDesktopLayout } = useLayoutPreference()
const hideNav = computed(() => !isDesktopLayout.value)
const isMobileChat = computed(() => !isDesktopLayout.value)

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
    updateEvidenceInMessage,
    updateEvidenceStatus,
    resumePending,
} = useChat(props.initialMessages, props.conversation?.id ?? null, props.initialHasMore)

const {
    commandsByCategory,
    categories,
    categoryLabels,
    isSheetOpen,
    openSheet,
    closeSheet,
} = useChatCommands(props.commands)

const { height: viewportHeight } = useVisualViewport()

const {
    setFile,
    setEvidenceFromShare,
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
const hasAttachment = computed(() => !!uploadedFile.value || !!evidence.value?.uuid)

const containerStyle = computed(() => {
    if (!isMobileChat.value) return {}
    return {
        height: viewportHeight.value > 0 ? `${viewportHeight.value}px` : '100dvh',
    }
})

const composerRef = ref(null)
const chatAreaComp = ref(null)

const composerHeight = ref(76)
let fabObserver = null
function syncComposerHeight() {
    const el = composerRef.value?.$el ?? composerRef.value
    if (el && el.offsetHeight) {
        composerHeight.value = el.offsetHeight + 16
    } else if (composerRef.value?.$el) {
        composerHeight.value = composerRef.value.$el.offsetHeight + 16
    }
}
const fabStyle = computed(() => ({
    bottom: composerHeight.value + 'px',
}))

onMounted(async () => {
    await nextTick()
    if (chatAreaComp.value?.el) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    resumePending()
    await scrollToBottom(false)

    try {
        const params = new URLSearchParams(window.location.search)
        const shareUuid = params.get('share_evidence_uuid')
        const shareHold = params.get('share_hold')
        if (shareUuid && shareHold) {
            const shareEvidence = {
                uuid: shareUuid,
                url: route('chat.evidence.image', { uuid: shareUuid }),
                original_name: 'Shared image',
            }
            setEvidenceFromShare(shareEvidence)
            try {
                const currentShareUrl = window.location.href
                window.history.replaceState({}, '', route('dashboard'))
                window.history.pushState({}, '', currentShareUrl)
            } catch (_) {}
            nextTick(() => {
                composerRef.value?.$el?.querySelector('textarea')?.focus()
            })
            setTimeout(() => {
                const url = new URL(window.location.href)
                url.searchParams.delete('share_evidence_uuid')
                url.searchParams.delete('share_hold')
                url.searchParams.delete('share_caption')
                window.history.replaceState({}, '', url.pathname)
            }, 500)
        } else {
            const evUuid = params.get('evidence_uuid') || params.get('evidenceUuid')
            if (evUuid) {
                let tries = 0
                const highlight = () => {
                    const found = messages.value.find(
                        (m) => (m.metadata?.evidence_uuid === evUuid) ||
                               m.content?.some((c) => (c.evidenceUuid === evUuid || c.evidence_uuid === evUuid))
                    )
                    if (found) scrollToBottom(true)
                    else if (tries++ < 10) setTimeout(highlight, 1000)
                }
                setTimeout(highlight, 500)
                setTimeout(() => {
                    const url = new URL(window.location.href)
                    url.searchParams.delete('evidence_uuid')
                    url.searchParams.delete('evidenceUuid')
                    url.searchParams.delete('share')
                    window.history.replaceState({}, '', url.pathname)
                }, 3000)
            }
        }
    } catch (_) {}

    await nextTick()
    syncComposerHeight()
    try {
        const target = composerRef.value?.$el
        if (target && typeof ResizeObserver !== 'undefined') {
            fabObserver = new ResizeObserver(() => syncComposerHeight())
            fabObserver.observe(target)
        }
        window.addEventListener('resize', syncComposerHeight)
    } catch (_) {}
})

onBeforeUnmount(() => {
    try { fabObserver?.disconnect() } catch (_) {}
    window.removeEventListener('resize', syncComposerHeight)
})

watch(isTyping, async (isNowTyping) => {
    if (isNowTyping) {
        await nextTick()
        if (typeof scrollToBottom === 'function') {
            await scrollToBottom()
        }
    }
})
watch([attachmentPreview, () => attachmentName.value], async () => {
    await nextTick()
    syncComposerHeight()
})

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

function handleFileSelected(file) {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }

    setFile(file)
    isUploadSheetOpen.value = false
    nextTick(() => {
        composerRef.value?.$el?.querySelector('textarea')?.focus()
    })
}

function handleRemoveAttachment() {
    resetUpload()
}

async function handleCommandSelect(commandText) {
    closeSheet()
    await handleSend(commandText)
}

function handleSuggestionSelect(text) {
    composerRef.value?.insertText(text)
    nextTick(() => {
        composerRef.value?.$el?.querySelector('textarea')?.focus()
    })
}

async function handleRegenerate(botMessage) {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    await regenerateMessage(botMessage)
}

async function handleRetryEvidence(uuid) {
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
</script>

<template>
    <M3Layout :hide-nav="hideNav" :title="t('chat.assistant')">
        <Head :title="t('chat.assistant')" />

        <div
            class="m3-chat"
            :class="{ 'm3-chat--mobile': isMobileChat }"
            :style="containerStyle"
        >
            <M3ChatHeader
                :bot-name="botProfile.name"
                :bot-avatar="botProfile.avatar"
                :is-typing="isTyping"
            />

            <M3ChatArea
                ref="chatAreaComp"
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
                @retryEvidence="handleRetryEvidence"
            >
                <template v-if="messages.length === 0 && !isLoading">
                    <M3ChatEmptyState
                        :bot-name="botProfile.name"
                        :bot-avatar="botProfile.avatar"
                        @select="handleSuggestionSelect"
                    />
                </template>
            </M3ChatArea>

            <M3JumpButton
                :show="showJumpBtn"
                :unread-count="unreadCount"
                :bottom="fabStyle.bottom"
                @click="jumpToLatest"
            />

            <M3ChatComposer
                ref="composerRef"
                :is-loading="isLoading"
                :is-uploading="isUploading"
                :attachment-preview="attachmentPreview"
                :attachment-name="attachmentName"
                @send="handleSend"
                @open-commands="openSheet"
                @open-upload="isUploadSheetOpen = true"
                @remove-attachment="handleRemoveAttachment"
            />

            <M3ChatCommandSheet
                v-model="isSheetOpen"
                :commands-by-category="commandsByCategory"
                :categories="categories"
                :category-labels="categoryLabels"
                @select="handleCommandSelect"
            />

            <M3ChatUploadSheet
                v-model="isUploadSheetOpen"
                @camera="handleFileSelected"
            />
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-chat {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    height: 100dvh;
    background: var(--m3-color-surface);
    overflow: hidden;
}

.m3-chat--mobile {
    position: fixed;
    inset: 0;
    max-width: none;
    z-index: 30;
}

@media (min-width: 1024px) {
    .m3-chat {
        height: calc(100dvh - var(--m3-size-appbar) - var(--m3-space-8));
    }
}
</style>
