<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import TextInput from '@/Components/TextInput.vue';
import InfoPopover from '@/Components/Settings/InfoPopover.vue';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { t, locale } = useI18n();
const { showToast } = useToast();

const formatDateTime = (iso: string) => {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  try {
    return d.toLocaleString(locale.value === 'en' ? 'en-US' : 'id-ID', { dateStyle: 'short', timeStyle: 'short' });
  } catch {
    return iso;
  }
};

const providers = ['gemini', 'openai', 'deepseek', 'openai-compatible'];

interface Props {
  aiAccounts?: Record<string, any>[];
  aiRouting?: Record<string, any>;
  aiCandidates?: Record<string, any>[];
  aiSkipped?: Record<string, any>[];
}

const props = withDefaults(defineProps<Props>(), {
  aiAccounts: () => [],
  aiRouting: () => ({}),
  aiCandidates: () => [],
  aiSkipped: () => [],
});

const poolAccounts = ref<Record<string, any>[]>([...(props.aiAccounts || [])]);
const poolCandidates = ref<Record<string, any>[]>([...(props.aiCandidates || [])]);
const poolSkipped = ref<Record<string, any>[]>([...(props.aiSkipped || [])]);
const newAccount = ref({ provider: 'gemini', alias: '', api_key: '', base_url: '', priority: 1 });
const addingAccount = ref(false);
const routingForm = ref({
  mode: (props.aiRouting as any)?.mode || 'automatic',
  primary_provider: (props.aiRouting as any)?.primary_provider || '',
  primary_model: (props.aiRouting as any)?.primary_model || '',
  free_policy: (props.aiRouting as any)?.free_policy || 'free_first',
  allow_paid_fallback: !!(props.aiRouting as any)?.allow_paid_fallback,
  fallback_models: ([...((props.aiRouting as any)?.fallback_models || [])] as Record<string, any>[]).slice(0, 3),
});
const accountModels = ref<Record<number, Record<string, any>[]>>({});
const expandedAccount = ref<number | null>(null);
const loadingModelsFor = ref<number | null>(null);
const savingRouting = ref(false);
const busyAccountId = ref<number | null>(null);
const removeTarget = ref<Record<string, any> | null>(null);
const removingAccount = ref(false);
const accountNotices = ref<Record<number, Record<string, any>>>({});
const routingNotice = ref<Record<string, any> | null>(null);

const healthLabel = (status: string) => {
  const key = `settings.ai.status.health.${status}`;
  const label = t(key);
  return label === key ? status : label;
};

const healthVariant = (status: string) => {
  if (status === 'healthy') return 'success';
  if (status === 'invalid' || status === 'disabled') return 'danger';
  if (status === 'rate_limited') return 'warning';
  return 'neutral';
};

const noticeFor = (id: number) => accountNotices.value[id] || null;

function setAccountNotice(id: number, notice: Record<string, any>) {
  accountNotices.value = { ...accountNotices.value, [id]: notice };
}

function clearAccountNotice(id: number) {
  if (!accountNotices.value[id]) return;
  const next = { ...accountNotices.value };
  delete next[id];
  accountNotices.value = next;
}

async function refreshPool() {
  try {
    const { data } = await axios.get(route('settings.ai.accounts.index'));
    poolAccounts.value = data.accounts || [];
    poolCandidates.value = (data.candidates || []).slice(0, 8);
    poolSkipped.value = (data.skipped || []).slice(0, 20);
    if (data.routing) {
      routingForm.value.mode = data.routing.mode || 'automatic';
      routingForm.value.primary_provider = data.routing.primary_provider || '';
      routingForm.value.primary_model = data.routing.primary_model || '';
      routingForm.value.free_policy = data.routing.free_policy || 'free_first';
      routingForm.value.allow_paid_fallback = !!data.routing.allow_paid_fallback;
      routingForm.value.fallback_models = ([...(data.routing.fallback_models || [])] as Record<string, any>[]).slice(0, 3);
    }
  } catch (err: any) {
    showToast(err.response?.data?.message || t('settings.ai.models.messages.load_failed'), 'error');
  }
}

async function addAccount() {
  if (addingAccount.value || !newAccount.value.api_key) return;
  addingAccount.value = true;
  try {
    await axios.post(route('settings.ai.accounts.store'), { ...newAccount.value });
    showToast(t('settings.ai.models.messages.account_added'), 'success');
    newAccount.value = { provider: 'gemini', alias: '', api_key: '', base_url: '', priority: 1 };
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || t('settings.ai.models.messages.add_failed'), 'error');
  } finally {
    addingAccount.value = false;
  }
}

async function testAccount(id: number) {
  busyAccountId.value = id;
  try {
    const { data } = await axios.post(route('settings.ai.accounts.test', { id }));
    setAccountNotice(id, {
      tone: 'success',
      titleKey: 'settings.ai.models.test_button',
      lines: [
        data.message,
        data.model ? `${data.model}${data.latency_ms != null ? ` · ${data.latency_ms} ms` : ''}` : null,
      ].filter(Boolean),
    });
    showToast(t('settings.ai.models.messages.test_ok'), 'success');
    await refreshPool();
  } catch (err: any) {
    const data = err.response?.data || {};
    setAccountNotice(id, {
      tone: 'danger',
      titleKey: 'settings.ai.models.test_button',
      lines: [
        data.message || t('settings.ai.models.messages.test_failed'),
        data.model ? `${data.model}${data.latency_ms != null ? ` · ${data.latency_ms} ms` : ''}` : null,
        data.error_code,
      ].filter(Boolean),
    });
    showToast(t('settings.ai.models.messages.test_failed'), 'error');
    await refreshPool();
  } finally {
    busyAccountId.value = null;
  }
}

async function refreshDiscovery(id: number) {
  busyAccountId.value = id;
  try {
    const { data } = await axios.post(route('settings.ai.accounts.refresh', { id }));
    const discovery = data.discovery || {};
    if (discovery.error) {
      setAccountNotice(id, {
        tone: 'danger',
        titleKey: 'settings.ai.models.pool.actions.refresh',
        lines: [discovery.error],
      });
      showToast(t('settings.ai.models.messages.refresh_failed'), 'error');
    } else {
      setAccountNotice(id, {
        tone: 'success',
        titleKey: 'settings.ai.models.pool.actions.refresh',
        lines: [
          t('settings.ai.models.messages.refresh_ok', { count: discovery.eligible ?? 0 }),
          discovery.source ? `${discovery.source} · ${discovery.discovered ?? 0}` : null,
        ].filter(Boolean),
      });
      showToast(t('settings.ai.models.messages.refresh_ok', { count: discovery.eligible ?? 0 }), 'success');
    }
    await refreshPool();
  } catch (err: any) {
    const message = err.response?.data?.message || t('settings.ai.models.messages.refresh_failed');
    setAccountNotice(id, {
      tone: 'danger',
      titleKey: 'settings.ai.models.pool.actions.refresh',
      lines: [message],
    });
    showToast(t('settings.ai.models.messages.refresh_failed'), 'error');
  } finally {
    busyAccountId.value = null;
  }
}

async function toggleAccountActive(acc: Record<string, any>) {
  busyAccountId.value = acc.id;
  try {
    await axios.patch(route('settings.ai.accounts.update', { id: acc.id }), { is_active: !acc.is_active });
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || t('settings.ai.models.messages.toggle_failed'), 'error');
  } finally {
    busyAccountId.value = null;
  }
}

async function confirmDeleteAccount() {
  if (!removeTarget.value) return;
  removingAccount.value = true;
  try {
    await axios.delete(route('settings.ai.accounts.destroy', { id: removeTarget.value.id }));
    clearAccountNotice(removeTarget.value.id);
    showToast(t('settings.ai.models.messages.removed'), 'success');
    removeTarget.value = null;
    await refreshPool();
  } catch (err: any) {
    showToast(err.response?.data?.message || t('settings.ai.models.messages.remove_failed'), 'error');
  } finally {
    removingAccount.value = false;
  }
}

async function toggleModels(accId: number) {
  if (expandedAccount.value === accId) {
    expandedAccount.value = null;
    return;
  }
  expandedAccount.value = accId;
  await loadAccountModels(accId, true);
}

async function loadAccountModels(accId: number, force = false) {
  if (loadingModelsFor.value === accId) return;
  if (accountModels.value[accId] && !force) return;
  loadingModelsFor.value = accId;
  try {
    const { data } = await axios.get(route('settings.ai.accounts.models', { id: accId }));
    accountModels.value = { ...accountModels.value, [accId]: data.models || [] };
  } catch (err: any) {
    const message = err.response?.data?.message || t('settings.ai.models.messages.models_load_failed');
    setAccountNotice(accId, {
      tone: 'danger',
      titleKey: 'settings.ai.models.pool.actions.view_models',
      lines: [message],
    });
    showToast(t('settings.ai.models.messages.models_load_failed'), 'error');
  } finally {
    loadingModelsFor.value = null;
  }
}

async function toggleModelEnabled(accId: number, modelId: string, enable: boolean) {
  try {
    await axios.patch(route('settings.ai.accounts.models.toggle', { id: accId, model: modelId }), { is_enabled: enable });
    showToast(
      enable
        ? t('settings.ai.models.messages.model_enabled', { model: modelId })
        : t('settings.ai.models.messages.model_disabled', { model: modelId }),
      'success',
    );
    await loadAccountModels(accId, true);
    await refreshPool();
  } catch (err: any) {
    const message = err.response?.data?.message || t('settings.ai.models.messages.model_failed');
    setAccountNotice(accId, {
      tone: 'danger',
      titleKey: enable
        ? 'settings.ai.models.pool.actions.enable_model'
        : 'settings.ai.models.pool.actions.disable_model',
      lines: [message],
    });
    showToast(t('settings.ai.models.messages.model_failed'), 'error');
  }
}

function moveFallback(index: number, dir: -1 | 1) {
  const list = [...routingForm.value.fallback_models];
  const j = index + dir;
  if (j < 0 || j >= list.length) return;
  [list[index], list[j]] = [list[j], list[index]];
  routingForm.value.fallback_models = list;
}

function addFallbackRow() {
  if (routingForm.value.fallback_models.length >= 3) return;
  routingForm.value.fallback_models = [...routingForm.value.fallback_models, { provider: 'gemini', model: '' }];
}

function removeFallbackRow(index: number) {
  routingForm.value.fallback_models = routingForm.value.fallback_models.filter((_, i) => i !== index);
}

async function saveRouting() {
  if (savingRouting.value) return;
  savingRouting.value = true;
  try {
    const fallbacks = routingForm.value.fallback_models
      .filter((f) => f?.provider && f?.model?.trim())
      .slice(0, 3)
      .map((f) => ({ provider: f.provider, model: f.model.trim() }));
    const payload: Record<string, any> = {
      mode: routingForm.value.mode,
      primary_provider: routingForm.value.primary_provider || null,
      primary_model: routingForm.value.primary_model || null,
      fallback_models: fallbacks,
      provider_priority: ['gemini', 'openai', 'deepseek', 'openai-compatible'],
      free_policy: routingForm.value.free_policy,
      allow_paid_fallback: routingForm.value.allow_paid_fallback,
    };
    const { data } = await axios.put(route('settings.ai.routing.store'), payload);
    showToast(t('settings.ai.models.messages.routing_saved'), 'success');
    routingNotice.value = data.warnings?.length
      ? {
          tone: 'warning',
          titleKey: 'settings.ai.models.routing.title',
          lines: data.warnings,
        }
      : null;
    if (data.warnings?.length) showToast(t('common.warning'), 'error');
    await refreshPool();
  } catch (err: any) {
    const message = err.response?.data?.message || t('settings.ai.models.messages.routing_failed');
    routingNotice.value = {
      tone: 'danger',
      titleKey: 'settings.ai.models.routing.title',
      lines: [message],
    };
    showToast(t('settings.ai.models.messages.routing_failed'), 'error');
  } finally {
    savingRouting.value = false;
  }
}
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.models.title')" />

    <SettingsLayout
      :title="t('settings.ai.models.title')"
      :description="t('settings.ai.models.description')"
    >
      <SettingsCard
        :title="t('settings.ai.models.pool.title')"
        :description="t('settings.ai.models.pool.description')"
      >
        <div class="space-y-3">
          <p v-if="!poolAccounts.length" class="text-xs text-[var(--color-text-muted)]">
            {{ t('settings.ai.models.pool.empty') }}
          </p>

          <div
            v-for="acc in poolAccounts"
            :key="acc.id"
            class="rounded-[var(--radius-lg)] border border-[var(--color-border-subtle)] bg-[var(--color-surface-raised)] p-3"
          >
            <div class="flex flex-wrap items-start gap-2">
              <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-[var(--color-text-primary)]">
                  {{ String(acc.provider).toUpperCase() }} · {{ acc.alias }}
                </span>
                <Badge v-if="acc.key_suffix" variant="neutral" size="sm">
                  {{ t('settings.ai.models.pool.key_suffix', { suffix: acc.key_suffix }) }}
                </Badge>
                <Badge :variant="healthVariant(acc.health_status)" size="sm">
                  {{ healthLabel(acc.health_status) }}
                </Badge>
                <Badge v-if="acc.in_cooldown" variant="warning" size="sm">
                  {{ t('settings.ai.status.health.cooldown') }}
                </Badge>
                <Badge v-if="!acc.is_active" variant="neutral" size="sm">
                  {{ t('common.inactive') }}
                </Badge>

                <InfoPopover
                  :label="`${t('common.info')} · ${acc.alias}`"
                  :tone="noticeFor(acc.id)?.tone || healthVariant(acc.health_status)"
                >
                  <div class="space-y-3">
                    <div>
                      <p class="text-2xs font-bold text-[var(--color-text-primary)]">{{ t('common.status') }}</p>
                      <p>{{ healthLabel(acc.health_status) }}</p>
                    </div>
                    <div>
                      <p class="text-2xs font-bold text-[var(--color-text-primary)]">{{ t('settings.ai.models.test_button') }}</p>
                      <p>{{ acc.last_test_status || t('settings.ai.status.accounts.no_test') }}</p>
                      <p v-if="acc.last_test_message" class="whitespace-pre-wrap break-words">{{ acc.last_test_message }}</p>
                      <p v-if="acc.last_tested_at">{{ formatDateTime(acc.last_tested_at) }}</p>
                    </div>
                    <div v-if="acc.last_discovery_at">
                      <p class="text-2xs font-bold text-[var(--color-text-primary)]">{{ t('settings.ai.models.pool.actions.refresh') }}</p>
                      <p>{{ t('settings.ai.models.pool.discovery_at', { time: formatDateTime(acc.last_discovery_at) }) }}</p>
                      <p v-if="acc.discovery_status">{{ acc.discovery_status }}</p>
                    </div>
                    <div v-if="acc.in_cooldown">
                      <p class="text-2xs font-bold text-[var(--color-text-primary)]">{{ t('settings.ai.status.health.cooldown') }}</p>
                      <p v-if="acc.cooldown_until">{{ formatDateTime(acc.cooldown_until) }}</p>
                    </div>
                    <div
                      v-if="noticeFor(acc.id)"
                      class="rounded-[var(--radius-sm)] p-2"
                      :class="{
                        'bg-[var(--color-income-bg)] text-[var(--color-income-text)]': noticeFor(acc.id).tone === 'success',
                        'bg-[var(--color-debt-bg)] text-[var(--color-debt-text)]': noticeFor(acc.id).tone === 'warning',
                        'bg-[var(--color-expense-bg)] text-[var(--color-expense-text)]': noticeFor(acc.id).tone === 'danger',
                        'bg-[var(--color-surface-muted)] text-[var(--color-text-secondary)]': noticeFor(acc.id).tone === 'neutral',
                      }"
                    >
                      <p class="text-2xs font-bold">{{ t(noticeFor(acc.id).titleKey) }}</p>
                      <p v-for="(line, i) in noticeFor(acc.id).lines" :key="i" class="whitespace-pre-wrap break-words">
                        {{ line }}
                      </p>
                    </div>
                  </div>
                </InfoPopover>
              </div>

              <div class="flex flex-wrap items-center gap-1">
                <Button variant="secondary" size="xs" :disabled="busyAccountId === acc.id || acc.id === 0" @click="testAccount(acc.id)">
                  {{ t('settings.ai.models.pool.actions.test') }}
                </Button>
                <Button variant="ghost" size="xs" :disabled="busyAccountId === acc.id || acc.id === 0" @click="refreshDiscovery(acc.id)">
                  {{ t('settings.ai.models.pool.actions.refresh') }}
                </Button>
                <Button
                  v-if="acc.id !== 0"
                  variant="ghost"
                  size="xs"
                  :disabled="busyAccountId === acc.id"
                  @click="toggleAccountActive(acc)"
                >
                  {{ acc.is_active ? t('settings.ai.models.pool.actions.deactivate') : t('settings.ai.models.pool.actions.activate') }}
                </Button>
                <Button v-if="acc.id !== 0" variant="danger" size="xs" @click="removeTarget = acc">
                  {{ t('settings.ai.models.pool.actions.remove') }}
                </Button>
              </div>
            </div>

            <p class="mt-1 text-2xs text-[var(--color-text-muted)]">
              {{ t('settings.ai.models.pool.priority', { priority: acc.priority }) }}
              · {{ t('settings.ai.models.pool.models_count', { count: acc.model_count }) }}
            </p>

            <div v-if="acc.id !== 0" class="mt-2">
              <Button variant="ghost" size="xs" @click="toggleModels(acc.id)">
                {{ expandedAccount === acc.id
                  ? t('settings.ai.models.pool.actions.hide_models')
                  : (accountModels[acc.id] ? t('settings.ai.models.pool.actions.reload_models') : t('settings.ai.models.pool.actions.view_models')) }}
              </Button>

              <div v-if="expandedAccount === acc.id" class="mt-2 space-y-1">
                <p v-if="loadingModelsFor === acc.id && !accountModels[acc.id]" class="text-2xs text-[var(--color-text-muted)]">
                  {{ t('common.loading') }}
                </p>
                <div
                  v-for="m in accountModels[acc.id] || []"
                  :key="m.model_id"
                  class="flex items-center gap-2 border-b border-[var(--color-border-subtle)] py-1 text-2xs"
                >
                  <div class="min-w-0 flex-1">
                    <p class="break-words font-semibold text-[var(--color-text-primary)]">{{ m.model_id }}</p>
                    <p class="text-[var(--color-text-muted)]">{{ m.free_tier }} · {{ m.category }}</p>
                    <div v-if="!m.is_enabled" class="mt-1 flex items-center gap-1">
                      <Badge variant="danger" size="sm">{{ t('common.inactive') }}</Badge>
                      <InfoPopover v-if="m.last_error" :label="`${t('common.warning')} · ${m.model_id}`" tone="danger">
                        <p class="whitespace-pre-wrap break-words">{{ m.last_error }}</p>
                      </InfoPopover>
                    </div>
                  </div>
                  <Button
                    :variant="m.is_enabled ? 'ghost' : 'secondary'"
                    size="xs"
                    @click="toggleModelEnabled(acc.id, m.model_id, !m.is_enabled)"
                  >
                    {{ m.is_enabled ? t('settings.ai.models.pool.actions.disable_model') : t('settings.ai.models.pool.actions.enable_model') }}
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-2 rounded-[var(--radius-lg)] border border-dashed border-[var(--color-border-default)] p-3">
            <p class="text-xs font-semibold text-[var(--color-text-primary)]">{{ t('settings.ai.models.pool.add.title') }}</p>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
              <label class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
                <span>{{ t('settings.ai.models.provider.label') }}</span>
                <select
                  v-model="newAccount.provider"
                  class="w-full rounded-[var(--radius-md)] border border-[var(--color-border-default)] bg-[var(--color-surface-muted)] px-3 py-2 text-xs text-[var(--color-text-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-brand)]"
                >
                  <option v-for="p in providers" :key="p" :value="p">{{ p }}</option>
                </select>
              </label>

              <label class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
                <span>{{ t('settings.ai.models.pool.add.alias') }}</span>
                <TextInput
                  id="ai-account-alias"
                  v-model="newAccount.alias"
                  :placeholder="t('settings.ai.models.pool.add.alias_placeholder')"
                />
              </label>
            </div>

            <label class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
              <span>{{ t('settings.ai.models.pool.add.api_key') }}</span>
              <TextInput
                id="ai-account-api-key"
                v-model="newAccount.api_key"
                type="password"
                :placeholder="t('settings.ai.models.pool.add.api_key_placeholder')"
                autocomplete="new-password"
              />
            </label>

            <label v-if="newAccount.provider === 'openai-compatible'" class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
              <span>{{ t('settings.ai.models.pool.add.base_url') }}</span>
              <TextInput
                id="ai-account-base-url"
                v-model="newAccount.base_url"
                :placeholder="t('settings.ai.models.pool.add.base_url_placeholder')"
              />
            </label>

            <Button variant="primary" size="sm" :disabled="addingAccount || !newAccount.api_key" @click="addAccount">
              {{ addingAccount ? t('settings.ai.models.pool.add.submitting') : t('settings.ai.models.pool.add.submit') }}
            </Button>
          </div>
        </div>
      </SettingsCard>

      <SettingsCard
        :title="t('settings.ai.models.routing.title')"
        :description="t('settings.ai.models.routing.description')"
      >
        <div class="space-y-3">
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
              <span>{{ t('settings.ai.models.routing.mode') }}</span>
              <select
                v-model="routingForm.mode"
                class="w-full rounded-[var(--radius-md)] border border-[var(--color-border-default)] bg-[var(--color-surface-muted)] px-3 py-2 text-xs text-[var(--color-text-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-brand)]"
              >
                <option value="automatic">{{ t('settings.ai.models.routing.mode_automatic') }}</option>
                <option value="manual">{{ t('settings.ai.models.routing.mode_manual') }}</option>
              </select>
            </label>

            <label class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
              <span>{{ t('settings.ai.models.routing.free_policy') }}</span>
              <select
                v-model="routingForm.free_policy"
                class="w-full rounded-[var(--radius-md)] border border-[var(--color-border-default)] bg-[var(--color-surface-muted)] px-3 py-2 text-xs text-[var(--color-text-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-brand)]"
              >
                <option value="free_only">{{ t('settings.ai.models.routing.free_only') }}</option>
                <option value="free_first">{{ t('settings.ai.models.routing.free_first') }}</option>
                <option value="paid_allowed">{{ t('settings.ai.models.routing.paid_allowed') }}</option>
              </select>
            </label>
          </div>

          <div class="flex items-start gap-2 text-xs text-[var(--color-text-secondary)]">
            <input v-model="routingForm.allow_paid_fallback" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0" />
            <div>
              <p class="font-semibold text-[var(--color-text-primary)]">{{ t('settings.ai.models.routing.allow_paid') }}</p>
              <p>{{ t('settings.ai.models.routing.allow_paid_supporting') }}</p>
            </div>
          </div>

          <div v-if="routingForm.mode === 'manual'" class="space-y-2">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
              <label class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
                <span>{{ t('settings.ai.models.routing.primary_provider') }}</span>
                <TextInput id="ai-routing-primary-provider" v-model="routingForm.primary_provider" />
              </label>
              <label class="space-y-1 text-2xs text-[var(--color-text-secondary)]">
                <span>{{ t('settings.ai.models.routing.primary_model') }}</span>
                <TextInput
                  id="ai-routing-primary-model"
                  v-model="routingForm.primary_model"
                  :placeholder="t('settings.ai.models.routing.primary_model_placeholder')"
                />
              </label>
            </div>

            <div>
              <p class="text-xs font-semibold text-[var(--color-text-primary)]">{{ t('settings.ai.models.routing.fallbacks') }}</p>
              <p class="text-2xs text-[var(--color-text-muted)]">{{ t('settings.ai.models.routing.fallbacks_hint') }}</p>
            </div>

            <div
              v-for="(fb, i) in routingForm.fallback_models"
              :key="i"
              class="grid grid-cols-[1fr_2fr_auto] items-center gap-2"
            >
              <select
                v-model="fb.provider"
                class="rounded-[var(--radius-md)] border border-[var(--color-border-default)] bg-[var(--color-surface-muted)] px-2 py-2 text-xs text-[var(--color-text-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-brand)]"
              >
                <option v-for="p in providers" :key="p" :value="p">{{ p }}</option>
              </select>
              <TextInput
                :id="`ai-routing-fallback-${i}`"
                v-model="fb.model"
                :placeholder="t('settings.ai.models.routing.primary_model_placeholder')"
              />
              <span class="flex gap-1">
                <Button
                  variant="ghost"
                  size="xs"
                  :disabled="i === 0"
                  :aria-label="t('settings.ai.models.routing.move_up')"
                  @click="moveFallback(i, -1)"
                >
                  ▲
                </Button>
                <Button
                  variant="ghost"
                  size="xs"
                  :disabled="i === routingForm.fallback_models.length - 1"
                  :aria-label="t('settings.ai.models.routing.move_down')"
                  @click="moveFallback(i, 1)"
                >
                  ▼
                </Button>
                <Button
                  variant="danger"
                  size="xs"
                  :aria-label="t('settings.ai.models.routing.remove_fallback')"
                  @click="removeFallbackRow(i)"
                >
                  ✕
                </Button>
              </span>
            </div>

            <Button
              v-if="routingForm.fallback_models.length < 3"
              variant="ghost"
              size="xs"
              @click="addFallbackRow"
            >
              {{ t('settings.ai.models.routing.add_fallback') }}
            </Button>
          </div>

          <div class="flex items-center gap-2">
            <Button variant="primary" size="sm" :disabled="savingRouting" @click="saveRouting">
              {{ savingRouting ? t('settings.ai.models.routing.saving') : t('settings.ai.models.routing.save') }}
            </Button>
            <InfoPopover v-if="routingNotice" :label="t('common.warning')" :tone="routingNotice.tone">
              <p
                v-for="(line, i) in routingNotice.lines"
                :key="i"
                class="whitespace-pre-wrap break-words"
              >
                {{ line }}
              </p>
            </InfoPopover>
          </div>
        </div>
      </SettingsCard>

      <SettingsCard
        :title="t('settings.ai.models.candidates.title')"
        :description="t('settings.ai.models.candidates.count', { count: poolCandidates.length })"
      >
        <div class="space-y-2">
          <p v-if="!poolCandidates.length" class="text-xs text-[var(--color-text-muted)]">
            {{ t('settings.ai.models.candidates.empty') }}
          </p>

          <div
            v-for="(c, i) in poolCandidates"
            :key="c.provider + '-' + c.model + '-' + i"
            class="flex items-center gap-2 border-b border-[var(--color-border-subtle)] py-1 text-2xs last:border-b-0"
          >
            <span class="font-bold text-[var(--color-text-muted)]">{{ i + 1 }}.</span>
            <span class="font-semibold text-[var(--color-text-primary)]">{{ c.model }}</span>
            <span class="text-[var(--color-text-muted)]">
              {{ c.provider }}<template v-if="c.account_alias"> · {{ c.account_alias }}</template>
              <template v-if="c.source"> · {{ c.source }}</template>
            </span>
          </div>
        </div>

        <div v-if="poolSkipped.length" class="mt-4 space-y-2">
          <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold text-[var(--color-text-primary)]">
              {{ t('settings.ai.models.candidates.skipped_title') }}
            </p>
            <span class="text-2xs text-[var(--color-text-muted)]">
              {{ t('settings.ai.models.candidates.skipped_count', { count: poolSkipped.length }) }}
            </span>
          </div>

          <div
            v-for="(s, i) in poolSkipped"
            :key="(s.provider || '') + '-' + (s.model || '') + '-' + i"
            class="flex items-center gap-2 py-1 text-2xs"
          >
            <div class="min-w-0 flex-1">
              <p class="break-words text-[var(--color-text-secondary)]">{{ s.model || '*' }}</p>
              <p class="text-[var(--color-text-muted)]">{{ s.provider }}</p>
            </div>
            <InfoPopover v-if="s.reason" :label="`${t('settings.ai.models.candidates.skipped_title')} · ${s.model || '*'}`" tone="warning">
              <p class="whitespace-pre-wrap break-words">{{ s.reason }}</p>
            </InfoPopover>
          </div>
        </div>
      </SettingsCard>
    </SettingsLayout>

    <ConfirmationDialog
      :show="!!removeTarget"
      :title="t('settings.ai.models.pool.remove_title')"
      :message="removeTarget ? `${removeTarget.alias} — ${t('settings.ai.models.pool.remove_message')}` : ''"
      :confirm-text="t('common.delete')"
      :cancel-text="t('common.cancel')"
      :loading="removingAccount"
      variant="danger"
      @close="removeTarget = null"
      @confirm="confirmDeleteAccount"
    />
  </AuthenticatedLayout>
</template>
