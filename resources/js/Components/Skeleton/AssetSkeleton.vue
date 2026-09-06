<template>
    <div class="p-4 sm:p-5 space-y-4 sm:space-y-5 animate-pulse">
        <!-- Total balance card -->
        <div class="p-5 sm:p-6 rounded-2xl bg-[var(--color-surface-muted)] space-y-3">
            <div class="h-2.5 w-24 sm:w-28 rounded-full bg-[var(--color-surface-overlay)]"></div>
            <div class="h-6 sm:h-7 w-40 sm:w-48 rounded-full bg-[var(--color-surface-overlay)]"></div>
        </div>

        <!-- Section: Liquid -->
        <div class="space-y-2.5 sm:space-y-3">
            <div class="h-2.5 w-14 sm:w-16 rounded-full bg-[var(--color-surface-muted)]"></div>
            <div v-for="i in 3" :key="'l'+i" class="flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl bg-[var(--color-surface-muted)]/60">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[var(--color-surface-overlay)] shrink-0"></div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 w-2/5 rounded-full bg-[var(--color-surface-overlay)]"></div>
                    <div class="h-2.5 w-1/4 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
                <div class="h-3 w-14 sm:w-16 rounded-full bg-[var(--color-surface-overlay)] shrink-0"></div>
            </div>
        </div>

        <!-- Section: Investasi -->
        <div class="space-y-2.5 sm:space-y-3">
            <div class="h-2.5 w-20 sm:w-24 rounded-full bg-[var(--color-surface-muted)]"></div>
            <div v-for="i in 2" :key="'a'+i" class="flex items-center gap-3 sm:gap-4 p-3 sm:p-4 rounded-xl bg-[var(--color-surface-muted)]/60">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[var(--color-surface-overlay)] shrink-0"></div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 w-2/5 rounded-full bg-[var(--color-surface-overlay)]"></div>
                    <div class="h-2.5 w-1/4 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
                <div class="h-3 w-14 sm:w-16 rounded-full bg-[var(--color-surface-overlay)] shrink-0"></div>
            </div>
        </div>

        <!-- Kewajiban grid -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <div class="h-20 sm:h-24 rounded-xl bg-[var(--color-surface-muted)]/60"></div>
            <div class="h-20 sm:h-24 rounded-xl bg-[var(--color-surface-muted)]/60"></div>
        </div>
    </div>
</template>
