<script setup>
/**
 * M3ChatUploadSheet — pilihan sumber gambar bukti (paritas ChatUploadSheet).
 * Presentasi: dialog di desktop, bottom sheet di mobile (M3Modal).
 * Kontrak event sama: memilih file apa pun memancarkan event `camera`.
 */
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Button from '@/Material/Components/M3Button.vue'

const { t } = useI18n()

defineProps({
    modelValue: { type: Boolean, required: true },
})

const emit = defineEmits(['update:modelValue', 'camera', 'gallery'])

const fileInput = ref(null)

function close() {
    emit('update:modelValue', false)
}

function openCamera() {
    if (fileInput.value) {
        fileInput.value.setAttribute('capture', 'environment')
        fileInput.value.click()
    }
}

function openGallery() {
    if (fileInput.value) {
        fileInput.value.removeAttribute('capture')
        fileInput.value.click()
    }
}

function onFileSelected(event) {
    const file = event.target.files?.[0]
    if (!file) return

    emit('camera', file)
    close()
    event.target.value = ''
}
</script>

<template>
    <M3Modal
        :show="modelValue"
        :aria-label="t('chat.uploadSheetTitle')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="md"
        @close="close"
    >
        <template #header>
            <div class="m3-upload__heading">
                <h2 class="m3-upload__title">{{ t('chat.uploadSheetTitle') }}</h2>
                <p class="m3-upload__desc">{{ t('chat.uploadSheetDesc') }}</p>
            </div>
        </template>

        <div class="m3-upload__options" role="group" :aria-label="t('chat.uploadSheetLabel')">
            <button type="button" class="m3-upload__option" @click="openCamera">
                <span class="m3-upload__icon m3-upload__icon--camera" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
                <span class="m3-upload__body">
                    <span class="m3-upload__name">{{ t('chat.uploadCamera') }}</span>
                    <span class="m3-upload__hint">{{ t('chat.uploadCameraDesc') }}</span>
                </span>
            </button>

            <button type="button" class="m3-upload__option" @click="openGallery">
                <span class="m3-upload__icon m3-upload__icon--gallery" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
                <span class="m3-upload__body">
                    <span class="m3-upload__name">{{ t('chat.uploadGallery') }}</span>
                    <span class="m3-upload__hint">{{ t('chat.uploadGalleryDesc') }}</span>
                </span>
            </button>
        </div>

        <template #footer>
            <M3Button variant="text" full-width @click="close">{{ t('common.cancel') }}</M3Button>
        </template>

        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="m3-upload__input"
            @change="onFileSelected"
        />
    </M3Modal>
</template>

<style scoped>
.m3-upload__heading {
    min-width: 0;
}

.m3-upload__title {
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    color: var(--m3-color-on-surface);
}

.m3-upload__desc {
    margin-top: var(--m3-space-1);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-upload__options {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-upload__option {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--m3-space-4);
    width: 100%;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-lg);
    background: transparent;
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    overflow: hidden;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-upload__option::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-upload__option:hover::after {
    opacity: 0.08;
}

.m3-upload__option:active::after {
    opacity: 0.1;
}

.m3-upload__option:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-upload__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    flex-shrink: 0;
    border-radius: var(--m3-radius-lg);
}

.m3-upload__icon svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-upload__icon--camera {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-upload__icon--gallery {
    background: var(--m3-color-tertiary-container);
    color: var(--m3-color-on-tertiary-container);
}

.m3-upload__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.m3-upload__name {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-upload__hint {
    margin-top: var(--m3-space-0-5);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-upload__input {
    display: none;
}
</style>
