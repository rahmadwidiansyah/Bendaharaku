<script setup>
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage width="content" gap="lg" title subtitle :aria-label="t('common.loading')">
        <M3Card variant="outlined" padding="md" class="m3-tx-skel__filters">
            <div class="m3-tx-skel__search">
                <M3SkeletonBlock height="var(--m3-size-text-field)" radius="var(--m3-radius-xs)" />
                <M3SkeletonBlock
                    circle
                    width="var(--m3-size-touch-target)"
                    height="var(--m3-size-touch-target)"
                />
            </div>
            <M3SkeletonBlock
                width="12rem"
                height="var(--m3-size-touch-target)"
                radius="var(--m3-radius-full)"
            />
            <div class="m3-tx-skel__chips">
                <M3SkeletonBlock
                    v-for="chip in 4"
                    :key="chip"
                    width="6rem"
                    height="var(--m3-size-touch-target)"
                    radius="var(--m3-radius-full)"
                />
            </div>
        </M3Card>

        <section v-for="group in 2" :key="group" class="m3-tx-skel__group">
            <div class="m3-tx-skel__group-head">
                <M3SkeletonBlock width="7rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
            </div>

            <M3Card variant="outlined" padding="none" class="m3-tx-skel__list">
                <div
                    v-for="row in 3"
                    :key="row"
                    class="m3-tx-skel__row"
                    :class="{ 'm3-tx-skel__row--divider': row < 3 }"
                >
                    <M3SkeletonBlock
                        circle
                        width="var(--m3-size-touch-target)"
                        height="var(--m3-size-touch-target)"
                    />
                    <div class="m3-tx-skel__row-main">
                        <M3SkeletonBlock width="45%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="65%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    </div>
                    <div class="m3-tx-skel__row-side">
                        <M3SkeletonBlock width="5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="3.5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    </div>
                </div>
            </M3Card>
        </section>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-tx-skel__filters {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-tx-skel__search,
.m3-tx-skel__chips,
.m3-tx-skel__group,
.m3-tx-skel__group-head,
.m3-tx-skel__row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-tx-skel__search > :first-child {
    flex: 1;
    min-width: 0;
}

.m3-tx-skel__chips {
    overflow: hidden;
}

.m3-tx-skel__group {
    flex-direction: column;
    align-items: stretch;
    gap: var(--m3-space-2);
}

.m3-tx-skel__group-head {
    justify-content: space-between;
    padding: 0 var(--m3-space-1);
}

.m3-tx-skel__row {
    gap: var(--m3-space-4);
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-tx-skel__row--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-tx-skel__row-main {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-tx-skel__row-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--m3-space-1);
}

@media (min-width: 64rem) {
    .m3-tx-skel__filters {
        flex-direction: row;
        align-items: center;
        flex-wrap: wrap;
    }

    .m3-tx-skel__search {
        flex: 1 1 18rem;
    }

    .m3-tx-skel__chips {
        flex-basis: 100%;
    }
}
</style>
