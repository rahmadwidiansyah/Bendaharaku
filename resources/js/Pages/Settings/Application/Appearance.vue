<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref, watch, onMounted, computed, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { applyAccentColor, saveAccentColor, ACCENT_PALETTES, isCustomColor, isValidHex, getColorValue } from '@/Composables/useAccentColor.js';
import { setCategoryIconColored } from '@/Composables/useIcon.js';
import { useTheme } from '@/Composables/useTheme';
import { M3_SCHEME_KEYS, DEFAULT_M3_COLOR_SCHEME, isValidM3ColorScheme, applyM3ColorScheme, getM3SchemePreview } from '@/Composables/useM3ColorScheme.js';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { showToast } = useToast();

const props = defineProps<{
  userAccentColor: string;
  userTheme: string;
  categoryIconColored: boolean;
  userUiTheme: string;
  userM3ColorScheme: string;
}>();

const { theme: currentTheme, setTheme } = useTheme();
const theme = ref(props.userTheme ?? 'system');
const accentColor = ref(props.userAccentColor);
const customHex = ref('');
const showCustomPicker = ref(false);
const saving = ref(false);
const iconColored = ref(props.categoryIconColored);
// Multi-UI: pilihan UI tersimpan; dipakai untuk deteksi perubahan + revert saat gagal.
const uiTheme = ref(props.userUiTheme ?? 'default');
const savedUiTheme = ref(uiTheme.value);

// Skema warna M3 (hanya relevan saat ui_theme === 'm3'). INDEPENDEN dari
// accent_color UI Default; tersimpan di users.ui_m3_color_scheme.
const m3ColorScheme = ref(isValidM3ColorScheme(props.userM3ColorScheme) ? props.userM3ColorScheme : DEFAULT_M3_COLOR_SCHEME);
const savedM3ColorScheme = ref(m3ColorScheme.value);
// Warna pratinjau mengikuti mode terang/gelap Default UI saat ini agar chip
// tetap kontras di kartu pengaturan (bergantung pada `theme` agar reaktif).
const m3PreviewMode = computed(() => {
  void theme.value;
  return document.documentElement.classList.contains('light') ? 'light' : 'dark';
});

const isM3Active = computed(() => uiTheme.value === 'm3');


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

let revertingM3Scheme = false;
watch(m3ColorScheme, (scheme) => {
  if (!isM3Active.value) return;
  applyM3ColorScheme(scheme);
  // Lewati save saat revert otomatis pasca-gagal (hindari PATCH ganda).
  if (revertingM3Scheme) return;
  saveAppearance();
});

let revertingUiTheme = false;
watch(uiTheme, () => {
  // Lewati save saat revert otomatis pasca-gagal (hindari PATCH ganda).
  if (revertingUiTheme) return;
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
        ui_theme: uiTheme.value,
        ui_m3_color_scheme: m3ColorScheme.value,
      });
      saveAccentColor(accentColor.value);
      setCategoryIconColored(iconColored.value);
      savedM3ColorScheme.value = m3ColorScheme.value;
      showToast(t('toast.updated'), 'success');
      // Komponen halaman Dashboard berubah sesuai ui_theme — reload penuh
      // hanya bila pilihan UI benar-benar berubah dan berhasil disimpan.
      if (uiTheme.value !== savedUiTheme.value) {
        savedUiTheme.value = uiTheme.value;
        window.location.reload();
      }
    } catch (error: any) {
      // Gagal: kembalikan pilihan UI + skema M3 ke nilai tersimpan, tanpa
      // reload. Watcher berjalan async — flag direset setelah watcher terpanggil.
      revertingUiTheme = true;
      uiTheme.value = savedUiTheme.value;
      nextTick(() => { revertingUiTheme = false; });
      revertingM3Scheme = true;
      m3ColorScheme.value = savedM3ColorScheme.value;
      applyM3ColorScheme(savedM3ColorScheme.value);
      nextTick(() => { revertingM3Scheme = false; });
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
      <!-- UI Theme (Multi-UI Phase 1: Dashboard saja) -->
      <SettingsCard
        :title="t('settings.application.appearance.ui_theme.title')"
        :description="t('settings.application.appearance.ui_theme.description')"
      >
        <div class="space-y-3">
          <label
            v-for="value in ['default', 'm3']"
            :key="value"
            class="flex items-start gap-3 p-3 border border-border-default rounded-lg cursor-pointer hover:bg-surface-muted transition-colors"
            :class="uiTheme === value && 'border-purple-500 bg-purple-500/10'"
          >
            <input v-model="uiTheme" type="radio" :value="value" class="w-4 h-4 mt-0.5" />
            <span>
              <span class="block text-sm text-text-primary">
                {{ t(`settings.application.appearance.ui_theme.${value}`) }}
              </span>
              <span class="block text-2xs text-text-muted mt-0.5">
                {{ t(`settings.application.appearance.ui_theme.${value}_desc`) }}
              </span>
            </span>
          </label>
        </div>
      </SettingsCard>

      <!-- M3 Color Scheme (hanya tampil saat M3 UI aktif; independen dari
           accent_color Default di bawah) -->
      <SettingsCard v-if="isM3Active"
        :title="t('settings.application.appearance.m3_color_scheme.title')"
        :description="t('settings.application.appearance.m3_color_scheme.description')"
      >
        <div class="grid grid-cols-3 gap-3">
          <label
            v-for="key in M3_SCHEME_KEYS"
            :key="key"
            class="flex flex-col items-center gap-2 p-3 border border-border-default rounded-lg cursor-pointer hover:bg-surface-muted transition-colors focus-within:ring-2 focus-within:ring-[var(--color-brand)]"
            :class="m3ColorScheme === key && 'border-purple-500 bg-purple-500/10'"
          >
            <input v-model="m3ColorScheme" type="radio" :value="key" class="sr-only" />
            <span
              class="w-12 h-12 rounded-full border border-border-default flex items-center justify-center gap-0.5 mb-1 shrink-0"
              :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].surface }"
              aria-hidden="true"
            >
              <span class="w-3.5 h-3.5 rounded-full" :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].primary }" />
              <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].secondary }" />
              <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].tertiary }" />
            </span>
            <span class="text-xs text-text-primary text-center font-medium">{{ t(`settings.application.appearance.m3_color_scheme.${key}`) }}</span>
            <span class="text-2xs text-text-muted text-center leading-tight">{{ t(`settings.application.appearance.m3_color_scheme.${key}_desc`) }}</span>
          </label>
        </div>
      </SettingsCard>

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
