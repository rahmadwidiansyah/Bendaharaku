<script setup>
/**
 * M3CategoriesSkeleton — vault kategori M3.
 * Mobile: grid tile. Desktop: daftar per grup dalam M3Card outlined.
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage width="content" gap="lg" title subtitle :aria-label="t('common.loading')">
        <M3SkeletonBlock
            class="m3-cat-skel__mobile-cta"
            width="100%"
            height="var(--m3-size-touch-target-lg)"
            radius="var(--m3-radius-full)"
        />

        <div class="m3-cat-skel__toolbar">
            <M3SkeletonBlock width="8rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
            <M3SkeletonBlock width="7rem" height="var(--m3-size-touch-target)" radius="var(--m3-radius-full)" />
        </div>

        <section v-for="group in 3" :key="group" class="m3-cat-skel__group">
            <header class="m3-cat-skel__group-head">
                <M3SkeletonBlock circle width="var(--m3-space-8)" height="var(--m3-space-8)" />
                <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock class="m3-cat-skel__count" width="1.75rem" height="var(--m3-space-4)" radius="var(--m3-radius-full)" />
            </header>

            <div class="m3-cat-skel__grid">
                <div v-for="tile in 6" :key="tile" class="m3-cat-skel__tile">
                    <M3SkeletonBlock circle width="var(--m3-size-icon-lg)" height="var(--m3-size-icon-lg)" />
                    <M3SkeletonBlock width="80%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>
            </div>

            <M3Card variant="outlined" padding="none" class="m3-cat-skel__list">
                <div
                    v-for="row in 4"
                    :key="row"
                    class="m3-cat-skel__row"
                    :class="{ 'm3-cat-skel__row--divider': row < 4 }"
                >
                    <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                    <M3SkeletonBlock width="40%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock class="m3-cat-skel__row-end" width="var(--m3-size-icon-3xs)" height="var(--m3-size-icon-3xs)" radius="var(--m3-radius-full)" />
                </div>
            </M3Card>
        </section>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-cat-skel__toolbar,
.m3-cat-skel__group-head,
.m3-cat-skel__row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-cat-skel__toolbar {
    display: none;
    justify-content: space-between;
    gap: var(--m3-space-3);
}

.m3-cat-skel__group {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-cat-skel__group-head {
    gap: var(--m3-space-2);
    padding: 0 var(--m3-space-1);
}

.m3-cat-skel__count {
    margin-left: auto;
}

.m3-cat-skel__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--m3-space-2);
}

.m3-cat-skel__tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    min-height: 7.5rem;
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-low);
}

.m3-cat-skel__list {
    display: none;
    overflow: hidden;
}

.m3-cat-skel__row {
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-cat-skel__row--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-cat-skel__row-end {
    margin-left: auto;
}

@media (min-width: 30rem) {
    .m3-cat-skel__grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (min-width: 48rem) {
    .m3-cat-skel__grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }
}

@media (min-width: 64rem) {
    .m3-cat-skel__mobile-cta,
    .m3-cat-skel__grid {
        display: none;
    }

    .m3-cat-skel__toolbar {
        display: flex;
    }

    .m3-cat-skel__list {
        display: flex;
        flex-direction: column;
    }

    .m3-cat-skel__group {
        gap: var(--m3-space-2);
    }
}
</style>
