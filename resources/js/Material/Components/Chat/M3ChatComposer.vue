<script setup>
/**
 * M3ChatComposer — input pesan M3 (paritas ChatComposer Default).
 * WA-style: preview foto struktuk + caption, kirim bersamaan dengan tombol Send.
 * Shortcut global "ketik langsung" tetap ada, dengan guard: tidak aktif saat
 * ada modal/sheet M3 terbuka.
 */
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'

const { t } = useI18n()

const props = defineProps({
    isLoading:         { type: Boolean, default: false },
    placeholder:       { type: String,  default: '' },
    attachmentPreview: { type: String,  default: null },
    attachmentName:    { type: String,  default: '' },
    isUploading:       { type: Boolean, default: false },
})

const emit = defineEmits(['send', 'openCommands', 'openUpload', 'removeAttachment'])

const text = ref('')
const textareaRef = ref(null)

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
    const maxH = LINE_HEIGHT * MAX_ROWS + 20
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

const canSend = computed(() => {
    const hasText = text.value.trim().length > 0
    const hasAttachment = !!props.attachmentPreview
    return (hasText || hasAttachment) && !props.isLoading && !props.isUploading
})

function submit() {
    if (!canSend.value) return
    const msg = text.value.trim()
    text.value = ''
    nextTick(resize)
    emit('send', msg)
}

function isOverlayOpen() {
    return typeof document !== 'undefined' && document.querySelector('.m3-modal__overlay') !== null
}

function handleGlobalKeydown(e) {
    if (e.ctrlKey || e.altKey || e.metaKey) return
    if (e.key.length !== 1) return
    // Jangan rebut keyboard saat sheet/modal M3 terbuka (perbaikan UX)
    if (isOverlayOpen()) return
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
    <div class="m3-composer">
        <!-- Preview lampiran (WA-style) -->
        <div v-if="attachmentPreview" class="m3-composer__preview">
            <div class="m3-composer__thumb">
                <img :src="attachmentPreview" alt="" class="m3-composer__thumb-img" />
                <div v-if="isUploading" class="m3-composer__thumb-scrim" aria-hidden="true">
                    <M3CircularProgress size="sm" />
                </div>
            </div>
            <div class="m3-composer__preview-info">
                <p class="m3-composer__preview-name">{{ attachmentName || t('chat.evidenceLabel') }}</p>
                <p class="m3-composer__preview-hint" :class="{ 'm3-composer__preview-hint--active': isUploading }">
                    {{ isUploading ? t('chat.evidenceUploading') : t('chat.captionHint') }}
                </p>
            </div>
            <M3IconButton :aria-label="t('chat.removeAttachment')" :disabled="isUploading" @click="emit('removeAttachment')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </M3IconButton>
        </div>

        <div class="m3-composer__row">
            <!-- Tombol command -->
            <M3IconButton :aria-label="t('chat.commandButton')" :disabled="isLoading" @click="emit('openCommands')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
            </M3IconButton>

            <!-- Field -->
            <div class="m3-composer__field">
                <textarea
                    ref="textareaRef"
                    v-model="text"
                    :placeholder="placeholderText"
                    :disabled="isLoading"
                    rows="1"
                    class="m3-composer__textarea"
                    :aria-label="placeholderText"
                    aria-multiline="true"
                    @keydown="onKeydown"
                    @input="resize"
                ></textarea>

                <button
                    type="button"
                    class="m3-composer__attach"
                    :disabled="isLoading"
                    :aria-label="t('chat.attachmentButton')"
                    @click="emit('openUpload')"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M8 13h8M8 17h8M10 9H8" />
                    </svg>
                </button>
            </div>

            <!-- Kirim -->
            <button
                type="button"
                class="m3-composer__send"
                :class="{ 'm3-composer__send--active': canSend }"
                :disabled="!canSend"
                :aria-label="t('chat.sendButton')"
                @click="submit"
            >
                <svg v-if="!isLoading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                <M3CircularProgress v-else size="sm" />
            </button>
        </div>

        <p class="m3-composer__hint">{{ t('chat.desktopHint') }}</p>
    </div>
</template>

<style scoped>
.m3-composer {
    z-index: 10;
    flex-shrink: 0;
    background: var(--m3-color-surface-container);
    border-top: 1px solid var(--m3-color-outline-variant);
    padding-bottom: max(var(--m3-space-2), env(safe-area-inset-bottom, var(--m3-space-2)));
}

/* ── Preview lampiran ── */
.m3-composer__preview {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    margin: var(--m3-space-2-5) var(--m3-space-3) var(--m3-space-1);
    padding: var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-high);
}

.m3-composer__thumb {
    position: relative;
    width: 3.5rem;
    height: 3.5rem;
    flex-shrink: 0;
    border-radius: var(--m3-radius-md);
    overflow: hidden;
    background: var(--m3-color-surface-container-highest);
}

.m3-composer__thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.m3-composer__thumb-scrim {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--m3-color-scrim) 60%, transparent);
}

.m3-composer__preview-info {
    flex: 1;
    min-width: 0;
}

.m3-composer__preview-name {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-composer__preview-hint {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-composer__preview-hint--active {
    color: var(--m3-color-primary);
}

/* ── Baris input ── */
.m3-composer__row {
    display: flex;
    align-items: flex-end;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2-5) var(--m3-space-3) var(--m3-space-1-5);
}

.m3-composer__field {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    border: 1px solid transparent;
    border-radius: var(--m3-radius-lg-inc);
    background: var(--m3-color-surface-container-high);
    transition: border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-composer__field:focus-within {
    border-color: var(--m3-color-primary);
    box-shadow: inset 0 0 0 1px var(--m3-color-primary);
}

.m3-composer__textarea {
    flex: 1;
    width: 100%;
    display: block;
    resize: none;
    border: 0;
    outline: none;
    box-shadow: none;
    background: transparent;
    padding: 0.6875rem calc(var(--m3-space-1) + var(--m3-size-touch-target) + var(--m3-space-2)) 0.6875rem var(--m3-space-3);
    min-height: 2.75rem;
    overflow-y: hidden;
    font-family: inherit;
    font-size: var(--m3-type-body-large-size);
    line-height: 1.375rem;
    color: var(--m3-color-on-surface);
}

.m3-composer__textarea::placeholder {
    color: var(--m3-color-on-surface-variant);
}

.m3-composer__textarea:disabled {
    opacity: 0.5;
}

.m3-composer__attach {
    position: absolute;
    right: var(--m3-space-1);
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-composer__attach:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface-variant) 8%, transparent);
    color: var(--m3-color-primary);
}

.m3-composer__attach:disabled {
    opacity: 0.38;
    cursor: not-allowed;
}

.m3-composer__attach:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-composer__attach svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

/* ── Tombol kirim ── */
.m3-composer__send {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    flex-shrink: 0;
    border: none;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
    cursor: not-allowed;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-composer__send--active {
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
    cursor: pointer;
}

.m3-composer__send--active:hover {
    box-shadow: var(--m3-elevation-2);
}

.m3-composer__send--active:active {
    transform: scale(0.94);
    box-shadow: none;
}

.m3-composer__send:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-composer__send svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

/* Hint desktop */
.m3-composer__hint {
    display: none;
    text-align: center;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
    padding: 0 var(--m3-space-3) var(--m3-space-1-5);
}

@media (min-width: 1024px) {
    .m3-composer__hint {
        display: block;
    }
}
</style>
