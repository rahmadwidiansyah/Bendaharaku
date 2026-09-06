<template>
    <div class="p-4 sm:p-5 space-y-3 sm:space-y-4 animate-pulse">
        <!-- Filter tabs -->
        <div class="flex gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar">
            <div class="h-8 w-16 sm:w-20 rounded-xl bg-[var(--color-surface-muted)] shrink-0"></div>
            <div class="h-8 w-16 sm:w-20 rounded-xl bg-[var(--color-surface-muted)] shrink-0"></div>
            <div class="h-8 w-16 sm:w-20 rounded-xl bg-[var(--color-surface-muted)] shrink-0"></div>
        </div>

        <!-- Search bar -->
        <div class="h-10 sm:h-11 rounded-xl bg-[var(--color-surface-muted)]"></div>

        <!-- Date group label -->
        <div class="h-3 w-20 sm:w-24 rounded-full bg-[var(--color-surface-muted)]"></div>

        <!-- Transaction rows -->
        <div class="space-y-2 sm:space-y-2.5">
            <div v-for="i in 8" :key="i" class="flex items-center gap-2.5 sm:gap-3 p-3 sm:p-3.5 rounded-xl bg-[var(--color-surface-muted)]/60">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl shrink-0"
                    :class="i % 3 === 0 ? 'bg-[var(--color-income-bg)]' : i % 3 === 1 ? 'bg-[var(--color-expense-bg)]' : 'bg-[var(--color-transfer-bg)]'">
                </div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 rounded-full bg-[var(--color-surface-overlay)]" :style="{ width: (45 + (i * 7) % 35) + '%' }"></div>
                    <div class="h-2.5 w-20 sm:w-24 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
                <div class="shrink-0 text-right space-y-1.5">
                    <div class="h-3 w-14 sm:w-16 rounded-full bg-[var(--color-surface-overlay)] ml-auto"></div>
                    <div class="h-2 w-8 sm:w-10 rounded-full bg-[var(--color-surface-muted)] ml-auto"></div>
                </div>
            </div>
        </div>
    </div>
</template>
