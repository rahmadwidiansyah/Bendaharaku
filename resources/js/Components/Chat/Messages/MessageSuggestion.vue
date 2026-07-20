<script setup>
defineProps({
    component: { type: Object, required: true },
})
</script>

<template>
    <div class="flex items-start gap-2.5 text-indigo-300">
        <p class="text-sm leading-relaxed">{{ component.message }}</p>
    </div>
</template>
