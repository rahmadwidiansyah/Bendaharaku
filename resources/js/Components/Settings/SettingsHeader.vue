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
  <div>
    <h1 class="text-2xl font-black text-white tracking-tight leading-none">{{ title }}</h1>
    <p v-if="description" class="text-sm text-gray-400 mt-1.5 font-medium">{{ description }}</p>
  </div>
</template>

<style scoped>
</style>
