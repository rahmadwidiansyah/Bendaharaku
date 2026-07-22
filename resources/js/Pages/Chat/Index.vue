<script setup>
/**
 * Pages/Chat/Index.vue
 *
 * Halaman utama Web Chat Bendaharaku.
 *
 * Layout: fullscreen (hideNav=true), max-w-2xl centered di desktop.
 *
 * Jump-to-latest button ada di sini sebagai FAB overlay,
 * BUKAN di dalam ChatArea — agar tidak masuk ke scroll container
 * dan tidak menutupi bubble/card.
 */

import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ChatHeader      from '@/Components/Chat/ChatHeader.vue'
import ChatArea        from '@/Components/Chat/ChatArea.vue'
import ChatComposer    from '@/Components/Chat/ChatComposer.vue'
import CommandSheet    from '@/Components/Chat/CommandSheet.vue'
import ChatEmptyState  from '@/Components/Chat/ChatEmptyState.vue'
import ChatUploadSheet    from '@/Components/Chat/ChatUploadSheet.vue'
import EvidenceReviewSheet from '@/Components/Chat/EvidenceReviewSheet.vue'
import { useChat }            from '@/Composables/useChat.js'
import { useChatCommands }    from '@/Composables/useChatCommands.js'
import { useVisualViewport }  from '@/Composables/useVisualViewport.js'
import { useEvidenceUpload }  from '@/Composables/useEvidenceUpload.js'

// ── Props dari Inertia (server-side) ──────────────────────────────
const props = defineProps({
    initialMessages: { type: Array,   default: () => [] },
    initialHasMore:  { type: Boolean, default: false },
    conversation:    { type: Object,  default: null },
    botProfile:      { type: Object,  default: () => ({ name: 'Ken-Chan', avatar: null }) },
    commands:        { type: Array,   default: () => [] },
})

// ── Auth user ─────────────────────────────────────────────────────
const page     = usePage()
const authUser = page.props.auth?.user ?? {}

// ── Composables ───────────────────────────────────────────────────
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
} = useChat(props.initialMessages, props.conversation?.id ?? null, props.initialHasMore)

const {
    commandsByCategory,
    categories,
    categoryLabels,
    isSheetOpen,
    openSheet,
    closeSheet,
} = useChatCommands(props.commands)

const { t } = useI18n()

// ── Visual viewport (responsive to Android keyboard) ───────────────
const { height: viewportHeight } = useVisualViewport()

// ── Evidence upload ───────────────────────────────────────────────
const {
    setFile,
    upload,
    localPreviewUrl,
    evidence,
    reset: resetUpload,
} = useEvidenceUpload()

const isUploadSheetOpen = ref(false)

// ── Evidence review ───────────────────────────────────────────────
const isReviewSheetOpen = ref(false)
const reviewUuid        = ref(null)

// Dynamic container height: use visualViewport when available, fallback to 100dvh
const containerStyle = computed(() => ({
    height: viewportHeight.value > 0
        ? viewportHeight.value + 'px'
        : '100dvh',
}))

// ── Refs ──────────────────────────────────────────────────────────
const composerRef  = ref(null)
const chatAreaComp = ref(null)

// ── Lifecycle ─────────────────────────────────────────────────────
onMounted(async () => {
    await nextTick()
    if (chatAreaComp.value?.el) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    await scrollToBottom(false)
})

// Pantau jika bot mulai mengetik, otomatis scroll ke bawah agar indikator 3 titik terlihat
watch(isTyping, async (isNowTyping) => {
    if (isNowTyping) {
        await nextTick()
        if (typeof scrollToBottom === 'function') {
            await scrollToBottom()
        }
    }
})

// ── Handlers ──────────────────────────────────────────────────────

async function handleSend(text) {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    await sendMessage(text)
}

async function handleFileSelected(file) {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }

    // Siapkan file dan buat local preview URL
    setFile(file)

    // Upload ke backend dulu, dapatkan UUID
    await upload()

    // Setelah upload selesai, evidence.value berisi { uuid, url, ... }
    if (evidence.value?.uuid) {
        await sendEvidenceMessage(evidence.value.uuid, localPreviewUrl.value ?? evidence.value.url)
    }

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

function handleReview(uuid) {
    reviewUuid.value        = uuid
    isReviewSheetOpen.value = true
}

function handleCommitSuccess({ uuid }) {
    // Update bubble gambar agar status berubah ke COMMITTED dan tombol Review hilang
    updateEvidenceInMessage(uuid)
}
</script>

<template>
    <AuthenticatedLayout :hideNav="true">
        <Head :title="t('chat.assistant')" />

        <!--
            flex column, full viewport height.
            Uses visualViewport API for accurate height on Android when keyboard opens.
            `relative` agar FAB bisa absolute di dalam container ini.
        -->
        <div class="flex flex-col max-w-2xl mx-auto relative" :style="containerStyle">

            <!-- Header -->
            <ChatHeader
                :bot-name="botProfile.name"
                :bot-avatar="botProfile.avatar"
                :is-typing="isTyping"
            />

            <!-- Chat area (flex-1, scrollable) -->
            <ChatArea
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
                @review="handleReview"
                class="flex-1"
            >
                <template v-if="messages.length === 0 && !isLoading">
                    <ChatEmptyState
                        :bot-name="botProfile.name"
                        :bot-avatar="botProfile.avatar"
                        @select="handleSuggestionSelect"
                    />
                </template>
            </ChatArea>

            <!--
                ── Jump-to-Latest FAB ─────────────────────────────────────
                `absolute` di dalam container `relative` ini.
                bottom dihitung dari atas composer:
                  - composer ~60px + gap 16px = bottom-[76px]
                Tidak masuk ke scroll container, tidak pernah nutup bubble.
                z-20 > ChatArea, < modal (z-50).
            -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-3 scale-90"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-3 scale-90"
            >
                <button
                    v-if="showJumpBtn"
                    @click="jumpToLatest"
                    class="absolute bottom-[76px] right-4 z-20 flex items-center gap-1.5 pl-2.5 pr-3 py-2 rounded-full bg-gray-900 border border-white/12 shadow-xl shadow-black/40 hover:bg-gray-800 hover:border-white/20 active:scale-95 transition-all"
                    :aria-label="t('chat.scrollToBottom')"
                    style="backdrop-filter: blur(12px);"
                >
                    <!-- Arrow down icon -->
                    <span class="w-5 h-5 rounded-full bg-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                    <!-- Label + badge -->
                    <span class="text-xs font-semibold text-white leading-none">
                        <template v-if="unreadCount > 0">
                            {{ unreadCount }} {{ t('chat.newMessages') }}
                        </template>
                        <template v-else>
                            {{ t('chat.latest') }}
                        </template>
                    </span>
                </button>
            </Transition>

            <!-- Command bottom sheet -->
            <CommandSheet
                v-model="isSheetOpen"
                :commands-by-category="commandsByCategory"
                :categories="categories"
                :category-labels="categoryLabels"
                @select="handleCommandSelect"
            />

            <!-- Composer (sticky bottom) -->
            <ChatComposer
                ref="composerRef"
                :is-loading="isLoading"
                @send="handleSend"
                @openCommands="openSheet"
                @openUpload="isUploadSheetOpen = true"
            />

            <!-- Upload bottom sheet -->
            <ChatUploadSheet
                v-model="isUploadSheetOpen"
                @camera="handleFileSelected"
            />

            <!-- Evidence review sheet -->
            <EvidenceReviewSheet
                v-model="isReviewSheetOpen"
                :evidence-uuid="reviewUuid"
                @commitSuccess="handleCommitSuccess"
            />

        </div>
    </AuthenticatedLayout>
</template>
