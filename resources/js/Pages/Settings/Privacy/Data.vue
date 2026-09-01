<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { ref } from 'vue';
import { formatLocalYMD } from '@/utils/format.js';
import { useToast } from '@/Composables/useToast';
 
const { t } = useI18n();
const { showToast } = useToast();
 

const isExporting = ref(false);

const handleExport = async () => {
 isExporting.value = true;
 try {
   const response = await axios.post('/api/account/export', {}, { responseType: 'blob' });
   // Create blob and download
   const blob = new Blob([response.data], { type: 'application/json' });
   const link = document.createElement('a');
   const fileName = `bendaharaku-export-${formatLocalYMD()}.json`;
   link.href = window.URL.createObjectURL(blob);
   link.download = fileName;
   document.body.appendChild(link);
   link.click();
   link.remove();

   showToast(t('settings.privacy.data.export.success'), 'success');
 } catch (err: any) {
   showToast(err?.response?.data?.message || t('errors.generic'), 'error');
 } finally {
   isExporting.value = false;
 }
};

</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.privacy.data.title')" />
    
    <SettingsLayout
      :title="t('settings.privacy.data.title')"
      :description="t('settings.privacy.data.description')"
    >
      <SettingsCard :title="t('settings.privacy.data.export.title')" :description="t('settings.privacy.data.export.description')">
        <button @click="handleExport" :disabled="isExporting" class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2.5 bg-purple-600 hover:bg-purple-700 disabled:bg-surface-muted disabled:cursor-not-allowed text-white rounded-lg sm:rounded-xl text-sm font-medium transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 11l5 5 5-5M12 4v12" />
          </svg>
          {{ isExporting ? t('common.processing') : t('settings.privacy.data.export.button') }}
        </button>
      </SettingsCard>

      <SettingsCard :title="t('settings.privacy.data.import.title')" :description="t('settings.privacy.data.import.description')">
        <button disabled class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2.5 bg-surface-muted text-text-muted rounded-lg sm:rounded-xl text-sm font-medium opacity-50 cursor-not-allowed">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 11l5-5 5 5M12 4v12" />
          </svg>
          {{ t('settings.privacy.data.import.button') }}
        </button>
      </SettingsCard>

      <SettingsCard :title="t('settings.privacy.data.backup.title')" :description="t('settings.privacy.data.backup.description')">
        <button disabled class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2.5 bg-surface-muted text-text-muted rounded-lg sm:rounded-xl text-sm font-medium opacity-50 cursor-not-allowed">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ t('settings.privacy.data.backup.button') }}
        </button>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
