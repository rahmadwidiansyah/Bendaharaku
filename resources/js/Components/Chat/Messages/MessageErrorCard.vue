<script setup>
import { computed } from 'vue'

const props = defineProps({
    component: { type: Object, required: true },
})

const isWarning = computed(() => props.component.severity === 'warning')

// Hapus properti 'icon' dari config karena sudah dari backend
const config = computed(() => isWarning.value ? { text: 'text-amber-300', raw: 'text-amber-500/80' } : { text: 'text-red-300', raw: 'text-red-500/80' })
</script>

<template>
    <div class="px-3.5 py-3 flex gap-2.5">
        <!-- Bagian <span class="text-sm mt-0.5 shrink-0">{{ config.icon }}</span> SUDAH DIHAPUS -->

        <div class="flex-1 min-w-0">
            <!-- Index label -->
            <p v-if="component.index !== null && component.index !== undefined"
                class="text-2xs font-bold text-gray-600 mb-1">Item #{{ component.index }}</p>

            <!-- Raw input -->
            <p v-if="component.raw" :class="['text-xs font-mono mb-1 truncate', config.raw]">
                "{{ component.raw }}"
            </p>

            <!-- Message (Emoji silang atau warning dari backend akan menyatu langsung di sini) -->
            <p :class="['text-sm leading-relaxed', config.text]">{{ component.message }}</p>
        </div>
    </div>
</template>