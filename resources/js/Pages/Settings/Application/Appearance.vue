<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref, watch, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { applyAccentColor, saveAccentColor, ACCENT_PALETTES, isCustomColor, isValidHex, getColorValue } from '@/Composables/useAccentColor.js';

const { t } = useI18n();

const props = defineProps<{
  userAccentColor: string;
}>();

const theme = ref('dark');
const accentColor = ref(props.userAccentColor);
const customHex = ref('');
const showCustomPicker = ref(false);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const isCustom = computed(() => isCustomColor(accentColor.value));

const currentDisplayColor = computed(() => {
  if (isCustom.value) return getColorValue(accentColor.value);
  return ACCENT_PALETTES[accentColor.value]?.[600] || '#9333ea';
});

const customHexValid = computed(() => isValidHex(customHex.value));

function selectPredefined(color: string) {
  accentColor.value = color;
  customHex.value = '';
  showCustomPicker.value = false;
}

function handlePickerInput(e: Event) {
  customHex.value = (e.target as HTMLInputElement).value;
}

watch(customHex, (hex) => {
  if (isValidHex(hex)) {
    accentColor.value = `custom:${hex}`;
  }
});

watch(accentColor, (color) => {
  applyAccentColor(color);
});

onMounted(() => {
  applyAccentColor(accentColor.value);
  if (isCustom.value) {
    customHex.value = getColorValue(accentColor.value);
  }
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
        <!-- Predefined colors grid -->
        <div class="grid grid-cols-6 gap-3">
          <button
            v-for="color in Object.keys(ACCENT_PALETTES)"
            :key="color"
            @click="selectPredefined(color)"
            :style="{ backgroundColor: ACCENT_PALETTES[color][600] }"
            :class="[
              'w-full h-12 rounded-lg transition-all',
              !isCustom && accentColor === color
                ? 'ring-2 ring-white ring-offset-2 ring-offset-gray-900 scale-105'
                : 'hover:scale-105 opacity-70 hover:opacity-100',
            ]"
            :aria-label="t('settings.application.appearance.accent_color.setAccent', { name: color })"
            :aria-pressed="!isCustom && accentColor === color"
          />
        </div>

        <!-- Custom color picker toggle -->
        <button
          type="button"
          @click="showCustomPicker = !showCustomPicker"
          :class="[
            'mt-3 w-full flex items-center gap-3 px-4 py-2.5 rounded-xl border transition-all',
            isCustom
              ? 'border-purple-500/40 bg-purple-500/10 ring-2 ring-purple-500/20'
              : 'border-white/10 bg-gray-800/50 hover:bg-gray-800',
          ]"
        >
          <div
            class="w-6 h-6 rounded-md border border-white/20 shrink-0"
            :style="{ backgroundColor: isCustom ? currentDisplayColor : '#888' }"
          />
          <span class="flex-1 text-left text-xs font-semibold text-gray-300">
            {{ isCustom ? currentDisplayColor : t('settings.application.appearance.accent_color.custom') }}
          </span>
          <svg
            class="w-3.5 h-3.5 text-gray-500 transition-transform"
            :class="showCustomPicker ? 'rotate-180' : ''"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Custom color HEX input + native picker -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          leave-active-class="transition-all duration-150 ease-in"
          enter-from-class="opacity-0 -translate-y-2 max-h-0"
          enter-to-class="opacity-100 translate-y-0 max-h-32"
          leave-from-class="opacity-100 translate-y-0 max-h-32"
          leave-to-class="opacity-0 -translate-y-2 max-h-0"
        >
          <div v-if="showCustomPicker" class="mt-3 overflow-hidden">
            <div class="flex gap-2 items-center p-3 bg-gray-800/80 border border-white/10 rounded-xl">
              <input
                type="color"
                :value="isCustom ? currentDisplayColor : '#8B5CF6'"
                @input="handlePickerInput"
                class="w-10 h-10 rounded-lg border border-white/10 cursor-pointer shrink-0 bg-transparent"
              />
              <input
                v-model="customHex"
                type="text"
                placeholder="#8B5CF6"
                maxlength="7"
                class="flex-1 bg-transparent border-none text-sm text-white font-mono placeholder-gray-600 focus:ring-0 focus:outline-none"
              />
            </div>
            <p v-if="customHex && !customHexValid" class="mt-1 text-2xs text-red-400 ml-1">
              Format HEX tidak valid. Gunakan format #RRGGBB atau #RGB.
            </p>
          </div>
        </Transition>

        <!-- Preview label warna terpilih -->
        <p class="mt-3 text-xs text-gray-400 capitalize">
          {{ t('settings.application.appearance.accent_color.title') }}:
          <span class="text-white font-semibold">{{ isCustom ? currentDisplayColor : accentColor }}</span>
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
