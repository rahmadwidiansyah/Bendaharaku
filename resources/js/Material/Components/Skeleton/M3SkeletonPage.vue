<script setup>
/**
 * M3SkeletonPage — kerangka halaman skeleton M3.
 * Menyamakan max-width, padding, gap, dan judul in-content mobile
 * dengan halaman final agar tidak terjadi layout shift.
 */
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'

defineProps({
    width: {
        type: String,
        default: 'content',
        validator: (v) => ['content', 'list'].includes(v),
    },
    gap: {
        type: String,
        default: 'lg',
        validator: (v) => ['md', 'lg', 'xl'].includes(v),
    },
    title: { type: Boolean, default: true },
    subtitle: { type: Boolean, default: false },
    titleActions: { type: Boolean, default: false },
    back: { type: Boolean, default: false },
    ariaLabel: { type: String, default: '' },
})
</script>

<template>
    <div
        class="m3-skel-page"
        :class="[`m3-skel-page--${width}`, `m3-skel-page--gap-${gap}`]"
        role="status"
        :aria-label="ariaLabel || undefined"
    >
        <div v-if="back" class="m3-skel-page__back">
            <M3SkeletonBlock
                circle
                width="var(--m3-size-touch-target)"
                height="var(--m3-size-touch-target)"
            />
        </div>

        <div v-if="title" class="m3-skel-page__title">
            <div class="m3-skel-page__title-text">
                <M3SkeletonBlock
                    width="10rem"
                    height="var(--m3-type-headline-small-size)"
                    radius="var(--m3-radius-full)"
                />
                <M3SkeletonBlock
                    v-if="subtitle"
                    width="7rem"
                    height="var(--m3-type-body-medium-size)"
                    radius="var(--m3-radius-full)"
                />
            </div>
            <M3SkeletonBlock
                v-if="titleActions"
                circle
                width="var(--m3-size-touch-target)"
                height="var(--m3-size-touch-target)"
            />
        </div>

        <slot />
    </div>
</template>

<style scoped>
.m3-skel-page {
    display: flex;
    flex-direction: column;
    width: 100%;
    margin: 0 auto;
    padding: var(--m3-space-4);
    animation: m3-skel-page-pulse 1.6s ease-in-out infinite;
}

.m3-skel-page--content {
    max-width: var(--m3-size-content-max);
}

.m3-skel-page--list {
    max-width: var(--m3-size-list-max);
}

.m3-skel-page--gap-md {
    gap: var(--m3-space-4);
}

.m3-skel-page--gap-lg {
    gap: var(--m3-space-5);
}

.m3-skel-page--gap-xl {
    gap: var(--m3-space-6);
}

.m3-skel-page__back {
    display: flex;
    margin-bottom: calc(-1 * var(--m3-space-3));
}

.m3-skel-page__title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    margin: calc(-1 * var(--m3-space-4)) calc(-1 * var(--m3-space-4)) 0;
    padding: var(--m3-space-4);
}

.m3-skel-page__title-text {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
}

@keyframes m3-skel-page-pulse {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.55;
    }
}

@media (min-width: 1024px) {
    .m3-skel-page__title {
        display: none;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-skel-page {
        animation: none;
    }
}
</style>
