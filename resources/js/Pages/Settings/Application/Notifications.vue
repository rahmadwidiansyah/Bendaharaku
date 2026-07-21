<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t } = useI18n();


const emailNotifications = ref(true);
const pushNotifications = ref(false);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleSave = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await axios.patch(route('settings.application.notifications.update'), {
      email_notifications: emailNotifications.value,
      push_notifications: pushNotifications.value,
    });

    successMessage.value = t('toast.updated');
    setTimeout(() => {
      successMessage.value = '';
    }, 3000);
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || t('errors.generic');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.application.notifications.title')" />
    
    <SettingsLayout
      :title="t('settings.application.notifications.title')"
      :description="t('settings.application.notifications.description')"
    >
      <!-- Messages -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <SettingsCard :title="t('settings.application.notifications.email.title')" :description="t('settings.application.notifications.email.description')">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">{{ t('settings.application.notifications.email.label') }}</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input v-model="emailNotifications" type="checkbox" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
          </label>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.application.notifications.push.title')" :description="t('settings.application.notifications.push.description')">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">{{ t('settings.application.notifications.push.label') }}</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input v-model="pushNotifications" type="checkbox" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
          </label>
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
