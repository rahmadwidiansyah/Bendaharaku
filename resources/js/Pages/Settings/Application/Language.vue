<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t, locale } = useI18n();


const props = defineProps<{
  userLanguage: string;
}>();

const language = ref(props.userLanguage);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleSave = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await axios.patch(route('settings.application.language.update'), {
      language: language.value,
    });

    // Terapkan perubahan bahasa langsung di sisi client
    if (language.value !== locale.value) {
      locale.value = language.value;
      localStorage.setItem('locale', language.value);
    }

    successMessage.value = t('toast.updated');
    setTimeout(() => { successMessage.value = ''; }, 3000);
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || t('errors.generic');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.application.language.title')" />

    <SettingsLayout
      :title="t('settings.application.language.title')"
      :description="t('settings.application.language.description')"
    >
      <!-- Messages -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <SettingsCard
        :title="t('settings.application.language.language.title')"
        :description="t('settings.application.language.language.description')"
      >
        <div class="space-y-2">
          <select
            v-model="language"
            class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-purple-500"
          >
            <option value="id">{{ t('settings.application.language.language.id') }}</option>
            <option value="en">{{ t('settings.application.language.language.en') }}</option>
          </select>
        </div>
      </SettingsCard>

      <!-- Save Button -->
      <div class="flex gap-3 pt-4">
        <button
          @click="handleSave"
          :disabled="saving"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors"
        >
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
