<script setup>
/**
 * M3WalletsSkeleton — daftar dompet M3 (hero saldo + dua grup).
 */
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SkeletonBlock from '@/Material/Components/Skeleton/M3SkeletonBlock.vue'
import M3SkeletonPage from '@/Material/Components/Skeleton/M3SkeletonPage.vue'

const { t } = useI18n()
</script>

<template>
    <M3SkeletonPage width="content" gap="lg" title :aria-label="t('common.loading')">
        <M3Card variant="elevated" padding="lg">
            <div class="m3-wallets-skel__hero-head">
                <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
            </div>
            <div class="m3-wallets-skel__hero-body">
                <M3SkeletonBlock width="7rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                <M3SkeletonBlock width="13rem" height="var(--m3-type-headline-medium-size)" radius="var(--m3-radius-sm)" />
            </div>
            <div class="m3-wallets-skel__summary">
                <div v-for="i in 2" :key="i" class="m3-wallets-skel__summary-cell">
                    <M3SkeletonBlock width="60%" height="var(--m3-space-2)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="80%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>
            </div>
        </M3Card>

        <M3SkeletonBlock width="100%" height="var(--m3-size-touch-target-lg)" radius="var(--m3-radius-full)" />

        <div class="m3-wallets-skel__groups">
            <section v-for="group in 2" :key="group" class="m3-wallets-skel__group">
                <div class="m3-wallets-skel__group-head">
                    <M3SkeletonBlock circle width="var(--m3-space-2)" height="var(--m3-space-2)" />
                    <M3SkeletonBlock width="6rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    <M3SkeletonBlock width="1.5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                </div>
                <M3Card variant="outlined" padding="none">
                    <div
                        v-for="row in 3"
                        :key="row"
                        class="m3-wallets-skel__row"
                        :class="{ 'm3-wallets-skel__row--divider': row < 3 }"
                    >
                        <M3SkeletonBlock circle width="var(--m3-size-touch-target)" height="var(--m3-size-touch-target)" />
                        <M3SkeletonBlock width="40%" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                        <M3SkeletonBlock class="m3-wallets-skel__row-end" width="5rem" height="var(--m3-space-3)" radius="var(--m3-radius-full)" />
                    </div>
                </M3Card>
            </section>
        </div>
    </M3SkeletonPage>
</template>

<style scoped>
.m3-wallets-skel__hero-head,
.m3-wallets-skel__group-head,
.m3-wallets-skel__row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-wallets-skel__hero-head,
.m3-wallets-skel__group-head {
    justify-content: space-between;
}

.m3-wallets-skel__hero-body {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-4);
}

.m3-wallets-skel__summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-4);
}

.m3-wallets-skel__summary-cell {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-wallets-skel__groups {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
}

.m3-wallets-skel__group {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-wallets-skel__group-head {
    justify-content: flex-start;
    padding: 0 var(--m3-space-1);
}

.m3-wallets-skel__group-head :deep(.m3-skel-block:last-child) {
    margin-left: auto;
}

.m3-wallets-skel__row {
    padding: var(--m3-space-3) var(--m3-space-4);
}

.m3-wallets-skel__row--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-wallets-skel__row-end {
    margin-left: auto;
}

@media (max-width: 30rem) {
    .m3-wallets-skel__summary {
        grid-template-columns: 1fr;
    }
}
</style>
