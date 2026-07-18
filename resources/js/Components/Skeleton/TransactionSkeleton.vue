<template>
    <div class="p-5 space-y-4 animate-pulse">
        <!-- Filter tabs -->
        <div class="flex gap-2">
            <div class="h-8 w-20 rounded-xl bg-gray-700/50"></div>
            <div class="h-8 w-20 rounded-xl bg-gray-700/50"></div>
            <div class="h-8 w-20 rounded-xl bg-gray-700/50"></div>
        </div>

        <!-- Search bar -->
        <div class="h-11 rounded-xl bg-gray-700/50"></div>

        <!-- Date group label -->
        <div class="h-3 w-24 rounded-full bg-gray-700/50"></div>

        <!-- Transaction rows -->
        <div class="space-y-2.5">
            <div v-for="i in 8" :key="i" class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-700/40">
                <div class="w-10 h-10 rounded-xl shrink-0"
                    :class="i % 3 === 0 ? 'bg-green-500/20' : i % 3 === 1 ? 'bg-red-500/20' : 'bg-blue-500/20'">
                </div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="h-3 rounded-full bg-gray-600/60" :style="{ width: (45 + (i * 7) % 35) + '%' }"></div>
                    <div class="h-2.5 w-24 rounded-full bg-gray-700/60"></div>
                </div>
                <div class="shrink-0 text-right space-y-1.5">
                    <div class="h-3 w-16 rounded-full bg-gray-600/60 ml-auto"></div>
                    <div class="h-2 w-10 rounded-full bg-gray-700/60 ml-auto"></div>
                </div>
            </div>
        </div>
    </div>
</template>
