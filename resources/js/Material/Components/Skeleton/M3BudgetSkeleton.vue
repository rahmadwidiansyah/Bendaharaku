<script setup>
/**
 * M3BudgetSkeleton — komposisi konten Budgeting M3.
 * Dipakai di dalam halaman (judul + toolbar tetap dirender halaman final),
 * jadi hanya meniru kartu ringkasan dan grid grup.
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'

const { t } = useI18n()
</script>

<template>
    <div class="m3-bud-skel" role="status" :aria-label="t('common.loading')">
        <M3Card variant="outlined" padding="lg">
            <div class="m3-bud-skel__summary-head">
                <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                <div class="m3-bud-skel__summary-text">
                    <M3SkeletonBlock width="55%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="75%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                </div>
                <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
            </div>

            <div class="m3-bud-skel__hero">
                <M3SkeletonBlock width="45%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="70%" height="var(--m3-type-title-large-size)" radius="var(--m3-radius-sm)" />
                <M3SkeletonBlock width="35%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
            </div>

            <div class="m3-bud-skel__stats">
                <div v-for="i in 2" :key="i" class="m3-bud-skel__stat">
                    <M3SkeletonBlock width="50%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="80%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>
            </div>

            <div class="m3-bud-skel__bar">
                <M3SkeletonBlock width="100%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="2.5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
            </div>
        </M3Card>

        <div class="m3-bud-skel__grid">
            <M3Card v-for="i in 3" :key="i" variant="outlined" padding="sm">
                <div class="m3-bud-skel__group-head">
                    <M3SkeletonBlock circle width="var(--m3-space-8)" height="var(--m3-space-8)" />
                    <div class="m3-bud-skel__group-text">
                        <M3SkeletonBlock width="55%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="35%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    </div>
                    <M3SkeletonBlock circle width="var(--m3-size-icon)" height="var(--m3-size-icon)" />
                </div>

                <div class="m3-bud-skel__bar m3-bud-skel__group-bar">
                    <M3SkeletonBlock width="100%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="2.25rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>

                <div class="m3-bud-skel__group-meta">
                    <M3SkeletonBlock width="4.5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="4rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="4.5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                </div>
            </M3Card>
        </div>
    </div>
</template>

<style scoped>
.m3-bud-skel {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    animation: m3-bud-skel-pulse 1.6s ease-in-out infinite;
}

.m3-bud-skel__summary-head,
.m3-bud-skel__bar,
.m3-bud-skel__group-head,
.m3-bud-skel__group-meta {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-bud-skel__summary-text,
.m3-bud-skel__group-text {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-bud-skel__hero {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-4);
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
}

.m3-bud-skel__hero :deep(.m3-skel-block) {
    background: var(--m3-color-surface-container);
}

.m3-bud-skel__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-3);
}

.m3-bud-skel__stat {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    padding: var(--m3-space-2) var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-low);
}

.m3-bud-skel__stat :deep(.m3-skel-block) {
    background: var(--m3-color-surface-container);
}

.m3-bud-skel__bar {
    margin-top: var(--m3-space-3);
}

.m3-bud-skel__bar :deep(.m3-skel-block:first-child) {
    flex: 1;
    width: auto;
    min-width: 0;
}

.m3-bud-skel__group-bar {
    margin-top: var(--m3-space-3);
}

.m3-bud-skel__group-head {
    gap: var(--m3-space-2);
}

.m3-bud-skel__group-meta {
    flex-wrap: wrap;
    gap: var(--m3-space-1) var(--m3-space-3);
    margin-top: var(--m3-space-2);
}

.m3-bud-skel__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--m3-space-3);
    min-width: 0;
}

@media (min-width: 1024px) {
    .m3-bud-skel__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1280px) {
    .m3-bud-skel__grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@keyframes m3-bud-skel-pulse {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.55;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-bud-skel {
        animation: none;
    }
}
</style>
