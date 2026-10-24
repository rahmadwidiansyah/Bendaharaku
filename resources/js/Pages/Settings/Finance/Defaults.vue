<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
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
          <ToggleSwitch
            v-model="allowNegativeBalance"
            :disabled="saving"
            :aria-label="t('settings.finance.defaults.transaction_logic.label')"
            @change="handleToggle"
          />
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
