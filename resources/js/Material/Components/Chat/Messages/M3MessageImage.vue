<script setup>
/**
 * M3MessageImage — thumbnail evidence di dalam bubble user + viewer
 * fullscreen WA-style (pinch/wheel zoom, double-tap, drag, swipe-down).
 * Paritas MessageImage Default TANPA tombol/alur Review (keputusan scope M3);
 * status memakai color role M3, bukan warna finansial.
 *
 * Catatan: elemen di atas kanvas viewer selalu gelap (media-overlay),
 * sehingga teks/ikon memakai putih tetap — konvensi viewer gambar.
 */
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'

const { t } = useI18n()

const props = defineProps({
    component: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['retry'])

const isPreviewOpen = ref(false)

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
        const dy = e.touches[0].clientY - swipeStartY.value
        if (dy > 0) {
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

const src = computed(() =>
    props.component.localPreviewUrl || props.component.imageUrl || null
)

const rawStatus = computed(() => props.component.evidenceStatus ?? 'UPLOADED')
const status = computed(() => String(rawStatus.value).toUpperCase())
const committed = computed(() => !!props.component.committed)
const uuid = computed(() => props.component.evidenceUuid ?? null)

const isDoneStatus = computed(() => ['COMMITTED', 'COMPLETED', 'DONE', 'SUCCESS'].includes(status.value))

const statusMeta = computed(() => {
    const map = {
        PENDING:    { label: t('chat.evidenceStatus.pending'),    cls: 'm3-ev__dot--neutral', spin: false },
        UPLOADING:  { label: t('chat.evidenceStatus.uploading'),  cls: 'm3-ev__dot--busy',    spin: true },
        UPLOADED:   { label: t('chat.evidenceStatus.processing'), cls: 'm3-ev__dot--busy',    spin: true },
        PROCESSING: { label: t('chat.evidenceStatus.processing'), cls: 'm3-ev__dot--busy',    spin: true },
        READY:      { label: t('chat.evidenceStatus.ready'),      cls: 'm3-ev__dot--ready',   spin: false },
        RESOLVED:   { label: t('chat.evidenceStatus.ready'),      cls: 'm3-ev__dot--ready',   spin: false },
        COMMITTED:  { label: t('chat.evidenceStatus.committed'),  cls: 'm3-ev__dot--ready',   spin: false },
        COMPLETED:  { label: t('chat.evidenceStatus.committed'),  cls: 'm3-ev__dot--ready',   spin: false },
        FAILED:     { label: t('chat.evidenceStatus.failed'),     cls: 'm3-ev__dot--failed',  spin: false },
    }
    return map[status.value] ?? map.UPLOADED
})
</script>

<template>
    <div class="m3-ev">
        <button
            type="button"
            class="m3-ev__thumb"
            :aria-label="t('chat.openFullscreen')"
            @click="isPreviewOpen = true"
        >
            <img
                v-if="src"
                :src="src"
                :alt="t('chat.evidence')"
                class="m3-ev__img"
                loading="lazy"
            />
            <span class="m3-ev__zoom-hint" aria-hidden="true">
                <AppIcon icon="maximize-2" iconClass="m3-ev__zoom-icon" />
            </span>
            <div v-if="statusMeta.spin" class="m3-ev__loading" aria-hidden="true">
                <M3CircularProgress class="m3-ev__spinner" size="md" />
            </div>
            <div v-else-if="committed" class="m3-ev__committed" aria-hidden="true">
                <AppIcon icon="check" iconClass="m3-ev__committed-icon" />
            </div>
        </button>

        <div class="m3-ev__status-row">
            <div class="m3-ev__status">
                <span
                    class="m3-ev__dot"
                    :class="[statusMeta.cls, statusMeta.spin ? 'm3-ev__dot--pulse' : '']"
                    aria-hidden="true"
                />
                <span class="m3-ev__status-label" :class="statusMeta.cls">{{ statusMeta.label }}</span>
            </div>
            <span class="m3-ev__kind">{{ t('chat.evidenceLabel') }}</span>
        </div>

        <p v-if="committed || isDoneStatus" class="m3-ev__done">
            <AppIcon icon="check" iconClass="m3-ev__done-icon" />
            {{ t('chat.committed') }}
        </p>

        <button
            v-if="status === 'FAILED' && uuid"
            type="button"
            class="m3-ev__retry"
            @click="emit('retry', uuid)"
        >
            <AppIcon icon="refresh-cw" iconClass="m3-ev__retry-icon" />
            {{ t('chat.retry') }}
        </button>
    </div>

    <Teleport to="body">
        <Transition
            enter-active-class="m3-ev-view-enter-active"
            leave-active-class="m3-ev-view-leave-active"
            enter-from-class="m3-ev-view-enter-from"
            leave-to-class="m3-ev-view-leave-to"
        >
            <div
                v-if="isPreviewOpen"
                class="m3-ev-view"
                role="dialog"
                aria-modal="true"
                :aria-label="t('chat.evidencePreview')"
                tabindex="-1"
                @keydown.esc="closePreview"
                @click.self="closePreview"
            >
                <header class="m3-ev-view__bar">
                    <button type="button" class="m3-ev-view__btn" :aria-label="t('common.close')" @click="closePreview">
                        <AppIcon icon="arrow-left" iconClass="m3-ev-view__icon" />
                    </button>
                    <div class="m3-ev-view__titles">
                        <p class="m3-ev-view__title">{{ t('chat.evidencePreview') }}</p>
                        <p class="m3-ev-view__subtitle">Pinch/scroll untuk zoom • double-tap • drag saat zoom</p>
                    </div>
                    <a v-if="src" :href="src" target="_blank" rel="noopener" class="m3-ev-view__btn" :title="t('chat.openFullscreen')">
                        <AppIcon icon="external-link" iconClass="m3-ev-view__icon" />
                    </a>
                    <button type="button" class="m3-ev-view__btn" :aria-label="t('common.close')" @click="closePreview">
                        <AppIcon icon="x" iconClass="m3-ev-view__icon" />
                    </button>
                </header>

                <div
                    class="m3-ev-view__stage"
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
                        class="m3-ev-view__img"
                        :style="{
                            transform: `translate(${translate.x}px, ${translate.y}px) scale(${scale})`,
                            transition: isDragging ? 'none' : 'transform 0.2s ease-out',
                            cursor: scale > 1 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in'
                        }"
                        draggable="false"
                    />
                    <p v-else class="m3-ev-view__empty">{{ t('common.noData') }}</p>
                    <p v-if="scale === 1" class="m3-ev-view__hint">Scroll untuk zoom • double-tap</p>
                </div>

                <div class="m3-ev-view__controls">
                    <button type="button" class="m3-ev-view__btn" :disabled="scale <= 1" aria-label="Zoom out" @click="scale = clampScale(scale - 0.25)">
                        <AppIcon icon="zoom-out" iconClass="m3-ev-view__icon" />
                    </button>
                    <span class="m3-ev-view__scale">{{ Math.round(scale * 100) }}%</span>
                    <button type="button" class="m3-ev-view__btn" :disabled="scale >= 4" aria-label="Zoom in" @click="scale = clampScale(scale + 0.25)">
                        <AppIcon icon="zoom-in" iconClass="m3-ev-view__icon" />
                    </button>
                    <span class="m3-ev-view__sep" aria-hidden="true" />
                    <button type="button" class="m3-ev-view__reset" :disabled="scale === 1 && translate.x === 0 && translate.y === 0" @click="resetZoom">
                        Reset
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* ── Thumbnail di bubble user ── */
.m3-ev {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1-5);
    width: 100%;
}

.m3-ev__thumb {
    position: relative;
    display: block;
    width: 100%;
    max-height: 22.5rem;
    padding: 0;
    border: none;
    border-radius: var(--m3-radius-md);
    overflow: hidden;
    background: color-mix(in srgb, var(--m3-color-on-primary-container) 8%, transparent);
    cursor: pointer;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-ev__thumb:active {
    transform: scale(0.98);
}

.m3-ev__thumb:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-ev__img {
    display: block;
    width: 100%;
    height: auto;
    max-height: 22.5rem;
    object-fit: cover;
}

.m3-ev__zoom-hint {
    position: absolute;
    bottom: var(--m3-space-2);
    right: var(--m3-space-2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-icon-lg);
    height: var(--m3-size-icon-lg);
    border-radius: var(--m3-radius-full);
    background: color-mix(in srgb, var(--m3-color-scrim) 62%, transparent);
    color: #fff;
    opacity: 0.85;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-ev__thumb:hover .m3-ev__zoom-hint {
    opacity: 1;
}

.m3-ev__zoom-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
}

.m3-ev__loading,
.m3-ev__committed {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.m3-ev__loading {
    background: color-mix(in srgb, var(--m3-color-scrim) 50%, transparent);
}

.m3-ev__spinner {
    color: var(--m3-color-primary);
}

.m3-ev__committed {
    background: color-mix(in srgb, var(--m3-color-income) 30%, transparent);
}

.m3-ev__committed-icon {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
    color: var(--m3-color-income);
    filter: drop-shadow(0 1px 2px var(--m3-color-scrim));
}

/* ── Baris status ── */
.m3-ev__status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    padding: 0 var(--m3-space-1);
}

.m3-ev__status {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1-5);
    min-width: 0;
}

.m3-ev__dot {
    display: inline-block;
    width: 0.375rem;
    height: 0.375rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-ev__dot--pulse {
    animation: m3-ev-pulse 1.4s ease-in-out infinite;
}

@keyframes m3-ev-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.m3-ev__status-label {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
}

.m3-ev__dot--neutral { background: var(--m3-color-on-surface-variant); }
.m3-ev__dot--busy { background: var(--m3-color-primary); }
.m3-ev__dot--ready { background: var(--m3-color-income); }
.m3-ev__dot--failed { background: var(--m3-color-error); }

.m3-ev__status-label.m3-ev__dot--neutral { color: var(--m3-color-on-surface-variant); }
.m3-ev__status-label.m3-ev__dot--busy { color: var(--m3-color-primary); }
.m3-ev__status-label.m3-ev__dot--ready { color: var(--m3-color-income); }
.m3-ev__status-label.m3-ev__dot--failed { color: var(--m3-color-error); }

.m3-ev__kind {
    flex-shrink: 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: color-mix(in srgb, currentColor 55%, transparent);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-ev__done {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    margin: 0;
    padding: 0 var(--m3-space-1);
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    color: var(--m3-color-income);
}

.m3-ev__done-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
}

.m3-ev__retry {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-1-5);
    align-self: stretch;
    min-height: var(--m3-size-touch-target);
    padding: var(--m3-space-2) var(--m3-space-3);
    border: 1px solid var(--m3-color-error);
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-error);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-ev__retry:hover {
    background: color-mix(in srgb, var(--m3-color-error) 8%, transparent);
}

.m3-ev__retry:active {
    transform: scale(0.98);
}

.m3-ev__retry:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-ev__retry-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
}

/* ── Viewer fullscreen (inverse roles = konvensi kontras M3) ── */
.m3-ev-view {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    flex-direction: column;
    background: var(--m3-color-inverse-surface);
    color: var(--m3-color-inverse-on-surface);
    user-select: none;
}

.m3-ev-view:focus-visible {
    outline: none;
}

.m3-ev-view__bar,
.m3-ev-view__controls {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-2) var(--m3-space-3);
    background: color-mix(in srgb, var(--m3-color-inverse-on-surface) 6%, transparent);
}

.m3-ev-view__bar {
    padding-top: calc(var(--m3-space-2) + env(safe-area-inset-top, 0px));
}

.m3-ev-view__controls {
    justify-content: center;
    padding-bottom: calc(var(--m3-space-2) + env(safe-area-inset-bottom, 0px));
}

.m3-ev-view__titles {
    min-width: 0;
    flex: 1;
}

.m3-ev-view__title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-ev-view__subtitle {
    margin: 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    opacity: 0.65;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-ev-view__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border: none;
    border-radius: var(--m3-radius-full);
    background: color-mix(in srgb, var(--m3-color-inverse-on-surface) 10%, transparent);
    color: inherit;
    cursor: pointer;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-ev-view__btn:hover {
    background: color-mix(in srgb, var(--m3-color-inverse-on-surface) 18%, transparent);
}

.m3-ev-view__btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.m3-ev-view__btn:focus-visible {
    outline: 2px solid var(--m3-color-inverse-primary);
    outline-offset: 2px;
}

.m3-ev-view__icon {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-ev-view__stage {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: color-mix(in srgb, var(--m3-color-scrim) 88%, transparent);
    touch-action: none;
}

.m3-ev-view__img {
    max-width: 92vw;
    max-height: 82vh;
    object-fit: contain;
    will-change: transform;
    user-select: none;
}

.m3-ev-view__empty {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    color: #fff;
    opacity: 0.6;
}

.m3-ev-view__hint {
    position: absolute;
    bottom: var(--m3-space-4);
    left: 50%;
    transform: translateX(-50%);
    margin: 0;
    padding: var(--m3-space-1) var(--m3-space-2-5);
    border-radius: var(--m3-radius-full);
    background: color-mix(in srgb, var(--m3-color-scrim) 55%, transparent);
    color: #fff;
    font-size: var(--m3-type-label-small-size);
    pointer-events: none;
}

.m3-ev-view__scale {
    min-width: 3.5rem;
    text-align: center;
    padding: var(--m3-space-1) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: color-mix(in srgb, var(--m3-color-inverse-on-surface) 10%, transparent);
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    font-variant-numeric: tabular-nums;
}

.m3-ev-view__sep {
    width: 1px;
    height: var(--m3-size-icon);
    background: color-mix(in srgb, var(--m3-color-inverse-on-surface) 16%, transparent);
}

.m3-ev-view__reset {
    min-height: var(--m3-size-touch-target);
    padding: var(--m3-space-1) var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-inverse-on-surface);
    color: var(--m3-color-inverse-surface);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    cursor: pointer;
}

.m3-ev-view__reset:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.m3-ev-view__reset:focus-visible {
    outline: 2px solid var(--m3-color-inverse-primary);
    outline-offset: 2px;
}

/* Transisi viewer */
.m3-ev-view-enter-active {
    transition: opacity var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-ev-view-leave-active {
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-ev-view-enter-from,
.m3-ev-view-leave-to {
    opacity: 0;
}
</style>
