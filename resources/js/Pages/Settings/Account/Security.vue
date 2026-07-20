<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';

const { t } = useI18n();
const page = usePage();

const breadcrumbs = [
  { label: t('settings.title'), href: route('settings.index') },
  { label: t('settings.account.title') },
  { label: t('settings.account.security.title') },
];

interface SessionInfo {
  ip: string;
  user_agent: string;
  last_activity: string;
  is_current?: boolean;
}

const props = withDefaults(
  defineProps<{
    currentSession?: SessionInfo | null;
    otherSessions?: SessionInfo[];
  }>(),
  {
    currentSession: null,
    otherSessions: () => [],
  }
);

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const successMessage = ref('');
const errorMessage = ref('');

function parseBrowser(ua: string): string {
  if (!ua) return 'Unknown';
  if (ua.includes('Edg')) return 'Edge';
  if (ua.includes('Chrome')) return 'Chrome';
  if (ua.includes('Firefox')) return 'Firefox';
  if (ua.includes('Safari')) return 'Safari';
  return 'Browser';
}

function formatActivity(ts: string): string {
  if (!ts) return '';
  const date = new Date(ts);
  if (isNaN(date.getTime())) return ts;
  return date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

const handleUpdatePassword = () => {
  errorMessage.value = '';
  successMessage.value = '';

  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Passwords do not match.';
    return;
  }

  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      successMessage.value = t('profile.passwordUpdated', 'Password updated successfully.');
      setTimeout(() => { successMessage.value = ''; }, 4000);
    },
    onError: () => {
      errorMessage.value = form.errors.current_password
        || form.errors.password
        || form.errors.password_confirmation
        || t('errors.generic');
    },
  });
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.account.security.title')" />
    
    <SettingsLayout
      :title="t('settings.account.security.title')"
      :description="t('settings.account.security.description')"
      :breadcrumbs="breadcrumbs"
    >
      <!-- Success/Error Banners -->
      <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg">
        <p class="text-sm text-green-400">✓ {{ successMessage }}</p>
      </div>
      <div v-if="errorMessage" class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
        <p class="text-sm text-red-400">✗ {{ errorMessage }}</p>
      </div>

      <!-- Change Password Form -->
      <SettingsCard :title="t('settings.account.security.password.title')" :description="t('settings.account.security.password.description')">
        <form @submit.prevent="handleUpdatePassword" class="space-y-4">
          <!-- Current Password -->
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
              {{ t('profile.currentPassword') }}
            </label>
            <input
              v-model="form.current_password"
              type="password"
              required
              class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-colors"
              :placeholder="t('profile.currentPassword')"
            />
            <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-500 font-semibold">{{ form.errors.current_password }}</p>
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
              {{ t('profile.newPassword') }}
            </label>
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-colors"
              :placeholder="t('profile.newPassword')"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500 font-semibold">{{ form.errors.password }}</p>
          </div>

          <!-- Confirm New Password -->
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
              {{ t('profile.confirmPassword') }}
            </label>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              class="w-full px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-colors"
              :placeholder="t('profile.confirmPassword')"
            />
            <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500 font-semibold">{{ form.errors.password_confirmation }}</p>
          </div>

          <!-- Save Button -->
          <div class="flex gap-3 pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full sm:w-auto px-5 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-colors"
            >
              {{ form.processing ? t('common.saving') : t('settings.account.security.password.change_button') }}
            </button>
          </div>
        </form>
      </SettingsCard>

      <!-- Active Sessions and History Section -->
      <SettingsCard :title="t('settings.account.security.login_activity.title')" :description="t('settings.account.security.login_activity.description')">
        <div class="space-y-3">

          <!-- No session data -->
          <template v-if="!currentSession">
            <p class="text-sm text-gray-400">⏳ Riwayat login sedang dikembangkan.</p>
          </template>

          <template v-else>
            <!-- Current Session -->
            <div class="flex items-start gap-3 p-3 bg-green-500/5 border border-green-500/20 rounded-xl">
              <span class="text-xl mt-0.5 shrink-0">🖥️</span>
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="text-sm font-semibold text-white">{{ parseBrowser(currentSession.user_agent) }}</p>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-green-500/20 text-green-400 border border-green-500/30">
                    Sesi ini
                  </span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">{{ currentSession.ip }}</p>
                <p v-if="currentSession.last_activity" class="text-xs text-gray-500 mt-0.5">
                  {{ formatActivity(currentSession.last_activity) }}
                </p>
              </div>
            </div>

            <!-- Other Sessions -->
            <template v-if="otherSessions && otherSessions.length > 0">
              <div
                v-for="(session, index) in otherSessions"
                :key="index"
                class="flex items-start gap-3 p-3 bg-gray-800/50 border border-white/[0.07] rounded-xl"
              >
                <span class="text-xl mt-0.5 shrink-0">🖥️</span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-white">{{ parseBrowser(session.user_agent) }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">{{ session.ip }}</p>
                  <p v-if="session.last_activity" class="text-xs text-gray-500 mt-0.5">
                    {{ formatActivity(session.last_activity) }}
                  </p>
                </div>
              </div>
            </template>
          </template>

        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
