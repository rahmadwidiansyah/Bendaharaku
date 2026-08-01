<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useToast } from '@/Composables/useToast.js';

const { t } = useI18n();
const { showToast } = useToast();

const autoBudgetEnabled = ref(false);
const isLoading = ref(true);
const isSaving = ref(false);

const loadSettings = async () => {
  try {
    const response = await axios.get('/api/v1/budget/settings');
    autoBudgetEnabled.value = Boolean(response.data.auto_budget_enabled);
  } catch {
    showToast(t('settings.finance.budget.save_error'), 'error');
  } finally {
    isLoading.value = false;
  }
};

const saveAutoBudget = async (value: boolean) => {
  isSaving.value = true;
  const previous = autoBudgetEnabled.value;
  autoBudgetEnabled.value = value;

  try {
    await axios.post('/api/v1/budget/settings', { auto_budget_enabled: value });
    showToast(t('settings.finance.budget.save_success'), 'success');
  } catch {
    autoBudgetEnabled.value = previous;
    showToast(t('settings.finance.budget.save_error'), 'error');
  } finally {
    isSaving.value = false;
  }
};

onMounted(loadSettings);
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.finance.budget.title')" />

    <SettingsLayout
      :title="t('settings.finance.budget.title')"
      :description="t('settings.finance.budget.description')"
    >
      <!-- Auto-generate Toggle -->
      <SettingsCard
        :title="t('settings.finance.budget.auto_title')"
        :description="t('settings.finance.budget.auto_description')"
      >
        <div class="flex items-center gap-3">
          <button
            type="button"
            role="switch"
            :aria-checked="autoBudgetEnabled"
            :disabled="isLoading || isSaving"
            @click="saveAutoBudget(!autoBudgetEnabled)"
            class="relative w-10 h-6 rounded-full transition-colors disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            :class="autoBudgetEnabled ? 'bg-purple-600' : 'bg-gray-700'"
          >
            <span
              class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform"
              :class="autoBudgetEnabled ? 'translate-x-4' : ''"
            />
          </button>
          <span class="text-sm text-gray-300">
            {{ autoBudgetEnabled ? t('common.active') : t('common.inactive') }}
          </span>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
