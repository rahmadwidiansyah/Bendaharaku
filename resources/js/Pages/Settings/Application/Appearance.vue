<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.application.title') },
  { label: t('settings.application.appearance.title') },
];

const theme = ref('dark');
const accentColor = ref('purple');
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleSave = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await axios.patch(route('settings.application.appearance.update'), {
      theme: theme.value,
      accent_color: accentColor.value,
    });

    successMessage.value = t('toast.updated');
    setTimeout(() => {
      successMessage.value = '';
    }, 3000);
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || t('errors.generic');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.application.appearance.title')" />
    
    <SettingsLayout
      :title="t('settings.application.appearance.title')"
      :description="t('settings.application.appearance.description')"
      :breadcrumbs="breadcrumbs"
    >
      <!-- Messages -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <SettingsCard :title="t('settings.application.appearance.theme.title')" :description="t('settings.application.appearance.theme.description')">
        <div class="space-y-3">
          <label class="flex items-center p-3 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 transition-colors"
            :class="theme === 'light' && 'border-purple-500 bg-purple-500/10'">
            <input v-model="theme" type="radio" value="light" class="w-4 h-4" />
            <span class="ml-3 text-sm text-white">{{ t('settings.application.appearance.theme.light') }}</span>
          </label>
          <label class="flex items-center p-3 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 transition-colors"
            :class="theme === 'dark' && 'border-purple-500 bg-purple-500/10'">
            <input v-model="theme" type="radio" value="dark" class="w-4 h-4" />
            <span class="ml-3 text-sm text-white">{{ t('settings.application.appearance.theme.dark') }}</span>
          </label>
          <label class="flex items-center p-3 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 transition-colors"
            :class="theme === 'system' && 'border-purple-500 bg-purple-500/10'">
            <input v-model="theme" type="radio" value="system" class="w-4 h-4" />
            <span class="ml-3 text-sm text-white">{{ t('settings.application.appearance.theme.system') }}</span>
          </label>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.application.appearance.accent_color.title')" :description="t('settings.application.appearance.accent_color.description')">
        <div class="grid grid-cols-4 gap-3">
          <button
            v-for="color in ['purple', 'blue', 'green', 'orange', 'red', 'pink']"
            :key="color"
            @click="accentColor = color"
            :class="[
              'w-full h-12 rounded-lg transition-all',
              accentColor === color ? 'ring-2 ring-white' : 'hover:scale-105',
              {
                'bg-purple-600': color === 'purple',
                'bg-blue-600': color === 'blue',
                'bg-green-600': color === 'green',
                'bg-orange-600': color === 'orange',
                'bg-red-600': color === 'red',
                'bg-pink-600': color === 'pink',
              }
            ]"
            :aria-label="`Set ${color} as accent color`"
          />
        </div>
      </SettingsCard>

      <!-- Save Button -->
      <div class="flex gap-3 pt-4">
        <button
          @click="handleSave"
          :disabled="saving"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors"
        >
          {{ saving ? t('common.saving') : t('common.save') }}
        </button>
      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
