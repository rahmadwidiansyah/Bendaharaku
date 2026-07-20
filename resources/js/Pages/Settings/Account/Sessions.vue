<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.account.title') },
  { label: t('settings.account.sessions.title') },
];
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.account.sessions.title')" />
    
    <SettingsLayout
      :title="t('settings.account.sessions.title')"
      :description="t('settings.account.sessions.description')"
      :breadcrumbs="breadcrumbs"
    >
      <SettingsCard :title="t('settings.account.sessions.current')" :description="t('settings.account.sessions.description')">
        <div class="space-y-4">
          <div class="flex justify-between items-center p-3 bg-gray-800 rounded-lg">
            <div>
              <p class="text-sm font-medium text-white">{{ t('settings.account.sessions.current_browser') }}</p>
              <p class="text-2xs text-gray-400">{{ t('settings.account.sessions.last_active') }}</p>
            </div>
            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-2xs rounded">{{ t('settings.account.sessions.active') }}</span>
          </div>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.account.sessions.other_sessions')" :description="t('settings.account.sessions.description')">
        <div class="text-center py-8">
          <p class="text-sm text-gray-400">{{ t('settings.account.sessions.no_other_sessions') }}</p>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
