<script setup>
/**
 * Pages/Chat/Index.vue
 *
 * Halaman utama Web Chat Bendaharaku.
 *
 * Layout: fullscreen (hideNav=true), max-w-2xl centered di desktop.
 *
 * Alur:
 * 1. Inertia props: initialMessages, conversation, botProfile, commands
 * 2. useChat() → state messages, send, load history
 * 3. useChatCommands() → command list, sheet state
 * 4. ChatHeader → avatar/nama/status bot
 * 5. ChatArea   → daftar bubble pesan, scroll
 * 6. CommandSheet → bottom sheet pilihan command
 * 7. ChatComposer → textarea + kirim
 */

import { ref, onMounted, nextTick } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ChatHeader     from '@/Components/Chat/ChatHeader.vue'
import ChatArea       from '@/Components/Chat/ChatArea.vue'
import ChatComposer   from '@/Components/Chat/ChatComposer.vue'
import CommandSheet   from '@/Components/Chat/CommandSheet.vue'
import ChatEmptyState from '@/Components/Chat/ChatEmptyState.vue'
import { useChat }         from '@/Composables/useChat.js'
import { useChatCommands } from '@/Composables/useChatCommands.js'

// ── Props dari Inertia (server-side) ──────────────────────────────
const props = defineProps({
    initialMessages: { type: Array,  default: () => [] },
    conversation:    { type: Object, default: null },
    botProfile:      { type: Object, default: () => ({ name: 'Ken-Chan', avatar: null }) },
    commands:        { type: Array,  default: () => [] },
})

// ── Auth user ─────────────────────────────────────────────────────
const page      = usePage()
const authUser  = page.props.auth?.user ?? {}

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
    loadMore,
    scrollToBottom,
} = useChat(props.initialMessages, props.conversation?.id ?? null)

const {
    commandsByCategory,
    categories,
    categoryLabels,
    isSheetOpen,
    openSheet,
    closeSheet,
} = useChatCommands(props.commands)

// ── Refs ──────────────────────────────────────────────────────────
const composerRef  = ref(null)
const chatAreaComp = ref(null)  // ChatArea component ref

// ── Lifecycle ─────────────────────────────────────────────────────
onMounted(async () => {
    // chatAreaRef dari useChat harus menunjuk ke DOM element scroll container
    // ChatArea mengexpose el via defineExpose({ el: containerRef })
    await nextTick()
    if (chatAreaComp.value?.el) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    // Scroll ke bawah tanpa animasi saat initial load
    await scrollToBottom(false)
})

// ── Handlers ──────────────────────────────────────────────────────

async function handleSend(text) {
    // Pastikan chatAreaRef terpasang
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }
    await sendMessage(text)
}

function handleCommandSelect(commandText) {
    // Insert command ke composer textarea
    composerRef.value?.insertText(commandText)
    closeSheet()
}

function handleSuggestionSelect(text) {
    composerRef.value?.insertText(text)
    nextTick(() => {
        composerRef.value?.$el?.querySelector('textarea')?.focus()
    })
}
</script>

<template>
    <AuthenticatedLayout :hideNav="true">
        <Head title="AI Chat" />

        <!--
            Layout: flex column, full viewport height.
            max-w-2xl di desktop agar tidak terlalu lebar.
            Terpusat dengan margin auto.
        -->
        <div class="flex flex-col h-screen max-w-2xl mx-auto relative">

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
                :user-avatar="authUser.avatar ?? null"
                :user-name="authUser.name ?? 'Kamu'"
                @loadMore="loadMore"
                class="flex-1"
            >
                <!-- Empty state saat belum ada pesan -->
                <template v-if="messages.length === 0 && !isLoading">
                    <ChatEmptyState
                        :bot-name="botProfile.name"
                        :bot-avatar="botProfile.avatar"
                        @select="handleSuggestionSelect"
                    />
                </template>
            </ChatArea>

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
            />

        </div>
    </AuthenticatedLayout>
</template>
