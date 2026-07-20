<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.system.title') },
  { label: t('settings.system.diagnostics.title') },
];
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.system.diagnostics.title')" />
    
    <SettingsLayout
      :title="t('settings.system.diagnostics.title')"
      :description="t('settings.system.diagnostics.description')"
      :breadcrumbs="breadcrumbs"
    >
      <SettingsCard :title="t('settings.system.diagnostics.system_status.title')" :description="t('settings.system.diagnostics.system_status.description')">
        <div class="space-y-3">
          <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
            <span class="text-sm text-gray-300">{{ t('settings.system.diagnostics.system_status.api') }}</span>
            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-2xs rounded">{{ t('settings.system.diagnostics.system_status.healthy') }}</span>
          </div>
          <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
            <span class="text-sm text-gray-300">{{ t('settings.system.diagnostics.system_status.database') }}</span>
            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-2xs rounded">{{ t('settings.system.diagnostics.system_status.connected') }}</span>
          </div>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.system.diagnostics.logs.title')" :description="t('settings.system.diagnostics.logs.description')">
        <div class="text-center py-8">
          <p class="text-sm text-gray-400">{{ t('settings.system.diagnostics.logs.no_logs') }}</p>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
