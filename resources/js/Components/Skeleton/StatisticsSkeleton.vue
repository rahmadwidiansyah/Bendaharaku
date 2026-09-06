<template>
    <div class="p-4 sm:p-5 space-y-4 sm:space-y-5 animate-pulse">
        <!-- Header -->
        <div class="flex items-end justify-between gap-3">
            <div class="space-y-1.5">
                <div class="h-2 w-20 sm:w-24 rounded-full bg-[var(--color-surface-muted)]"></div>
                <div class="h-3 w-32 sm:w-36 rounded-full bg-[var(--color-surface-overlay)]"></div>
            </div>
            <div class="h-8 sm:h-9 w-24 sm:w-28 rounded-xl bg-[var(--color-surface-muted)] shrink-0"></div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
            <div class="p-3 sm:p-4 rounded-xl bg-[var(--color-income-bg)] space-y-2 border border-[var(--color-income-border)]">
                <div class="h-2 w-16 sm:w-20 rounded-full bg-[var(--color-income-border)]"></div>
                <div class="h-4 w-20 sm:w-24 rounded-full bg-[var(--color-income-border)]"></div>
            </div>
            <div class="p-3 sm:p-4 rounded-xl bg-[var(--color-expense-bg)] space-y-2 border border-[var(--color-expense-border)]">
                <div class="h-2 w-16 sm:w-20 rounded-full bg-[var(--color-expense-border)]"></div>
                <div class="h-4 w-20 sm:w-24 rounded-full bg-[var(--color-expense-border)]"></div>
            </div>
        </div>

        <!-- Line chart placeholder -->
        <div class="p-4 sm:p-5 rounded-xl bg-[var(--color-surface-muted)]/60 space-y-3">
            <div class="h-3 w-28 sm:w-32 rounded-full bg-[var(--color-surface-overlay)]"></div>
            <div class="h-[120px] sm:h-[140px] rounded-lg bg-[var(--color-surface-overlay)] flex items-end px-2 pb-2 gap-1">
                <div v-for="(h, i) in [40,55,35,70,45,60,80,50,65,75,45,55,70,60,85]" :key="i"
                    class="flex-1 rounded-sm bg-[var(--color-brand-subtle)]" :style="{ height: h + '%' }"></div>
            </div>
        </div>

        <!-- Bar chart placeholder -->
        <div class="p-4 sm:p-5 rounded-xl bg-[var(--color-surface-muted)]/60 space-y-3">
            <div class="flex justify-between items-center gap-2">
                <div class="h-3 w-16 sm:w-20 rounded-full bg-[var(--color-surface-overlay)]"></div>
                <div class="h-7 w-32 sm:w-36 rounded-lg bg-[var(--color-surface-muted)]"></div>
            </div>
            <div class="h-[160px] sm:h-[180px] rounded-lg bg-[var(--color-surface-overlay)] flex items-end px-2 pb-2 gap-1.5 sm:gap-2">
                <div v-for="(h, i) in [60,40,75,30,85,50,65,45,70,55]" :key="i"
                    class="flex-1 rounded-sm"
                    :class="i % 2 === 0 ? 'bg-[var(--color-income-bg)]' : 'bg-[var(--color-expense-bg)]'"
                    :style="{ height: h + '%' }"></div>
            </div>
        </div>

        <!-- Category tabs -->
        <div class="h-10 sm:h-11 rounded-xl bg-[var(--color-surface-muted)]"></div>

        <!-- Doughnut + list -->
        <div class="p-4 sm:p-5 rounded-xl bg-[var(--color-surface-muted)]/60 flex flex-col items-center gap-3 sm:gap-4">
            <div class="w-36 h-36 sm:w-44 sm:h-44 rounded-full bg-[var(--color-surface-overlay)] border-[20px] sm:border-[28px] border-[var(--color-surface-muted)]"></div>
            <div class="w-full space-y-2">
                <div v-for="i in 4" :key="i" class="flex items-center gap-2.5 sm:gap-3 p-2.5 sm:p-3 rounded-xl bg-[var(--color-surface-muted)]">
                    <div class="w-1.5 h-6 rounded-full bg-[var(--color-brand-border)] shrink-0"></div>
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-[var(--color-surface-overlay)] shrink-0"></div>
                    <div class="flex-1 space-y-1.5 min-w-0">
                        <div class="h-2.5 w-2/5 rounded-full bg-[var(--color-surface-overlay)]"></div>
                        <div class="h-2 w-1/5 rounded-full bg-[var(--color-surface-muted)]"></div>
                    </div>
                    <div class="h-3 w-14 sm:w-16 rounded-full bg-[var(--color-surface-overlay)] shrink-0"></div>
                </div>
            </div>
        </div>
    </div>
</template>
