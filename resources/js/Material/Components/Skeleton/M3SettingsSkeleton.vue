<script setup>
/**
 * M3SettingsSkeleton — subhalaman Settings M3 (form/daftar pengaturan).
 * Struktur generik mengikuti M3SettingsPage: back + judul + kartu bertumpuk.
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage width="content" gap="xl" title subtitle back :aria-label="t('common.loading')">
        <M3Card variant="outlined" padding="md">
            <div class="m3-settings-skel__head">
                <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="12rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
            </div>
            <div class="m3-settings-skel__fields">
                <div v-for="i in 2" :key="i" class="m3-settings-skel__field">
                    <M3SkeletonBlock width="5rem" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="100%" height="var(--m3-size-touch-target-lg)" radius="var(--m3-radius-md)" />
                </div>
            </div>
        </M3Card>

        <M3Card variant="outlined" padding="none">
            <div
                v-for="row in 3"
                :key="row"
                class="m3-settings-skel__row"
                :class="{ 'm3-settings-skel__row--divider': row < 3 }"
            >
                <div class="m3-settings-skel__row-main">
                    <M3SkeletonBlock width="40%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="65%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                </div>
                <M3SkeletonBlock width="var(--m3-space-12)" height="var(--m3-space-6)" radius="var(--m3-radius-full)" />
            </div>
        </M3Card>

        <div class="m3-settings-skel__actions">
            <M3SkeletonBlock width="100%" height="var(--m3-size-touch-target-lg)" radius="var(--m3-radius-full)" />
        </div>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-settings-skel__head,
.m3-settings-skel__row,
.m3-settings-skel__actions {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-settings-skel__head {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--m3-space-1);
}

.m3-settings-skel__fields {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-4);
}

.m3-settings-skel__field {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-settings-skel__row {
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-settings-skel__row--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-settings-skel__row-main {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

</style>
