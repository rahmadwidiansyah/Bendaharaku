<script setup>
import { computed } from 'vue'
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-vue-next'

const props = defineProps({
    column: { type: String, required: true },
    label: { type: String, required: true },
    sort: { type: String, default: null },
    direction: { type: String, default: null },
    align: { type: String, default: 'left' },
})

const emit = defineEmits(['sort'])

const isActive = computed(() => props.sort === props.column)
const ariaSort = computed(() => {
    if (!isActive.value) return 'none'
    return props.direction === 'asc' ? 'ascending' : 'descending'
})
const Icon = computed(() => {
    if (!isActive.value) return ChevronsUpDown
    return props.direction === 'asc' ? ArrowUp : ArrowDown
})
</script>

<template>
    <th
        scope="col"
        :aria-sort="ariaSort"
        :class="[
            'px-4 lg:px-6 py-3 text-xs font-semibold uppercase tracking-wider text-[rgb(var(--m3-color-on-surface-variant-rgb))]',
            align === 'right' ? 'text-right' : 'text-left',
        ]"
    >
        <button
            type="button"
            class="group inline-flex items-center gap-1.5 rounded-md px-1 py-0.5 transition-colors hover:text-[rgb(var(--m3-color-on-surface-rgb))] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[rgb(var(--m3-color-primary-rgb))]"
            @click="emit('sort', column)"
        >
            <span>{{ label }}</span>
            <Icon
                :class="[
                    'h-3.5 w-3.5 transition-opacity',
                    isActive
                        ? 'text-[rgb(var(--m3-color-primary-rgb))]'
                        : 'opacity-40 group-hover:opacity-70',
                ]"
            />
        </button>
    </th>
</template>
