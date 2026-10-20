<script setup>
/**
 * M3CategoryShowSkeleton — detail kategori M3 (hero, ringkasan periode, transaksi).
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage width="content" gap="lg" :title="false" :aria-label="t('common.loading')">
        <header class="m3-cat-show-skel__topbar">
            <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
            <M3SkeletonBlock width="9rem" height="var(--m3-type-title-large-size)" radius="var(--m3-radius-full)" />
        </header>

        <div class="m3-cat-show-skel__layout">
            <div class="m3-cat-show-skel__aside">
                <M3Card variant="filled" padding="lg">
                    <M3SkeletonBlock
                        class="m3-cat-show-skel__edit"
                        circle
                        width="var(--m3-size-touch-target)"
                        height="var(--m3-size-touch-target)"
                    />
                    <M3SkeletonBlock
                        class="m3-cat-show-skel__avatar"
                        circle
                        width="calc(var(--m3-size-touch-target-lg) * 1.5)"
                        height="calc(var(--m3-size-touch-target-lg) * 1.5)"
                    />
                    <M3SkeletonBlock width="8rem" height="var(--m3-type-headline-small-size)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="5rem" height="var(--m3-space-5)" radius="var(--m3-radius-full)" />
                </M3Card>

                <M3Card variant="elevated" padding="md">
                    <div class="m3-cat-show-skel__summary">
                        <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="10rem" height="var(--m3-type-headline-medium-size)" radius="var(--m3-radius-sm)" />
                        <M3SkeletonBlock width="8rem" height="2rem" radius="var(--m3-radius-full)" />
                    </div>
                </M3Card>
            </div>

            <section class="m3-cat-show-skel__transactions">
                <header class="m3-cat-show-skel__section-head">
                    <M3SkeletonBlock width="9rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                </header>

                <M3Card variant="outlined" padding="none">
                    <div
                        v-for="row in 6"
                        :key="row"
                        class="m3-cat-show-skel__row"
                        :class="{ 'm3-cat-show-skel__row--divider': row < 6 }"
                    >
                        <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                        <div class="m3-cat-show-skel__row-main">
                            <M3SkeletonBlock width="55%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                            <M3SkeletonBlock width="70%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        </div>
                        <div class="m3-cat-show-skel__row-side">
                            <M3SkeletonBlock width="5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                            <M3SkeletonBlock width="3.5rem" height="var(--m3-space-4)" radius="var(--m3-radius-full)" />
                        </div>
                    </div>
                </M3Card>
            </section>
        </div>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-cat-show-skel__topbar,
.m3-cat-show-skel__section-head,
.m3-cat-show-skel__row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-cat-show-skel__layout {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    min-width: 0;
}

.m3-cat-show-skel__aside {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-width: 0;
}

.m3-cat-show-skel__aside :deep(.m3-card:first-child) {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-cat-show-skel__aside :deep(.m3-card:first-child .m3-skel-block) {
    background: var(--m3-color-surface-container);
}

.m3-cat-show-skel__edit {
    position: absolute;
    top: var(--m3-space-2);
    right: var(--m3-space-2);
}

.m3-cat-show-skel__avatar {
    margin-bottom: var(--m3-space-1);
}

.m3-cat-show-skel__summary {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-cat-show-skel__transactions {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-cat-show-skel__section-head {
    justify-content: space-between;
}

.m3-cat-show-skel__row-main,
.m3-cat-show-skel__row-side {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-cat-show-skel__row-main {
    flex: 1;
}

.m3-cat-show-skel__row {
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-cat-show-skel__row--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-cat-show-skel__row-side {
    align-items: flex-end;
}

@media (min-width: 64rem) {
    .m3-cat-show-skel__topbar {
        display: none;
    }

    .m3-cat-show-skel__layout {
        display: grid;
        grid-template-columns: minmax(0, 22rem) minmax(0, 1fr);
        align-items: start;
        gap: var(--m3-space-5);
    }

    .m3-cat-show-skel__aside {
        position: sticky;
        top: var(--m3-space-4);
    }
}
</style>
