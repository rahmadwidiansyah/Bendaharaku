<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import SettingsBreadcrumb from '@/Components/Settings/SettingsBreadcrumb.vue';
import { useI18n } from 'vue-i18n';
import { ref, onMounted } from 'vue';

const { t } = useI18n();

const form = ref({
  enable_conversation_history: true,
});

const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.ai.title') },
  { label: t('settings.ai.memory.title') },
];

onMounted(() => {
  try {
    const stored = localStorage.getItem('ai_memory_settings');
    if (stored) {
      const parsed = JSON.parse(stored);
      Object.assign(form.value, parsed);
    }
  } catch (e) {
    // ignore
  }
});

const handleSave = () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    localStorage.setItem('ai_memory_settings', JSON.stringify(form.value));
    successMessage.value = t('toast.updated');
    setTimeout(() => {
      successMessage.value = '';
    }, 3000);
  } catch (error: any) {
    errorMessage.value = t('errors.generic');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.memory.title')" />
    
    <SettingsLayout>
      <template #header>
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-black text-white tracking-tight leading-none">{{ t('settings.ai.memory.title') }}</h2>
          <p class="text-sm text-gray-400 mt-1.5 font-medium">{{ t('settings.ai.memory.description') }}</p>
        </div>
        <div class="mt-2">
          <SettingsBreadcrumb :breadcrumbs="breadcrumbs" />
        </div>
      </template>

      <!-- Messages -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <!-- Conversation History -->
      <SettingsCard
        :title="t('settings.ai.memory.conversation_history.label')"
        :description="t('settings.ai.memory.conversation_history.description')"
      >
        <div class="flex items-center justify-between p-3 bg-gray-900 rounded-lg border border-gray-700">
          <span class="text-sm text-gray-300">
            {{ t('settings.ai.memory.conversation_history.enable') }}
          </span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="form.enable_conversation_history"
              type="checkbox"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600" />
          </label>
        </div>
      </SettingsCard>

      <!-- Save Button -->
      <div class="mt-6 flex justify-end gap-2">
        <button
          @click="handleSave"
          :disabled="saving"
          class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed transition-colors font-medium"
        >
          {{ saving ? t('common.saving') : t('settings.save_button') }}
        </button>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
