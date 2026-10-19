<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from '../Layouts/SettingsLayout.vue';
import SettingsCard from '@/Components/Settings/SettingsCard.vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';
import { usePushNotifications } from '@/Composables/usePushNotifications';

interface Props {
  emailNotifications: boolean;
  pushNotifications: boolean;
}

const props = defineProps<Props>();

const page = usePage();
const { t } = useI18n();
const { showToast } = useToast();
const push = usePushNotifications();

const vapidPublicKey = computed<string | null>(() => (page.props as any).vapidPublicKey ?? null);
const pushEnabled = ref(props.pushNotifications);

const permissionHint = computed(() => {
  if (!push.isSupported) return t('settings.application.notifications.push.unsupported');
  if (!vapidPublicKey.value) return t('settings.application.notifications.push.vapid_missing');
  if (push.permission.value === 'denied') return t('settings.application.notifications.push.denied');
  if (push.permission.value === 'granted') return t('settings.application.notifications.push.granted');
  return t('settings.application.notifications.push.default');
});

interface HubLink {
  labelKey: string;
  descKey: string;
  route: string;
  iconPath: string;
}

const botLinks: HubLink[] = [
  {
    labelKey: 'settings.chat.hub.bot.title',
    descKey: 'settings.chat.hub.bot.description',
    route: 'settings.ai.bot',
    iconPath: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  {
    labelKey: 'settings.chat.hub.memory.title',
    descKey: 'settings.chat.hub.memory.description',
    route: 'settings.ai.memory',
    iconPath: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
  },
  {
    labelKey: 'settings.chat.hub.providers.title',
    descKey: 'settings.chat.hub.providers.description',
    route: 'settings.ai.models',
    iconPath: 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18',
  },
];

const activityLinks: HubLink[] = [
  {
    labelKey: 'settings.chat.hub.logs.title',
    descKey: 'settings.chat.hub.logs.description',
    route: 'settings.privacy.logs',
    iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
  },
];

const saveNotificationPrefs = async () => {
  try {
    await axios.patch(route('settings.application.notifications.update'), {
      email_notifications: props.emailNotifications,
      push_notifications: pushEnabled.value,
    });
    showToast(t('toast.updated'), 'success');
  } catch (error: any) {
    showToast(error.response?.data?.message || t('errors.generic'), 'error');
  }
};

watch(pushEnabled, async (enabled) => {
  if (enabled) {
    const ok = await push.enablePush();
    if (!ok) {
      pushEnabled.value = false;
      return;
    }
  } else {
    await push.disablePush();
  }
  await saveNotificationPrefs();
});

onMounted(async () => {
  push.setVapidKey(vapidPublicKey.value);
  push.startPresence();
  await push.updateState();
});

onUnmounted(() => {
  push.stopPresence();
});
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.chat.hub.head')" />

    <SettingsLayout
      :title="t('settings.chat.hub.title')"
      :description="t('settings.chat.hub.description')"
    >
      <SettingsCard :title="t('settings.chat.hub.section.bot')">
        <div class="space-y-1">
          <Link
            v-for="item in botLinks"
            :key="item.route"
            :href="route(item.route)"
            class="group flex items-center gap-3 px-2.5 sm:px-3 py-2.5 sm:py-3 rounded-lg border border-transparent hover:bg-[var(--color-surface-overlay)] hover:border-[var(--color-border-subtle)] active:scale-[0.99] transition-all duration-150"
          >
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center shrink-0 border bg-[var(--color-brand)]/10 border-[var(--color-brand)]/20">
              <svg class="w-3.5 h-3.5 sm:w-4.5 sm:h-4.5 text-[var(--color-brand)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs sm:text-sm font-semibold text-[var(--color-text-primary)] leading-tight">{{ t(item.labelKey) }}</p>
              <p class="text-2xs text-[var(--color-text-muted)] mt-0.5 truncate">{{ t(item.descKey) }}</p>
            </div>
            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-secondary)] shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.chat.hub.notifications.title')" :description="t('settings.chat.hub.notifications.description')">
        <div class="flex items-center justify-between gap-4">
          <div class="min-w-0">
            <span class="text-2xs sm:text-sm text-text-secondary">{{ t('settings.chat.hub.notifications.label') }}</span>
            <p class="text-2xs text-text-secondary mt-1">{{ permissionHint }}</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer shrink-0" :class="{ 'opacity-50 pointer-events-none': push.busy.value }">
            <input
              v-model="pushEnabled"
              type="checkbox"
              class="sr-only peer"
              :disabled="!push.isSupported || !vapidPublicKey"
            />
            <div class="w-11 h-6 bg-surface-muted peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--color-brand)] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--color-brand)]"></div>
          </label>
        </div>
      </SettingsCard>

      <SettingsCard :title="t('settings.chat.hub.section.activity')">
        <div class="space-y-1">
          <Link
            v-for="item in activityLinks"
            :key="item.route"
            :href="route(item.route, { filter: 'ai' })"
            class="group flex items-center gap-3 px-2.5 sm:px-3 py-2.5 sm:py-3 rounded-lg border border-transparent hover:bg-[var(--color-surface-overlay)] hover:border-[var(--color-border-subtle)] active:scale-[0.99] transition-all duration-150"
          >
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center shrink-0 border bg-[var(--color-brand)]/10 border-[var(--color-brand)]/20">
              <svg class="w-3.5 h-3.5 sm:w-4.5 sm:h-4.5 text-[var(--color-brand)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs sm:text-sm font-semibold text-[var(--color-text-primary)] leading-tight">{{ t(item.labelKey) }}</p>
              <p class="text-2xs text-[var(--color-text-muted)] mt-0.5 truncate">{{ t(item.descKey) }}</p>
            </div>
            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-secondary)] shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </SettingsCard>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
