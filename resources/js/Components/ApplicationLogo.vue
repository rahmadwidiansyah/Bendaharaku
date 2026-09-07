<script setup>
const appIcon = '/icons/logo.png';
</script>

<template>
    <div class="flex items-center justify-center bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl overflow-hidden p-2">
        <img :src="appIcon" alt="Bendaharaku" class="w-full h-full object-contain" />
    </div>
</template>