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

const close = () => {
    if (props.closeable) emit('close')
}

const onKeydown = (e) => {
    if (e.key === 'Escape') close()
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
            lockScroll()
            window.addEventListener('keydown', onKeydown)
            await nextTick()
            panelRef.value?.focus()
        } else {
            unlockScroll()
            window.removeEventListener('keydown', onKeydown)
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
    background: rgba(0, 0, 0, 0.55);
}

.m3-modal__panel {
    width: 100%;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    border-radius: var(--m3-radius-xl);
    box-shadow: var(--m3-elevation-3);
    padding: 1.5rem;
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
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-title-large-weight);
    color: var(--m3-color-on-surface);
    text-align: center;
    flex: 1;
}

.m3-modal__close {
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
}

.m3-modal__close:hover {
    background: rgba(227, 226, 230, 0.08);
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
    transition: opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-modal-leave-active {
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-modal-enter-from,
.m3-modal-leave-to {
    opacity: 0;
}
</style>
