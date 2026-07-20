<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { ref } from 'vue';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.privacy.title') },
  { label: t('settings.privacy.danger.title') },
];

const isDeleting = ref(false);
const showDeleteConfirm = ref(false);
const errorMessage = ref('');

const handleDeleteAccount = async () => {
  isDeleting.value = true;
  errorMessage.value = '';

  try {
    const response = await axios.post('/api/account/delete');
    window.location.href = '/';
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || t('errors.generic');
    isDeleting.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.privacy.danger.title')" />
    
    <SettingsLayout
      :title="t('settings.privacy.danger.title')"
      :description="t('settings.privacy.danger.description')"
      :breadcrumbs="breadcrumbs"
    >
      <!-- Messages -->
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <SettingsCard :title="t('settings.privacy.danger.delete_account.title')" :description="t('settings.privacy.danger.delete_account.description')">
        <div class="space-y-4">
          <p class="text-sm text-gray-400">{{ t('settings.privacy.danger.delete_account.warning') }}</p>
          <button
            @click="showDeleteConfirm = true"
            :disabled="isDeleting || showDeleteConfirm"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors"
          >
            ⚠️ {{ t('settings.privacy.danger.delete_account.button') }}
          </button>

          <!-- Delete Confirmation Modal -->
          <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-gray-900 rounded-lg p-6 max-w-sm mx-4 border border-gray-700" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title" aria-describedby="delete-modal-desc">
              <h3 id="delete-modal-title" class="text-lg font-bold text-white mb-4">{{ t('settings.privacy.danger.delete_account.confirm_title') }}</h3>
              <p id="delete-modal-desc" class="text-sm text-gray-400 mb-6">{{ t('settings.privacy.danger.delete_account.confirm_description') }}</p>
              <div class="flex gap-3">
                <button
                  @click="showDeleteConfirm = false"
                  :disabled="isDeleting"
                  class="flex-1 px-4 py-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  {{ t('common.cancel') }}
                </button>
                <button
                  @click="handleDeleteAccount"
                  :disabled="isDeleting"
                  class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  {{ isDeleting ? t('common.deleting') : t('settings.privacy.danger.delete_account.confirm_button') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
