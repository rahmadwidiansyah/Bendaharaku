<script setup>
/**
 * M3DashboardSkeleton — komposisi dashboard M3.
 * Mobile: aset lalu histori. Desktop: aset 1/3 sticky + histori 2/3.
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage
        class="m3-dash-skel"
        width="content"
        gap="md"
        title
        title-actions
        :aria-label="t('common.loading')"
    >
        <div class="m3-dash-skel__layout">
            <div class="m3-dash-skel__assets">
                <M3Card variant="elevated" padding="md">
                    <div class="m3-dash-skel__card-head">
                        <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                    </div>
                    <div class="m3-dash-skel__hero">
                        <M3SkeletonBlock width="5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="11rem" height="var(--m3-type-headline-medium-size)" radius="var(--m3-radius-sm)" />
                    </div>
                    <div class="m3-dash-skel__wallet-list">
                        <div v-for="i in 3" :key="i" class="m3-dash-skel__row">
                            <M3SkeletonBlock circle width="var(--m3-size-icon)" height="var(--m3-size-icon)" />
                            <M3SkeletonBlock width="45%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                            <M3SkeletonBlock class="m3-dash-skel__row-end" width="4rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        </div>
                    </div>
                    <M3SkeletonBlock width="7rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </M3Card>

                <div class="m3-dash-skel__cashflow">
                    <M3Card v-for="i in 2" :key="i" variant="filled" padding="sm">
                        <M3SkeletonBlock circle width="var(--m3-space-8)" height="var(--m3-space-8)" />
                        <M3SkeletonBlock width="55%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="80%" height="var(--m3-type-title-large-size)" radius="var(--m3-radius-sm)" />
                    </M3Card>
                </div>

                <M3Card variant="outlined" padding="sm">
                    <M3SkeletonBlock width="7rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <div class="m3-dash-skel__debt-list">
                        <div v-for="i in 2" :key="i" class="m3-dash-skel__debt">
                            <div class="m3-dash-skel__debt-main">
                                <M3SkeletonBlock width="70%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                                <M3SkeletonBlock width="4rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                            </div>
                            <M3SkeletonBlock width="4.5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        </div>
                    </div>
                </M3Card>
            </div>

            <div class="m3-dash-skel__history">
                <div class="m3-dash-skel__history-head">
                    <M3SkeletonBlock width="9rem" height="var(--m3-space-4)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="100%" height="var(--m3-size-touch-target)" radius="var(--m3-radius-full)" />
                </div>

                <div class="m3-dash-skel__period">
                    <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                    <div class="m3-dash-skel__period-label">
                        <M3SkeletonBlock width="3.5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    </div>
                    <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                    <M3SkeletonBlock class="m3-dash-skel__filter" width="5rem" height="2.25rem" radius="var(--m3-radius-full)" />
                </div>

                <div class="m3-dash-skel__day">
                    <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>

                <div class="m3-dash-skel__trx-list">
                    <div v-for="i in 6" :key="i" class="m3-dash-skel__trx">
                        <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                        <div class="m3-dash-skel__trx-main">
                            <M3SkeletonBlock width="55%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                            <M3SkeletonBlock width="35%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        </div>
                        <div class="m3-dash-skel__trx-side">
                            <M3SkeletonBlock width="4.5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                            <M3SkeletonBlock width="2.5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-dash-skel__layout {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-width: 0;
}

.m3-dash-skel__assets,
.m3-dash-skel__history {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-dash-skel__card-head,
.m3-dash-skel__debt,
.m3-dash-skel__period,
.m3-dash-skel__day,
.m3-dash-skel__row,
.m3-dash-skel__trx {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-dash-skel__card-head {
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-dash-skel__hero {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-3);
}

.m3-dash-skel__wallet-list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    margin: var(--m3-space-4) 0;
}

.m3-dash-skel__row-end {
    margin-left: auto;
}

.m3-dash-skel__cashflow {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-3);
}

.m3-dash-skel__cashflow :deep(.m3-card) {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-dash-skel__cashflow :deep(.m3-skel-block) {
    background: var(--m3-color-surface-container);
}

.m3-dash-skel__debt-list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-3);
}

.m3-dash-skel__debt-main {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-dash-skel__history-head {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-dash-skel__period {
    gap: var(--m3-space-1);
}

.m3-dash-skel__period-label {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-dash-skel__filter {
    margin-left: auto;
}

.m3-dash-skel__day {
    justify-content: space-between;
    padding: var(--m3-space-2) var(--m3-space-1);
}

.m3-dash-skel__trx-list {
    display: flex;
    flex-direction: column;
}

.m3-dash-skel__trx {
    padding: var(--m3-space-3);
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-dash-skel__trx:last-child {
    border-bottom: none;
}

.m3-dash-skel__trx-main {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-dash-skel__trx-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--m3-space-1);
}

@media (min-width: 1024px) {
    .m3-dash-skel {
        padding: var(--m3-space-5);
    }

    .m3-dash-skel__layout {
        flex-direction: row;
        align-items: flex-start;
        gap: var(--m3-space-8);
    }

    .m3-dash-skel__assets {
        position: sticky;
        top: calc(var(--m3-size-appbar) + var(--m3-space-4));
        width: 33.333%;
    }

    .m3-dash-skel__history {
        width: 66.667%;
    }
}
</style>
