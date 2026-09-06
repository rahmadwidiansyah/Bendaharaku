<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { ref } from 'vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const page = usePage();
const { showToast } = useToast();

const allowNegativeBalance = ref((page.props.auth as any)?.user?.allow_negative_balance || false);
const saving = ref(false);

const handleToggle = async () => {
  saving.value = true;

  try {
    await axios.patch(route('settings.finance.logic.update'), {
      allow_negative_balance: allowNegativeBalance.value,
    });

    if (page.props.auth && (page.props.auth as any).user) {
      (page.props.auth as any).user.allow_negative_balance = allowNegativeBalance.value;
    }

    showToast(t('toast.updated'), 'success');
  } catch (error: any) {
    allowNegativeBalance.value = !allowNegativeBalance.value;
    showToast(error.response?.data?.message || t('errors.generic'), 'error');
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
      <!-- Allow Negative Balance Toggle -->
      <SettingsCard
        :title="t('settings.finance.defaults.transaction_logic.title')"
        :description="t('settings.finance.defaults.transaction_logic.description')"
      >
        <div class="flex items-center justify-between">
          <div>
            <span class="text-sm text-gray-300 font-medium">{{ t('settings.finance.defaults.transaction_logic.label') }}</span>
            <p class="text-2xs text-[var(--color-text-muted)] mt-0.5">
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
            <div class="w-11 h-6 bg-[var(--color-surface-muted)] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--color-brand)] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--color-brand)] animate-transition"></div>
          </label>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
