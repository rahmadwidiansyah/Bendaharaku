<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useScrollRestore } from '@/Composables/useScrollRestore.js';

const { t } = useI18n();

interface Props {
  title?: string;
  description?: string;
  hideBack?: boolean;
}

withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
  hideBack: false,
});

useScrollRestore();
</script>

<template>
  <div class="w-full max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6">

    <!-- Page title -->
    <div v-if="title" class="mb-4 sm:mb-6">
      <h1 class="text-lg sm:text-2xl font-black text-white tracking-tight leading-none">{{ title }}</h1>
      <p v-if="description" class="text-xs sm:text-sm text-gray-400 mt-1 sm:mt-1.5 font-medium">{{ description }}</p>
    </div>

    <!-- Page content -->
    <div class="space-y-4">
      <slot />
    </div>

  </div>
</template>
