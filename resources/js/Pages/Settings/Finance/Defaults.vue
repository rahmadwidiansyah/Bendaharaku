<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';

const { t } = useI18n();
const page = usePage();

interface Wallet {
  id: number;
  name: string;
  group_type: string;
}

const props = defineProps<{
  wallets?: Wallet[];
}>();

const availableWallets = computed(() => props.wallets || []);

const defaultWallet = ref('');
const defaultCurrency = ref('IDR');
const allowNegativeBalance = ref((page.props.auth as any)?.user?.allow_negative_balance || false);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.finance.title') },
  { label: t('settings.finance.defaults.title') },
];

onMounted(() => {
  defaultWallet.value = localStorage.getItem('default_wallet') || (availableWallets.value[0]?.id?.toString() || '');
  defaultCurrency.value = localStorage.getItem('default_currency') || 'IDR';
});

const handleSave = async () => {
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await axios.patch(route('settings.finance.defaults.update'), {
      default_wallet: defaultWallet.value,
      default_currency: defaultCurrency.value,
      allow_negative_balance: allowNegativeBalance.value,
    });

    localStorage.setItem('default_wallet', defaultWallet.value);
    localStorage.setItem('default_currency', defaultCurrency.value);

    // Update frontend auth user object so other pages get updated value
    if (page.props.auth && (page.props.auth as any).user) {
      (page.props.auth as any).user.allow_negative_balance = allowNegativeBalance.value;
    }

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
    <Head :title="t('settings.finance.defaults.title')" />
    
    <SettingsLayout
      :title="t('settings.finance.defaults.title')"
      :description="t('settings.finance.defaults.description')"
      :breadcrumbs="breadcrumbs"
    >
      <!-- Messages -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <!-- Default Wallet Selection -->
      <SettingsCard :title="t('settings.finance.defaults.wallet.title')" :description="t('settings.finance.defaults.wallet.description')">
        <div class="space-y-2">
          <select v-model="defaultWallet" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-purple-500">
            <option value="">{{ t('transaction.chooseWallet') }}</option>
            <option v-for="w in availableWallets" :key="w.id" :value="w.id.toString()">
              {{ w.name }} ({{ w.group_type }})
            </option>
          </select>
        </div>
      </SettingsCard>

      <!-- Default Currency Selection -->
      <SettingsCard :title="t('settings.finance.defaults.currency.title')" :description="t('settings.finance.defaults.currency.description')">
        <div class="space-y-2">
          <select v-model="defaultCurrency" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-purple-500">
            <option value="IDR">{{ t('settings.application.language.currency.idr') }}</option>
            <option value="USD">{{ t('settings.application.language.currency.usd') }}</option>
            <option value="EUR">{{ t('settings.application.language.currency.eur') }}</option>
          </select>
        </div>
      </SettingsCard>

      <!-- Transaction Logic (Allow Negative Balance) -->
      <SettingsCard :title="t('settings.finance.defaults.transaction_logic.title')" :description="t('settings.finance.defaults.transaction_logic.description')">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">{{ t('settings.finance.defaults.transaction_logic.label') }}</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input v-model="allowNegativeBalance" type="checkbox" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600 animate-transition"></div>
          </label>
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
