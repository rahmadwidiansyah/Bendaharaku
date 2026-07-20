<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.finance.title') },
  { label: t('settings.finance.budget.title') },
];

import { ref, onMounted } from 'vue';
const budgets = ref([] as Array<{id:string,name:string,amount:number,period:string}>);
const newBudget = ref({ name: '', amount: 0, period: 'monthly' });

onMounted(()=>{
  try{ const raw = localStorage.getItem('finance_budgets'); if(raw) budgets.value = JSON.parse(raw);}catch(e){}
});
function persistBudgets(){ try{ localStorage.setItem('finance_budgets', JSON.stringify(budgets.value)); }catch(e){} }
function addBudget(){ if(!newBudget.value.name.trim() || !newBudget.value.amount) return; budgets.value.push({ id: Date.now().toString(), name: newBudget.value.name.trim(), amount: newBudget.value.amount, period: newBudget.value.period}); newBudget.value.name=''; newBudget.value.amount=0; newBudget.value.period='monthly'; persistBudgets(); }
function removeBudget(i:number){ budgets.value.splice(i,1); persistBudgets(); }
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.finance.budget.title')" />
    
    <SettingsLayout
      :title="t('settings.finance.budget.title')"
      :description="t('settings.finance.budget.description')"
      :breadcrumbs="breadcrumbs"
    >
      <SettingsCard :title="t('settings.finance.budget.title')" :description="t('settings.finance.budget.description')">
        <div class="space-y-4">
          <div v-for="(b, idx) in budgets" :key="b.id" class="flex items-center justify-between p-3 bg-gray-900 rounded-lg border border-gray-700">
            <div>
              <div class="text-sm font-medium">{{ b.name }}</div>
              <div class="text-xs text-gray-400">{{ b.amount }} • {{ b.period }}</div>
            </div>
            <div class="flex gap-2">
              <button @click="removeBudget(idx)" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs transition-colors">{{ t('common.delete') }}</button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <input v-model="newBudget.name" :placeholder="t('common.name')" class="col-span-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded focus:outline-none focus:border-purple-500 text-sm text-white" />
            <input v-model.number="newBudget.amount" type="number" :placeholder="t('common.amount')" class="col-span-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded focus:outline-none focus:border-purple-500 text-sm text-white" />
            <select v-model="newBudget.period" class="col-span-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded focus:outline-none focus:border-purple-500 text-sm text-white">
              <option value="monthly">{{ t('common.period') }}: Monthly</option>
              <option value="weekly">{{ t('common.period') }}: Weekly</option>
            </select>
          </div>
          <div class="flex justify-end">
            <button @click="addBudget" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm transition-colors">{{ t('common.add') }}</button>
          </div>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
