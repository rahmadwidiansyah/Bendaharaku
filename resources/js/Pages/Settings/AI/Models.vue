<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import { ref, computed, watch, reactive } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();

interface Props {
  providerStatuses?: Record<string, any>;
  availableProviders?: string[];
  modelsByProvider?: Record<string, string[]>;
}

const props = withDefaults(defineProps<Props>(), {
  providerStatuses: () => ({}),
  availableProviders: () => [],
  modelsByProvider: () => ({}),
});

const selectedProvider = ref(props.availableProviders?.[0] || 'gemini');
const isTesting = ref(false);
const testResult = ref<string | null>(null);
const testResultType = ref<'success' | 'error'>('success');

// Track active provider and selected model locally — syncs with backend after save
const initialActive = Object.entries(props.providerStatuses).find(
  ([, s]) => s?.is_active_provider,
);
const localActiveProvider = ref(initialActive?.[0] ?? (props.availableProviders?.[0] ?? ''));
const localStatuses = reactive<Record<string, any>>({});

// Form state per provider yang sedang aktif
const form = ref({
  provider: selectedProvider.value,
  api_key: '',
  selected_model: '',
  is_active_provider: false,
});

const saving = ref(false);


// ── Helpers ───────────────────────────────────────────────────────

// Provider status: local override > props from server
function getProviderStatus(provider: string) {
  return localStatuses[provider] ?? props.providerStatuses?.[provider];
}

const currentStatus = computed(() => {
  return getProviderStatus(selectedProvider.value)?.status || 'Not Configured';
});

const currentModels = computed(() => props.modelsByProvider?.[selectedProvider.value] || []);

// Apakah form berbeda dari keadaan saat ini (server)?
const hasChanges = computed(() => {
  const status = getProviderStatus(form.value.provider);
  if (!status) return true;
  return form.value.selected_model !== (status.selected_model ?? '')
    || form.value.is_active_provider !== (status.is_active_provider ?? false);
});

const syncFormWithProvider = (provider: string) => {
  const config = getProviderStatus(provider);
  const models = props.modelsByProvider?.[provider] || [];

  form.value.provider           = provider;
  form.value.api_key            = '';
  form.value.selected_model     = config?.selected_model || models[0] || '';
  form.value.is_active_provider = provider === localActiveProvider.value;
testResult.value              = null;
};

const handleProviderChange = (provider: string) => {
  if (saving.value) return;
  selectedProvider.value = provider;
};

// Sync form setiap kali provider berubah
watch(selectedProvider, (newProvider) => {
  syncFormWithProvider(newProvider);
}, { immediate: true });

// ── Actions ───────────────────────────────────────────────────────

const testConnection = async () => {
  isTesting.value = true;
  testResult.value = null;
  try {
    const response = await axios.post(route('settings.ai.test'), {
      provider: selectedProvider.value,
    });
    testResult.value = response.data.message || t('settings.ai.models.test_success');
    testResultType.value = 'success';
    localStatuses[selectedProvider.value] = {
      ...getProviderStatus(selectedProvider.value),
      status: 'Connected',
    };
  } catch (error: any) {
    testResult.value = error.response?.data?.message || t('settings.ai.models.test_failed');
    testResultType.value = 'error';
    localStatuses[selectedProvider.value] = {
      ...getProviderStatus(selectedProvider.value),
      status: 'Failed',
    };
  } finally {
    isTesting.value = false;
  }
};

const saveSettings = async () => {
  if (saving.value || !hasChanges.value) return;

  const prevStatus = { ...getProviderStatus(form.value.provider) };

  saving.value = true;
  try {
    const { data } = await axios.patch(route('settings.ai.store'), {
      provider:             form.value.provider,
      api_key:              form.value.api_key || null,
      selected_model:       form.value.selected_model,
      is_active_provider:   form.value.is_active_provider,
    });

    showToast(data.message || t('toast.updated'), 'success');

    // Sync local state with server response
    localStatuses[form.value.provider] = {
      ...getProviderStatus(form.value.provider),
      selected_model: data.selected_model,
      is_active_provider: data.is_active_provider,
    };

    if (data.is_active_provider) {
      // Deactivate all other providers in local state
      for (const key in localStatuses) {
        if (key !== form.value.provider) {
          localStatuses[key] = { ...localStatuses[key], is_active_provider: false };
        }
      }
      localActiveProvider.value = form.value.provider;
    }

    // Reset API key field setelah simpan (keamanan — jangan tampilkan key)
    form.value.api_key = '';
  } catch (err: any) {
    // Rollback — restore to previous state
    localStatuses[form.value.provider] = prevStatus;
    showToast(err.response?.data?.message || t('errors.generic'), 'error');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.models.title')" />

    <SettingsLayout
      :title="t('settings.ai.models.title')"
      :description="t('settings.ai.models.description')"
    >
      <!-- Provider Selection -->
      <SettingsCard
        :title="t('settings.ai.models.provider.label')"
        :description="t('settings.ai.models.provider.description')"
      >
        <div class="space-y-3">
          <!-- Provider Tabs -->
          <div class="-mx-4 sm:mx-0 overflow-x-auto">
            <div class="flex gap-2 px-4 sm:px-0 whitespace-nowrap">
              <button
                v-for="provider in availableProviders"
                :key="provider"
                @click="handleProviderChange(provider)"
                :class="[
                  'inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg font-semibold text-xs sm:text-sm transition-all select-none',
                  selectedProvider === provider
                    ? 'bg-[var(--color-brand)] text-[var(--color-text-primary)] shadow-md'
                    : 'bg-[var(--color-surface-muted)] text-gray-300 hover:bg-[var(--color-surface-muted)]',
                ]"
              >
                <span
                  v-if="getProviderStatus(provider)?.is_active_provider"
                  class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-400 shrink-0 mr-1.5 sm:mr-2"
                />
                {{ provider.toUpperCase() }}
              </button>
            </div>
          </div>

          <!-- Status Indicator -->
          <div class="flex items-center gap-2 p-2.5 sm:p-3 bg-[var(--color-surface-raised)] rounded-lg border border-white/5">
            <span
              :class="[
                'w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full shrink-0',
                currentStatus === 'Connected' ? 'bg-emerald-500' : 'bg-gray-500',
              ]"
            />
            <span class="text-xs sm:text-sm text-gray-300">
              {{ t('settings.ai.models.status') }}:
              <span :class="currentStatus === 'Connected' ? 'text-emerald-400' : 'text-[var(--color-text-secondary)]'">
                {{ currentStatus }}
              </span>
            </span>

            <!-- Test Connection -->
            <button
              @click="testConnection"
              :disabled="isTesting"
              class="ml-auto px-2.5 py-1 sm:px-3 sm:py-1.5 text-2xs sm:text-xs bg-[var(--color-surface-muted)] hover:bg-gray-600 text-[var(--color-text-primary)] rounded-lg disabled:opacity-50 transition-colors font-semibold"
            >
              {{ isTesting ? t('settings.ai.models.testing') : t('settings.ai.models.test_button') }}
            </button>
          </div>

          <!-- Test Result -->
          <div
            v-if="testResult"
            :class="[
              'p-2.5 sm:p-3 rounded-lg text-xs sm:text-sm border',
              testResultType === 'success'
                ? 'bg-emerald-900/30 text-emerald-300 border-emerald-500/30'
                : 'bg-red-900/30 text-red-300 border-red-500/30',
            ]"
          >
            {{ testResult }}
          </div>
        </div>
      </SettingsCard>

      <!-- Model Selection -->
      <SettingsCard
        :title="t('settings.ai.models.model.label')"
        :description="t('settings.ai.models.model.description')"
      >
        <select
          v-model="form.selected_model"
          class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] focus:outline-none focus:border-purple-500 transition-all text-sm"
        >
          <option value="">{{ t('settings.ai.models.select_model') }}</option>
          <option v-for="model in currentModels" :key="model" :value="model">
            {{ model }}
          </option>
        </select>
        <p class="mt-2 text-2xs text-[var(--color-text-secondary)]">{{ t('settings.ai.models.model.hint') }}</p>
      </SettingsCard>

      <!-- API Key -->
      <SettingsCard
        :title="t('settings.ai.models.api_key.label')"
        :description="t('settings.ai.models.api_key.description')"
      >
        <input
          v-model="form.api_key"
          type="password"
          :placeholder="t('settings.ai.models.api_key.placeholder')"
          autocomplete="new-password"
          class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-all text-sm"
        />
        <p class="mt-2 text-2xs text-[var(--color-text-secondary)]">
          {{ t('settings.ai.models.api_key.warning') }}
        </p>
      </SettingsCard>

      <!-- Set as Active Provider -->
      <SettingsCard
        :title="t('settings.ai.models.set_active')"
        :description="t('settings.ai.models.set_active_desc')"
      >
        <label class="flex items-center gap-3 cursor-pointer select-none">
          <div class="relative">
            <input
              v-model="form.is_active_provider"
              type="checkbox"
              class="sr-only peer"
            />
            <div class="w-9 h-5 sm:w-10 sm:h-6 bg-[var(--color-surface-muted)] rounded-full peer peer-checked:bg-[var(--color-brand)] transition-colors" />
            <div class="absolute top-0.5 sm:top-1 left-0.5 sm:left-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 sm:peer-checked:translate-x-4" />
          </div>
          <span class="text-xs sm:text-sm text-gray-300">
            {{ t('settings.ai.models.provider_toggle') }}
          </span>
        </label>
      </SettingsCard>

      <!-- Help Text -->
      <div class="p-3 sm:p-4 bg-blue-900/20 border border-blue-500/20 rounded-lg">
        <p class="text-xs sm:text-sm text-blue-400">
          {{ t('settings.ai.models.help_text') }}
        </p>
      </div>

      <!-- Save -->
      <div class="flex items-center gap-3 pt-1 sm:pt-2">
        <button
          @click="saveSettings"
          :disabled="saving || !hasChanges"
          class="px-4 py-2 sm:px-5 sm:py-3 bg-[var(--color-brand)] hover:bg-[var(--color-brand-hover)] disabled:bg-[var(--color-surface-muted)] disabled:cursor-not-allowed text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-xs font-bold uppercase tracking-wider transition-all inline-flex items-center gap-2 shadow-lg shadow-purple-900/20"
        >
          <svg
            v-if="saving"
            class="animate-spin w-3.5 h-3.5"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
        <span v-if="!hasChanges && !saving" class="text-2xs text-emerald-400 font-semibold">
          ✓ {{ t('settings.ai.models.active') }}
        </span>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
