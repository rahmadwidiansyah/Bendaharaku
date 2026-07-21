<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();


</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.finance.categories.title')" />
    
    <SettingsLayout
      :title="t('settings.finance.categories.title')"
      :description="t('settings.finance.categories.description')"
    >
      <SettingsCard :title="t('settings.finance.categories.title')" :description="t('settings.finance.categories.description')">
        <div class="text-center py-8">
          <p class="text-sm text-gray-400">{{ t('settings.finance.categories.manage') }}</p>
          <Link :href="route('categories.index')" class="mt-4 inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors">
            {{ t('settings.finance.categories.go_to') }}
          </Link>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
