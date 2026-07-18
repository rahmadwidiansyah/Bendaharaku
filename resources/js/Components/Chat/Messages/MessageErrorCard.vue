<script setup>
import { computed } from 'vue'

const props = defineProps({
    component: { type: Object, required: true },
})

const isWarning = computed(() =>
    props.component.severity === 'warning'
)

const config = computed(() => isWarning.value
    ? { bg: 'bg-amber-500/8', border: 'border-amber-500/20', icon: 'text-amber-400', text: 'text-amber-300', raw: 'text-amber-500' }
    : { bg: 'bg-red-500/8',   border: 'border-red-500/20',   icon: 'text-red-400',   text: 'text-red-300',   raw: 'text-red-500' }
)
</script>

<template>
    <div :class="['rounded-2xl border px-4 py-3.5 flex gap-3', config.bg, config.border]">
        <!-- Icon -->
        <span :class="['text-base mt-0.5 shrink-0', config.icon]">
            {{ isWarning ? '⚠️' : '❌' }}
        </span>

        <div class="flex-1 min-w-0">
            <!-- Index label -->
            <p v-if="component.index !== null && component.index !== undefined"
                class="text-2xs font-black text-gray-500 mb-1">
                Item #{{ component.index }}
            </p>

            <!-- Raw input yang bermasalah -->
            <p v-if="component.raw"
                :class="['text-xs font-mono mb-1 truncate', config.raw]">
                "{{ component.raw }}"
            </p>

            <!-- Error message -->
            <p :class="['text-sm leading-relaxed', config.text]">
                {{ component.message }}
            </p>
        </div>
    </div>
</template>
