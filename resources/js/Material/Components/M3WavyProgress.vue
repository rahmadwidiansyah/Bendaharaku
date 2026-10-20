<script setup>
/**
 * M3WavyProgress — linear progress indicator bergelombang (indeterminate)
 * khas M3 Expressive. Dipakai sebagai indikator navigasi global di layout M3.
 * Dekoratif secara visual, tetapi tetap progresbar yang dapat dibaca
 * screen reader melalui `ariaLabel`.
 */
defineProps({
    active: { type: Boolean, default: true },
    belowAppBar: { type: Boolean, default: false },
    ariaLabel: { type: String, default: '' },
})
</script>

<template>
    <div
        v-if="active"
        class="m3-wavy"
        :class="{ 'm3-wavy--below-appbar': belowAppBar }"
        role="progressbar"
        :aria-label="ariaLabel || undefined"
    >
        <svg class="m3-wavy__svg" viewBox="0 0 100 8" preserveAspectRatio="none" aria-hidden="true">
            <path
                class="m3-wavy__path"
                d="M0 5 Q 3.125 0.5 6.25 5 T 12.5 5 T 18.75 5 T 25 5 T 31.25 5 T 37.5 5 T 43.75 5 T 50 5 T 56.25 5 T 62.5 5 T 68.75 5 T 75 5 T 81.25 5 T 87.5 5 T 93.75 5 T 100 5"
                vector-effect="non-scaling-stroke"
            />
        </svg>
    </div>
</template>

<style scoped>
.m3-wavy {
    position: fixed;
    top: env(safe-area-inset-top, 0px);
    right: 0;
    left: 0;
    z-index: 60;
    height: var(--m3-space-2);
    overflow: hidden;
    background: var(--m3-color-surface-container-highest);
    pointer-events: none;
}

.m3-wavy--below-appbar {
    top: var(--m3-size-appbar);
}

.m3-wavy__svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 200%;
    height: 100%;
    animation: m3-wavy-slide 1.1s linear infinite;
}

.m3-wavy__path {
    fill: none;
    stroke: var(--m3-color-primary);
    stroke-width: 2.5;
    stroke-linecap: round;
}

@keyframes m3-wavy-slide {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(-50%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-wavy__svg {
        animation: none;
    }
}
</style>
