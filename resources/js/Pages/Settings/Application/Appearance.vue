<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref, watch, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { applyAccentColor, saveAccentColor, ACCENT_PALETTES, isCustomColor, isValidHex, getColorValue } from '@/Composables/useAccentColor.js';
import { setCategoryIconColored } from '@/Composables/useIcon.js';
import { useTheme } from '@/Composables/useTheme';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();

const props = defineProps<{
  userAccentColor: string;
  userTheme: string;
  categoryIconColored: boolean;
}>();

const { theme: currentTheme, setTheme } = useTheme();
const theme = ref(props.userTheme ?? 'system');
const accentColor = ref(props.userAccentColor);
const customHex = ref('');
const showCustomPicker = ref(false);
const saving = ref(false);
const iconColored = ref(props.categoryIconColored);


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
  saveAppearance();
});

watch(theme, (newTheme) => {
  setTheme(newTheme);
  saveAppearance();
});

watch(iconColored, () => {
  saveAppearance();
});

onMounted(() => {
  applyAccentColor(accentColor.value);
  if (isCustom.value) {
    customHex.value = getColorValue(accentColor.value);
  }
});

let saveTimer = null;
const saveAppearance = () => {
  if (saveTimer) clearTimeout(saveTimer);
  saveTimer = setTimeout(async () => {
    saving.value = true;
    try {
      await axios.patch(route('settings.application.appearance.update'), {
        theme: theme.value,
        accent_color: accentColor.value,
        category_icon_colored: iconColored.value,
      });
      saveAccentColor(accentColor.value);
      setCategoryIconColored(iconColored.value);
      showToast(t('toast.updated'), 'success');
    } catch (error: any) {
      showToast(error.response?.data?.message || t('errors.generic'), 'error');
    } finally {
      saving.value = false;
    }
  }, 500);
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.application.appearance.title')" />

    <SettingsLayout
      :title="t('settings.application.appearance.title')"
      :description="t('settings.application.appearance.description')"
    >
      <!-- Theme -->
      <SettingsCard
        :title="t('settings.application.appearance.theme.title')"
        :description="t('settings.application.appearance.theme.description')"
      >
        <div class="space-y-3">
          <label
            v-for="value in ['light', 'dark', 'system']"
            :key="value"
            class="flex items-center p-3 border border-border-default rounded-lg cursor-pointer hover:bg-surface-muted transition-colors"
            :class="theme === value && 'border-purple-500 bg-purple-500/10'"
          >
            <input v-model="theme" type="radio" :value="value" class="w-4 h-4" />
            <span class="ml-3 text-sm text-text-primary">
              {{ t(`settings.application.appearance.theme.${value}`) }}
            </span>
          </label>
        </div>
      </SettingsCard>

      <!-- Category Icon Color -->
      <SettingsCard
        :title="t('settings.application.appearance.category_icon_color.title')"
        :description="t('settings.application.appearance.category_icon_color.description')"
      >
        <label class="flex items-center justify-between p-3 border border-border-default rounded-lg cursor-pointer hover:bg-surface-muted transition-colors">
          <div>
            <p class="text-sm text-text-primary font-medium">{{ t('settings.application.appearance.category_icon_color.label') }}</p>
            <p class="text-2xs text-text-muted mt-0.5">{{ iconColored ? t('settings.application.appearance.category_icon_color.on') : t('settings.application.appearance.category_icon_color.off') }}</p>
          </div>
          <button
            type="button"
            role="switch"
            :aria-checked="iconColored"
            @click="iconColored = !iconColored"
            class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[var(--color-brand)] focus:ring-offset-2 focus:ring-offset-surface-raised"
            :class="iconColored ? 'bg-[var(--color-brand)]' : 'bg-surface-muted'"
          >
            <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out" :class="iconColored ? 'translate-x-5' : 'translate-x-0'" />
          </button>
        </label>
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
                ? 'ring-2 ring-white ring-offset-2 ring-offset-surface-raised scale-105'
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
              : 'border-border-default bg-surface-muted hover:bg-surface-overlay',
          ]"
        >
          <div
            class="w-6 h-6 rounded-md border border-border-subtle shrink-0"
            :style="{ backgroundColor: isCustom ? currentDisplayColor : '#888' }"
          />
          <span class="flex-1 text-left text-xs font-semibold text-text-secondary">
            {{ isCustom ? currentDisplayColor : t('settings.application.appearance.accent_color.custom') }}
          </span>
          <svg
            class="w-3.5 h-3.5 text-text-muted transition-transform"
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
            <div class="flex gap-2 items-center p-3 bg-surface-overlay border border-border-default rounded-xl">
              <input
                type="color"
                :value="isCustom ? currentDisplayColor : '#8B5CF6'"
                @input="handlePickerInput"
                class="w-10 h-10 rounded-lg border border-border-default cursor-pointer shrink-0 bg-transparent"
              />
              <input
                v-model="customHex"
                type="text"
                placeholder="#8B5CF6"
                maxlength="7"
                class="flex-1 bg-transparent border-none text-sm text-text-primary font-mono placeholder-text-muted focus:ring-0 focus:outline-none"
              />
            </div>
            <p v-if="customHex && !customHexValid" class="mt-1 text-2xs text-expense-text ml-1">
              Format HEX tidak valid. Gunakan format #RRGGBB atau #RGB.
            </p>
          </div>
        </Transition>

        <!-- Preview label warna terpilih -->
        <p class="mt-3 text-xs text-text-secondary capitalize">
          {{ t('settings.application.appearance.accent_color.title') }}:
          <span class="text-text-primary font-semibold">{{ isCustom ? currentDisplayColor : accentColor }}</span>
        </p>
      </SettingsCard>


    </SettingsLayout>
  </AuthenticatedLayout>
</template>
