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
 * Slot `leading` menampung aksi navigasi kiri (tombol kembali sub-page)
 * agar terletak sejajar satu baris dengan judul.
 */
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
})
</script>

<template>
    <header class="m3-page-title">
        <!-- Slot leading (mis. tombol kembali sub-page): sejajar satu baris
             dengan judul per pola top app bar M3 (leading action + judul). -->
        <div v-if="$slots.leading" class="m3-page-title__leading">
            <slot name="leading" />
        </div>
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
    margin: calc(-1 * var(--m3-space-4)) calc(-1 * var(--m3-space-4))
        calc(-1 * var(--m3-space-4));
    /* Bawah: padding space-2 + margin -space-4 = net −8px ekstra melewati
       cancel padding, sehingga jarak visual judul→blok pertama =
       section-gap halaman − 8px (16→8, 20→12, 24→16). Sebelumnya
       gap penuh (16–24px) dirasa masih terlalu lega. */
    padding: var(--m3-space-4) var(--m3-space-4) var(--m3-space-2);
}

.m3-page-title__leading {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}

/* flex:1 membuat judul mengisi sisa baris sehingga tetap rata kiri
   setelah leading (tombol kembali) dan mendorong actions ke kanan —
   jpengganti space-between yang akan menengahkan judul saat leading hadir. */
.m3-page-title__text-block {
    display: flex;
    flex-direction: column;
    flex: 1;
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
