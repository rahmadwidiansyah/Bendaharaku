<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useI18n();
const page = usePage();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.ai.title') },
  { label: t('settings.ai.integration.title') },
];

const telegramId = computed(() => (page.props.auth as any)?.user?.telegram_id || '');
const isTelegramActive = computed(() => !!telegramId.value);
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.ai.integration.title')" />
    
    <SettingsLayout
      :title="t('settings.ai.integration.title')"
      :description="t('settings.ai.integration.description')"
      :breadcrumbs="breadcrumbs"
    >
      <!-- Telegram Integration -->
      <SettingsCard :title="t('settings.ai.integrations.telegram.title')" :description="t('settings.ai.integrations.telegram.description')">
        <div class="space-y-4">
          <!-- Status Badge -->
          <div :class="[
            'flex items-center gap-3 p-4 rounded-lg border',
            isTelegramActive 
              ? 'bg-emerald-500/10 border-emerald-500/20' 
              : 'bg-gray-800/50 border-gray-700'
          ]">
            <div :class="[
              'w-3 h-3 rounded-full shrink-0',
              isTelegramActive ? 'bg-emerald-500' : 'bg-gray-500'
            ]" />
            <div>
              <p :class="['text-sm font-semibold', isTelegramActive ? 'text-emerald-400' : 'text-gray-400']">
                {{ isTelegramActive ? 'Active' : 'Not Connected' }}
              </p>
              <p v-if="isTelegramActive" class="text-xs text-gray-400 mt-0.5">
                ID: {{ telegramId }}
              </p>
            </div>
          </div>

          <!-- Open Bot Button -->
          <a
            href="https://t.me/catatwidi_bot"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white rounded-lg text-sm font-semibold transition-all"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z" />
            </svg>
            {{ t('common.open') }} Bot
          </a>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
