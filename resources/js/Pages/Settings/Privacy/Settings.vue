<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.privacy.title') },
  { label: t('settings.privacy.settings.title') },
];
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.privacy.settings.title')" />
    
    <SettingsLayout
      :title="t('settings.privacy.settings.title')"
      :description="t('settings.privacy.settings.description')"
      :breadcrumbs="breadcrumbs"
    >
      <SettingsCard :title="t('settings.privacy.settings.data_collection.title')" :description="t('settings.privacy.settings.data_collection.description')">
        <div class="p-4 bg-blue-900/20 border border-blue-500/20 rounded-lg">
          <p class="text-sm text-blue-400">
            ℹ️ Semua data transaksi, kategori, dan pengaturan disimpan dalam database aplikasi Anda. Kami tidak membagikan data dengan pihak ketiga tanpa persetujuan.
          </p>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.privacy.settings.analytics.title')" :description="t('settings.privacy.settings.analytics.description')">
        <div class="p-4 bg-blue-900/20 border border-blue-500/20 rounded-lg">
          <p class="text-sm text-blue-400">
            ℹ️ Kebijakan privasi organisasi berlaku untuk semua pengguna aplikasi ini. Semua log dan data audit disimpan secara internal untuk keamanan dan compliance.
          </p>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
