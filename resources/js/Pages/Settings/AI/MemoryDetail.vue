<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Badge from '@/Components/Badge.vue';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
  memory: Object as any,
  logs: Array as any,
});

const { t } = useI18n();
const { showToast } = useToast();

const weightColor = (w: number) => {
  if (w < 1) return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
  if (w < 3) return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
  return 'bg-green-500/20 text-green-400 border-green-500/30';
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
    REBUILT: 'bg-cyan-600/20 text-cyan-400 border-cyan-600/30',
  };
  return badges[action] || 'bg-gray-600/20 text-[var(--color-text-secondary)] border-gray-600/30';
};

const targetTypeBadge = (type: string) => {
  if (type === 'wallet') return 'transfer';
  if (type === 'category') return 'brand';
  return 'neutral';
};

const showDelete = ref(false);
const isDeleting = ref(false);
function confirmDelete() {
  if (!props.memory?.id || isDeleting.value) return;
  isDeleting.value = true;
  router.delete(route('settings.ai.memory.destroy', { id: props.memory.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showToast(t('settings.ai.memory.detail.deleted'), 'success');
      showDelete.value = false;
    },
    onError: () => showToast(t('toast.error'), 'error'),
    onFinish: () => { isDeleting.value = false; },
  });
}

const openMeta = ref<number | null>(null);
function toggleMeta(id: number) {
  openMeta.value = openMeta.value === id ? null : id;
}
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
          <button
            type="button"
            @click="showDelete = true"
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-expense-bg border border-expense-border text-expense-text hover:bg-expense-bg-hover text-xs font-bold transition-colors shrink-0"
          >
            <AppIcon icon="trash-2" iconClass="w-3.5 h-3.5" />
            {{ t('settings.ai.memory.detail.delete_button') }}
          </button>
        </div>
      </template>

      <!-- Hero Card -->
      <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-2xl p-5 mb-4 sm:mb-6">
        <div class="flex flex-wrap items-center gap-2 mb-3">
          <Badge :variant="targetTypeBadge(memory.target_type)" size="sm" pill>
            <AppIcon :icon="memory.target_type === 'wallet' ? 'wallet' : 'tag'" iconClass="w-3 h-3" />
            {{ memory.target_type ?? 'category' }}
          </Badge>
          <Badge variant="neutral" size="sm" pill>
            {{ memory.algorithm_version }}
          </Badge>
          <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border', weightColor(memory.weight)]">
            w {{ memory.weight.toFixed(2) }} · {{ memory.hit_count }}×
          </span>
        </div>
        <h3 class="text-xl sm:text-2xl font-black text-[var(--color-text-primary)] tracking-tight leading-tight break-words">{{ memory.keyword }}</h3>
        <p v-if="memory.raw_subject && memory.raw_subject !== memory.keyword" class="text-sm text-[var(--color-text-secondary)] mt-1.5 break-words">{{ memory.raw_subject }}</p>
        <p v-if="memory.normalized_subject && memory.normalized_subject !== memory.keyword" class="text-xs text-[var(--color-text-muted)] mt-1 break-words">→ {{ memory.normalized_subject }}</p>

        <!-- Effective weight + decay bar -->
        <div class="mt-4">
          <div class="flex items-center justify-between text-xs mb-1.5">
            <span class="text-[var(--color-text-muted)] font-medium">{{ t('settings.ai.memory.detail.effective_weight') }}</span>
            <span class="font-bold text-[var(--color-text-primary)] tabular-nums">{{ (memory.effective_weight ?? memory.weight).toFixed(2) }} / 10.00</span>
          </div>
          <div class="h-2 rounded-full bg-[var(--color-surface-muted)] border border-[var(--color-border-subtle)] overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="weightColor(memory.effective_weight ?? memory.weight).split(' ')[0]"
              :style="{ width: Math.min(100, ((memory.effective_weight ?? memory.weight) / 10) * 100) + '%', background: 'var(--color-brand)' }"
            />
          </div>
          <p v-if="memory.days_since !== null && memory.days_since !== undefined" class="text-2xs text-[var(--color-text-muted)] mt-1.5">
            {{ t('settings.ai.memory.detail.days_since', { days: memory.days_since }) }} · {{ memory.last_applied_at ?? memory.created_at }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2 mt-4">
          <span v-if="memory.category" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] text-xs text-[var(--color-text-secondary)]">
            <AppIcon icon="folder" iconClass="w-3 h-3 text-[var(--color-text-muted)]" />
            {{ memory.category }}
          </span>
          <span v-if="memory.wallet" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] text-xs text-[var(--color-text-secondary)]">
            <AppIcon icon="wallet" iconClass="w-3 h-3 text-[var(--color-text-muted)]" />
            {{ memory.wallet }}
          </span>
          <span v-if="memory.contributions_count !== undefined" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-medium">
            {{ memory.contributions_count }} {{ t('settings.ai.memory.detail.contributions_count') }}
          </span>
        </div>
      </div>

      <!-- Detail Grid -->
      <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl p-5 mb-4 sm:mb-6">
        <h3 class="text-sm font-semibold text-gray-300 mb-4">{{ t('settings.ai.memory.detail.info') }}</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.keyword') }}</span>
            <span class="text-[var(--color-text-primary)] font-medium break-words">{{ memory.keyword }}</span>
          </div>
          <div v-if="memory.keyword_pattern && memory.keyword_pattern !== memory.keyword">
            <span class="text-[var(--color-text-muted)] text-xs block">Keyword Pattern</span>
            <span class="text-[var(--color-text-primary)] font-mono text-xs break-words">{{ memory.keyword_pattern }}</span>
          </div>
          <div v-if="memory.memory_keyword && memory.memory_keyword !== memory.keyword">
            <span class="text-[var(--color-text-muted)] text-xs block">Memory Keyword</span>
            <span class="text-[var(--color-text-primary)] font-mono text-xs">{{ memory.memory_keyword }}</span>
          </div>
          <div v-if="memory.raw_subject">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.raw_subject') }}</span>
            <span class="text-[var(--color-text-primary)] break-words">{{ memory.raw_subject }}</span>
          </div>
          <div v-if="memory.normalized_subject">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.normalized_subject') }}</span>
            <span class="text-[var(--color-text-primary)] break-words">{{ memory.normalized_subject }}</span>
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
            <span :class="['inline-block px-2 py-0.5 rounded text-xs font-medium mt-0.5 border', weightColor(memory.weight)]">
              {{ memory.weight.toFixed(2) }}
            </span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.effective_weight') }}</span>
            <span class="text-[var(--color-text-primary)] font-medium">{{ (memory.effective_weight ?? memory.weight).toFixed(2) }}</span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.hit_count') }}</span>
            <span class="text-[var(--color-text-primary)]">{{ memory.hit_count }}×</span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.created_at') }}</span>
            <span class="text-[var(--color-text-primary)] text-xs">{{ memory.created_at }}</span>
          </div>
          <div v-if="memory.last_applied_at">
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.last_used') }}</span>
            <span class="text-[var(--color-text-primary)] text-xs">{{ memory.last_applied_at }}</span>
          </div>
          <div>
            <span class="text-[var(--color-text-muted)] text-xs block">{{ t('settings.ai.memory.detail.algorithm_version') }}</span>
            <span class="text-[var(--color-text-primary)] text-xs bg-[var(--color-surface-muted)] px-2 py-0.5 rounded">{{ memory.algorithm_version }}</span>
          </div>
        </div>
      </div>

      <!-- Contributions -->
      <div v-if="memory.contributions && memory.contributions.length > 0" class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl p-5 mb-4 sm:mb-6">
        <h3 class="text-sm font-semibold text-gray-300 mb-4">{{ t('settings.ai.memory.detail.contributions_title') }} <span class="text-[var(--color-text-muted)] font-normal">({{ memory.contributions.length }})</span></h3>
        <div class="divide-y divide-[var(--color-border-subtle)]">
          <div v-for="c in memory.contributions" :key="c.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
            <div class="w-8 h-8 rounded-full bg-blue-500/10 border border-blue-500/20 flex items-center justify-center shrink-0">
              <AppIcon :icon="c.target_type === 'wallet' ? 'wallet' : 'tag'" iconClass="w-4 h-4 text-blue-400" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-mono font-medium text-[var(--color-text-primary)] truncate">{{ c.keyword }}</span>
                <Badge :variant="c.target_type === 'wallet' ? 'transfer' : 'brand'" size="sm">{{ c.target_name }}</Badge>
              </div>
              <div class="flex items-center gap-2 mt-1 text-2xs text-[var(--color-text-muted)]">
                <span v-if="c.transaction_id" class="inline-flex items-center gap-1">
                  <AppIcon icon="receipt" iconClass="w-3 h-3" />
                  #{{ c.transaction_id }}
                </span>
                <span>{{ c.source }}</span>
                <span>· {{ c.created_at_diff }}</span>
              </div>
            </div>
            <span class="text-xs font-bold text-income-text shrink-0">+{{ c.weight_delta.toFixed(1) }}</span>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl p-5 mb-4 sm:mb-6">
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
                <span v-if="log.transaction_id" class="text-xs text-[var(--color-text-muted)]">· #{{ log.transaction_id }}</span>
              </div>

              <div v-if="log.raw_subject" class="text-xs text-[var(--color-text-secondary)] mt-1.5 break-words">
                {{ log.raw_subject }}
              </div>

              <div v-if="(log.old_weight !== null && log.new_weight !== null) || (log.old_hit_count !== null && log.new_hit_count !== null)" class="flex gap-3 mt-1.5 text-xs text-[var(--color-text-muted)]">
                <span v-if="log.old_weight !== null && log.new_weight !== null">
                  {{ t('settings.ai.memory.detail.weight_change', { old: log.old_weight.toFixed(1), new: (log.new_weight ?? 0).toFixed(1) }) }}
                </span>
                <span v-if="log.old_hit_count !== null && log.new_hit_count !== null">
                  {{ t('settings.ai.memory.detail.hit_change', { old: log.old_hit_count, new: log.new_hit_count }) }}
                </span>
              </div>

              <div v-if="log.reason" class="text-xs text-[var(--color-text-muted)] mt-1 italic break-words">
                {{ log.reason }}
              </div>

              <div v-if="log.source" class="text-xs text-gray-600 mt-1">
                {{ log.source }}
              </div>

              <div v-if="log.metadata && Object.keys(log.metadata).length" class="mt-1.5">
                <button type="button" @click="toggleMeta(log.id)" class="text-2xs text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] inline-flex items-center gap-1">
                  <AppIcon :icon="openMeta === log.id ? 'chevron-up' : 'chevron-down'" iconClass="w-3 h-3" />
                  Metadata
                </button>
                <pre v-if="openMeta === log.id" class="mt-1.5 text-2xs font-mono text-[var(--color-text-secondary)] bg-[var(--color-surface-muted)] rounded-lg p-2 border border-[var(--color-border-default)] overflow-x-auto whitespace-pre-wrap break-words">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <p class="text-sm text-[var(--color-text-muted)]">{{ t('settings.ai.memory.detail.timeline_empty') }}</p>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="bg-expense-bg/30 border border-expense-border rounded-2xl p-5">
        <h3 class="text-sm font-bold text-expense-text flex items-center gap-2">
          <AppIcon icon="triangle-alert" iconClass="w-4 h-4" />
          {{ t('settings.ai.memory.detail.danger_title') }}
        </h3>
        <p class="text-xs text-[var(--color-text-secondary)] mt-1.5 leading-relaxed">{{ t('settings.ai.memory.detail.danger_desc') }}</p>
        <button
          type="button"
          @click="showDelete = true"
          class="mt-3 w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-expense-bg border border-expense-border text-expense-text hover:bg-expense-bg-hover text-xs font-bold transition-colors"
        >
          <AppIcon icon="trash-2" iconClass="w-4 h-4" />
          {{ t('settings.ai.memory.detail.delete_button') }}
        </button>
      </div>
    </SettingsLayout>

    <BaseModal :show="showDelete" max-width="sm" @close="showDelete = false">
      <div class="text-center px-1">
        <div class="w-14 h-14 rounded-full bg-expense-bg text-expense-text mx-auto flex items-center justify-center mb-4 border border-expense-border">
          <AppIcon icon="trash-2" iconClass="w-7 h-7" />
        </div>
        <h3 class="text-base font-black text-[var(--color-text-primary)] mb-2">{{ t('settings.ai.memory.detail.delete_confirm_title') }}</h3>
        <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed break-words">{{ t('settings.ai.memory.detail.delete_confirm_desc', { keyword: memory.keyword, count: memory.contributions_count ?? 0 }) }}</p>
        <p class="text-2xs text-expense-text/80 mt-2">{{ t('settings.ai.memory.detail.delete_confirm_warn') }}</p>
      </div>
      <template #footer>
        <button type="button" :disabled="isDeleting" @click="showDelete = false" class="flex-1 py-3 rounded-xl bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] text-[var(--color-text-secondary)] text-xs font-bold uppercase tracking-widest hover:border-[var(--color-border-strong)] transition-all disabled:opacity-50">
          {{ t('common.cancel') }}
        </button>
        <button type="button" :disabled="isDeleting" @click="confirmDelete" class="flex-1 py-3 rounded-xl bg-expense-chart text-white text-xs font-black uppercase tracking-widest hover:opacity-90 transition-all disabled:opacity-50 inline-flex items-center justify-center gap-2">
          <svg v-if="isDeleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ t('settings.ai.memory.detail.delete_confirm') }}
        </button>
      </template>
    </BaseModal>
  </AuthenticatedLayout>
</template>
