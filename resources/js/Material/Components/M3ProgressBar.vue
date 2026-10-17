<script setup>
/**
 * M3ProgressBar — linear progress indicator M3 Expressive.
 * Determinate: track surface-container-highest, active indicator flat
 * primary (expense saat over-budget), stop indicator 4dp di ujung track
 * (lihat references/components/progress-indicators.md). Ukuran:
 * md = 8dp (summary & grup), sm = 4dp (baris anggota).
 */
import { computed } from 'vue'

const props = defineProps({
    value: { type: Number, default: 0 }, // 0–100
    over: { type: Boolean, default: false },
    size: { type: String, default: 'md' }, // sm | md
    ariaLabel: { type: String, default: '' },
})

const clamped = computed(() => Math.max(0, Math.min(100, Number(props.value) || 0)))
</script>

<template>
    <div
        class="m3-progress"
        :class="[`m3-progress--${size}`, over ? 'm3-progress--over' : '']"
        role="progressbar"
        :aria-valuenow="Math.round(clamped)"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="ariaLabel || undefined"
    >
        <div class="m3-progress__track">
            <div class="m3-progress__indicator" :style="{ width: clamped + '%' }"></div>
        </div>
    </div>
</template>

<style scoped>
.m3-progress {
    width: 100%;
}

.m3-progress__track {
    position: relative;
    width: 100%;
    height: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    overflow: hidden;
}

.m3-progress--sm .m3-progress__track {
    height: var(--m3-space-1);
}

/* Stop indicator 4dp di ujung track — tetap terlihat di semua nilai */
.m3-progress__track::after {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    z-index: 1;
    width: var(--m3-space-1);
    height: var(--m3-space-1);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
    transform: translateY(-50%);
    transition: background-color var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-progress__indicator {
    height: 100%;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
    transition:
        width var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects),
        background-color var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

.m3-progress--over .m3-progress__track::after,
.m3-progress--over .m3-progress__indicator {
    background: var(--m3-color-expense);
}

@media (prefers-reduced-motion: reduce) {
    .m3-progress__indicator,
    .m3-progress__track::after {
        transition: none;
    }
}
</style>
