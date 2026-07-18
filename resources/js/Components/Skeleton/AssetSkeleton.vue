<template>
    <div class="p-5 space-y-5 animate-pulse">
        <!-- Total balance card -->
        <div class="p-6 rounded-2xl bg-gray-700/50 space-y-3">
            <div class="h-2.5 w-28 rounded-full bg-gray-600/60"></div>
            <div class="h-7 w-48 rounded-full bg-gray-600/60"></div>
        </div>

        <!-- Section: Liquid -->
        <div class="space-y-3">
            <div class="h-2.5 w-16 rounded-full bg-gray-700/50"></div>
            <div v-for="i in 3" :key="'l'+i" class="flex items-center gap-4 p-4 rounded-xl bg-gray-700/40">
                <div class="w-12 h-12 rounded-xl bg-gray-600/60 shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-2/5 rounded-full bg-gray-600/60"></div>
                    <div class="h-2.5 w-1/4 rounded-full bg-gray-700/60"></div>
                </div>
                <div class="h-3 w-16 rounded-full bg-gray-600/60 shrink-0"></div>
            </div>
        </div>

        <!-- Section: Investasi -->
        <div class="space-y-3">
            <div class="h-2.5 w-24 rounded-full bg-gray-700/50"></div>
            <div v-for="i in 2" :key="'a'+i" class="flex items-center gap-4 p-4 rounded-xl bg-gray-700/40">
                <div class="w-12 h-12 rounded-xl bg-gray-600/60 shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-2/5 rounded-full bg-gray-600/60"></div>
                    <div class="h-2.5 w-1/4 rounded-full bg-gray-700/60"></div>
                </div>
                <div class="h-3 w-16 rounded-full bg-gray-600/60 shrink-0"></div>
            </div>
        </div>

        <!-- Kewajiban grid -->
        <div class="grid grid-cols-2 gap-4">
            <div class="h-24 rounded-xl bg-gray-700/40"></div>
            <div class="h-24 rounded-xl bg-gray-700/40"></div>
        </div>
    </div>
</template>
