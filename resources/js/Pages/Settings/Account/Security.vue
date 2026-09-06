<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const page = usePage();
const { showToast } = useToast();


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

const sessions = computed(() => {
  const all = [...(props.otherSessions ?? [])];
  if (props.currentSession) {
    const idx = all.findIndex(s => s.ip === props.currentSession!.ip);
    if (idx >= 0) {
      if (new Date(props.currentSession.last_activity) >= new Date(all[idx].last_activity)) {
        all[idx] = { ...props.currentSession, is_current: true };
      }
    } else {
      all.push({ ...props.currentSession, is_current: true });
    }
  }
  const map = new Map<string, SessionInfo>();
  for (const s of all) {
    const existing = map.get(s.ip);
    if (!existing || new Date(s.last_activity) >= new Date(existing.last_activity)) {
      map.set(s.ip, s);
    }
  }
  return Array.from(map.values()).sort(
    (a, b) => new Date(b.last_activity).getTime() - new Date(a.last_activity).getTime()
  );
});

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

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

  if (form.password !== form.password_confirmation) {
    showToast('Passwords do not match.', 'error');
    return;
  }

  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      showToast(t('profile.passwordUpdated'), 'success');
    },
    onError: () => {
      showToast(form.errors.current_password
        || form.errors.password
        || form.errors.password_confirmation
        || t('errors.generic'), 'error');
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
    >
      <!-- Change Password Form -->
      <SettingsCard :title="t('settings.account.security.password.title')" :description="t('settings.account.security.password.description')">
        <form @submit.prevent="handleUpdatePassword" class="space-y-3">
          <!-- Current Password -->
          <div>
            <label class="block text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest mb-1 ml-1">
              {{ t('profile.currentPassword') }}
            </label>
            <input
              v-model="form.current_password"
              type="password"
              required
              class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-all text-sm"
              :placeholder="t('profile.currentPassword')"
            />
            <p v-if="form.errors.current_password" class="mt-1 text-2xs text-red-500 font-semibold ml-1">{{ form.errors.current_password }}</p>
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest mb-1 ml-1">
              {{ t('profile.newPassword') }}
            </label>
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-all text-sm"
              :placeholder="t('profile.newPassword')"
            />
            <p v-if="form.errors.password" class="mt-1 text-2xs text-red-500 font-semibold ml-1">{{ form.errors.password }}</p>
          </div>

          <!-- Confirm New Password -->
          <div>
            <label class="block text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest mb-1 ml-1">
              {{ t('profile.confirmPassword') }}
            </label>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              class="w-full px-3 py-1.5 sm:px-4 sm:py-2.5 bg-[var(--color-surface-muted)] border border-[var(--color-border-default)] rounded-lg sm:rounded-xl text-[var(--color-text-primary)] placeholder-gray-500 focus:outline-none focus:border-purple-500 transition-all text-sm"
              :placeholder="t('profile.confirmPassword')"
            />
            <p v-if="form.errors.password_confirmation" class="mt-1 text-2xs text-red-500 font-semibold ml-1">{{ form.errors.password_confirmation }}</p>
          </div>

          <!-- Save Button -->
          <div class="flex gap-2 pt-1 sm:pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full sm:w-auto px-4 py-2 sm:px-5 sm:py-3 bg-[var(--color-brand)] hover:bg-[var(--color-brand-hover)] disabled:bg-[var(--color-surface-muted)] disabled:cursor-not-allowed text-[var(--color-text-primary)] rounded-lg sm:rounded-xl text-xs font-bold uppercase tracking-wider transition-all"
            >
              {{ form.processing ? t('common.saving') : t('settings.account.security.password.change_button') }}
            </button>
          </div>
        </form>
      </SettingsCard>

      <!-- Active Sessions -->
      <SettingsCard :title="t('settings.account.security.login_activity.title')" :description="t('settings.account.security.login_activity.description')">
        <div class="space-y-2">
          <div v-if="sessions.length === 0" class="text-2xs sm:text-sm text-[var(--color-text-secondary)] py-2 text-center">{{ t('common.noData') }}</div>

          <div
            v-for="(session, index) in sessions"
            :key="session.ip + index"
            :class="[
              'flex items-start gap-2.5 sm:gap-3 p-2.5 sm:p-3 rounded-lg sm:rounded-xl border transition-colors',
              session.is_current
                ? 'bg-green-500/5 border-green-500/20'
                : 'bg-[var(--color-surface-muted)]/50 border-white/5',
            ]"
          >
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center shrink-0 bg-[var(--color-surface-muted)] border border-white/5">
              <svg class="w-4 h-4 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-1.5">
                <p class="text-xs sm:text-sm font-semibold text-[var(--color-text-primary)]">{{ parseBrowser(session.user_agent) }}</p>
                <span v-if="session.is_current" class="px-1.5 py-0.5 rounded-full text-2xs font-bold bg-green-500/20 text-green-400 border border-green-500/30">
                  {{ t('settings.account.security.login_activity.current') }}
                </span>
              </div>
              <p class="text-2xs text-[var(--color-text-secondary)] mt-0.5">{{ session.ip }}</p>
              <p v-if="session.last_activity" class="text-2xs text-[var(--color-text-muted)] mt-0.5">
                {{ formatActivity(session.last_activity) }}
              </p>
            </div>
          </div>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
