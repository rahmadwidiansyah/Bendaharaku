<script setup>
/**
 * M3ImageCropModal — crop image Material Design 3 Expressive.
 *
 * Kontrak perilaku identik dengan Components/ImageCropModal.vue (Default):
 * pilih/seret gambar → crop → output blob (webp/jpeg/png, batas ukuran KB)
 * dengan kompresi iteratif. Bedanya hanya presentasi: dibungkus M3Modal
 * (fullscreen otomatis di <1024px) dan memakai token --m3-*.
 */
import { ref, computed, watch } from 'vue'
import { Cropper, CircleStencil, RectangleStencil } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import { useI18n } from 'vue-i18n'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Button from '@/Material/Components/M3Button.vue'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    aspectRatio: { type: Number, default: 1 },
    maxSizeKb: { type: Number, default: 500 },
    quality: { type: Number, default: 0.85 },
    outputFormat: {
        type: String,
        default: 'image/webp',
        validator: (v) => ['image/jpeg', 'image/webp', 'image/png'].includes(v),
    },
    circle: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'cropped', 'cancel'])

const { t } = useI18n()

const cropperRef = ref(null)
const fileInputRef = ref(null)
const imageSrc = ref(null)
const isDragging = ref(false)
const isProcessing = ref(false)
const errorMsg = ref(null)
const fileName = ref('')

const modalTitle = computed(() => props.title || t('common.crop.title'))

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

const stencilComponent = computed(() => (props.circle ? CircleStencil : RectangleStencil))
const stencilProps = computed(() => ({ aspectRatio: props.aspectRatio }))

watch(isOpen, (val) => {
    if (!val) resetState()
})

function resetState() {
    imageSrc.value = null
    errorMsg.value = null
    isProcessing.value = false
    fileName.value = ''
    if (fileInputRef.value) fileInputRef.value.value = ''
}

function close() {
    isOpen.value = false
    emit('cancel')
}

function triggerFileInput() {
    fileInputRef.value?.click()
}

function onFileChange(event) {
    const file = event.target.files?.[0]
    if (file) loadFile(file)
}

function onDrop(event) {
    isDragging.value = false
    const file = event.dataTransfer?.files?.[0]
    if (file) loadFile(file)
}

function loadFile(file) {
    errorMsg.value = null

    if (!file.type.startsWith('image/')) {
        errorMsg.value = t('common.crop.file_invalid')
        return
    }
    if (file.size > 10 * 1024 * 1024) {
        errorMsg.value = t('common.crop.file_too_large')
        return
    }

    fileName.value = file.name
    const reader = new FileReader()
    reader.onload = (e) => {
        imageSrc.value = e.target?.result
    }
    reader.readAsDataURL(file)
}

async function confirmCrop() {
    if (!cropperRef.value) return

    isProcessing.value = true
    errorMsg.value = null

    try {
        const { canvas } = cropperRef.value.getResult()
        if (!canvas) {
            errorMsg.value = t('common.crop.failed')
            return
        }

        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob(
                (b) => (b ? resolve(b) : reject(new Error('toBlob returned null'))),
                props.outputFormat,
                props.quality,
            )
        })

        if (blob.size > props.maxSizeKb * 1024) {
            emit('cropped', await compressBlob(canvas))
        } else {
            emit('cropped', blob)
        }

        isOpen.value = false
    } catch (err) {
        errorMsg.value = t('common.crop.error')
        console.error('M3ImageCropModal: crop error', err)
    } finally {
        isProcessing.value = false
    }
}

async function compressBlob(canvas) {
    let quality = props.quality
    let blob = null
    while (quality > 0.3) {
        quality -= 0.1
        blob = await new Promise((resolve) => {
            canvas.toBlob(resolve, 'image/webp', Math.max(0.3, quality))
        })
        if (blob && blob.size <= props.maxSizeKb * 1024) break
    }
    return blob
}
</script>

<template>
    <M3Modal
        :show="isOpen"
        :title="modalTitle"
        max-width="md"
        variant="dialog"
        mobile-variant="fullscreen"
        @close="close"
    >
        <div class="m3-crop">
            <p v-if="errorMsg" class="m3-crop__error" role="alert">{{ errorMsg }}</p>

            <div
                v-if="!imageSrc"
                class="m3-crop__drop"
                :class="{ 'm3-crop__drop--active': isDragging }"
                role="button"
                tabindex="0"
                @click="triggerFileInput"
                @keydown.enter.prevent="triggerFileInput"
                @keydown.space.prevent="triggerFileInput"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
            >
                <span class="m3-crop__drop-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                </span>
                <p class="m3-crop__drop-title">
                    {{ isDragging ? t('common.crop.drop_active') : t('common.crop.drop') }}
                </p>
                <p class="m3-crop__drop-hint">{{ t('common.crop.formats') }}</p>
            </div>

            <div v-else class="m3-crop__stage">
                <Cropper
                    ref="cropperRef"
                    :src="imageSrc"
                    :stencil-component="stencilComponent"
                    :stencil-props="stencilProps"
                    class="m3-crop__cropper"
                    image-restriction="stencil"
                />
            </div>

            <div v-if="imageSrc" class="m3-crop__file">
                <p class="m3-crop__file-name">{{ fileName }}</p>
                <M3Button variant="text" size="sm" @click="triggerFileInput">
                    {{ t('common.crop.change') }}
                </M3Button>
            </div>

            <input
                ref="fileInputRef"
                type="file"
                accept="image/*"
                class="m3-crop__input"
                aria-hidden="true"
                @change="onFileChange"
            >
        </div>

        <template #footer>
            <M3Button variant="text" @click="close">{{ t('common.cancel') }}</M3Button>
            <M3Button variant="filled" :disabled="!imageSrc || isProcessing" @click="confirmCrop">
                {{ isProcessing ? t('common.crop.processing') : t('common.crop.use') }}
            </M3Button>
        </template>
    </M3Modal>
</template>

<style scoped>
.m3-crop {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-crop__error {
    margin: 0;
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

.m3-crop__drop {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-10) var(--m3-space-4);
    border: 2px dashed var(--m3-color-outline);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container);
    cursor: pointer;
    text-align: center;
    transition:
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-crop__drop:hover,
.m3-crop__drop--active {
    border-color: var(--m3-color-primary);
    background: var(--m3-color-primary-container);
}

.m3-crop__drop:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-crop__drop-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-crop__drop-icon svg {
    width: var(--m3-size-icon); 
    height: var(--m3-size-icon);
}

.m3-crop__drop-title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-crop__drop-hint {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-crop__stage {
    height: min(50dvh, calc(var(--m3-space-12) * 6));
    border-radius: var(--m3-radius-md);
    overflow: hidden;
    background: var(--m3-color-surface-container-highest);
}

.m3-crop__cropper {
    width: 100%;
    height: 100%;
}

.m3-crop__file {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-crop__file-name {
    flex: 1;
    min-width: 0;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-crop__input {
    display: none;
}
</style>
