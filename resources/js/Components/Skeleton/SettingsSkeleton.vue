<template>
    <div class="p-4 sm:p-5 space-y-6 sm:space-y-8 animate-pulse">
        <!-- Section: Akun -->
        <div class="space-y-2.5 sm:space-y-3">
            <div class="h-2.5 w-14 sm:w-16 rounded-full bg-[var(--color-surface-muted)]"></div>
            <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-5 rounded-2xl bg-[var(--color-surface-muted)]/60">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-[var(--color-surface-overlay)] shrink-0"></div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 w-1/3 rounded-full bg-[var(--color-surface-overlay)]"></div>
                    <div class="h-2.5 w-1/2 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
                <div class="w-4 h-4 rounded-full bg-[var(--color-surface-overlay)] shrink-0"></div>
            </div>
        </div>

        <!-- Section: Transaksi -->
        <div class="space-y-2.5 sm:space-y-3">
            <div class="h-2.5 w-20 sm:w-24 rounded-full bg-[var(--color-surface-muted)]"></div>
            <div class="p-4 sm:p-5 rounded-2xl bg-[var(--color-surface-muted)]/60 space-y-4">
                <div class="flex items-start gap-3 sm:gap-4">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-[var(--color-surface-overlay)] shrink-0"></div>
                    <div class="flex-1 space-y-2 pt-1 min-w-0">
                        <div class="h-3 w-2/5 rounded-full bg-[var(--color-surface-overlay)]"></div>
                        <div class="h-2.5 w-4/5 rounded-full bg-[var(--color-surface-muted)]"></div>
                        <div class="h-2.5 w-3/5 rounded-full bg-[var(--color-surface-muted)]"></div>
                    </div>
                    <div class="w-10 sm:w-11 h-5 sm:h-6 rounded-full bg-[var(--color-surface-overlay)] shrink-0 mt-1"></div>
                </div>
            </div>
        </div>

        <!-- Section: Integrasi -->
        <div class="space-y-2.5 sm:space-y-3">
            <div class="h-2.5 w-16 sm:w-20 rounded-full bg-[var(--color-surface-muted)]"></div>
            <div v-for="i in 2" :key="i" class="flex items-center gap-3 sm:gap-4 p-4 sm:p-5 rounded-2xl bg-[var(--color-surface-muted)]/60">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-[var(--color-surface-overlay)] shrink-0"></div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 w-2/5 rounded-full bg-[var(--color-surface-overlay)]"></div>
                    <div class="h-2.5 w-3/5 rounded-full bg-[var(--color-surface-muted)]"></div>
                </div>
                <div class="w-4 h-4 rounded-full bg-[var(--color-surface-overlay)] shrink-0"></div>
            </div>
        </div>

        <!-- Section: Bahaya -->
        <div class="space-y-2.5 sm:space-y-3">
            <div class="h-2.5 w-16 sm:w-20 rounded-full bg-[var(--color-expense-border)]"></div>
            <div class="flex items-center gap-3 sm:gap-4 p-4 sm:p-5 rounded-2xl bg-[var(--color-expense-bg)] border border-[var(--color-expense-border)]">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-[var(--color-expense-border)] shrink-0"></div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 w-1/3 rounded-full bg-[var(--color-expense-border)]"></div>
                    <div class="h-2.5 w-1/2 rounded-full bg-[var(--color-expense-bg)]"></div>
                </div>
            </div>
        </div>
    </div>
</template>
