<script setup>
defineProps({
    // true bila skeleton dirender di bawah top bar nyata halaman (dipakai di Index.vue)
    embedded: { type: Boolean, default: false },
})
</script>

<template>
    <div
        class="px-4 sm:px-5 pb-40 pt-4 sm:pt-6 w-full lg:max-w-7xl mx-auto lg:px-8 animate-pulse"
        role="status"
        aria-label="Memuat budget"
    >
        <!-- Top bar: month selector kiri, aksi kanan (sembunyikan jika embedded) -->
        <template v-if="!embedded">
            <div class="flex items-center justify-between gap-2 mb-5 sm:mb-6">
                <div class="flex items-center gap-1.5">
                    <div class="w-8 h-8 rounded-lg bg-[var(--color-surface-muted)]"></div>
                    <div class="h-8 w-40 rounded-lg bg-[var(--color-surface-muted)]"></div>
                    <div class="w-8 h-8 rounded-lg bg-[var(--color-surface-muted)]"></div>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="h-8 w-20 rounded-lg bg-[var(--color-surface-muted)]"></div>
                    <div class="w-8 h-8 rounded-lg bg-[var(--color-surface-muted)]"></div>
                </div>
            </div>
        </template>

        <!-- Summary card -->
        <div class="rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] shadow-card p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[var(--color-surface-muted)] shrink-0"></div>
                <div class="flex-1 min-w-0 space-y-2">
                    <div class="h-4 w-40 rounded-full bg-[var(--color-surface-muted)]"></div>
                    <div class="h-2.5 w-52 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3 mt-5">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-surface-overlay)] p-3 sm:p-4 space-y-2"
                >
                    <div class="h-5 w-24 rounded-full bg-[var(--color-surface-muted)]"></div>
                    <div class="h-2 w-20 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
            </div>
        </div>

        <!-- Item grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 lg:gap-4 mt-4 sm:mt-5">
            <div
                v-for="i in 6"
                :key="i"
                class="rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] shadow-card p-3 space-y-3"
            >
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 lg:w-7 lg:h-7 rounded-lg bg-[var(--color-surface-muted)] shrink-0"></div>
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="h-3 w-32 rounded-full bg-[var(--color-surface-muted)]"></div>
                        <div class="h-2 w-20 rounded-full bg-[var(--color-surface-muted)]"></div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-1">
                        <div class="h-2 w-14 rounded-full bg-[var(--color-surface-muted)]"></div>
                        <div class="h-3 w-20 rounded-full bg-[var(--color-surface-muted)]"></div>
                    </div>
                    <div class="h-1.5 rounded-full bg-[var(--color-surface-muted)]/60"></div>
                    <div class="flex items-center justify-between gap-1">
                        <div class="h-2 w-24 rounded-full bg-[var(--color-surface-muted)]"></div>
                        <div class="h-2 w-16 rounded-full bg-[var(--color-surface-muted)]"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>