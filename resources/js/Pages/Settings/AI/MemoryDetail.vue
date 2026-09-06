<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';

const props = defineProps({
  memory: Object,
  logs: Array,
});

const { t } = useI18n();

const weightColor = (w: number) => {
  if (w < 1) return 'bg-yellow-500/20 text-yellow-400';
  if (w < 3) return 'bg-blue-500/20 text-blue-400';
  return 'bg-green-500/20 text-green-400';
};

const actionBadge = (action: string) => {
  const badges: Record<string, string> = {
    CREATED: 'bg-green-600/20 text-green-400 border-green-600/30',
    REWARDED: 'bg-blue-600/20 text-blue-400 border-blue-600/30',
    DECAYED: 'bg-yellow-600/20 text-yellow-400 border-yellow-600/30',
    PRUNED: 'bg-red-600/20 text-red-400 border-red-600/30',
    UPDATED: 'bg-[var(--color-brand)]/20 text-purple-400 border-purple-600/30',
    DELETED: 'bg-red-600/20 text-red-400 border-red-600/30',
    CONFLICT: 'bg-orange-600/20 text-orange-400 border-orange-600/30',
    MERGE: 'bg-cyan-600/20 text-cyan-400 border-cyan-600/30',
  };
  return badges[action] || 'bg-gray-600/20 text-[var(--color-text-secondary)] border-gray-600/30';
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.memory.detail.title')" />

    <SettingsLayout>
      <template #header>
        <div class="flex items-center gap-3">
          <Link
            :href="route('settings.ai.memory.manage')"
            class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--color-surface-muted)] hover:bg-[var(--color-surface-muted)] transition-colors shrink-0"
          >
            <svg class="w-4 h-4 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </Link>
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-black text-[var(--color-text-primary)] tracking-tight leading-none">{{ t('settings.ai.memory.detail.title') }}</h2>
            <p class="text-sm text-[var(--color-text-secondary)] mt-1.5 font-medium truncate">{{ memory.keyword }}</p>
          </div>
        </div>
      </template>

      <!-- Memory Info Card -->
      <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl p-5 mb-4 sm:mb-6">
        <h3 class="text-sm font-semibold text-gray-300 mb-4">{{ t('settings.ai.memory.detail.info') }}</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.keyword') }}</span>
            <span class="text-[var(--color-text-primary)] font-medium">{{ memory.keyword }}</span>
          </div>
          <div v-if="memory.raw_subject">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.raw_subject') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.raw_subject }}</span>
          </div>
          <div v-if="memory.normalized_subject">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.normalized_subject') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.normalized_subject }}</span>
          </div>
          <div v-if="memory.category">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.category') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.category }}</span>
          </div>
          <div v-if="memory.wallet">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.wallet') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.wallet }}</span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.current_weight') }}</span>
            <span :class="['inline-block px-2 py-0.5 rounded text-xs font-medium mt-0.5', weightColor(memory.weight)]">
              {{ memory.weight.toFixed(2) }}
            </span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.hit_count') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.hit_count }}×</span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.created_at') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.created_at }}</span>
          </div>
          <div v-if="memory.last_applied_at">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.last_used') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.last_applied_at }}</span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.algorithm_version') }}</span>
            <span class="text-[var(--color-text-primary)] text-xs bg-[var(--color-surface-muted)] px-2 py-0.5 rounded">{{ memory.algorithm_version }}</span>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gray-300 mb-4">{{ t('settings.ai.memory.detail.timeline') }}</h3>

        <div v-if="logs && logs.length > 0" class="relative">
          <div class="absolute left-[17px] top-2 bottom-2 w-0.5 bg-[var(--color-surface-muted)]" />

          <div v-for="(log, i) in logs" :key="log.id" class="relative flex gap-4 pb-6 last:pb-0">
            <div class="shrink-0 relative z-10">
              <div class="w-[34px] h-[34px] rounded-full bg-[var(--color-surface-muted)] border-2 border-gray-900 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-[var(--color-text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="log.action === 'CREATED'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  <path v-else-if="log.action === 'PRUNED' || log.action === 'DELETED'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0 pt-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span :class="['px-2 py-0.5 rounded text-xs font-medium border', actionBadge(log.action)]">
                  {{ t(`settings.ai.memory.detail.action.${log.action}`) }}
                </span>
                <span class="text-xs text-[var(--color-text-muted)]">
                  {{ log.created_at_diff }}
                </span>
              </div>

              <div v-if="log.raw_subject" class="text-xs text-[var(--color-text-secondary)] mt-1.5">
                {{ log.raw_subject }}
              </div>

              <div v-if="(log.old_weight !== null && log.new_weight !== null) || (log.old_hit_count !== null && log.new_hit_count !== null)" class="flex gap-3 mt-1.5 text-xs text-[var(--color-text-muted)]">
                <span v-if="log.old_weight !== null && log.new_weight !== null">
                  {{ t('settings.ai.memory.detail.weight_change', { old: log.old_weight.toFixed(1), new: log.new_weight.toFixed(1) }) }}
                </span>
                <span v-if="log.old_hit_count !== null && log.new_hit_count !== null">
                  {{ t('settings.ai.memory.detail.hit_change', { old: log.old_hit_count, new: log.new_hit_count }) }}
                </span>
              </div>

              <div v-if="log.reason" class="text-xs text-[var(--color-text-muted)] mt-1 italic">
                {{ log.reason }}
              </div>

              <div v-if="log.source" class="text-xs text-gray-600 mt-1">
                {{ log.source }}
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <p class="text-sm text-[var(--color-text-muted)]">{{ t('settings.ai.memory.detail.timeline_empty') }}</p>
        </div>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
