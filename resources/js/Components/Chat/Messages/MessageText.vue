<script setup>
defineProps({
    component: { type: Object, required: true },
})
</script>

<template>
    <p
        :class="[
            'text-sm leading-relaxed whitespace-pre-wrap break-words',
            component.bold ? 'font-semibold text-white' : 'text-gray-200'
        ]"
    >{{ component.text }}</p>
</template>
