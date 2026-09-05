<script setup>
/**
 * Pages/Chat/Index.vue
 *
 * Halaman utama Web Chat Bendaharaku.
 *
 * Layout: desktop sidebar tetap tampil (hideNav hanya di mobile), full width max-w-7xl.
 *
 * Jump-to-latest button ada di sini sebagai FAB overlay,
 * BUKAN di dalam ChatArea — agar tidak masuk ke scroll container
 * dan tidak menutupi bubble/card.
 */

import { ref, computed, onMounted, nextTick, watch, onUnmounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
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
import { useLayoutPreference } from '@/Composables/useLayoutPreference.js'

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

const { t } = useI18n()

// ── Layout preference (desktop sidebar khusus chat) ─────────────────
const { isDesktopLayout } = useLayoutPreference()
const hideNav = computed(() => !isDesktopLayout.value)

// ── Visual viewport (responsive to Android keyboard) ───────────────
const { height: viewportHeight } = useVisualViewport()

// ── Evidence upload — WA-style: foto dipilih → preview di composer, tidak langsung kirim ──
// User bisa tambah caption lalu klik Send (seperti WA/Tele)
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

// Preview untuk composer (WA-style): tampilkan thumbnail di atas input, tunggu Send
const attachmentPreview = computed(() => localPreviewUrl.value ?? evidence.value?.url ?? null)
const attachmentName = computed(() => uploadedFile.value?.name ?? evidence.value?.original_name ?? '')
const hasAttachment = computed(() => !!uploadedFile.value || !!evidence.value?.uuid)

// ── Polling status evidence biar tombol Review muncul otomatis (fix: foto cuma jadi [Evidence] karena status ga ke-update) ──
const evidencePollTimers = new Map()
function pollEvidenceStatus(uuid) {
    if (evidencePollTimers.has(uuid)) return
    updateEvidenceStatus(uuid, 'PROCESSING')
    const timer = setInterval(async () => {
        try {
            const { data } = await axios.get(route('chat.evidence.draft.show', { uuid }))
            if (data.success) {
                const status = data.evidence?.status ?? 'READY'
                updateEvidenceStatus(uuid, status)
                clearInterval(timer)
                evidencePollTimers.delete(uuid)
            }
        } catch (e) {
            const sc = e.response?.status
            if (sc === 404) {
                updateEvidenceStatus(uuid, 'FAILED')
                clearInterval(timer)
                evidencePollTimers.delete(uuid)
            } else if (sc === 422) {
                updateEvidenceStatus(uuid, 'PROCESSING')
            }
        }
    }, 2000)
    evidencePollTimers.set(uuid, timer)
    setTimeout(() => {
        if (evidencePollTimers.has(uuid)) {
            clearInterval(evidencePollTimers.get(uuid))
            evidencePollTimers.delete(uuid)
        }
    }, 90000)
}

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
    // Resume polling pesan bot yang masih diproses (dari sesi sebelumnya)
    resumePending()
    await scrollToBottom(false)
})

onUnmounted(() => {
    for (const t of evidencePollTimers.values()) clearInterval(t)
    evidencePollTimers.clear()
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

/**
 * WA-style: kirim teks + foto struk bersamaan.
 * - Jika ada attachment (foto dipilih) → upload dulu, baru kirim evidence+caption sebagai SATU pesan.
 * - Jika tidak ada attachment → kirim teks biasa.
 * Foto HANYA ditampilkan sebagai image (tidak jadi text "[Evidence]").
 */
async function handleSend(text) {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }

    const trimmed = (text ?? '').trim()
    const hasFile = !!uploadedFile.value

    // Tidak ada teks & tidak ada foto → ignore
    if (!trimmed && !hasFile) return

    // Jika ada foto: upload dulu (jika belum), lalu kirim sebagai 1 bubble image+caption
    if (hasFile) {
        // Capture preview sebelum upload (karena upload() akan revoke blob)
        const previewBefore = localPreviewUrl.value

        // Upload jika belum punya evidence.uuid
        if (!evidence.value?.uuid) {
            await upload()
        }

        if (!evidence.value?.uuid) {
            // Upload gagal → jangan kirim, biarkan user coba lagi (preview tetap ada)
            return
        }

        const uuid = evidence.value.uuid
        const previewUrl = previewBefore ?? evidence.value.url ?? localPreviewUrl.value
        // Kirim SATU pesan: image + caption (caption bisa kosong)
        await sendEvidenceMessage(uuid, previewUrl, trimmed)
        // Mulai polling status OCR biar tombol Review muncul otomatis tanpa reload
        pollEvidenceStatus(uuid)

        resetUpload()
        return
    }

    // Tidak ada foto → kirim teks biasa
    await sendMessage(trimmed)
}

function handleFileSelected(file) {
    if (chatAreaComp.value?.el && !chatAreaRef.value) {
        chatAreaRef.value = chatAreaComp.value.el
    }

    // WA-style: hanya set preview di composer, TIDAK langsung upload/kirim.
    // User bisa tambah caption lalu klik Send (seperti WA & Telegram).
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
    <AuthenticatedLayout :hideNav="hideNav">
        <Head :title="t('chat.assistant')" />

        <!--
            flex column, full viewport height.
            Uses visualViewport API for accurate height on Android when keyboard opens.
            `relative` agar FAB bisa absolute di dalam container ini.
            `max-w-7xl mx-auto` agar desktop memakai ruang layar (bukan 672px ala mobile).
        -->
        <div class="flex flex-col w-full max-w-7xl mx-auto relative" :style="containerStyle">

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

            <!-- Composer (sticky bottom) — WA-style: preview foto di atas input, caption bisa diketik -->
            <ChatComposer
                ref="composerRef"
                :is-loading="isLoading"
                :is-uploading="isUploading"
                :attachment-preview="attachmentPreview"
                :attachment-name="attachmentName"
                @send="handleSend"
                @openCommands="openSheet"
                @openUpload="isUploadSheetOpen = true"
                @removeAttachment="handleRemoveAttachment"
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
