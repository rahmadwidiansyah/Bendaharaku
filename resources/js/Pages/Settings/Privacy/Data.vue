<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { ref } from 'vue';
 
const { t } = useI18n();
 

const isExporting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleExport = async () => {
 isExporting.value = true;
 successMessage.value = '';
 errorMessage.value = '';
 try {
   const response = await axios.post('/api/account/export', {}, { responseType: 'blob' });
   // Create blob and download
   const blob = new Blob([response.data], { type: 'application/json' });
   const link = document.createElement('a');
   const fileName = `bendaharaku-export-${new Date().toISOString().slice(0,10)}.json`;
   link.href = window.URL.createObjectURL(blob);
   link.download = fileName;
   document.body.appendChild(link);
   link.click();
   link.remove();

   successMessage.value = t('settings.privacy.data.export.success');
   setTimeout(() => (successMessage.value = ''), 3000);
 } catch (err: any) {
   errorMessage.value = err?.response?.data?.message || t('errors.generic');
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
      <!-- Messages -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <button @click="handleExport" :disabled="isExporting" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors">
        {{ isExporting ? t('common.processing') : '📥 ' + t('settings.privacy.data.export.button') }}
      </button>
      </SettingsCard>

      <SettingsCard :title="t('settings.privacy.data.import.title')" :description="t('settings.privacy.data.import.description')">
      <button class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors opacity-50 cursor-not-allowed">
        📤 {{ t('settings.privacy.data.import.button') }}
      </button>
      </SettingsCard>

      <SettingsCard :title="t('settings.privacy.data.backup.title')" :description="t('settings.privacy.data.backup.description')">
        <button class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition-colors opacity-50 cursor-not-allowed">
          💾 {{ t('settings.privacy.data.backup.button') }}
        </button>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
