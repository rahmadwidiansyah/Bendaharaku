<script setup>
/**
 * M3MessageText — teks AI sebagai markdown + collapse >600 karakter
 * (paritas MessageText Default). Fade memakai token permukaan bubble.
 */
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import M3MarkdownRenderer from './M3MarkdownRenderer.vue'

const { t } = useI18n()

const props = defineProps({
    component: { type: Object, required: true },
})

const isExpanded = ref(false)
const threshold = 600

const isLongText = computed(() => {
    const text = props.component.text ?? ''
    return text.length > threshold
})

function toggleExpand() {
    isExpanded.value = !isExpanded.value
}
</script>

<template>
    <div class="m3-msg-text">
        <div class="m3-msg-text__body" :class="isLongText && !isExpanded ? 'm3-msg-text__body--clamped' : ''">
            <M3MarkdownRenderer
                :content="component.text ?? ''"
                :class="component.bold ? 'm3-msg-text--bold' : ''"
            />

            <div v-if="isLongText && !isExpanded" class="m3-msg-text__fade" aria-hidden="true" />

            <div v-if="isLongText" class="m3-msg-text__toggle-row">
                <button
                    type="button"
                    class="m3-msg-text__toggle"
                    :aria-expanded="isExpanded"
                    @click="toggleExpand"
                >
                    <span>{{ isExpanded ? t('chat.collapse') : t('chat.showMore') }}</span>
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        aria-hidden="true"
                        :class="isExpanded ? 'm3-msg-text__chevron--open' : ''"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.m3-msg-text {
    position: relative;
    width: 100%;
}

.m3-msg-text__body {
    position: relative;
    overflow: hidden;
    transition: max-height var(--m3-motion-slow-effects-duration) var(--m3-motion-slow-effects);
}

.m3-msg-text__body--clamped {
    max-height: 220px;
}

.m3-msg-text--bold :deep(strong),
.m3-msg-text--bold :deep(b) {
    color: var(--m3-color-on-surface);
    font-weight: var(--m3-type-emphasized-title-small-weight);
}

.m3-msg-text__fade {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4rem;
    pointer-events: none;
    background: linear-gradient(
        to top,
        var(--m3-color-surface-container-high),
        color-mix(in srgb, var(--m3-color-surface-container-high) 80%, transparent),
        transparent
    );
}

.m3-msg-text__toggle-row {
    display: flex;
    justify-content: center;
    margin-top: var(--m3-space-2);
}

.m3-msg-text__toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1-5);
    min-height: 2rem;
    padding: 0 var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-primary);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-label-large-weight);
    cursor: pointer;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-msg-text__toggle:hover {
    background: color-mix(in srgb, var(--m3-color-primary) 8%, transparent);
}

.m3-msg-text__toggle:active {
    transform: scale(0.97);
}

.m3-msg-text__toggle:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-msg-text__toggle svg {
    width: 0.875rem;
    height: 0.875rem;
    transition: transform var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-msg-text__chevron--open {
    transform: rotate(180deg);
}
</style>
