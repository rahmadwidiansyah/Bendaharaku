<script setup>
/**
 * M3MobileWidgetSkeleton — placeholder mobile untuk widget dashboard yang
 * hanya ditampilkan pada layout desktop (arus kas, budget, pemakaian AI).
 *
 * Sengaja tidak memakai M3SkeletonPage karena komponen ini dirender inline
 * di antara widget lain; animasi pulse didefinisikan lokal agar tetap
 * sinkron dengan skeleton M3 lain dan menghormati reduced-motion.
 */
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'

defineProps({
    variant: {
        type: String,
        required: true,
        validator: (v) => ['cashflow', 'budget', 'ai'].includes(v),
    },
})
</script>

<template>
    <M3Card
        variant="outlined"
        padding="md"
        class="m3-widget-skel"
        :data-variant="variant"
        aria-hidden="true"
    >
        <!-- Arus kas: judul + area chart + legenda -->
        <template v-if="variant === 'cashflow'">
            <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
            <M3SkeletonBlock
                class="m3-widget-skel__chart"
                height="9rem"
                radius="var(--m3-radius-md)"
            />
            <div class="m3-widget-skel__legend">
                <M3SkeletonBlock width="4rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="4rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
            </div>
        </template>

        <!-- Budget: judul + persentase + total + progress + 3 item -->
        <template v-else-if="variant === 'budget'">
            <div class="m3-widget-skel__head">
                <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="3rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
            </div>
            <M3SkeletonBlock
                class="m3-widget-skel__totals"
                width="70%"
                height="var(--m3-space-3)"
                radius="var(--m3-radius-full)"
            />
            <M3SkeletonBlock height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
            <div
                v-for="i in 3"
                :key="i"
                class="m3-widget-skel__budget-item"
            >
                <M3SkeletonBlock width="45%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock height="var(--m3-space-1-5)" radius="var(--m3-radius-full)" />
            </div>
        </template>

        <!-- Pemakaian AI: ikon + judul + statistik -->
        <div v-else class="m3-widget-skel__ai-row">
            <M3SkeletonBlock
                circle
                width="var(--m3-size-touch-target)"
                height="var(--m3-size-touch-target)"
            />
            <div class="m3-widget-skel__ai-body">
                <M3SkeletonBlock width="60%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="35%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
            </div>
        </div>
    </M3Card>
</template>

<style scoped>
.m3-widget-skel {
    animation: m3-widget-skel-pulse 1.6s ease-in-out infinite;
}

.m3-widget-skel__chart {
    margin-top: var(--m3-space-3);
}

.m3-widget-skel__legend {
    display: flex;
    justify-content: center;
    gap: var(--m3-space-4);
    margin-top: var(--m3-space-2);
}

.m3-widget-skel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    margin-bottom: var(--m3-space-2);
}

.m3-widget-skel__totals {
    margin-bottom: var(--m3-space-2);
}

.m3-widget-skel__budget-item {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-2);
}

.m3-widget-skel__ai-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-widget-skel__ai-body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

@keyframes m3-widget-skel-pulse {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.55;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-widget-skel {
        animation: none;
    }
}
</style>
