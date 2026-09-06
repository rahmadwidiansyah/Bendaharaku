<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = withDefaults(defineProps<{
  appVersion?: string;
}>(), {
  appVersion: '1.0.0',
});


</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.system.about.title')" />
    
    <SettingsLayout
      :title="t('settings.system.about.title')"
      :description="t('settings.system.about.description')"
    >
      <SettingsCard :title="t('settings.system.about.app_name')" :description="t('settings.system.about.app_description')">
        <div class="space-y-4">
          <div class="flex justify-between">
            <span class="text-2xs sm:text-sm text-[var(--color-text-secondary)]">{{ t('settings.system.about.version') }}</span>
            <span class="text-sm text-[var(--color-text-primary)] font-medium">{{ appVersion }}</span>
          </div>
          <div class="border-t border-[var(--color-border-default)] pt-4">
            <p class="text-2xs text-[var(--color-text-muted)]">{{ t('settings.system.about.app_description') }}</p>
          </div>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.system.about.license.title')" :description="t('settings.system.about.license.description')">
        <p class="text-2xs sm:text-sm text-[var(--color-text-secondary)]">{{ t('settings.system.about.license.type') }}</p>
      </SettingsCard>

      <SettingsCard :title="t('settings.system.about.credits.title')" :description="t('settings.system.about.credits.description')">
        <div class="space-y-2 text-2xs text-[var(--color-text-secondary)]">
          <p>• {{ t('settings.system.about.credits.laravel') }}</p>
          <p>• {{ t('settings.system.about.credits.vue') }}</p>
          <p>• {{ t('settings.system.about.credits.inertia') }}</p>
          <p>• {{ t('settings.system.about.credits.tailwind') }}</p>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
