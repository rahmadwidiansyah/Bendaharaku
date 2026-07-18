<template>
    <div class="p-5 space-y-5 animate-pulse">
        <!-- Header -->
        <div class="flex items-end justify-between">
            <div class="space-y-1.5">
                <div class="h-2 w-24 rounded-full bg-gray-700/50"></div>
                <div class="h-3 w-36 rounded-full bg-gray-600/60"></div>
            </div>
            <div class="h-9 w-28 rounded-xl bg-gray-700/50"></div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 gap-3">
            <div class="p-4 rounded-xl bg-green-500/10 space-y-2">
                <div class="h-2 w-20 rounded-full bg-green-500/20"></div>
                <div class="h-4 w-24 rounded-full bg-green-500/20"></div>
            </div>
            <div class="p-4 rounded-xl bg-red-500/10 space-y-2">
                <div class="h-2 w-20 rounded-full bg-red-500/20"></div>
                <div class="h-4 w-24 rounded-full bg-red-500/20"></div>
            </div>
        </div>

        <!-- Line chart placeholder -->
        <div class="p-5 rounded-xl bg-gray-700/40 space-y-3">
            <div class="h-3 w-32 rounded-full bg-gray-600/60"></div>
            <div class="h-[140px] rounded-lg bg-gray-600/30 flex items-end px-2 pb-2 gap-1">
                <div v-for="(h, i) in [40,55,35,70,45,60,80,50,65,75,45,55,70,60,85]" :key="i"
                    class="flex-1 rounded-sm bg-purple-500/20" :style="{ height: h + '%' }"></div>
            </div>
        </div>

        <!-- Bar chart placeholder -->
        <div class="p-5 rounded-xl bg-gray-700/40 space-y-3">
            <div class="flex justify-between items-center">
                <div class="h-3 w-20 rounded-full bg-gray-600/60"></div>
                <div class="h-7 w-36 rounded-lg bg-gray-700/50"></div>
            </div>
            <div class="h-[180px] rounded-lg bg-gray-600/30 flex items-end px-2 pb-2 gap-2">
                <div v-for="(h, i) in [60,40,75,30,85,50,65,45,70,55]" :key="i"
                    class="flex-1 rounded-sm"
                    :class="i % 2 === 0 ? 'bg-green-500/20' : 'bg-red-500/20'"
                    :style="{ height: h + '%' }"></div>
            </div>
        </div>

        <!-- Category tabs -->
        <div class="h-11 rounded-xl bg-gray-700/50"></div>

        <!-- Doughnut + list -->
        <div class="p-5 rounded-xl bg-gray-700/40 flex flex-col items-center gap-4">
            <div class="w-44 h-44 rounded-full bg-gray-600/40 border-[28px] border-gray-700/50"></div>
            <div class="w-full space-y-2">
                <div v-for="i in 4" :key="i" class="flex items-center gap-3 p-3 rounded-xl bg-gray-700/40">
                    <div class="w-1.5 h-6 rounded-full bg-purple-500/30 shrink-0"></div>
                    <div class="w-8 h-8 rounded-xl bg-gray-600/60 shrink-0"></div>
                    <div class="flex-1 space-y-1.5">
                        <div class="h-2.5 w-2/5 rounded-full bg-gray-600/60"></div>
                        <div class="h-2 w-1/5 rounded-full bg-gray-700/60"></div>
                    </div>
                    <div class="h-3 w-16 rounded-full bg-gray-600/60 shrink-0"></div>
                </div>
            </div>
        </div>
    </div>
</template>
