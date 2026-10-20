<script setup>
/**
 * M3SettingsSection — judul seksi + konten daftar/kartu Settings M3.
 */
defineProps({
    title: { type: String, default: '' },
})
</script>

<template>
    <section class="m3-settings-section">
        <h2 v-if="title" class="m3-settings-section__title">{{ title }}</h2>
        <slot />
    </section>
</template>

<style scoped>
.m3-settings-section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-settings-section__title {
    margin: 0;
    padding-left: var(--m3-space-4);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
    color: var(--m3-color-on-surface-variant);
}
</style>
