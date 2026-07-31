<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();


const emailNotifications = ref(true);
const pushNotifications = ref(false);
const saving = ref(false);
const handleSave = async () => {
  saving.value = true;

  try {
    await axios.patch(route('settings.application.notifications.update'), {
      email_notifications: emailNotifications.value,
      push_notifications: pushNotifications.value,
    });

    showToast(t('toast.updated'), 'success');
  } catch (error: any) {
    showToast(error.response?.data?.message || t('errors.generic'), 'error');
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
      <SettingsCard :title="t('settings.application.notifications.email.title')" :description="t('settings.application.notifications.email.description')">
        <div class="flex items-center justify-between">
          <span class="text-2xs sm:text-sm text-text-secondary">{{ t('settings.application.notifications.email.label') }}</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input v-model="emailNotifications" type="checkbox" class="sr-only peer" />
            <div class="w-11 h-6 bg-surface-muted peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
          </label>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.application.notifications.push.title')" :description="t('settings.application.notifications.push.description')">
        <div class="flex items-center justify-between">
          <span class="text-2xs sm:text-sm text-text-secondary">{{ t('settings.application.notifications.push.label') }}</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input v-model="pushNotifications" type="checkbox" class="sr-only peer" />
            <div class="w-11 h-6 bg-surface-muted peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
          </label>
        </div>
      </SettingsCard>

      <!-- Save Button -->
      <div class="flex gap-3 pt-4">
        <button
          @click="handleSave"
          :disabled="saving"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-surface-muted disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors"
        >
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
