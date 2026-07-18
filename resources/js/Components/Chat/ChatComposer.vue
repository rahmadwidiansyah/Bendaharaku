<script setup>
/**
 * ChatComposer.vue
 *
 * Composer input sticky di bagian bawah Chat.
 * Feel: compact seperti Telegram/WhatsApp.
 *
 * Fitur:
 * - Textarea autogrow (1–4 baris)
 * - Tombol command (/) di kiri → membuka CommandSheet
 * - Send button di kanan
 * - Enter = kirim, Shift+Enter = baris baru
 * - Disabled saat isLoading
 * - Safe area inset bottom untuk iPhone
 */

import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
    isLoading:   { type: Boolean, default: false },
    placeholder: { type: String,  default: 'Pesan atau /perintah...' },
})

const emit = defineEmits(['send', 'openCommands'])

const text         = ref('')
const textareaRef  = ref(null)

// Public method: insert text
function insertText(value) {
    text.value = value
    nextTick(() => {
        textareaRef.value?.focus()
        resize()
    })
}

defineExpose({ insertText })

// ── Auto-resize textarea ──────────────────────────────────────────
const LINE_HEIGHT = 20
const MIN_ROWS    = 1
const MAX_ROWS    = 4

function resize() {
    const el = textareaRef.value
    if (!el) return
    el.style.height = 'auto'
    const rows = Math.min(MAX_ROWS, Math.max(MIN_ROWS, Math.ceil(el.scrollHeight / LINE_HEIGHT)))
    el.style.height = `${rows * LINE_HEIGHT}px`
}

watch(text, () => nextTick(resize))

// ── Keyboard shortcuts ────────────────────────────────────────────
function onKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        submit()
    }
}

// ── Send ──────────────────────────────────────────────────────────
const canSend = computed(() => text.value.trim().length > 0 && !props.isLoading)

function submit() {
    if (!canSend.value) return
    const msg = text.value.trim()
    text.value = ''
    nextTick(resize)
    emit('send', msg)
}
</script>

<template>
    <div
        class="sticky bottom-0 z-10 bg-gray-950/95 backdrop-blur-xl border-t border-white/6"
        style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0.5rem));"
    >
        <div class="flex items-end gap-1.5 px-2.5 pt-2 pb-0.5">

            <!-- Command button -->
            <button
                type="button"
                @click="$emit('openCommands')"
                :disabled="isLoading"
                class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center bg-gray-800/80 border border-white/8 text-gray-500 hover:text-purple-400 hover:border-purple-500/30 hover:bg-gray-800 transition-all disabled:opacity-40"
                aria-label="Lihat perintah"
                title="Perintah (/)"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
            </button>

            <!-- Textarea container -->
            <div class="flex-1 relative">
                <textarea
                    ref="textareaRef"
                    v-model="text"
                    :placeholder="placeholder"
                    :disabled="isLoading"
                    rows="1"
                    @keydown="onKeydown"
                    @input="resize"
                    class="w-full resize-none bg-gray-800/80 border border-white/8 rounded-xl px-3.5 py-1.5 text-sm text-white placeholder-gray-600 outline-none focus:border-purple-500/40 focus:ring-1 focus:ring-purple-500/20 focus:bg-gray-800 transition-all disabled:opacity-50 leading-5"
                    style="min-height: 34px; max-height: 100px; overflow-y: auto;"
                    :aria-label="placeholder"
                    aria-multiline="true"
                ></textarea>
            </div>

            <!-- Send button -->
            <button
                type="button"
                @click="submit"
                :disabled="!canSend"
                :class="[
                    'shrink-0 w-8 h-8 rounded-xl flex items-center justify-center transition-all',
                    canSend
                        ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20 hover:bg-purple-500 active:scale-95'
                        : 'bg-gray-800/80 border border-white/8 text-gray-600 cursor-not-allowed'
                ]"
                aria-label="Kirim pesan"
            >
                <!-- Send icon -->
                <svg v-if="!isLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                <!-- Loading spinner -->
                <svg v-else class="animate-spin w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </button>
        </div>

        <!-- Keyboard hint (desktop only) -->
        <p class="hidden lg:block text-center text-2xs text-gray-700 pb-1 pt-0.5">
            Enter kirim &nbsp;·&nbsp; Shift+Enter baris baru
        </p>
    </div>
</template>
