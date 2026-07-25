<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import axios from 'axios';

const { t } = useI18n();
const page = usePage();

const allowNegativeBalance = ref((page.props.auth as any)?.user?.allow_negative_balance ?? false);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleToggle = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await axios.patch(route('settings.transaction-logic.update'), {
      allow_negative_balance: allowNegativeBalance.value,
    });

    if (page.props.auth && (page.props.auth as any).user) {
      (page.props.auth as any).user.allow_negative_balance = allowNegativeBalance.value;
    }

    successMessage.value = t('toast.updated');
    setTimeout(() => { successMessage.value = ''; }, 3000);
  } catch (error: any) {
    allowNegativeBalance.value = !allowNegativeBalance.value;
    errorMessage.value = error.response?.data?.message || t('errors.generic');
  } finally {
    saving.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.finance.defaults.transaction_logic.title')" />

    <SettingsLayout
      :title="t('settings.finance.defaults.transaction_logic.title')"
      :description="t('settings.finance.defaults.transaction_logic.description')"
    >
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <SettingsCard
        :title="t('settings.finance.defaults.transaction_logic.title')"
        :description="t('settings.finance.defaults.transaction_logic.description')"
      >
        <div class="flex items-center justify-between">
          <div>
            <span class="text-sm text-gray-300 font-medium">{{ t('settings.finance.defaults.transaction_logic.label') }}</span>
            <p class="text-2xs text-gray-500 mt-0.5">
              {{ allowNegativeBalance
                ? t('settings.finance.defaults.transaction_logic.on')
                : t('settings.finance.defaults.transaction_logic.off')
              }}
            </p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="allowNegativeBalance"
              type="checkbox"
              class="sr-only peer"
              :disabled="saving"
              @change="handleToggle"
            />
            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600 animate-transition"></div>
          </label>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
