<script setup>
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage width="content" gap="lg" title subtitle :aria-label="t('common.loading')">
        <div class="m3-loans-skel__tabs">
            <M3SkeletonBlock
                v-for="tab in 2"
                :key="tab"
                width="100%"
                height="var(--m3-size-touch-target-lg)"
                radius="var(--m3-radius-full)"
            />
        </div>

        <div class="m3-loans-skel__layout">
            <M3Card variant="elevated" padding="lg" class="m3-loans-skel__hero">
                <div class="m3-loans-skel__hero-head">
                    <M3SkeletonBlock
                        circle
                        width="var(--m3-size-touch-target-lg)"
                        height="var(--m3-size-touch-target-lg)"
                    />
                    <M3SkeletonBlock
                        circle
                        width="var(--m3-size-touch-target)"
                        height="var(--m3-size-touch-target)"
                    />
                </div>
                <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock
                    width="14rem"
                    height="var(--m3-type-headline-medium-size)"
                    radius="var(--m3-radius-sm)"
                />
                <M3SkeletonBlock width="9rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />

                <div class="m3-loans-skel__due">
                    <M3SkeletonBlock
                        circle
                        width="var(--m3-size-touch-target)"
                        height="var(--m3-size-touch-target)"
                    />
                    <div class="m3-loans-skel__due-copy">
                        <M3SkeletonBlock width="6rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    </div>
                </div>
            </M3Card>

            <section class="m3-loans-skel__list-section">
                <header class="m3-loans-skel__list-head">
                    <M3SkeletonBlock circle width="var(--m3-space-2)" height="var(--m3-space-2)" />
                    <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock
                        class="m3-loans-skel__list-count"
                        width="2rem"
                        height="var(--m3-space-4)"
                        radius="var(--m3-radius-full)"
                    />
                </header>

                <M3Card variant="outlined" padding="none" class="m3-loans-skel__list">
                    <div
                        v-for="row in 4"
                        :key="row"
                        class="m3-loans-skel__row"
                        :class="{ 'm3-loans-skel__row--divider': row < 4 }"
                    >
                        <M3SkeletonBlock
                            circle
                            width="var(--m3-size-touch-target)"
                            height="var(--m3-size-touch-target)"
                        />
                        <div class="m3-loans-skel__row-main">
                            <M3SkeletonBlock width="45%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                            <M3SkeletonBlock width="65%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        </div>
                        <div class="m3-loans-skel__row-side">
                            <M3SkeletonBlock width="5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                            <M3SkeletonBlock width="3.5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                        </div>
                    </div>
                </M3Card>
            </section>
        </div>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-loans-skel__tabs {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-1);
    padding: var(--m3-space-1);
}

.m3-loans-skel__layout {
    display: grid;
    gap: var(--m3-space-5);
    min-width: 0;
}

.m3-loans-skel__hero {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-loans-skel__hero-head,
.m3-loans-skel__list-head,
.m3-loans-skel__row,
.m3-loans-skel__due {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-loans-skel__hero-head {
    justify-content: space-between;
    margin-bottom: var(--m3-space-2);
}

.m3-loans-skel__due {
    margin-top: var(--m3-space-3);
    padding-top: var(--m3-space-3);
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-loans-skel__due-copy,
.m3-loans-skel__row-main,
.m3-loans-skel__row-side {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-loans-skel__row-main {
    flex: 1;
}

.m3-loans-skel__row-side {
    align-items: flex-end;
}

.m3-loans-skel__list-head {
    padding: 0 var(--m3-space-1);
}

.m3-loans-skel__list-count {
    margin-left: auto;
}

.m3-loans-skel__list {
    overflow: hidden;
}

.m3-loans-skel__row {
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-loans-skel__row--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

@media (min-width: 64rem) {
    .m3-loans-skel__layout {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
        gap: var(--m3-space-6);
        align-items: start;
    }
}

@media (max-width: 22.4375rem) {
    .m3-loans-skel__tabs {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
