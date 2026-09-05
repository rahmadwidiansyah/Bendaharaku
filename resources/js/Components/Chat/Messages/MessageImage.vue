<script setup>
/**
 * MessageImage.vue
 *
 * Render komponen gambar evidence di dalam user bubble.
 * Menampilkan thumbnail + status badge + tombol "Review" setelah OCR selesai.
 *
 * Props:
 *   component.localPreviewUrl  — URL lokal (blob) saat upload belum selesai
 *   component.evidenceUuid     — UUID evidence dari backend
 *   component.imageUrl         — URL permanen dari backend
 *   component.evidenceStatus   — status OCR: PENDING | UPLOADING | UPLOADED | PROCESSING | READY | FAILED | COMMITTED
 *   component.committed        — true jika sudah di-commit menjadi transaksi
 *
 * Emits:
 *   review(evidenceUuid)       — user menekan tombol Review
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'

const { t } = useI18n()

const props = defineProps({
    component: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['review'])

// Pilih URL gambar: lokalPreview dulu, fallback ke URL backend
const src = computed(() =>
    props.component.localPreviewUrl || props.component.imageUrl || null
)

const status = computed(() => props.component.evidenceStatus ?? 'UPLOADED')
const committed = computed(() => !!props.component.committed)
const uuid = computed(() => props.component.evidenceUuid ?? null)

// Hanya tampilkan tombol Review jika OCR sudah selesai & belum di-commit
const showReviewBtn = computed(() =>
    !committed.value && uuid.value && (status.value === 'READY' || status.value === 'RESOLVED')
)

// Status label + warna — semua via i18n (audit fix)
const statusMeta = computed(() => {
    const map = {
        PENDING:    { label: t('chat.evidenceStatus.pending'),   color: 'text-gray-400',       dot: 'bg-gray-400' },
        UPLOADING:  { label: t('chat.evidenceStatus.uploading'), color: 'text-transfer-text', dot: 'bg-transfer-text',   spin: true },
        UPLOADED:   { label: t('chat.evidenceStatus.processing'),color: 'text-debt-text',     dot: 'bg-debt-text', spin: true },
        PROCESSING: { label: t('chat.evidenceStatus.processing'),color: 'text-debt-text',     dot: 'bg-debt-text', spin: true },
        READY:      { label: t('chat.evidenceStatus.ready'),     color: 'text-income-text',   dot: 'bg-income-text' },
        RESOLVED:   { label: t('chat.evidenceStatus.ready'),     color: 'text-income-text',   dot: 'bg-income-text' },
        COMMITTED:  { label: t('chat.evidenceStatus.committed'), color: 'text-purple-400',    dot: 'bg-purple-400' },
        FAILED:     { label: t('chat.evidenceStatus.failed'),    color: 'text-expense-text',  dot: 'bg-expense-text' },
    }
    return map[status.value] ?? map.UPLOADED
})
</script>

<template>
    <div class="flex items-start gap-2.5 py-0.5">
        <!-- Thumbnail -->
        <div class="relative shrink-0 w-16 h-16 rounded-xl overflow-hidden border border-white/10 bg-gray-800">
            <img
                v-if="src"
                :src="src"
                :alt="t('chat.evidence')"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <!-- Overlay saat loading -->
            <div
                v-if="statusMeta.spin"
                class="absolute inset-0 bg-black/50 flex items-center justify-center"
            >
                <svg class="animate-spin w-5 h-5 text-white/70" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <!-- Committed checkmark -->
            <div
                v-else-if="committed"
                class="absolute inset-0 bg-purple-600/30 flex items-center justify-center"
            >
                <svg class="w-6 h-6 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <!-- Info + actions -->
        <div class="flex flex-col gap-1 min-w-0">
            <!-- Status -->
            <div class="flex items-center gap-1.5">
                <span
                    class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                    :class="[statusMeta.dot, statusMeta.spin ? 'animate-pulse' : '']"
                />
                <span class="text-2xs font-medium" :class="statusMeta.color">
                    {{ statusMeta.label }}
                </span>
            </div>

            <!-- Label bukti -->
            <p class="text-xs text-white/80 leading-tight truncate max-w-[140px]">
                {{ t('chat.evidenceLabel') }}
            </p>

            <!-- Tombol Review -->
            <button
                v-if="showReviewBtn"
                type="button"
                @click="emit('review', uuid)"
                class="mt-0.5 self-start inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-2xs font-bold
                       bg-white/10 text-white border border-white/15
                       hover:bg-white/20 hover:border-white/25 active:scale-95 transition-all"
            >
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ t('chat.reviewBtn') }}
            </button>

            <!-- Committed label -->
            <p v-else-if="committed" class="text-2xs text-purple-400 font-semibold inline-flex items-center gap-1">
                <AppIcon icon="check" class="w-3 h-3 shrink-0" />
                {{ t('chat.committed') }}
            </p>
        </div>
    </div>
</template>
