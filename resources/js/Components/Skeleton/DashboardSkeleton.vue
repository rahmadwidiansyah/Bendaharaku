<template>
    <div class="p-5 space-y-5 animate-pulse">
        <!-- Portfolio card -->
        <div class="h-36 rounded-2xl bg-gray-700/50"></div>

        <!-- Summary row: income + expense -->
        <div class="grid grid-cols-2 gap-3">
            <div class="h-20 rounded-xl bg-gray-700/50"></div>
            <div class="h-20 rounded-xl bg-gray-700/50"></div>
        </div>

        <!-- Pinned wallets row -->
        <div class="flex gap-3 overflow-hidden">
            <div class="h-16 w-28 shrink-0 rounded-xl bg-gray-700/50"></div>
            <div class="h-16 w-28 shrink-0 rounded-xl bg-gray-700/50"></div>
            <div class="h-16 w-28 shrink-0 rounded-xl bg-gray-700/50"></div>
        </div>

        <!-- Section label -->
        <div class="h-3 w-32 rounded-full bg-gray-700/50"></div>

        <!-- Transaction list -->
        <div class="space-y-3">
            <div v-for="i in 6" :key="i" class="flex items-center gap-3 p-3 rounded-xl bg-gray-700/40">
                <div class="w-10 h-10 rounded-xl bg-gray-600/60 shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-3/5 rounded-full bg-gray-600/60"></div>
                    <div class="h-2.5 w-2/5 rounded-full bg-gray-700/60"></div>
                </div>
                <div class="h-3 w-16 rounded-full bg-gray-600/60 shrink-0"></div>
            </div>
        </div>
    </div>
</template>
