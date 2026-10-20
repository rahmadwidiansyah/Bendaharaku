<script setup>
/**
 * M3IconPicker — pemilih ikon kategori versi M3.
 * Katalog dan alur upload identik dengan Components/IconPicker.vue:
 * pilih ikon lucide, cari, atau unggah gambar (crop 1:1 → webp).
 * Presentasi memakai M3Modal, M3TextField, M3Chip, dan M3ImageCropModal.
 */
import { computed, onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3ImageCropModal from '@/Material/Components/M3ImageCropModal.vue'
import { ICON_CATALOG, ICON_TABS } from '@/utils/iconCatalog.js'

const { t } = useI18n()

const props = defineProps({
    modelValue: { type: String, default: '' },
    defaultIcon: { type: String, default: 'folder' },
})

const emit = defineEmits(['update:modelValue', 'file-selected'])

const isOpen = ref(false)
const showCropModal = ref(false)
const activeTab = ref(ICON_TABS[0])
const searchTerm = ref('')
const previewUrl = ref(null)

const filteredIcons = computed(() => {
    const term = searchTerm.value.trim().toLowerCase()
    if (term) {
        return Object.values(ICON_CATALOG).flat().filter((icon) => icon.includes(term))
    }
    return ICON_CATALOG[activeTab.value] ?? []
})

const openModal = () => {
    isOpen.value = true
}

const closeModal = () => {
    isOpen.value = false
    searchTerm.value = ''
}

const selectIcon = (icon) => {
    previewUrl.value = null
    emit('update:modelValue', icon)
    closeModal()
}

const openCropForUpload = () => {
    showCropModal.value = true
}

const handleCropped = (blob) => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
    const file = new File([blob], 'icon.webp', { type: blob.type })
    previewUrl.value = URL.createObjectURL(blob)
    emit('file-selected', file)
    showCropModal.value = false
    closeModal()
}

onBeforeUnmount(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
})
</script>

<template>
    <div class="m3-icon-picker">
        <button
            type="button"
            class="m3-icon-picker__trigger"
            :aria-label="t('iconPicker.title')"
            aria-haspopup="dialog"
            @click="openModal"
        >
            <img v-if="previewUrl" :src="previewUrl" alt="" class="m3-icon-picker__preview">
            <AppIcon
                v-else
                :icon="modelValue || defaultIcon"
                fallback="folder"
                class="m3-icon-picker__preview-icon"
            />
        </button>

        <M3Modal
            :show="isOpen"
            :title="t('iconPicker.title')"
            variant="dialog"
            mobile-variant="sheet"
            max-width="sm"
            @close="closeModal"
        >
            <div class="m3-icon-picker__body">
                <div class="m3-icon-picker__toolbar">
                    <M3TextField
                        v-model="searchTerm"
                        :label="t('iconPicker.search')"
                        type="search"
                    />
                    <M3IconButton
                        variant="tonal"
                        :aria-label="t('iconPicker.upload')"
                        :title="t('iconPicker.upload')"
                        @click="openCropForUpload"
                    >
                        <AppIcon icon="image-up" />
                    </M3IconButton>
                </div>

                <div v-if="!searchTerm.trim()" class="m3-icon-picker__tabs" role="tablist">
                    <M3Chip
                        v-for="tab in ICON_TABS"
                        :key="tab"
                        :selected="activeTab === tab"
                        role="tab"
                        :aria-selected="activeTab === tab"
                        @click="activeTab = tab"
                    >
                        {{ t('iconPicker.tabs.' + tab) }}
                    </M3Chip>
                </div>

                <div class="m3-icon-picker__grid" role="listbox" :aria-label="t('iconPicker.title')">
                    <button
                        v-for="icon in filteredIcons"
                        :key="icon"
                        type="button"
                        class="m3-icon-picker__option"
                        :class="{ 'm3-icon-picker__option--selected': modelValue === icon }"
                        :aria-label="icon"
                        :aria-selected="modelValue === icon"
                        role="option"
                        @click="selectIcon(icon)"
                    >
                        <AppIcon :icon="icon" class="m3-icon-picker__option-icon" />
                    </button>
                </div>

                <div v-if="filteredIcons.length === 0" class="m3-icon-picker__empty" role="status">
                    <AppIcon icon="search-x" class="m3-icon-picker__empty-icon" />
                    <p>{{ t('iconPicker.notFound') }}</p>
                </div>
            </div>
        </M3Modal>

        <M3ImageCropModal
            v-model="showCropModal"
            :title="t('iconPicker.cropTitle')"
            :aspect-ratio="1"
            :circle="false"
            :max-size-kb="500"
            output-format="image/webp"
            @cropped="handleCropped"
        />
    </div>
</template>

<style scoped>
.m3-icon-picker {
    --m3-icon-picker-size: 4.5rem;

    flex-shrink: 0;
}

.m3-icon-picker__trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-icon-picker-size);
    height: var(--m3-icon-picker-size);
    padding: 0;
    overflow: hidden;
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-primary);
    cursor: pointer;
    transition:
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-icon-picker__trigger:hover {
    border-color: var(--m3-color-primary);
}

.m3-icon-picker__trigger:active {
    transform: scale(0.96);
}

.m3-icon-picker__trigger:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-icon-picker__preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.m3-icon-picker__preview-icon {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
}

.m3-icon-picker__body {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-width: 0;
}

.m3-icon-picker__toolbar {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-2);
}

.m3-icon-picker__tabs {
    display: flex;
    gap: var(--m3-space-2);
    overflow-x: auto;
    padding-bottom: var(--m3-space-1);
    scrollbar-width: none;
}

.m3-icon-picker__tabs::-webkit-scrollbar {
    display: none;
}

.m3-icon-picker__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(2.75rem, 1fr));
    gap: var(--m3-space-2);
    max-height: 50vh;
    overflow-y: auto;
    padding: var(--m3-space-1);
}

.m3-icon-picker__option {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
    min-width: 2.75rem;
    min-height: 2.75rem;
    padding: 0;
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-icon-picker__option:hover {
    border-color: var(--m3-color-primary);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-icon-picker__option:active {
    transform: scale(0.92);
}

.m3-icon-picker__option:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-icon-picker__option--selected {
    border-color: var(--m3-color-primary);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-icon-picker__option-icon {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-icon-picker__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-8) var(--m3-space-4);
    color: var(--m3-color-on-surface-variant);
    text-align: center;
}

.m3-icon-picker__empty p {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-icon-picker__empty-icon {
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
    color: var(--m3-color-primary);
}
</style>
