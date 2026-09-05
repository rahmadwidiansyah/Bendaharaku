<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';

const props = defineProps<{
  logs: any[];
  pagination: { current_page: number; per_page: number; total: number; total_pages: number; has_more: boolean };
  filters: { filter: string };
  stats: Record<string, number>;
}>();

const { t } = useI18n();

const activeFilter = ref(props.filters?.filter || 'all');

const filterOptions = computed(() => [
  { value: 'all', label: t('settings.privacy.logs.filters.all'), icon: 'M4 6h16M4 12h16M4 18h16' },
  { value: 'transaction', label: t('settings.privacy.logs.filters.transaction'), icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
  { value: 'memory', label: t('settings.privacy.logs.filters.memory'), icon: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4' },
  { value: 'settings', label: t('settings.privacy.logs.filters.settings'), icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
  { value: 'ai', label: t('settings.privacy.logs.filters.ai'), icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
  { value: 'chat', label: t('settings.privacy.logs.filters.chat'), icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
]);

const typeBadge = (type: string) => {
  switch (type) {
    case 'transaction': return 'bg-emerald-500/15 text-emerald-400 border-emerald-500/20';
    case 'memory': return 'bg-violet-500/15 text-violet-400 border-violet-500/20';
    case 'settings': return 'bg-blue-500/15 text-blue-400 border-blue-500/20';
    case 'ai': return 'bg-indigo-500/15 text-indigo-400 border-indigo-500/20';
    case 'chat': return 'bg-cyan-500/15 text-cyan-400 border-cyan-500/20';
    default: return 'bg-gray-500/15 text-gray-400 border-gray-500/20';
  }
};

const typeDot = (type: string) => {
  switch (type) {
    case 'transaction': return 'bg-emerald-500';
    case 'memory': return 'bg-violet-500';
    case 'settings': return 'bg-blue-500';
    case 'ai': return 'bg-indigo-500';
    case 'chat': return 'bg-cyan-500';
    default: return 'bg-gray-500';
  }
};

const applyFilter = (val: string) => {
  activeFilter.value = val;
  router.get(route('settings.privacy.logs'), { filter: val }, { preserveState: false, replace: true });
};

const goPage = (p: number) => {
  if (p < 1 || p > props.pagination.total_pages) return;
  router.get(route('settings.privacy.logs'), { filter: activeFilter.value, page: p }, { preserveState: false });
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.privacy.logs.page_title')" />
    <SettingsLayout
      :title="t('settings.privacy.logs.page_title')"
      :description="t('settings.privacy.logs.page_desc')"
    >
      <!-- Stats -->
      <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-5">
        <button
          v-for="opt in filterOptions"
          :key="opt.value"
          @click="applyFilter(opt.value)"
          :class="[
            'rounded-xl border p-3 text-left transition-all',
            activeFilter === opt.value
              ? 'bg-violet-600 border-violet-500 text-white shadow-lg shadow-violet-600/20'
              : 'bg-gray-900 border-white/5 hover:border-white/10 hover:bg-gray-800 text-gray-300'
          ]"
        >
          <p :class="['text-[10px] font-bold tracking-widest uppercase', activeFilter === opt.value ? 'text-violet-200' : 'text-gray-500']">{{ opt.label }}</p>
          <p class="text-lg font-black tabular-nums mt-1">{{ stats[opt.value] ?? 0 }}</p>
        </button>
      </div>

      <!-- Filter pills -->
      <div class="flex gap-1.5 overflow-x-auto pb-2 mb-4 -mx-1 px-1">
        <button
          v-for="opt in filterOptions"
          :key="'pill-' + opt.value"
          @click="applyFilter(opt.value)"
          :class="[
            'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-colors shrink-0',
            activeFilter === opt.value
              ? 'bg-white text-gray-900 border-white'
              : 'bg-gray-800 text-gray-400 border-white/10 hover:text-white hover:bg-gray-700'
          ]"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" :d="opt.icon" />
          </svg>
          {{ opt.label }}
        </button>
      </div>

      <!-- Timeline -->
      <div v-if="logs && logs.length > 0" class="relative">
        <!-- vertical line -->
        <div class="absolute left-[15px] top-2 bottom-2 w-px bg-white/5 hidden sm:block" />
        <div class="space-y-3">
          <div
            v-for="item in logs"
            :key="item.id"
            class="relative bg-gray-900 border border-white/5 rounded-2xl p-4 hover:border-white/10 transition-colors sm:pl-10"
          >
            <!-- dot -->
            <span :class="['hidden sm:block absolute left-[11px] top-5 w-2 h-2 rounded-full ring-4 ring-gray-900', typeDot(item.type)]" />

            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black tracking-widest uppercase border', typeBadge(item.type)]">
                    {{ item.type_label }}
                  </span>
                  <span class="text-[11px] text-gray-500">{{ item.created_at_human }}</span>
                  <span v-if="item.metadata?.amount" class="text-[11px] font-bold text-white">
                    Rp {{ Number(item.metadata.amount).toLocaleString('id-ID') }}
                  </span>
                </div>
                <h3 class="text-sm font-bold text-white leading-tight truncate">{{ item.title }}</h3>
                <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ item.description }}</p>
                <div v-if="item.metadata" class="flex flex-wrap gap-1.5 mt-2">
                  <span v-if="item.metadata.category" class="px-2 py-0.5 bg-white/5 border border-white/5 rounded-full text-[11px] text-gray-300"># {{ item.metadata.category }}</span>
                  <span v-if="item.metadata.source_wallet" class="px-2 py-0.5 bg-white/5 border border-white/5 rounded-full text-[11px] text-gray-300">{{ item.metadata.source_wallet }}</span>
                  <span v-if="item.metadata.source" class="px-2 py-0.5 bg-white/5 border border-white/5 rounded-full text-[11px] text-gray-500">src: {{ item.metadata.source }}</span>
                  <span v-if="item.metadata.provider" class="px-2 py-0.5 bg-white/5 border border-white/5 rounded-full text-[11px] text-indigo-300">{{ item.metadata.provider }}</span>
                </div>
              </div>
              <div class="shrink-0 text-right hidden sm:block">
                <p class="text-[11px] text-gray-500 whitespace-nowrap">{{ new Date(item.created_at_raw).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                <p class="text-[11px] text-gray-600">{{ new Date(item.created_at_raw).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="text-center py-16 border border-dashed border-white/10 rounded-2xl bg-gray-900/50">
        <div class="w-12 h-12 mx-auto rounded-xl bg-white/5 flex items-center justify-center mb-3">
          <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-sm font-bold text-white">{{ t('settings.privacy.logs.empty_title') }}</p>
        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">{{ t('settings.privacy.logs.empty_desc') }}</p>
        <p v-if="activeFilter !== 'all'" class="text-xs text-gray-600 mt-3">{{ t('settings.privacy.logs.empty_filter') }} <span class="font-bold text-gray-400">{{ activeFilter }}</span> — <button @click="applyFilter('all')" class="underline hover:text-white">{{ t('settings.privacy.logs.show_all') }}</button></p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total_pages > 1" class="mt-6 flex items-center justify-between">
        <p class="text-xs text-gray-500">{{ t('settings.privacy.logs.pagination.page') }} {{ pagination.current_page }} {{ t('settings.privacy.logs.pagination.of') }} {{ pagination.total_pages }} • {{ pagination.total }} {{ t('settings.privacy.logs.pagination.activities') }}</p>
        <div class="flex gap-2">
          <button
            @click="goPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="px-3 py-1.5 rounded-lg bg-gray-800 border border-white/10 text-xs font-bold text-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-700"
          >
            Prev
          </button>
          <button
            @click="goPage(pagination.current_page + 1)"
            :disabled="!pagination.has_more"
            class="px-3 py-1.5 rounded-lg bg-violet-600 border border-violet-500 text-xs font-bold text-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-violet-700"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Hint diagnostic — hanya tampil saat filter memory atau all & memory kosong, agar tidak bocor ke transaksi/settings/ai/chat -->
      <div v-if="activeFilter === 'memory' || (activeFilter === 'all' && (stats.memory ?? 0) === 0)" class="mt-6 rounded-xl border border-amber-500/20 bg-amber-500/5 px-4 py-3">
        <p class="text-xs font-bold text-amber-300">{{ t('settings.privacy.logs.hint_title') }}</p>
        <p class="text-xs text-amber-200/70 mt-1 leading-relaxed">
          {{ t('settings.privacy.logs.hint_desc') }}
        </p>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
