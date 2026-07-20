<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import SettingsHeader from '@/Components/Settings/SettingsHeader.vue';
import SettingsBreadcrumb from '@/Components/Settings/SettingsBreadcrumb.vue';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import axios from 'axios';

const { t } = useI18n();

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
const statusOverrides = ref<Record<string, string>>({});

// Form state
const form = ref({
  provider: selectedProvider.value,
  api_key: '',
  selected_model: '',
  is_active_provider: false,
});

const dirty = ref(false);
const saving = ref(false);

// mark dirty when form changes
watch(form, (newVal, oldVal) => {
  dirty.value = JSON.stringify(newVal) !== JSON.stringify({
    provider: selectedProvider.value,
    api_key: '',
    selected_model: props.providerStatuses?.[selectedProvider.value]?.selected_model || (props.modelsByProvider?.[selectedProvider.value]?.[0] ?? ''),
    is_active_provider: props.providerStatuses?.[selectedProvider.value]?.is_active_provider || false,
  });
}, { deep: true });

// Computed
const currentStatus = computed(() => {
  return statusOverrides.value[selectedProvider.value]
    || props.providerStatuses?.[selectedProvider.value]?.status
    || 'Not Configured';
});

const currentModels = computed(() => props.modelsByProvider?.[selectedProvider.value] || []);

// Breadcrumb
const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.ai.title') },
  { label: t('settings.ai.models.title') },
];

// Methods
const syncFormWithProvider = (provider: string) => {
  const config = props.providerStatuses?.[provider];
  const models = props.modelsByProvider?.[provider] || [];
  
  form.value.provider = provider;
  form.value.api_key = '';
  form.value.selected_model = config?.selected_model || models[0] || '';
  form.value.is_active_provider = config?.is_active_provider || false;
  testResult.value = null;
};

const testConnection = async () => {
  isTesting.value = true;
  try {
    const response = await axios.post(route('settings.ai.test'), {
      provider: selectedProvider.value,
    });
    testResult.value = response.data.message || t('settings.ai.models.test_success');
    statusOverrides.value[selectedProvider.value] = 'Connected';
  } catch (error: any) {
    testResult.value = error.response?.data?.message || t('settings.ai.models.test_failed');
    statusOverrides.value[selectedProvider.value] = 'Failed';
  } finally {
    isTesting.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    await axios.patch(route('settings.ai.store'), {
      provider: form.value.provider,
      api_key: form.value.api_key || null,
      selected_model: form.value.selected_model,
      is_active_provider: form.value.is_active_provider,
    });

    // reset dirty flag
    dirty.value = false;
    testResult.value = t('settings.notifications.save_success');
  } catch (err: any) {
    testResult.value = err.response?.data?.message || t('settings.notifications.save_failed');
  } finally {
    saving.value = false;
  }
};

const discardChanges = () => {
  syncFormWithProvider(selectedProvider.value);
  dirty.value = false;
};

const handleProviderChange = (provider: string) => {
  selectedProvider.value = provider;
  syncFormWithProvider(provider);
};

// Watch provider changes
import { watch } from 'vue';
watch(selectedProvider, (newProvider) => {
  syncFormWithProvider(newProvider);
}, { immediate: true });
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.models.title')" />
    
    <SettingsLayout>
      <template #header>
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-black text-white tracking-tight leading-none">{{ t('settings.ai.models.title') }}</h2>
            <p class="text-sm text-gray-400 mt-1.5 font-medium">{{ t('settings.ai.models.description') }}</p>
            <div class="mt-3">
              <SettingsBreadcrumb :breadcrumbs="breadcrumbs" />
            </div>
          </div>

          <!-- Actions: Save / Discard -->
          <div class="flex items-center gap-3">
            <button
              v-if="dirty"
              @click.prevent="discardChanges"
              class="px-4 py-2 bg-gray-800 text-gray-200 rounded-lg hover:bg-gray-700 border border-white/5"
            >
              {{ t('common.cancel') }}
            </button>

            <button
              :disabled="!dirty || saving"
              @click.prevent="saveSettings"
              class="px-4 py-2 rounded-lg font-semibold transition-colors"
              :class="dirty ? 'bg-purple-600 text-white hover:bg-purple-700' : 'bg-gray-700 text-gray-300 cursor-not-allowed'"
            >
              {{ saving ? t('common.saving') : t('common.save') }}
            </button>
          </div>
        </div>

        <!-- Unsaved changes indicator -->
        <div v-if="dirty" class="mt-3 p-3 bg-yellow-900/20 border border-yellow-500/20 rounded-lg text-sm text-yellow-200">
          ⚠️ {{ t('settings.notifications.unsaved_changes') }}
        </div>
      </template>

      <!-- AI Provider Selection -->
      <SettingsCard
        :title="t('settings.ai.models.provider.label')"
        :description="t('settings.ai.models.provider.description')"
      >
        <div class="space-y-4">
          <!-- Provider Tabs -->
          <div class="-mx-4 sm:mx-0 overflow-x-auto">
            <div class="flex gap-2 px-4 sm:px-0 whitespace-nowrap">
              <button
                v-for="provider in availableProviders"
                :key="provider"
                @click="handleProviderChange(provider)"
                :class="[
                  'inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-all select-none',
                  selectedProvider === provider
                    ? 'bg-purple-600 text-white shadow-md'
                    : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                ]"
              >
                {{ provider.toUpperCase() }}
              </button>
            </div>
          </div>

          <!-- Status Indicator -->
          <div class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg border border-gray-700">
            <span
              :class="[
                'w-3 h-3 rounded-full',
                currentStatus === 'Connected' ? 'bg-emerald-500' : 'bg-red-500'
              ]"
            />
            <span class="text-sm text-gray-300">
              {{ t('settings.ai.models.status') }}: 
              <span :class="currentStatus === 'Connected' ? 'text-emerald-400' : 'text-red-400'">
                {{ currentStatus }}
              </span>
            </span>
          </div>

          <!-- Test Connection Button -->
          <button
            @click="testConnection"
            :disabled="isTesting"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {{ isTesting ? t('settings.ai.models.testing') : t('settings.ai.models.test_button') }}
          </button>

          <!-- Test Result -->
          <div
            v-if="testResult"
            :class="[
              'p-3 rounded-lg text-sm',
              testResult.includes('success') || currentStatus === 'Connected'
                ? 'bg-emerald-900/30 text-emerald-300 border border-emerald-500/30'
                : 'bg-red-900/30 text-red-300 border border-red-500/30'
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
        <div class="space-y-3">
          <select
            v-model="form.selected_model"
            class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-purple-500"
          >
            <option value="">{{ t('settings.ai.models.select_model') }}</option>
            <option v-for="model in currentModels" :key="model" :value="model">
              {{ model }}
            </option>
          </select>
          <p class="text-xs text-gray-400">
            {{ t('settings.ai.models.model.hint') }}
          </p>
        </div>
      </SettingsCard>

      <!-- API Configuration -->
      <SettingsCard
        :title="t('settings.ai.models.api_key.label')"
        :description="t('settings.ai.models.api_key.description')"
      >
        <div class="space-y-3">
          <input
            v-model="form.api_key"
            type="password"
            placeholder="sk-..."
            class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500"
          />
          <div class="flex items-center gap-2 p-3 bg-yellow-900/20 border border-yellow-500/20 rounded-lg">
            <span class="text-yellow-400">⚠️</span>
            <span class="text-xs text-yellow-300">
              {{ t('settings.ai.models.api_key.warning') }}
            </span>
          </div>
        </div>
      </SettingsCard>

      <!-- Help Text -->
      <div class="mt-6 p-4 bg-blue-900/20 border border-blue-500/20 rounded-lg">
        <p class="text-sm text-blue-400">
          💡 {{ t('settings.ai.models.help_text') }}
        </p>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
