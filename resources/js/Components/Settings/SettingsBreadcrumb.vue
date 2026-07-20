<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Breadcrumb {
  label: string;
  href?: string;
}

// Accept both `breadcrumbs` and `items` for backward compatibility
interface Props {
  breadcrumbs?: Breadcrumb[];
  items?: Breadcrumb[];
}

const props = withDefaults(defineProps<Props>(), {
  breadcrumbs: undefined,
  items: undefined,
});

// Only show the LAST crumb — keeps breadcrumb short: "Settings / Profile"
const lastCrumb = computed<Breadcrumb | null>(() => {
  const list = props.breadcrumbs ?? props.items ?? [];
  return list.length ? list[list.length - 1] : null;
});
</script>

<template>
  <nav aria-label="breadcrumb" class="flex items-center gap-1.5 text-sm">
    <Link
      href="/settings"
      class="text-gray-500 hover:text-gray-300 transition-colors text-xs"
    >
      {{ t('settings.title') }}
    </Link>

    <template v-if="lastCrumb">
      <span class="text-gray-700 text-xs">/</span>
      <Link
        v-if="lastCrumb.href"
        :href="lastCrumb.href"
        class="text-gray-400 hover:text-gray-200 transition-colors text-xs"
      >
        {{ lastCrumb.label }}
      </Link>
      <span v-else class="text-gray-300 font-medium text-xs">{{ lastCrumb.label }}</span>
    </template>
  </nav>
</template>

<style scoped></style>
