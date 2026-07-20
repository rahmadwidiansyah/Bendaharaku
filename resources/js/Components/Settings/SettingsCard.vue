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
  <div class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-5 sm:p-6 space-y-4">
    <div v-if="title || description">
      <h3 class="text-sm font-bold text-white">{{ title }}</h3>
      <p v-if="description" class="text-2xs text-gray-500 mt-1">{{ description }}</p>
    </div>

    <div class="pt-2">
      <slot />
    </div>
  </div>
</template>

<style scoped>
</style>
