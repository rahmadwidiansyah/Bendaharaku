<script setup>
/**
 * M3AnalyticsSkeleton — komposisi halaman Analytics M3.
 * Dimensi kartu chart mengikuti container final (120px / 220px / 12rem)
 * agar tidak ada layout shift.
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage
        width="content"
        gap="md"
        title
        title-actions
        :aria-label="t('common.loading')"
    >
        <div class="m3-ana-skel__datebar">
            <M3SkeletonBlock width="11rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
            <M3SkeletonBlock width="5.5rem" height="2.25rem" radius="var(--m3-radius-full)" />
        </div>

        <div class="m3-ana-skel__cashflow">
            <M3Card v-for="i in 2" :key="i" class="m3-ana-skel__filled" variant="filled" padding="sm">
                <M3SkeletonBlock circle width="var(--m3-space-8)" height="var(--m3-space-8)" />
                <M3SkeletonBlock width="55%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="80%" height="var(--m3-type-title-large-size)" radius="var(--m3-radius-sm)" />
            </M3Card>
        </div>

        <M3Card class="m3-ana-skel__filled" variant="filled" padding="md">
            <div class="m3-ana-skel__card-head">
                <div class="m3-ana-skel__titles">
                    <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="13rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                </div>
                <M3SkeletonBlock width="7rem" height="var(--m3-type-title-large-size)" radius="var(--m3-radius-sm)" />
            </div>
            <M3SkeletonBlock class="m3-ana-skel__chart-small" width="100%" height="120px" radius="var(--m3-radius-md)" />
        </M3Card>

        <M3Card class="m3-ana-skel__filled" variant="filled" padding="md">
            <div class="m3-ana-skel__card-head">
                <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="8rem" height="2rem" radius="var(--m3-radius-full)" />
            </div>
            <div class="m3-ana-skel__legend">
                <M3SkeletonBlock v-for="i in 4" :key="i" width="4.5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
            </div>
            <M3SkeletonBlock width="100%" height="220px" radius="var(--m3-radius-md)" />
        </M3Card>

        <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
        <M3SkeletonBlock width="100%" height="2.5rem" radius="var(--m3-radius-full)" />

        <M3Card class="m3-ana-skel__filled" variant="filled" padding="md">
            <M3SkeletonBlock class="m3-ana-skel__doughnut" circle width="12rem" height="12rem" />
            <div class="m3-ana-skel__cat-list">
                <div v-for="i in 5" :key="i" class="m3-ana-skel__cat">
                    <M3SkeletonBlock circle width="var(--m3-size-icon)" height="var(--m3-size-icon)" />
                    <div class="m3-ana-skel__cat-main">
                        <M3SkeletonBlock width="45%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="20%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    </div>
                    <M3SkeletonBlock width="4.5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>
            </div>
        </M3Card>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-ana-skel__datebar,
.m3-ana-skel__card-head,
.m3-ana-skel__legend,
.m3-ana-skel__cat {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-ana-skel__datebar,
.m3-ana-skel__card-head {
    justify-content: space-between;
}

.m3-ana-skel__titles,
.m3-ana-skel__cat-main {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-ana-skel__cashflow {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-3);
}

.m3-ana-skel__cashflow :deep(.m3-card) {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-ana-skel__filled :deep(.m3-skel-block) {
    background: var(--m3-color-surface-container);
}

.m3-ana-skel__chart-small {
    margin-top: var(--m3-space-3);
}

.m3-ana-skel__legend {
    flex-wrap: wrap;
    gap: var(--m3-space-2) var(--m3-space-4);
    margin: var(--m3-space-3) 0;
}

.m3-ana-skel__doughnut {
    margin: 0 auto var(--m3-space-4);
}

.m3-ana-skel__cat-list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-ana-skel__cat-main {
    flex: 1;
}

@media (max-width: 22rem) {
    .m3-ana-skel__cashflow {
        grid-template-columns: 1fr;
    }
}
</style>
