<script setup>
/**
 * ChatUploadSheet.vue
 *
 * Bottom sheet untuk upload gambar bukti di chat.
 * Menampilkan opsi: Ambil Foto, Pilih dari Galeri, Batal.
 *
 * Pattern: BaseModal (sama dengan CommandSheet).
 */

import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseModal from '@/Components/BaseModal.vue'

const { t } = useI18n()

const props = defineProps({
    modelValue: { type: Boolean, required: true },
})

const emit = defineEmits(['update:modelValue', 'camera', 'gallery'])

const fileInput = ref(null)
const isUploading = ref(false)

function close() {
    emit('update:modelValue', false)
}

function openCamera() {
    // Untuk mobile: buka kamera via input capture
    if (fileInput.value) {
        fileInput.value.setAttribute('capture', 'environment')
        fileInput.value.click()
    }
}

function openGallery() {
    // Untuk mobile: buka galeri via input tanpa capture
    if (fileInput.value) {
        fileInput.value.removeAttribute('capture')
        fileInput.value.click()
    }
}

function onFileSelected(event) {
    const file = event.target.files?.[0]
    if (!file) return

    emit('camera', file) // 'camera' event = file selected (baik dari kamera maupun galeri)
    close()

    // Reset input agar bisa select file yang sama lagi
    event.target.value = ''
}
</script>

<template>
    <BaseModal
        :show="modelValue"
        max-width="xl"
        align="bottom-sheet"
        mobile-only
        @close="close"
    >
        <!-- Header -->
        <template #header>
            <div>
                <h2 class="text-sm font-bold text-[var(--color-text-primary)]">{{ t('chat.uploadSheetTitle') }}</h2>
                <p class="text-2xs text-[var(--color-text-muted)] mt-0.5">{{ t('chat.uploadSheetDesc') }}</p>
            </div>
        </template>

        <!-- Options -->
        <div class="border-t border-[var(--color-border-default)]" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0.75rem));">
            <!-- Ambil Foto -->
            <button
                type="button"
                @click="openCamera"
                class="w-full flex items-center gap-4 py-3.5 hover:bg-white/5 active:bg-white/8 transition-colors text-left"
            >
                <div class="w-11 h-11 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[var(--color-brand)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-[var(--color-text-primary)]">{{ t('chat.uploadCamera') }}</p>
                    <p class="text-2xs text-[var(--color-text-muted)] mt-0.5">{{ t('chat.uploadCameraDesc') }}</p>
                </div>
            </button>

            <!-- Pilih dari Galeri -->
            <button
                type="button"
                @click="openGallery"
                class="w-full flex items-center gap-4 py-3.5 hover:bg-white/5 active:bg-white/8 transition-colors text-left"
            >
                <div class="w-11 h-11 rounded-2xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-[var(--color-text-primary)]">{{ t('chat.uploadGallery') }}</p>
                    <p class="text-2xs text-[var(--color-text-muted)] mt-0.5">{{ t('chat.uploadGalleryDesc') }}</p>
                </div>
            </button>

            <!-- Batal -->
            <div class="pt-2">
                <button
                    type="button"
                    @click="close"
                    class="w-full py-2.5 rounded-xl bg-white/5 border border-[var(--color-border-default)] text-sm font-semibold text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-white/8 active:scale-[0.98] transition-all"
                >
                    {{ t('common.cancel') }}
                </button>
            </div>
        </div>

        <!-- Hidden file input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onFileSelected"
        />
    </BaseModal>
</template>
