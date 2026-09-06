<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();


const isDeleting = ref(false);
const showDeleteConfirm = ref(false);

const handleDeleteAccount = async () => {
  isDeleting.value = true;

  try {
    await axios.post('/api/account/delete');
    window.location.href = '/';
  } catch (error: any) {
    showToast(error.response?.data?.message || t('errors.generic'), 'error');
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
    >
      <SettingsCard :title="t('settings.privacy.danger.delete_account.title')" :description="t('settings.privacy.danger.delete_account.description')">
        <div class="space-y-4">
          <p class="text-2xs sm:text-sm text-[var(--color-text-secondary)]">{{ t('settings.privacy.danger.delete_account.warning') }}</p>
          <button
            @click="showDeleteConfirm = true"
            :disabled="isDeleting || showDeleteConfirm"
            class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-[var(--color-surface-muted)] disabled:cursor-not-allowed text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-sm font-medium transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            {{ t('settings.privacy.danger.delete_account.button') }}
          </button>

          <!-- Delete Confirmation Modal -->
          <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-[var(--color-surface-raised)] rounded-lg sm:rounded-xl p-4 sm:p-6 sm:max-w-md mx-4 border border-[var(--color-border-default)] w-full" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title" aria-describedby="delete-modal-desc">
              <h3 id="delete-modal-title" class="text-base sm:text-lg font-bold text-[var(--color-text-primary)] mb-3 sm:mb-4">{{ t('settings.privacy.danger.delete_account.confirm_title') }}</h3>
              <p id="delete-modal-desc" class="text-2xs sm:text-sm text-[var(--color-text-secondary)] mb-4 sm:mb-6">{{ t('settings.privacy.danger.delete_account.confirm_description') }}</p>
              <div class="flex gap-2 sm:gap-3">
                <button
                  @click="showDeleteConfirm = false"
                  :disabled="isDeleting"
                  class="flex-1 px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] hover:bg-gray-600 disabled:opacity-50 text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-sm font-medium transition-colors"
                >
                  {{ t('common.cancel') }}
                </button>
                <button
                  @click="handleDeleteAccount"
                  :disabled="isDeleting"
                  class="flex-1 px-3 py-1.5 sm:px-4 sm:py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-sm font-medium transition-colors"
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
