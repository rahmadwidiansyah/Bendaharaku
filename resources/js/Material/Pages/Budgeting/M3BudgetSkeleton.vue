<script setup>
/**
 * M3BudgetSkeleton — skeleton komposisi halaman Budgeting M3.
 * Dimensi blok mengikuti layout final (summary card + grid kartu grup)
 * supaya tidak ada layout shift saat data selesai dimuat.
 */
import M3Card from '@/Material/Components/M3Card.vue'
import M3ProgressBar from '@/Material/Components/M3ProgressBar.vue'
</script>

<template>
    <div class="m3-bud-skel" role="status" aria-label="Memuat anggaran">
        <M3Card variant="outlined" padding="lg">
            <div class="m3-bud-skel__head">
                <div class="m3-bud-skel__block m3-bud-skel__avatar"></div>
                <div class="m3-bud-skel__head-text">
                    <div class="m3-bud-skel__block m3-bud-skel__line-lg"></div>
                    <div class="m3-bud-skel__block m3-bud-skel__line-sm"></div>
                </div>
            </div>
            <div class="m3-bud-skel__block m3-bud-skel__hero"></div>
            <div class="m3-bud-skel__stats">
                <div v-for="i in 2" :key="i" class="m3-bud-skel__block m3-bud-skel__stat"></div>
            </div>
            <div class="m3-bud-skel__bar">
                <M3ProgressBar :value="0" size="md" />
            </div>
        </M3Card>

        <div class="m3-bud-skel__grid">
            <M3Card v-for="i in 3" :key="i" variant="outlined" padding="sm">
                <div class="m3-bud-skel__group-head">
                    <div class="m3-bud-skel__block m3-bud-skel__group-icon"></div>
                    <div class="m3-bud-skel__head-text">
                        <div class="m3-bud-skel__block m3-bud-skel__line-md"></div>
                        <div class="m3-bud-skel__block m3-bud-skel__line-xs"></div>
                    </div>
                </div>
                <div class="m3-bud-skel__group-bar">
                    <M3ProgressBar :value="0" size="md" />
                    <div class="m3-bud-skel__block m3-bud-skel__pct"></div>
                </div>
                <div class="m3-bud-skel__group-meta">
                    <div class="m3-bud-skel__block m3-bud-skel__meta"></div>
                    <div class="m3-bud-skel__block m3-bud-skel__meta"></div>
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

.m3-bud-skel__block {
    border-radius: var(--m3-radius-sm);
    background: var(--m3-color-surface-container-highest);
}

.m3-bud-skel__head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-bud-skel__avatar {
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-bud-skel__head-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1-5);
}

.m3-bud-skel__line-lg {
    width: 60%;
    height: 0.875rem;
    border-radius: var(--m3-radius-full);
}

.m3-bud-skel__line-md {
    width: 45%;
    height: 0.75rem;
    border-radius: var(--m3-radius-full);
}

.m3-bud-skel__line-sm {
    width: 70%;
    height: 0.625rem;
    border-radius: var(--m3-radius-full);
}

.m3-bud-skel__line-xs {
    width: 30%;
    height: 0.625rem;
    border-radius: var(--m3-radius-full);
}

.m3-bud-skel__hero {
    height: 6rem;
    margin-top: var(--m3-space-4);
    border-radius: var(--m3-radius-md);
}

.m3-bud-skel__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-3);
}

.m3-bud-skel__stat {
    height: 3.375rem;
    border-radius: var(--m3-radius-md);
}

.m3-bud-skel__bar {
    margin-top: var(--m3-space-3);
}

.m3-bud-skel__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--m3-space-3);
}

.m3-bud-skel__group-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-bud-skel__group-icon {
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-bud-skel__group-bar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-2);
}

.m3-bud-skel__group-bar :deep(.m3-progress) {
    flex: 1;
    min-width: 0;
}

.m3-bud-skel__pct {
    width: 2rem;
    height: 1.25rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-bud-skel__group-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1) var(--m3-space-3);
    margin-top: var(--m3-space-2);
}

.m3-bud-skel__meta {
    width: 5.5rem;
    height: 1rem;
    border-radius: var(--m3-radius-full);
}

@media (min-width: 640px) {
    .m3-bud-skel__hero {
        height: 6.25rem;
    }

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
