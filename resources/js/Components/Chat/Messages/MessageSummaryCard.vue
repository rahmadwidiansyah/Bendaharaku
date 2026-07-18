<script setup>
defineProps({
    component: { type: Object, required: true },
})
</script>

<template>
    <div class="rounded-2xl border border-white/10 bg-gray-950/50 overflow-hidden">
        <!-- Header -->
        <div class="px-4 py-3 bg-white/3 border-b border-white/5">
            <p class="text-xs font-bold text-gray-300">{{ component.label }}</p>
        </div>

        <!-- Stats row -->
        <div class="flex divide-x divide-white/5">
            <!-- Total -->
            <div class="flex-1 flex flex-col items-center py-3">
                <span class="text-xl font-black text-white tabular-nums">{{ component.total }}</span>
                <span class="text-2xs text-gray-500 mt-0.5">Total</span>
            </div>

            <!-- Success -->
            <div class="flex-1 flex flex-col items-center py-3">
                <span class="text-xl font-black text-emerald-400 tabular-nums">{{ component.success }}</span>
                <span class="text-2xs text-gray-500 mt-0.5">Berhasil</span>
            </div>

            <!-- Failed -->
            <div v-if="component.failed > 0" class="flex-1 flex flex-col items-center py-3">
                <span class="text-xl font-black text-red-400 tabular-nums">{{ component.failed }}</span>
                <span class="text-2xs text-gray-500 mt-0.5">Gagal</span>
            </div>
        </div>
    </div>
</template>
