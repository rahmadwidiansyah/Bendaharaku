<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    loading: { type: Boolean, default: false },
})

const showSkeleton = ref(true)
const imgLoaded = ref(false)

watch(() => props.src, () => {
    showSkeleton.value = true
    imgLoaded.value = false
})

function onImgLoad() {
    showSkeleton.value = false
    imgLoaded.value = true
}

function onImgError() {
    showSkeleton.value = false
}
</script>

<template>
    <div class="relative w-full h-full overflow-hidden bg-gray-700/50">
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            class="w-full h-full object-cover transition-opacity duration-300"
            :class="imgLoaded ? 'opacity-100' : 'opacity-0'"
            @load="onImgLoad"
            @error="onImgError"
            loading="lazy"
        />
        <div
            v-if="!src || showSkeleton"
            class="absolute inset-0 flex items-center justify-center"
            :class="{ 'animate-pulse bg-gray-700/30': !imgLoaded }"
        >
            <svg v-if="!loading" class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            <svg v-else class="animate-spin w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>
    </div>
</template>
