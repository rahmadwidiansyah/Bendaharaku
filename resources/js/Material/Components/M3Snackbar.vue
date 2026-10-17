<script setup>
/**
 * M3Snackbar — Material Design 3 Expressive snackbar untuk feedback transien.
 * Per references/components/feedback.md: container inverse-surface,
 * elevation level 3, corner extra-small, tinggi minimum 48dp, satu per waktu.
 * Varian success/error dibedakan lewat aksen semantik (badge + ikon) dengan
 * token income/expense yang di-mix ke inverse-on-surface agar kontras di
 * kedua mode. Logika identik dengan Toast Default (useToast + flash).
 */
import { watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

const page = usePage()
const { message, toastType, visible, showToast } = useToast()

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        showToast(flash.success, 'success')
    }
    if (flash?.error) {
        showToast(flash.error, 'error')
    }
}, { deep: true, immediate: true })

onMounted(() => {
    if (page.props.flash?.success) {
        showToast(page.props.flash.success, 'success')
    }
    if (page.props.flash?.error) {
        showToast(page.props.flash.error, 'error')
    }
})
</script>

<template>
    <div class="m3-snackbar-live" role="status" aria-live="polite" aria-atomic="true">
        <transition name="m3-snackbar">
            <div v-if="visible" class="m3-snackbar" :class="`m3-snackbar--${toastType}`">
                <span class="m3-snackbar__badge" aria-hidden="true">
                    <svg v-if="toastType === 'success'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
                <p class="m3-snackbar__text">{{ message }}</p>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.m3-snackbar-live {
    position: fixed;
    top: 4rem;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    z-index: 100;
    pointer-events: none;
}

.m3-snackbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-xs);
    background: var(--m3-color-inverse-surface);
    color: var(--m3-color-inverse-on-surface);
    box-shadow: var(--m3-elevation-3);
    pointer-events: auto;
}

.m3-snackbar__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-icon-xl);
    height: var(--m3-size-icon-xl);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    color: var(--m3-color-inverse-surface);
}

.m3-snackbar--success .m3-snackbar__badge {
    background: color-mix(in srgb, var(--m3-color-income) 18%, var(--m3-color-inverse-on-surface));
}

.m3-snackbar--error .m3-snackbar__badge {
    background: color-mix(in srgb, var(--m3-color-expense) 18%, var(--m3-color-inverse-on-surface));
}

.m3-snackbar__badge svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-snackbar__text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
    letter-spacing: var(--m3-type-body-medium-tracking);
}

.m3-snackbar-enter-active,
.m3-snackbar-leave-active {
    transition:
        opacity var(--m3-motion-default-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-snackbar-enter-from,
.m3-snackbar-leave-to {
    opacity: 0;
    transform: translateY(calc(-1 * var(--m3-space-3)));
}

@media (min-width: 640px) {
    .m3-snackbar-live {
        max-width: 28rem;
    }
}

@media (min-width: 1024px) {
    .m3-snackbar-live {
        top: 1.25rem;
        max-width: 32rem;
    }
}
</style>
