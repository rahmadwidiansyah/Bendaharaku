<script setup>
/**
 * M3Modal — Material Design 3 Expressive dialog.
 * Same behavioral contract as BaseModal (Teleport to body, Escape close,
 * scroll lock, aria-modal, initial focus) with M3 styling:
 * surface-container-high background, 28dp shape, elevation 3.
 * Used by M3 pages only (phase 1: Dashboard filter sheet).
 */
import { watch, onUnmounted, nextTick, ref } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    ariaLabel: { type: String, default: '' },
    closeable: { type: Boolean, default: true },
    maxWidth: { type: String, default: 'md' }, // sm | md | lg
})

const emit = defineEmits(['close'])

const panelRef = ref(null)
let previouslyFocused = null

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

onUnmounted(() => {
    unlockScroll()
    window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="m3-modal-enter-active"
            leave-active-class="m3-modal-leave-active"
            enter-from-class="m3-modal-enter-from"
            leave-to-class="m3-modal-leave-to"
        >
            <div v-if="show" class="m3-modal__overlay" @click.self="close">
                <div
                    ref="panelRef"
                    class="m3-modal__panel"
                    :class="`m3-modal__panel--${maxWidth}`"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="ariaLabel || title"
                    tabindex="-1"
                >
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
    padding: 1rem;
    background: rgba(0, 0, 0, 0.32);
}

.m3-modal__panel {
    width: 100%;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    border-radius: var(--m3-radius-xl);
    box-shadow: var(--m3-elevation-3);
    padding: var(--m3-space-5) var(--m3-space-4);
}

.m3-modal__panel--sm {
    max-width: 24rem;
}

.m3-modal__panel--md {
    max-width: 32rem;
}

.m3-modal__panel--lg {
    max-width: 44rem;
}

.m3-modal__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
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
    width: 2.5rem;
    height: 2.5rem;
    flex-shrink: 0;
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    overflow: hidden;
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
    width: 1.25rem;
    height: 1.25rem;
}

.m3-modal__body {
    min-width: 0;
}

.m3-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

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

.m3-modal-enter-from .m3-modal__panel {
    transform: scale(0.95);
    opacity: 0;
}

.m3-modal-leave-to .m3-modal__panel {
    transform: scale(0.95);
    opacity: 0;
}
</style>
