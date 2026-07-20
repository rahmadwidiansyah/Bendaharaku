<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { applyAccentColor, saveAccentColor, ACCENT_PALETTES } from '@/Composables/useAccentColor.js';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.application.title') },
  { label: t('settings.application.appearance.title') },
];

const props = defineProps<{
  userAccentColor: string;
}>();

const theme = ref('dark');
const accentColor = ref(props.userAccentColor);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

// Preview langsung saat user klik warna — tidak perlu simpan dulu
watch(accentColor, (color) => {
  applyAccentColor(color);
});

// Pastikan warna dari DB diterapkan saat halaman dibuka
onMounted(() => {
  applyAccentColor(accentColor.value);
});

const handleSave = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await axios.patch(route('settings.application.appearance.update'), {
      theme: theme.value,
      accent_color: accentColor.value,
    });

    // Simpan ke localStorage agar tetap aktif saat reload sebelum DB di-load
    saveAccentColor(accentColor.value);

    successMessage.value = t('toast.updated');
    setTimeout(() => { successMessage.value = ''; }, 3000);
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

      <!-- Theme -->
      <SettingsCard
        :title="t('settings.application.appearance.theme.title')"
        :description="t('settings.application.appearance.theme.description')"
      >
        <div class="space-y-3">
          <label
            v-for="value in ['light', 'dark', 'system']"
            :key="value"
            class="flex items-center p-3 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-800 transition-colors"
            :class="theme === value && 'border-purple-500 bg-purple-500/10'"
          >
            <input v-model="theme" type="radio" :value="value" class="w-4 h-4" />
            <span class="ml-3 text-sm text-white">
              {{ t(`settings.application.appearance.theme.${value}`) }}
            </span>
          </label>
        </div>
      </SettingsCard>

      <!-- Accent Color -->
      <SettingsCard
        :title="t('settings.application.appearance.accent_color.title')"
        :description="t('settings.application.appearance.accent_color.description')"
      >
        <div class="grid grid-cols-6 gap-3">
          <button
            v-for="color in Object.keys(ACCENT_PALETTES)"
            :key="color"
            @click="accentColor = color"
            :style="{ backgroundColor: ACCENT_PALETTES[color][600] }"
            :class="[
              'w-full h-12 rounded-lg transition-all',
              accentColor === color
                ? 'ring-2 ring-white ring-offset-2 ring-offset-gray-900 scale-105'
                : 'hover:scale-105 opacity-70 hover:opacity-100',
            ]"
            :aria-label="`Set ${color} as accent color`"
            :aria-pressed="accentColor === color"
          />
        </div>
        <!-- Preview label warna terpilih -->
        <p class="mt-3 text-xs text-gray-400 capitalize">
          {{ t('settings.application.appearance.accent_color.title') }}:
          <span class="text-white font-semibold">{{ accentColor }}</span>
        </p>
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
