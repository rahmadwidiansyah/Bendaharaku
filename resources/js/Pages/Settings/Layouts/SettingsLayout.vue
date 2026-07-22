<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
  <div class="w-full max-w-4xl mx-auto px-4 sm:px-6 py-6">

    <!-- Back link -->
    <Link
      v-if="!hideBack"
      href="/settings"
      class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-200 transition-colors mb-6 group"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7 7l-7-7 7-7" />
      </svg>
      <span>{{ t('settings.title') }}</span>
    </Link>

    <!-- Page title -->
    <div v-if="title" class="mb-6">
      <h1 class="text-2xl font-black text-white tracking-tight leading-none">{{ title }}</h1>
      <p v-if="description" class="text-sm text-gray-400 mt-1.5 font-medium">{{ description }}</p>
    </div>

    <!-- Page content -->
    <div class="space-y-4">
      <slot />
    </div>

  </div>
</template>
