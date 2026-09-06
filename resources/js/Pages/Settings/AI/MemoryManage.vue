<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';

const props = defineProps({
  memories: Object,
  filters: Object,
});

const { t } = useI18n();

const search = ref(props.filters?.search || '');
const activeFilter = ref(props.filters?.filter || 'all');

let debounce: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
  if (debounce) clearTimeout(debounce);
  debounce = setTimeout(() => applyFilters(), 300);
});

const applyFilters = (filter?: string) => {
  if (filter !== undefined) activeFilter.value = filter;
  router.get(
    route('settings.ai.memory.manage'),
    {
      search: search.value,
      filter: activeFilter.value,
    },
    { preserveState: true, replace: true },
  );
};

const filters = [
  { value: 'all', label: t('settings.ai.memory.manage.filter_all') },
  { value: 'active', label: t('settings.ai.memory.manage.filter_active') },
  { value: 'low', label: t('settings.ai.memory.manage.filter_low') },
  { value: 'high', label: t('settings.ai.memory.manage.filter_high') },
];

const weightLabel = (w: number) => {
  if (w < 1) return t('settings.ai.memory.manage.weight_low');
  if (w < 3) return t('settings.ai.memory.manage.weight_medium');
  return t('settings.ai.memory.manage.weight_high');
};

const weightColor = (w: number) => {
  if (w < 1) return 'bg-yellow-500/20 text-yellow-400';
  if (w < 3) return 'bg-blue-500/20 text-blue-400';
  return 'bg-green-500/20 text-green-400';
};

const data = ref(props.memories?.data || []);
const links = ref(props.memories?.links || []);
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.memory.manage.title')" />

    <SettingsLayout>
      <template #header>
        <div class="flex items-center gap-3">
          <Link
            :href="route('settings.ai.memory')"
            class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--color-surface-muted)] hover:bg-[var(--color-surface-muted)] transition-colors shrink-0"
          >
            <svg class="w-4 h-4 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </Link>
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-black text-[var(--color-text-primary)] tracking-tight leading-none">{{ t('settings.ai.memory.manage.title') }}</h2>
            <p class="text-2xs sm:text-sm text-[var(--color-text-secondary)] mt-1.5 font-medium">{{ t('settings.ai.memory.manage.description') }}</p>
          </div>
        </div>
      </template>

      <!-- Search & Filters -->
      <div class="space-y-3 mb-6">
        <div class="relative">
          <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="search"
            :placeholder="t('settings.ai.memory.manage.search_placeholder')"
            class="w-full pl-10 pr-4 py-2.5 bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-lg text-sm text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:border-transparent transition-all"
          />
        </div>

        <div class="flex gap-1.5 overflow-x-auto pb-1">
          <button
            v-for="f in filters"
            :key="f.value"
            @click="applyFilters(f.value)"
            :class="[
              'px-3.5 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-colors shrink-0',
              activeFilter === f.value
                ? 'bg-[var(--color-brand)] text-[var(--color-text-primary)]'
                : 'bg-[var(--color-surface-muted)] text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-surface-muted)]',
            ]"
          >
            {{ f.label }}
          </button>
        </div>
      </div>

      <!-- List -->
      <div v-if="data.length > 0" class="space-y-2">
        <div
          v-for="item in data"
          :key="item.id"
          class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl p-4 hover:border-[var(--color-border-default)] transition-colors"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <h3 class="text-xs sm:text-sm font-semibold text-[var(--color-text-primary)] truncate">{{ item.keyword }}</h3>
              <p v-if="item.normalized_subject && item.normalized_subject !== item.keyword" class="text-xs text-[var(--color-text-muted)] truncate mt-0.5">{{ item.raw_subject }}</p>
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs text-[var(--color-text-secondary)]">
                <span v-if="item.category">
                  <span class="text-gray-600">#</span> {{ item.category }}
                </span>
                <span v-if="item.wallet">
                  <svg class="inline w-3 h-3 mr-0.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                  </svg>
                  {{ item.wallet }}
                </span>
                <span>
                  <template v-if="item.last_applied_at">{{ item.last_applied_at }}</template>
                  <template v-else>{{ t('common.never') }}</template>
                </span>
              </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <div class="text-right">
                <div :class="['inline-block px-2 py-0.5 rounded text-xs font-medium', weightColor(item.weight)]">
                  {{ item.weight.toFixed(1) }}
                </div>
                <div class="text-[10px] text-[var(--color-text-muted)] mt-0.5">
                  {{ item.hit_count }}×
                </div>
              </div>
              <Link
                :href="route('settings.ai.memory.detail', { id: item.id })"
                class="flex items-center justify-center w-8 h-8 rounded-lg bg-[var(--color-surface-muted)] hover:bg-[var(--color-surface-muted)] transition-colors"
              >
                <svg class="w-4 h-4 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <svg class="w-16 h-16 mx-auto text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-300">{{ t('settings.ai.memory.manage.empty_title') }}</h3>
        <p class="text-sm text-[var(--color-text-muted)] mt-2 max-w-sm mx-auto">{{ t('settings.ai.memory.manage.empty_description') }}</p>
      </div>

      <!-- Pagination -->
      <div v-if="links && links.length > 3" class="mt-8 flex justify-center gap-1 flex-wrap">
        <template v-for="(link, k) in links" :key="k">
          <Link
            v-if="link.url"
            :href="link.url"
            v-html="link.label"
            :class="['px-3 py-1 text-sm rounded-md', link.active ? 'bg-[var(--color-brand)] text-[var(--color-text-primary)] font-bold' : 'bg-[var(--color-surface-muted)] text-[var(--color-text-secondary)] border border-[var(--color-border-default)] hover:text-[var(--color-text-primary)]']"
          />
          <span v-else v-html="link.label" class="px-3 py-1 text-sm rounded-md bg-[var(--color-surface-muted)] text-[var(--color-text-secondary)] border border-[var(--color-border-default)]" />
        </template>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
