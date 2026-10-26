<script setup>
/**
 * M3ChatCommandSheet — daftar perintah cepat (paritas CommandSheet).
 * Presentasi: dialog di desktop, bottom sheet di mobile (M3Modal).
 * Kontrak event sama: `select` mengirim `cmd.command + ' '`.
 */
import { useI18n } from 'vue-i18n'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { toLucide } from '@/utils/chatIcons.js'

const { t } = useI18n()

defineProps({
    modelValue: { type: Boolean, required: true },
    commandsByCategory: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    categoryLabels: { type: Object, default: () => ({}) },
    isLoading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'select'])

function close() {
    emit('update:modelValue', false)
}

function selectCommand(cmd) {
    emit('select', cmd.command + ' ')
    close()
}
</script>

<template>
    <M3Modal
        :show="modelValue"
        :aria-label="t('chat.sheetTitle')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="md"
        @close="close"
    >
        <template #header>
            <div class="m3-cmd__heading">
                <h2 class="m3-cmd__title">{{ t('chat.sheetTitle') }}</h2>
                <p class="m3-cmd__desc">{{ t('chat.sheetDesc') }}</p>
            </div>
        </template>

        <div class="m3-cmd">
            <div v-if="categories.length === 0" class="m3-cmd__state" role="status">
                <M3CircularProgress v-if="isLoading" size="sm" />
                <p>{{ isLoading ? t('common.loading') : t('chat.commandsEmpty') }}</p>
            </div>

            <template v-else>
                <section v-for="cat in categories" :key="cat" class="m3-cmd__group">
                    <h3 class="m3-cmd__group-label">{{ categoryLabels[cat] ?? cat }}</h3>

                    <button
                        v-for="cmd in commandsByCategory[cat]"
                        :key="cmd.command"
                        type="button"
                        class="m3-cmd__row"
                        @click="selectCommand(cmd)"
                    >
                        <span class="m3-cmd__icon" aria-hidden="true">
                            <AppIcon :icon="toLucide(cmd.icon)" fallback="circle-help" />
                        </span>

                        <span class="m3-cmd__body">
                            <span class="m3-cmd__name">{{ cmd.command }}</span>
                            <span v-if="cmd.description" class="m3-cmd__hint">{{ cmd.description }}</span>
                        </span>

                        <svg class="m3-cmd__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </section>
            </template>
        </div>
    </M3Modal>
</template>

<style scoped>
.m3-cmd__heading {
    min-width: 0;
}

.m3-cmd__title {
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    color: var(--m3-color-on-surface);
}

.m3-cmd__desc {
    margin-top: var(--m3-space-1);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-cmd {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-cmd__state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-3);
    min-height: 10rem;
    padding: var(--m3-space-6) var(--m3-space-4);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    text-align: center;
}

.m3-cmd__group {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-cmd__group-label {
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking, 0.05em);
    color: var(--m3-color-on-surface-variant);
    padding: 0 var(--m3-space-3);
    margin-bottom: var(--m3-space-1);
}

.m3-cmd__row {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    width: 100%;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-2) var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    overflow: hidden;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cmd__row::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cmd__row:hover::after {
    opacity: 0.08;
}

.m3-cmd__row:active::after {
    opacity: 0.1;
}

.m3-cmd__row:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-cmd__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-primary);
}

.m3-cmd__icon :deep(svg) {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-cmd__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.m3-cmd__name {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-cmd__hint {
    margin-top: var(--m3-space-0-5);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-cmd__chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
    color: var(--m3-color-outline);
}
</style>
