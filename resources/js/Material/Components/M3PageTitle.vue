<script setup>
/**
 * M3PageTitle — bar judul halaman in-content untuk mobile.
 *
 * Di compact breakpoint, layout Material tidak menampilkan app bar
 * (aksi global & judul pindah ke konten / halaman More), jadi setiap
 * halaman menaruh judulnya di dalam konten. Slot `actions` menampung
 * shortcut kontekstual per halaman (mis. Chat/Search di Dashboard,
 * Date di Analytics). Di >=1024px komponen ini disembunyikan karena
 * judul sudah tampil di app bar.
 *
 * Bar ini ikut scroll bersama konten (bukan sticky): di M3, shortcut
 * per halaman cukup tampil di awal konten dan tidak menempel di atas.
 */
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
})
</script>

<template>
    <header class="m3-page-title">
        <div class="m3-page-title__text-block">
            <h1 class="m3-page-title__text">{{ title }}</h1>
            <p v-if="subtitle" class="m3-page-title__sub">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="m3-page-title__actions">
            <slot name="actions" />
        </div>
    </header>
</template>

<style scoped>
.m3-page-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    min-width: 0;
    /* Bleed ke tepi container (semua halaman M3 memakai padding space-4)
       agar sejajar dengan grid konten di bawahnya. */
    margin: calc(-1 * var(--m3-space-4)) calc(-1 * var(--m3-space-4)) 0;
    padding: var(--m3-space-4);
}

.m3-page-title__text-block {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
}

.m3-page-title__text {
    margin: 0;
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    letter-spacing: var(--m3-type-headline-small-tracking);
    color: var(--m3-color-on-surface);
}

.m3-page-title__sub {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-page-title__actions {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    flex-shrink: 0;
}

@media (min-width: 1024px) {
    .m3-page-title {
        display: none;
    }
}
</style>
