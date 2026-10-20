<script setup>
/**
 * M3CircularProgress — indikator progres melingkar M3 (indeterminate).
 * Memakai `currentColor` agar mengikuti warna konteks (tombol, overlay,
 * chip). Dekoratif: status loading tetap diumumkan oleh parent
 * (`role="status"`) atau label tombol.
 */
defineProps({
    size: {
        type: String,
        default: 'sm',
        validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v),
    },
})
</script>

<template>
    <span class="m3-circular" :class="`m3-circular--${size}`" aria-hidden="true">
        <svg class="m3-circular__svg" viewBox="0 0 48 48" fill="none">
            <circle class="m3-circular__track" cx="24" cy="24" r="20" />
            <circle class="m3-circular__indicator" cx="24" cy="24" r="20" />
        </svg>
    </span>
</template>

<style scoped>
.m3-circular {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    vertical-align: middle;
}

.m3-circular--xs {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
}

.m3-circular--sm {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-circular--md {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-circular--lg {
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
}

.m3-circular__svg {
    width: 100%;
    height: 100%;
    transform-origin: 50% 50%;
    animation: m3-circular-rotate 1.4s linear infinite;
}

.m3-circular__track {
    stroke: currentColor;
    stroke-width: 4.5;
    opacity: 0.2;
}

.m3-circular__indicator {
    stroke: currentColor;
    stroke-width: 4.5;
    stroke-linecap: round;
    stroke-dasharray: 1 126;
    stroke-dashoffset: 0;
    animation: m3-circular-dash 1.4s ease-in-out infinite;
}

@keyframes m3-circular-rotate {
    to {
        transform: rotate(360deg);
    }
}

@keyframes m3-circular-dash {
    0% {
        stroke-dasharray: 1 126;
        stroke-dashoffset: 0;
    }

    50% {
        stroke-dasharray: 90 126;
        stroke-dashoffset: -34;
    }

    100% {
        stroke-dasharray: 90 126;
        stroke-dashoffset: -124;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-circular__svg {
        animation: none;
    }

    .m3-circular__indicator {
        animation: none;
        stroke-dasharray: 30 96;
        stroke-dashoffset: 0;
    }
}
</style>
