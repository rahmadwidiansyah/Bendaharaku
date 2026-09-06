<script setup>
/**
 * ImageCropModal.vue
 *
 * Komponen crop image reusable untuk seluruh aplikasi Bendaharaku.
 *
 * Dapat digunakan untuk:
 * - Bot Avatar (aspect 1:1)
 * - Foto Profil User (aspect 1:1)
 * - Icon Wallet / Category / Asset (aspect 1:1)
 * - Cover / Banner (aspect 16:9)
 * - Dan semua kebutuhan upload + crop di masa depan
 *
 * Props:
 *   modelValue  — Boolean v-model untuk buka/tutup modal
 *   title       — Judul modal (string, default: 'Crop Gambar')
 *   aspectRatio — Rasio aspek crop area: 1 (1:1), 16/9, 4/3, dst. Default: 1
 *   maxSizeKb   — Ukuran maksimum output dalam KB (default: 500)
 *   quality     — Kualitas JPEG/PNG 0.0–1.0 (default: 0.85)
 *   outputFormat— 'image/jpeg' | 'image/webp' | 'image/png' (default: 'image/webp')
 *
 * Emits:
 *   update:modelValue — Untuk v-model (true/false)
 *   cropped(blob)     — Blob hasil crop yang siap di-upload
 *   cancel            — User membatalkan
 *
 * Usage:
 *   <ImageCropModal
 *     v-model="showCropper"
 *     title="Atur Foto Bot"
 *     :aspectRatio="1"
 *     @cropped="handleCropped"
 *   />
 */

import { ref, computed, watch } from 'vue'
import { Cropper, CircleStencil, RectangleStencil } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import BaseModal from '@/Components/BaseModal.vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Crop Gambar',
    },
    aspectRatio: {
        type: Number,
        default: 1,
    },
    maxSizeKb: {
        type: Number,
        default: 500,
    },
    quality: {
        type: Number,
        default: 0.85,
    },
    outputFormat: {
        type: String,
        default: 'image/webp',
        validator: (v) => ['image/jpeg', 'image/webp', 'image/png'].includes(v),
    },
    // Gunakan circle stencil untuk avatar (aspect 1:1)
    circle: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue', 'cropped', 'cancel'])

// ── State ─────────────────────────────────────────────────────────
const cropperRef    = ref(null)
const fileInputRef  = ref(null)
const imageSrc      = ref(null)
const isDragging    = ref(false)
const isProcessing  = ref(false)
const errorMsg      = ref(null)
const fileName      = ref('')

// ── Computed ──────────────────────────────────────────────────────
const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

const stencilComponent = computed(() =>
    props.circle ? CircleStencil : RectangleStencil
)

const stencilProps = computed(() => ({
    aspectRatio: props.aspectRatio,
}))

// ── Watch ─────────────────────────────────────────────────────────
// Reset saat modal ditutup
watch(isOpen, (val) => {
    if (!val) {
        resetState()
    }
})

// ── Methods ───────────────────────────────────────────────────────
function resetState() {
    imageSrc.value   = null
    errorMsg.value   = null
    isProcessing.value = false
    fileName.value   = ''
    if (fileInputRef.value) {
        fileInputRef.value.value = ''
    }
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

    // Validasi tipe file
    if (!file.type.startsWith('image/')) {
        errorMsg.value = 'File harus berupa gambar (JPG, PNG, WebP, GIF).'
        return
    }

    // Validasi ukuran (max 10MB untuk original)
    if (file.size > 10 * 1024 * 1024) {
        errorMsg.value = 'Ukuran file maksimal 10MB.'
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
            errorMsg.value = 'Gagal memproses gambar. Coba lagi.'
            return
        }

        // Konversi canvas ke Blob
        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob(
                (b) => {
                    if (b) resolve(b)
                    else reject(new Error('toBlob returned null'))
                },
                props.outputFormat,
                props.quality,
            )
        })

        // Validasi ukuran output
        if (blob.size > props.maxSizeKb * 1024) {
            // Coba compress lebih
            const compressed = await compressBlob(canvas, blob.size)
            emit('cropped', compressed)
        } else {
            emit('cropped', blob)
        }

        isOpen.value = false

    } catch (err) {
        errorMsg.value = 'Terjadi kesalahan saat memproses gambar.'
        console.error('ImageCropModal: crop error', err)
    } finally {
        isProcessing.value = false
    }
}

// Compress blob dengan menurunkan quality secara iteratif
async function compressBlob(canvas, originalSize) {
    let quality = props.quality
    let blob = null

    // Coba sampai quality 0.3 atau ukuran di bawah limit
    while (quality > 0.3) {
        quality -= 0.1
        blob = await new Promise((resolve) => {
            canvas.toBlob(resolve, 'image/webp', Math.max(0.3, quality))
        })
        if (blob && blob.size <= props.maxSizeKb * 1024) break
    }

    return blob
}

// Keyboard: Escape menutup modal
function onKeydown(e) {
    if (e.key === 'Escape') close()
}
</script>

<template>
    <BaseModal
        :show="isOpen"
        :title="title"
        max-width="adaptive"
        @close="close"
        @keydown="onKeydown"
    >
                        <!-- Body -->
                        <div class="p-5">

                            <!-- Error message -->
                            <div
                                v-if="errorMsg"
                                class="mb-4 px-4 py-3 rounded-xl bg-[var(--color-expense-bg)] border border-[var(--color-expense-border)] text-[var(--color-expense-text)] text-xs"
                            >
                                {{ errorMsg }}
                            </div>

                            <!-- Drop zone (saat belum ada gambar) -->
                            <div
                                v-if="!imageSrc"
                                @click="triggerFileInput"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="onDrop"
                                :class="[
                                    'relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed cursor-pointer transition-all duration-200 p-10',
                                    isDragging
                                        ? 'border-purple-500 bg-purple-500/10'
                                        : 'border-white/15 bg-white/3 hover:border-purple-500/50 hover:bg-purple-500/5'
                                ]"
                            >
                                <!-- Upload icon -->
                                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-[var(--color-text-primary)]">
                                        {{ isDragging ? 'Lepas untuk upload' : 'Pilih atau drag gambar' }}
                                    </p>
                                    <p class="text-2xs text-gray-500 mt-1">JPG, PNG, WebP — Maks. 10MB</p>
                                </div>
                            </div>

                            <!-- Cropper area (saat gambar sudah dipilih) -->
                            <div v-else class="rounded-xl overflow-hidden bg-gray-950" style="height: 300px;">
                                <Cropper
                                    ref="cropperRef"
                                    :src="imageSrc"
                                    :stencil-component="stencilComponent"
                                    :stencil-props="stencilProps"
                                    class="w-full h-full"
                                    background-class="!bg-gray-950"
                                    image-restriction="stencil"
                                />
                            </div>

                            <!-- Nama file + tombol ganti -->
                            <div v-if="imageSrc" class="mt-3 flex items-center gap-2">
                                <p class="flex-1 text-2xs text-gray-500 truncate">{{ fileName }}</p>
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    class="shrink-0 text-2xs font-bold text-purple-400 hover:text-purple-300 transition-colors"
                                >
                                    Ganti foto
                                </button>
                            </div>

                        </div>

                        <!-- Footer actions -->
                        <template #footer>
                            <div class="flex items-center gap-3 w-full">
                                <button
                                    type="button"
                                    @click="close"
                                    class="flex-1 py-3 rounded-xl text-xs font-bold uppercase tracking-widest bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] text-gray-400 hover:text-[var(--color-text-primary)] hover:border-white/20 transition-all"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="confirmCrop"
                                    :disabled="!imageSrc || isProcessing"
                                    :class="[
                                        'flex-1 py-3 rounded-xl text-xs font-bold uppercase tracking-widest transition-all flex items-center justify-center gap-2',
                                        imageSrc && !isProcessing
                                            ? 'bg-gradient-to-br from-brand-deep to-brand-soft text-[var(--color-text-primary)] shadow-lg shadow-purple-500/20 hover:from-brand-mid hover:to-brand-tint'
                                            : 'bg-[var(--color-surface-muted)] text-gray-600 border border-white/5 cursor-not-allowed'
                                    ]"
                                >
                                    <svg
                                        v-if="isProcessing"
                                        class="animate-spin w-4 h-4 shrink-0"
                                        fill="none" viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ isProcessing ? 'Memproses...' : 'Gunakan Foto' }}
                                </button>
                            </div>
                        </template>

                        <!-- Hidden file input -->
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onFileChange"
                            aria-hidden="true"
                        />
    </BaseModal>
</template>
