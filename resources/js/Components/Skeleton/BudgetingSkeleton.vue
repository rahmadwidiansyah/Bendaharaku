<template>
    <div class="p-5 space-y-5 animate-pulse">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="h-2.5 w-40 rounded-full bg-gray-700/50"></div>
            <div class="flex items-center gap-1.5">
                <div class="w-8 h-8 rounded-lg bg-gray-700/50"></div>
                <div class="h-8 w-28 rounded-lg bg-gray-700/40"></div>
                <div class="w-8 h-8 rounded-lg bg-gray-700/50"></div>
            </div>
        </div>

        <!-- Banner -->
        <div class="h-14 rounded-xl bg-gray-700/40"></div>

        <!-- Baris kategori -->
        <div v-for="i in 3" :key="i" class="rounded-xl border border-white/5 bg-gray-700/40 p-3.5 space-y-2.5">
            <div class="h-11 rounded-xl bg-gray-600/50"></div>
            <div class="grid grid-cols-2 gap-2.5">
                <div class="h-11 rounded-xl bg-gray-600/50"></div>
                <div class="h-11 rounded-xl bg-gray-600/50"></div>
            </div>
        </div>

        <!-- Total -->
        <div class="h-14 rounded-xl bg-gray-700/40"></div>

        <!-- Submit -->
        <div class="h-14 rounded-xl bg-gray-600/40"></div>
    </div>
</template>
