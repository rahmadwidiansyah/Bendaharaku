<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { t, locale } = useI18n();
const { showToast } = useToast();


const props = defineProps<{
  userTimezone: string;
  userDateFormat: string;
  userLanguage: string;
}>();

const timezone = ref(props.userTimezone);
const dateFormat = ref(props.userDateFormat);
const language = ref(props.userLanguage);
const saving = ref(false);
const handleSave = async () => {
  saving.value = true;

  try {
    await axios.patch(route('settings.account.preferences.update'), {
      timezone: timezone.value,
      date_format: dateFormat.value,
      language: language.value,
    });

    if (language.value !== locale.value) {
      locale.value = language.value;
      localStorage.setItem('locale', language.value);
    }

    showToast(t('toast.updated'), 'success');
  } catch (error: any) {
    showToast(error.response?.data?.message || t('errors.generic'), 'error');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.account.preferences.title')" />
    
    <SettingsLayout
      :title="t('settings.account.preferences.title')"
      :description="t('settings.account.preferences.description')"
    >
      <form @submit.prevent="handleSave" class="space-y-3 sm:space-y-5">
        <SettingsCard :title="t('settings.account.preferences.language.title')" :description="t('settings.account.preferences.language.description')">
          <select v-model="language" class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] focus:outline-none focus:border-purple-500 transition-all text-sm">
            <option value="id">{{ t('settings.account.preferences.language.id') }}</option>
            <option value="en">{{ t('settings.account.preferences.language.en') }}</option>
          </select>
        </SettingsCard>

        <SettingsCard :title="t('settings.account.preferences.timezone.title')" :description="t('settings.account.preferences.timezone.description')">
          <select v-model="timezone" class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] focus:outline-none focus:border-purple-500 transition-all text-sm">
            <option>UTC</option>
            <option>Asia/Jakarta</option>
            <option>Asia/Bangkok</option>
            <option>Asia/Ho_Chi_Minh</option>
            <option>Asia/Singapore</option>
          </select>
        </SettingsCard>

        <SettingsCard :title="t('settings.account.preferences.date_format.title')" :description="t('settings.account.preferences.date_format.description')">
          <select v-model="dateFormat" class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] focus:outline-none focus:border-purple-500 transition-all text-sm">
            <option value="DD/MM/YYYY">{{ t('settings.account.preferences.date_format.ddmmyyyy') }}</option>
            <option value="MM/DD/YYYY">{{ t('settings.account.preferences.date_format.mmddyyyy') }}</option>
            <option value="YYYY-MM-DD">{{ t('settings.account.preferences.date_format.yyyymmdd') }}</option>
          </select>
        </SettingsCard>

        <!-- Save -->
        <div class="flex gap-4 pt-1 sm:pt-2">
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 sm:px-6 sm:py-3 bg-[var(--color-brand)] hover:bg-[var(--color-brand-hover)] disabled:bg-[var(--color-surface-muted)] disabled:cursor-not-allowed text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-lg shadow-purple-900/20"
          >
            <span v-if="saving" class="flex items-center gap-2">
              <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ t('common.saving') }}
            </span>
            <span v-else>{{ t('common.save') }}</span>
          </button>
        </div>
      </form>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
