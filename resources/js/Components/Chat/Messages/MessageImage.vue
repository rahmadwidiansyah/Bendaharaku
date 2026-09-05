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
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'

const { t } = useI18n()

const props = defineProps({
    component: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['review', 'retry'])

const isPreviewOpen = ref(false)

// ——— WA-style full-screen zoom state ———
const scale = ref(1)
const translate = ref({ x: 0, y: 0 })
const isDragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const startTranslate = ref({ x: 0, y: 0 })
const lastTouchDist = ref(0)
const lastTap = ref(0)
const swipeStartY = ref(0)

function resetZoom() {
    scale.value = 1
    translate.value = { x: 0, y: 0 }
}
function clampScale(v) { return Math.min(4, Math.max(1, v)) }
function handleWheel(e) {
    e.preventDefault()
    const delta = -e.deltaY * 0.001
    const next = clampScale(scale.value + delta)
    // keep centered — simple
    scale.value = next
    if (next === 1) translate.value = { x: 0, y: 0 }
}
function handleDoubleTap() {
    if (scale.value > 1.5) resetZoom()
    else scale.value = 2.5
}
function handleTouchStart(e) {
    if (e.touches.length === 2) {
        const dx = e.touches[0].clientX - e.touches[1].clientX
        const dy = e.touches[0].clientY - e.touches[1].clientY
        lastTouchDist.value = Math.hypot(dx, dy)
    } else if (e.touches.length === 1) {
        const now = Date.now()
        if (now - lastTap.value < 300) handleDoubleTap()
        lastTap.value = now
        swipeStartY.value = e.touches[0].clientY
        if (scale.value > 1) {
            isDragging.value = true
            dragStart.value = { x: e.touches[0].clientX, y: e.touches[0].clientY }
            startTranslate.value = { ...translate.value }
        }
    }
}
function handleTouchMove(e) {
    if (e.touches.length === 2 && lastTouchDist.value) {
        e.preventDefault()
        const dx = e.touches[0].clientX - e.touches[1].clientX
        const dy = e.touches[0].clientY - e.touches[1].clientY
        const dist = Math.hypot(dx, dy)
        const factor = dist / lastTouchDist.value
        scale.value = clampScale(scale.value * factor)
        lastTouchDist.value = dist
        if (scale.value === 1) translate.value = { x: 0, y: 0 }
    } else if (e.touches.length === 1 && isDragging.value) {
        e.preventDefault()
        const dx = e.touches[0].clientX - dragStart.value.x
        const dy = e.touches[0].clientY - dragStart.value.y
        translate.value = { x: startTranslate.value.x + dx, y: startTranslate.value.y + dy }
    } else if (e.touches.length === 1 && scale.value === 1) {
        // swipe down to close — track
        const dy = e.touches[0].clientY - swipeStartY.value
        if (dy > 0) {
            // visual feedback via translate
            translate.value = { x: 0, y: dy * 0.3 }
        }
    }
}
function handleTouchEnd(e) {
    if (e.touches.length === 0) {
        if (scale.value === 1) {
            const dy = (e.changedTouches?.[0]?.clientY ?? 0) - swipeStartY.value
            if (dy > 100) {
                isPreviewOpen.value = false
                resetZoom()
            } else {
                translate.value = { x: 0, y: 0 }
            }
        }
        isDragging.value = false
        lastTouchDist.value = 0
    }
}
function handleDragStart(e) {
    if (scale.value === 1) return
    isDragging.value = true
    dragStart.value = { x: e.clientX, y: e.clientY }
    startTranslate.value = { ...translate.value }
}
function handleDragMove(e) {
    if (!isDragging.value) return
    const dx = e.clientX - dragStart.value.x
    const dy = e.clientY - dragStart.value.y
    translate.value = { x: startTranslate.value.x + dx, y: startTranslate.value.y + dy }
}
function handleDragEnd() { isDragging.value = false }
function closePreview() { isPreviewOpen.value = false; resetZoom() }

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
    <div class="flex flex-col gap-1.5 py-0.5 w-full max-w-[280px]">
        <!-- WA-style: gambar di atas, full width bubble -->
        <button
            type="button"
            class="relative w-full aspect-[4/3] rounded-xl overflow-hidden border border-white/10 bg-gray-800 hover:border-white/20 active:scale-[0.98] transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/50 group"
            :aria-label="t('chat.openFullscreen')"
            @click="isPreviewOpen = true"
        >
            <img
                v-if="src"
                :src="src"
                :alt="t('chat.evidence')"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <!-- Hint zoom -->
            <span class="absolute bottom-2 right-2 w-6 h-6 rounded-full bg-black/60 backdrop-blur flex items-center justify-center opacity-80 group-hover:opacity-100 transition-opacity">
                <AppIcon icon="maximize-2" class="w-3 h-3 text-white" />
            </span>
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
                class="absolute inset-0 bg-purple-600/30 flex items-center justify-center pointer-events-none"
            >
                <svg class="w-6 h-6 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </button>

        <!-- Caption hint dari struk ada di bubble terpisah (MessageText), di sini cuma status -->
        <!-- Status di bawah gambar — WA style -->
        <div class="flex items-center justify-between gap-2 px-1">
            <div class="flex items-center gap-1.5">
                <span
                    class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                    :class="[statusMeta.dot, statusMeta.spin ? 'animate-pulse' : '']"
                />
                <span class="text-2xs font-medium" :class="statusMeta.color">
                    {{ statusMeta.label }}
                </span>
            </div>
            <span class="text-2xs text-white/50 truncate">{{ t('chat.evidenceLabel') }}</span>
        </div>

        <!-- Tombol Review — di bawah status, full width jika ada -->
        <button
            v-if="showReviewBtn"
            type="button"
            @click="emit('review', uuid)"
            class="self-stretch inline-flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs font-bold
                   bg-white text-black
                   hover:bg-white/90 active:scale-[0.98] transition-all"
        >
            <AppIcon icon="eye" class="w-3.5 h-3.5" />
            {{ t('chat.reviewBtn') }}
        </button>

        <p v-else-if="committed" class="text-2xs text-purple-400 font-semibold inline-flex items-center gap-1 px-1">
            <AppIcon icon="check" class="w-3 h-3 shrink-0" />
            {{ t('chat.committed') }}
        </p>

        <!-- Retry jika gagal grouping / LLM down — chat turun seperti kirim lagi -->
        <button
            v-if="status === 'FAILED' && uuid"
            type="button"
            @click="emit('retry', uuid)"
            class="self-stretch inline-flex items-center justify-center gap-1.5 py-2 rounded-xl text-xs font-bold
                   bg-expense-bg text-expense-text border border-expense-border
                   hover:bg-expense-bg-hover active:scale-[0.98] transition-all"
        >
            <AppIcon icon="refresh-cw" class="w-3.5 h-3.5" />
            {{ t('chat.retry') }}
        </button>
    </div>

    <!-- WA-style full-screen viewer — zoom wheel/pinch, double-tap, drag, swipe down -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isPreviewOpen"
                class="fixed inset-0 z-[100] bg-black/95 backdrop-blur flex flex-col select-none"
                tabindex="-1"
                @keydown.esc="closePreview"
                @click.self="closePreview"
            >
                <!-- Top bar WA -->
                <header class="shrink-0 flex items-center gap-3 px-3 sm:px-4 py-3 bg-black/60 backdrop-blur border-b border-white/10">
                    <button type="button" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" :aria-label="t('common.close')" @click="closePreview">
                        <AppIcon icon="arrow-left" class="w-5 h-5" />
                    </button>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-white leading-tight truncate">{{ t('chat.evidencePreview') }}</p>
                        <p class="text-2xs text-white/60 truncate">Pinch/scroll untuk zoom • double-tap • drag saat zoom</p>
                    </div>
                    <a v-if="src" :href="src" target="_blank" rel="noopener" class="ml-auto w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center" :title="t('chat.openFullscreen')">
                        <AppIcon icon="external-link" class="w-4 h-4" />
                    </a>
                    <button type="button" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center" @click="closePreview">
                        <AppIcon icon="x" class="w-5 h-5" />
                    </button>
                </header>

                <!-- Image stage -->
                <div
                    class="flex-1 relative overflow-hidden flex items-center justify-center bg-black touch-none"
                    @wheel.prevent="handleWheel"
                    @touchstart.passive="handleTouchStart"
                    @touchmove.prevent="handleTouchMove"
                    @touchend="handleTouchEnd"
                    @mousedown="handleDragStart"
                    @mousemove="handleDragMove"
                    @mouseup="handleDragEnd"
                    @mouseleave="handleDragEnd"
                    @dblclick="handleDoubleTap"
                >
                    <img
                        v-if="src"
                        :src="src"
                        :alt="t('chat.evidence')"
                        class="max-w-[92vw] max-h-[82vh] object-contain will-change-transform select-none"
                        :style="{
                            transform: `translate(${translate.x}px, ${translate.y}px) scale(${scale})`,
                            transition: isDragging ? 'none' : 'transform 0.2s ease-out',
                            cursor: scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in'
                        }"
                        draggable="false"
                    />
                    <p v-else class="text-sm text-white/60">{{ t('common.noData') }}</p>
                    <!-- hint -->
                    <p v-if="scale === 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-2xs text-white/50 bg-black/50 px-2.5 py-1 rounded-full pointer-events-none">Scroll untuk zoom • double-tap</p>
                </div>

                <!-- Bottom controls -->
                <div class="shrink-0 flex items-center justify-center gap-2 px-4 py-3 bg-black/60 backdrop-blur border-t border-white/10">
                    <button type="button" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center disabled:opacity-30" :disabled="scale<=1" @click="scale = clampScale(scale - 0.25)">
                        <AppIcon icon="zoom-out" class="w-4 h-4" />
                    </button>
                    <span class="min-w-[56px] text-center text-xs font-bold tabular-nums text-white bg-white/10 px-2.5 py-1 rounded-full">{{ Math.round(scale*100) }}%</span>
                    <button type="button" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center disabled:opacity-30" :disabled="scale>=4" @click="scale = clampScale(scale + 0.25)">
                        <AppIcon icon="zoom-in" class="w-4 h-4" />
                    </button>
                    <span class="w-px h-6 bg-white/10 mx-1"></span>
                    <button type="button" class="px-3 py-1.5 rounded-full bg-white text-black text-xs font-bold hover:bg-white/90 disabled:opacity-50" :disabled="scale===1 && translate.x===0 && translate.y===0" @click="resetZoom">Reset</button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
