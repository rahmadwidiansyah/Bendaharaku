<script setup lang="ts">
interface Props {
  title: string;
  description?: string;
}

withDefaults(defineProps<Props>(), {
  description: '',
});
</script>

<template>
  <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl sm:rounded-2xl p-3 sm:p-6 space-y-3 sm:space-y-4 shadow-card">
    <div v-if="title || description">
      <h3 class="text-xs sm:text-sm font-bold text-[var(--color-text-primary)]">{{ title }}</h3>
      <p v-if="description" class="text-2xs text-[var(--color-text-muted)] mt-1">{{ description }}</p>
    </div>

    <div class="pt-1 sm:pt-2">
      <slot />
    </div>
  </div>
</template>

<style scoped>
</style>
