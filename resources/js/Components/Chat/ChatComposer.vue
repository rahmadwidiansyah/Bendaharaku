<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  isLoading:   { type: Boolean, default: false },
  placeholder: { type: String,  default: '' },
})

const emit = defineEmits(['send', 'openCommands'])

const text        = ref('')
const textareaRef = ref(null)
const isFocused   = ref(false)

function insertText(value) {
  text.value = value
  nextTick(() => {
    textareaRef.value?.focus()
    resize()
  })
}
defineExpose({ insertText })

const LINE_HEIGHT = 22
const MIN_HEIGHT  = 44
const MAX_ROWS    = 5

function resize() {
  const el = textareaRef.value
  if (!el) return
  el.style.height = 'auto'
  const natural = el.scrollHeight
  const maxH    = LINE_HEIGHT * MAX_ROWS + 20
  el.style.height = Math.min(natural, maxH) + 'px'
  el.style.overflowY = natural > maxH ? 'auto' : 'hidden'
}

watch(text, () => nextTick(resize))

function onKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
    e.preventDefault()
    submit()
  }
}

const placeholderText = computed(() => props.placeholder || t('chat.placeholder'))

const canSend = computed(() => text.value.trim().length > 0 && !props.isLoading)

function submit() {
  if (!canSend.value) return
  const msg = text.value.trim()
  text.value = ''
  nextTick(resize)
  emit('send', msg)
}

function handleGlobalKeydown(e) {
  if (e.ctrlKey || e.altKey || e.metaKey) return
  if (e.key.length !== 1) return
  const activeTag = document.activeElement?.tagName?.toLowerCase()
  if (activeTag === 'input' || activeTag === 'textarea') return
  if (textareaRef.value) textareaRef.value.focus()
}

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<template>
  <div
    class="z-10 shrink-0 bg-gray-950/96 backdrop-blur-xl border-t border-white/6"
    :style="{
      boxShadow: '0 -8px 24px rgba(0,0,0,0.4)',
      paddingBottom: 'max(10px, env(safe-area-inset-bottom, 10px))',
    }"
  >
    <div class="flex items-center gap-3 px-3 pt-2.5 pb-1.5">

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
        :aria-label="t('chat.commandButton')"
        :title="t('chat.commandTitle')"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
        </svg>
      </button>

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
          :placeholder="placeholderText"
          :disabled="isLoading"
          rows="1"
          @keydown="onKeydown"
          @input="resize"
          @focus="isFocused = true"
          @blur="isFocused = false"
          class="w-full resize-none bg-transparent px-4 py-[11px] text-sm text-white placeholder-gray-500 outline-none focus:outline-none border-0 ring-0 focus:ring-0 disabled:opacity-50 leading-[22px] block"
          style="min-height: 44px; overflow-y: hidden;"
          :aria-label="placeholderText"
          aria-multiline="true"
        ></textarea>
      </div>

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
        :aria-label="t('chat.sendButton')"
      >
        <svg
          v-if="!isLoading"
          class="w-5 h-5"
          :class="canSend ? 'translate-x-px' : ''"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
        </svg>
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

    <p class="hidden lg:block text-center text-[11px] text-gray-700 pb-1.5 pt-0">
      {{ t('chat.desktopHint') }}
    </p>
  </div>
</template>
