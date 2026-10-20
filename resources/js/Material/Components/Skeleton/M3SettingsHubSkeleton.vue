<script setup>
/**
 * M3SettingsHubSkeleton — halaman indeks Settings M3.
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage width="list" gap="xl" title subtitle :aria-label="t('common.loading')">
        <M3Card variant="outlined">
            <div class="m3-settings-hub-skel__user">
                <M3SkeletonBlock circle width="var(--m3-size-touch-target-lg)" height="var(--m3-size-touch-target-lg)" />
                <div class="m3-settings-hub-skel__user-body">
                    <M3SkeletonBlock width="9rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="12rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <div class="m3-settings-hub-skel__badges">
                        <M3SkeletonBlock v-for="i in 2" :key="i" width="4rem" height="var(--m3-space-4)" radius="var(--m3-radius-full)" />
                    </div>
                </div>
            </div>
        </M3Card>

        <section v-for="section in 3" :key="section" class="m3-settings-hub-skel__section">
            <M3SkeletonBlock class="m3-settings-hub-skel__section-title" width="7rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
            <M3Card variant="outlined" padding="none">
                <div
                    v-for="row in 4"
                    :key="row"
                    class="m3-settings-hub-skel__row"
                    :class="{ 'm3-settings-hub-skel__row--divider': row < 4 }"
                >
                    <M3SkeletonBlock circle width="var(--m3-space-8)" height="var(--m3-space-8)" />
                    <M3SkeletonBlock width="45%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock class="m3-settings-hub-skel__row-end" width="var(--m3-size-icon-3xs)" height="var(--m3-size-icon-3xs)" radius="var(--m3-radius-full)" />
                </div>
            </M3Card>
        </section>

        <div class="m3-settings-hub-skel__logout">
            <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
            <M3SkeletonBlock width="7rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
        </div>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-settings-hub-skel__user,
.m3-settings-hub-skel__row,
.m3-settings-hub-skel__logout {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-settings-hub-skel__user-body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-settings-hub-skel__badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-1);
}

.m3-settings-hub-skel__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-settings-hub-skel__section-title {
    margin-left: var(--m3-space-4);
}

.m3-settings-hub-skel__row {
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-settings-hub-skel__row--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-settings-hub-skel__row-end {
    margin-left: auto;
}

.m3-settings-hub-skel__logout {
    padding: var(--m3-space-2) var(--m3-space-4);
}
</style>
