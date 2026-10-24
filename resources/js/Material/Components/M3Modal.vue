<script setup>
/**
 * M3Modal — Material Design 3 Expressive dialog / bottom sheet.
 * Same behavioral contract as BaseModal (Teleport to body, Escape close,
 * scroll lock, aria-modal, initial focus, focus trap) with M3 styling:
 * surface-container-high background, 28dp shape, elevation 3.
 *
 * Varian:
 *   - dialog     : centered, max-width sm/md/lg (default)
 *   - sheet      : anchored bottom, full-width, sudut atas 28dp + drag handle
 *   - fullscreen : 100dvh (spec modal date picker di compact window),
 *                  aman untuk safe-area atas/bawah
 *
 * `mobileVariant` dipakai otomatis saat viewport <1024px sehingga halaman
 * bisa memakai dialog di desktop namun sheet/fullscreen di mobile.
 */
import { watch, onMounted, onUnmounted, nextTick, ref, computed } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    ariaLabel: { type: String, default: '' },
    closeable: { type: Boolean, default: true },
    maxWidth: { type: String, default: 'md' }, // sm | md | lg (dialog)
    variant: { type: String, default: 'dialog' }, // dialog | sheet | fullscreen
    mobileVariant: { type: String, default: '' }, // dipakai saat <1024px
})

const emit = defineEmits(['close'])

const panelRef = ref(null)
let previouslyFocused = null

// ── Variant resolution (matchMedia 1024px, sinkron dengan breakpoint layout) ──
const isDesktop = ref(true)
let mql = null
const onMqlChange = (e) => { isDesktop.value = e.matches }

onMounted(() => {
    if (typeof window !== 'undefined' && window.matchMedia) {
        mql = window.matchMedia('(min-width: 1024px)')
        isDesktop.value = mql.matches
        mql.addEventListener('change', onMqlChange)
    }
})

const effectiveVariant = computed(() =>
    !isDesktop.value && props.mobileVariant ? props.mobileVariant : props.variant,
)

const close = () => {
    if (props.closeable) emit('close')
}

const onKeydown = (e) => {
    if (e.key === 'Escape') {
        close()
        return
    }
    if (e.key === 'Tab' && panelRef.value) {
        const focusable = panelRef.value.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
        )
        if (focusable.length === 0) return
        const first = focusable[0]
        const last = focusable[focusable.length - 1]
        if (e.shiftKey) {
            if (document.activeElement === first || !panelRef.value.contains(document.activeElement)) {
                e.preventDefault()
                last.focus()
            }
        } else {
            if (document.activeElement === last) {
                e.preventDefault()
                first.focus()
            }
        }
    }
}

const lockScroll = () => {
    document.body.style.overflow = 'hidden'
}

const unlockScroll = () => {
    document.body.style.overflow = ''
}

onUnmounted(() => {
    unlockScroll()
    window.removeEventListener('keydown', onKeydown)
    mql?.removeEventListener('change', onMqlChange)
})

watch(
    () => props.show,
    async (visible) => {
        if (visible) {
            previouslyFocused = document.activeElement
            lockScroll()
            window.addEventListener('keydown', onKeydown)
            await nextTick()
            panelRef.value?.focus()
        } else {
            unlockScroll()
            window.removeEventListener('keydown', onKeydown)
            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus()
            }
        }
    },
    { immediate: true },
)
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="m3-modal-enter-active"
            leave-active-class="m3-modal-leave-active"
            enter-from-class="m3-modal-enter-from"
            leave-to-class="m3-modal-leave-to"
        >
            <div
                v-if="show"
                class="m3-modal__overlay"
                :class="`m3-modal__overlay--${effectiveVariant}`"
                @click.self="close"
            >
                <div
                    ref="panelRef"
                    class="m3-modal__panel"
                    :class="[
                        `m3-modal__panel--${effectiveVariant}`,
                        effectiveVariant === 'dialog' ? `m3-modal__panel--${maxWidth}` : '',
                    ]"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="ariaLabel || title"
                    tabindex="-1"
                >
                    <div v-if="effectiveVariant === 'sheet'" class="m3-modal__handle" aria-hidden="true" />
                    <div v-if="title || $slots.header" class="m3-modal__header">
                        <slot name="header">
                            <h2 class="m3-modal__title">{{ title }}</h2>
                        </slot>
                        <button
                            v-if="closeable"
                            type="button"
                            class="m3-modal__close"
                            :aria-label="$t('common.close')"
                            @click="close"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="m3-modal__body">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="m3-modal__footer">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.m3-modal__overlay {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--m3-space-4);
    background: color-mix(in srgb, var(--m3-color-scrim) 32%, transparent);
}

.m3-modal__overlay--sheet,
.m3-modal__overlay--fullscreen {
    align-items: flex-end;
    padding: 0;
}

.m3-modal__panel {
    width: 100%;
    max-height: calc(100dvh - var(--m3-space-8));
    overflow-y: auto;
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    border-radius: var(--m3-radius-xl);
    box-shadow: var(--m3-elevation-3);
    padding: var(--m3-space-5) var(--m3-space-4);
}

.m3-modal__panel--sm {
    max-width: var(--m3-size-dialog-sm);
}

.m3-modal__panel--md {
    max-width: var(--m3-size-dialog-md);
}

.m3-modal__panel--lg {
    max-width: var(--m3-size-dialog-lg);
}

/* Bottom sheet: anchored bawah, sudut atas membulat, drag handle */
.m3-modal__panel--sheet {
    display: flex;
    flex-direction: column;
    max-width: var(--m3-size-list-max);
    max-height: calc(100dvh - var(--m3-space-8));
    border-radius: var(--m3-radius-xl) var(--m3-radius-xl) 0 0;
    padding: 0;
    overflow: hidden;
}

/* Fullscreen (spec date picker compact): 100dvh + aman safe-area */
.m3-modal__panel--fullscreen {
    display: flex;
    flex-direction: column;
    max-width: none;
    height: 100dvh;
    max-height: 100dvh;
    border-radius: 0;
    padding: 0;
    padding-top: env(safe-area-inset-top, 0px);
    overflow: hidden;
}

.m3-modal__handle {
    flex-shrink: 0;
    width: var(--m3-space-8);
    height: var(--m3-space-1);
    margin: var(--m3-space-3) auto 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-outline-variant);
}

.m3-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--m3-space-4);
    margin-bottom: var(--m3-space-4);
}

.m3-modal__panel--sheet .m3-modal__header,
.m3-modal__panel--fullscreen .m3-modal__header {
    margin-bottom: 0;
    padding: var(--m3-space-4) var(--m3-space-4) 0;
}

.m3-modal__title {
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-headline-small-weight);
    color: var(--m3-color-on-surface);
    text-align: start;
    flex: 1;
}

.m3-modal__close {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    overflow: visible;
}

/* Perluasan hit area ke 48px (paritas M3IconButton) */
.m3-modal__close::before {
    content: '';
    position: absolute;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
}

.m3-modal__close::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-modal__close:hover::after {
    opacity: 0.08;
}

.m3-modal__close:active::after {
    opacity: 0.12;
}

.m3-modal__close:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-modal__close svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-modal__body {
    min-width: 0;
}

.m3-modal__panel--sheet .m3-modal__body,
.m3-modal__panel--fullscreen .m3-modal__body {
    flex: 1;
    overflow-y: auto;
    padding: var(--m3-space-4);
}

.m3-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-6);
}

.m3-modal__panel--sheet .m3-modal__footer,
.m3-modal__panel--fullscreen .m3-modal__footer {
    flex-shrink: 0;
    margin-top: 0;
    padding: var(--m3-space-3) var(--m3-space-4) calc(var(--m3-space-4) + env(safe-area-inset-bottom, 0px));
}

/* ── Transitions ── */
.m3-modal-enter-active {
    transition:
        opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-modal-leave-active {
    transition:
        opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-modal-enter-active .m3-modal__panel {
    transition:
        transform var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial),
        opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-modal-leave-active .m3-modal__panel {
    transition:
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial),
        opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-modal-enter-from,
.m3-modal-leave-to {
    opacity: 0;
}

.m3-modal-enter-from .m3-modal__panel,
.m3-modal-leave-to .m3-modal__panel {
    transform: scale(0.95);
    opacity: 0;
}

/* Sheet & fullscreen: slide dari bawah (bukan scale) */
.m3-modal-enter-from .m3-modal__panel--sheet,
.m3-modal-leave-to .m3-modal__panel--sheet,
.m3-modal-enter-from .m3-modal__panel--fullscreen,
.m3-modal-leave-to .m3-modal__panel--fullscreen {
    transform: translateY(100%);
    opacity: 1;
}
</style>
