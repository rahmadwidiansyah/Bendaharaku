<script setup>
/**
 * ChatComposer.vue
 *
 * Input area chat — Telegram/WhatsApp quality.
 *
 * Layout (satu baris horizontal, semua items-center):
 *   [Command 44px] [12px] [Textarea flex-1] [12px] [Send 44px]
 *
 * Fitur:
 * - Textarea autogrow hingga MAX_ROWS baris, lalu scroll
 * - Enter = kirim, Shift+Enter = baris baru
 * - Animasi send button: scale + color transition
 * - Focus ring pada textarea
 * - Safe area bottom (iPhone home indicator)
 * - Shadow atas tipis agar terpisah dari chat list
 * - Disabled state saat isLoading
 *
 * Public API (defineExpose):
 *   insertText(value) — insert teks ke textarea (dari CommandSheet / suggestion)
 */

import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
    isLoading:   { type: Boolean, default: false },
    placeholder: { type: String,  default: 'Ketik pesan atau /perintah...' },
})

const emit = defineEmits(['send', 'openCommands'])

const text        = ref('')
const textareaRef = ref(null)
const isFocused   = ref(false)

// ── Public API ────────────────────────────────────────────────────
function insertText(value) {
    text.value = value
    nextTick(() => {
        textareaRef.value?.focus()
        resize()
    })
}
defineExpose({ insertText })

// ── Auto-resize ───────────────────────────────────────────────────
const LINE_HEIGHT = 22   // px — sesuai leading-[22px] di textarea
const MIN_HEIGHT  = 44   // px — minimum 1 baris (sama dengan tombol)
const MAX_ROWS    = 5

function resize() {
    const el = textareaRef.value
    if (!el) return
    el.style.height = 'auto'
    const natural = el.scrollHeight
    const maxH    = LINE_HEIGHT * MAX_ROWS + 20 // 20 = top+bottom padding
    el.style.height = Math.min(natural, maxH) + 'px'
    el.style.overflowY = natural > maxH ? 'auto' : 'hidden'
}

watch(text, () => nextTick(resize))

// ── Keyboard ──────────────────────────────────────────────────────
function onKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
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

// --- Global Type-to-Focus ---
function handleGlobalKeydown(e) {
  // Abaikan jika menekan tombol kombinasi (Ctrl, Alt, Meta/Cmd) agar shortcut browser tetap jalan
  if (e.ctrlKey || e.altKey || e.metaKey) return

  // Abaikan jika tombol yang ditekan bukan karakter tunggal (misal: Shift, Enter, Arrow, Escape)
  if (e.key.length !== 1) return

  // Abaikan jika user sedang fokus di input atau textarea (termasuk textarea ini sendiri)
  const activeTag = document.activeElement?.tagName?.toLowerCase()
  if (activeTag === 'input' || activeTag === 'textarea') return

  // Fokuskan ke textarea composer
  if (textareaRef.value) {
    textareaRef.value.focus()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<template>
    <!--
        Wrapper — sticky bottom, bg blur, safe area bottom, shadow atas.
        Shadow: shadow-[0_-1px_0_rgba(255,255,255,0.05)] baris pemisah tipis.
    -->
    <div
        class="sticky bottom-0 z-10 bg-gray-950/96 backdrop-blur-xl"
        style="
            box-shadow: 0 -1px 0 rgba(255,255,255,0.06), 0 -8px 24px rgba(0,0,0,0.4);
            padding-bottom: max(10px, env(safe-area-inset-bottom, 10px));
        "
    >
        <!--
            Row utama — semua items-center agar tombol + textarea selalu satu garis.
            gap-3 = 12px konsisten kiri/kanan textarea.
        -->
        <div class="flex items-center gap-3 px-3 pt-2.5 pb-1.5">

            <!-- ── Command Button ─────────────────────────────────
                 44×44 px touch target, icon centered, ripple hover.
            -->
            <button
                type="button"
                @click="$emit('openCommands')"
                :disabled="isLoading"
                :class="[
                    'shrink-0 w-11 h-11 rounded-2xl flex items-center justify-center',
                    'border transition-all duration-200',
                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/50',
                    isLoading
                        ? 'opacity-40 cursor-not-allowed bg-gray-800/60 border-white/8 text-gray-600'
                        : 'bg-gray-800/80 border-white/8 text-gray-400 hover:text-purple-400 hover:border-purple-500/30 hover:bg-gray-800 active:scale-95',
                ]"
                aria-label="Buka menu perintah"
                title="Perintah (/)"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
            </button>

            <!-- ── Textarea Wrapper ───────────────────────────────
                 flex-1 agar memenuhi ruang. Ring animasi saat focus.
            -->
            <div
                :class="[
                    'flex-1 relative rounded-2xl border transition-all duration-200',
                    isFocused
                        ? 'border-purple-500/50 bg-gray-800 ring-2 ring-purple-500/15'
                        : 'border-white/10 bg-gray-800/70 hover:border-white/15 hover:bg-gray-800/90',
                ]"
            >
                <textarea
                    ref="textareaRef"
                    v-model="text"
                    :placeholder="placeholder"
                    :disabled="isLoading"
                    rows="1"
                    @keydown="onKeydown"
                    @input="resize"
                    @focus="isFocused = true"
                    @blur="isFocused = false"
                    class="w-full resize-none bg-transparent px-4 py-[11px] text-sm text-white placeholder-gray-500 outline-none focus:outline-none border-0 ring-0 focus:ring-0 disabled:opacity-50 leading-[22px] block"
                    style="min-height: 44px; overflow-y: hidden;"
                    :aria-label="placeholder"
                    aria-multiline="true"
                ></textarea>
            </div>

            <!-- ── Send Button ────────────────────────────────────
                 44×44 px touch target.
                 Idle: abu-abu icon.
                 Active: purple gradient + scale animasi.
                 Loading: spinner.
            -->
            <button
                type="button"
                @click="submit"
                :disabled="!canSend"
                :class="[
                    'shrink-0 w-11 h-11 rounded-2xl flex items-center justify-center',
                    'transition-all duration-200',
                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/50',
                    canSend
                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-600/25 hover:bg-purple-500 active:scale-95 active:shadow-none'
                        : 'bg-gray-800/80 border border-white/8 text-gray-600 cursor-not-allowed',
                ]"
                aria-label="Kirim pesan"
            >
                <!-- Idle / Enabled: send icon -->
                <svg
                    v-if="!isLoading"
                    class="w-5 h-5"
                    :class="canSend ? 'translate-x-px' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                >
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                <!-- Loading: spinner -->
                <svg
                    v-else
                    class="animate-spin w-5 h-5 text-purple-400"
                    fill="none" viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </button>
        </div>

        <!-- Desktop hint -->
        <p class="hidden lg:block text-center text-[11px] text-gray-700 pb-1.5 pt-0">
            Enter kirim &nbsp;·&nbsp; Shift+Enter baris baru
        </p>
    </div>
</template>
