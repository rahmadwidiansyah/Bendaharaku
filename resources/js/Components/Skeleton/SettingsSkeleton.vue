<template>
    <div class="p-5 space-y-8 animate-pulse">
        <!-- Section: Akun -->
        <div class="space-y-3">
            <div class="h-2.5 w-16 rounded-full bg-gray-700/50"></div>
            <div class="flex items-center gap-4 p-5 rounded-2xl bg-gray-700/40">
                <div class="w-11 h-11 rounded-xl bg-gray-600/60 shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-1/3 rounded-full bg-gray-600/60"></div>
                    <div class="h-2.5 w-1/2 rounded-full bg-gray-700/60"></div>
                </div>
                <div class="w-4 h-4 rounded-full bg-gray-600/60 shrink-0"></div>
            </div>
        </div>

        <!-- Section: Transaksi -->
        <div class="space-y-3">
            <div class="h-2.5 w-24 rounded-full bg-gray-700/50"></div>
            <div class="p-5 rounded-2xl bg-gray-700/40 space-y-4">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-gray-600/60 shrink-0"></div>
                    <div class="flex-1 space-y-2 pt-1">
                        <div class="h-3 w-2/5 rounded-full bg-gray-600/60"></div>
                        <div class="h-2.5 w-4/5 rounded-full bg-gray-700/60"></div>
                        <div class="h-2.5 w-3/5 rounded-full bg-gray-700/60"></div>
                    </div>
                    <div class="w-11 h-6 rounded-full bg-gray-600/60 shrink-0 mt-1"></div>
                </div>
            </div>
        </div>

        <!-- Section: Integrasi -->
        <div class="space-y-3">
            <div class="h-2.5 w-20 rounded-full bg-gray-700/50"></div>
            <div v-for="i in 2" :key="i" class="flex items-center gap-4 p-5 rounded-2xl bg-gray-700/40">
                <div class="w-11 h-11 rounded-xl bg-gray-600/60 shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-2/5 rounded-full bg-gray-600/60"></div>
                    <div class="h-2.5 w-3/5 rounded-full bg-gray-700/60"></div>
                </div>
                <div class="w-4 h-4 rounded-full bg-gray-600/60 shrink-0"></div>
            </div>
        </div>

        <!-- Section: Bahaya -->
        <div class="space-y-3">
            <div class="h-2.5 w-20 rounded-full bg-red-500/20"></div>
            <div class="flex items-center gap-4 p-5 rounded-2xl bg-red-500/5 border border-red-500/10">
                <div class="w-11 h-11 rounded-xl bg-red-500/20 shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 w-1/3 rounded-full bg-red-500/20"></div>
                    <div class="h-2.5 w-1/2 rounded-full bg-red-500/10"></div>
                </div>
            </div>
        </div>
    </div>
</template>
