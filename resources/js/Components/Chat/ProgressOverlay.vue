<script setup>
const props = defineProps({
    progress: { type: Number, default: 0 },
    show: { type: Boolean, default: false },
})
</script>

<template>
    <div v-if="show" class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center gap-2">
        <svg class="animate-spin w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <div class="w-12 h-1 bg-gray-700 rounded-full overflow-hidden">
            <div
                class="h-full bg-purple-500 rounded-full transition-all duration-300 ease-out"
                :style="{ width: progress + '%' }"
            />
        </div>
    </div>
</template>
