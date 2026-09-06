<template>
    <div class="p-4 sm:p-5 space-y-4 sm:space-y-5 animate-pulse">
        <!-- Portfolio card -->
        <div class="h-32 sm:h-36 rounded-2xl bg-[var(--color-surface-muted)]"></div>

        <!-- Summary row: income + expense -->
        <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
            <div class="h-16 sm:h-20 rounded-xl bg-[var(--color-surface-muted)]"></div>
            <div class="h-16 sm:h-20 rounded-xl bg-[var(--color-surface-muted)]"></div>
        </div>

        <!-- Pinned wallets row -->
        <div class="flex gap-2.5 sm:gap-3 overflow-hidden">
            <div class="h-14 sm:h-16 w-24 sm:w-28 shrink-0 rounded-xl bg-[var(--color-surface-muted)]"></div>
            <div class="h-14 sm:h-16 w-24 sm:w-28 shrink-0 rounded-xl bg-[var(--color-surface-muted)]"></div>
            <div class="h-14 sm:h-16 w-24 sm:w-28 shrink-0 rounded-xl bg-[var(--color-surface-muted)] hidden sm:block"></div>
        </div>

        <!-- Section label -->
        <div class="h-3 w-28 sm:w-32 rounded-full bg-[var(--color-surface-muted)]"></div>

        <!-- Transaction list -->
        <div class="space-y-2.5 sm:space-y-3">
            <div v-for="i in 6" :key="i" class="flex items-center gap-2.5 sm:gap-3 p-2.5 sm:p-3 rounded-xl bg-[var(--color-surface-muted)]/60">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[var(--color-surface-overlay)] shrink-0"></div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 w-3/5 rounded-full bg-[var(--color-surface-overlay)]"></div>
                    <div class="h-2.5 w-2/5 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
                <div class="h-3 w-14 sm:w-16 rounded-full bg-[var(--color-surface-overlay)] shrink-0"></div>
            </div>
        </div>
    </div>
</template>
