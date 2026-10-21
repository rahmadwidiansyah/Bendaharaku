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

            <div class="m3-bud-skel__facts">
                <div v-for="i in 2" :key="i" class="m3-bud-skel__fact">
                    <M3SkeletonBlock width="50%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="80%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>
            </div>

            <div class="m3-bud-skel__bar">
                <M3SkeletonBlock width="100%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
            </div>
        </M3Card>

        <div class="m3-bud-skel__groups">
            <M3Card v-for="i in 3" :key="i" variant="outlined" padding="none">
                <div class="m3-bud-skel__group-head">
                    <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                    <div class="m3-bud-skel__group-text">
                        <M3SkeletonBlock width="55%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="35%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    </div>
                    <M3SkeletonBlock width="4.5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock circle width="var(--m3-size-icon-xs)" height="var(--m3-size-icon-xs)" />
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
.m3-bud-skel__group-head {
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
}

.m3-bud-skel__facts {
    display: flex;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-3);
}

.m3-bud-skel__fact {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-bud-skel__bar {
    margin-top: var(--m3-space-3);
}

.m3-bud-skel__bar :deep(.m3-skel-block) {
    flex: 1;
    width: auto;
    min-width: 0;
}

.m3-bud-skel__groups {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-bud-skel__group-head {
    gap: var(--m3-space-2);
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-2) var(--m3-space-3);
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
