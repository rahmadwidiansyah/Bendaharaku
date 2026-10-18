<script setup>
/**
 * M3MessageErrorCard — kartu error & warning (paritas MessageErrorCard Default).
 * Koreksi semantik: warning memakai role tertiary M3 (Default memakai warna
 * debt yang reserved untuk konteks finansial).
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import M3MarkdownRenderer from './M3MarkdownRenderer.vue'

const { t } = useI18n()

const props = defineProps({
    component: { type: Object, required: true },
})

const isWarning = computed(() => props.component.severity === 'warning')
</script>

<template>
    <div class="m3-msg-error" :class="isWarning ? 'm3-msg-error--warning' : 'm3-msg-error--error'">
        <div class="m3-msg-error__content">
            <p v-if="component.index !== null && component.index !== undefined" class="m3-msg-error__index">
                {{ t('chat.errorItem') }}{{ component.index }}
            </p>

            <p v-if="component.raw" class="m3-msg-error__raw">"{{ component.raw }}"</p>

            <div class="m3-msg-error__message">
                <M3MarkdownRenderer :content="component.message" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.m3-msg-error {
    display: flex;
    gap: var(--m3-space-2-5);
    padding: var(--m3-space-3);
}

.m3-msg-error--error {
    color: var(--m3-color-error);
}

.m3-msg-error--warning {
    color: var(--m3-color-tertiary);
}

.m3-msg-error__content {
    flex: 1;
    min-width: 0;
}

.m3-msg-error__index {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    color: var(--m3-color-on-surface-variant);
    margin-bottom: var(--m3-space-1);
}

.m3-msg-error__raw {
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    font-size: var(--m3-type-body-small-size);
    color: inherit;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: var(--m3-space-1);
}

.m3-msg-error__message {
    color: inherit;
}

.m3-msg-error__message :deep(.m3-md),
.m3-msg-error__message :deep(.m3-md p),
.m3-msg-error__message :deep(.m3-md strong),
.m3-msg-error__message :deep(.m3-md b) {
    color: inherit;
}
</style>
