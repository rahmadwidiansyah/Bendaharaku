<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from './Layouts/SettingsLayout.vue';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();
const page = usePage();
const user = computed(() => (page.props.auth as any)?.user ?? {});

const isUrl = (url: string) => url && (url.startsWith('http://') || url.startsWith('https://'));
const avatarSrc = computed(() => {
  const avatar = (user.value as any)?.avatar_url ?? user.value.avatar;
  if (avatar && (isUrl(avatar) || avatar.includes('/'))) {
    return isUrl(avatar) ? avatar : `/storage/${avatar}`;
  }
  return null;
});


</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.title')" />

    <SettingsLayout
      :title="t('settings.title')"
      :description="t('settings.subtitle')"
      hideBack
    >
      <div class="space-y-2">

        <!-- ── USER HERO CARD ─────────────────────────────────────── -->
        <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-purple-900/30 via-gray-900 to-gray-900 p-5 mb-6">
          <div class="pointer-events-none absolute -top-12 -right-12 w-56 h-56 rounded-full bg-purple-600/10 blur-3xl" />
          <div class="relative flex items-center gap-4">

            <!-- Avatar -->
            <div class="relative shrink-0">
              <div class="w-14 h-14 rounded-xl overflow-hidden border border-purple-500/30 bg-gradient-to-br from-purple-600 to-indigo-900 flex items-center justify-center">
                <img v-if="avatarSrc" :src="avatarSrc" :alt="user.name" class="w-full h-full object-cover" />
                <svg v-else class="w-7 h-7 text-white/60" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
              </div>
              <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-500 rounded-full border-2 border-gray-900" />
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-white truncate">{{ user.name || '—' }}</p>
              <p class="text-xs text-gray-400 truncate">{{ user.email || '—' }}</p>
              <div class="mt-1.5 flex flex-wrap gap-1.5">
                <span v-if="user.whatsapp_number" class="px-2 py-0.5 bg-green-500/10 border border-green-500/20 text-green-400 text-2xs font-bold rounded-md">WhatsApp</span>
                <span v-if="user.telegram_id"     class="px-2 py-0.5 bg-sky-500/10   border border-sky-500/20   text-sky-400   text-2xs font-bold rounded-md">Telegram</span>
                <span v-if="user.google_id"       class="px-2 py-0.5 bg-red-500/10   border border-red-500/20   text-red-400   text-2xs font-bold rounded-md">Google</span>
              </div>
            </div>

            <!-- Edit shortcut -->
            <Link
              :href="route('settings.account.profile')"
              class="shrink-0 p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 transition-colors"
              :aria-label="t('settings.account.profile.title')"
            >
              <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </Link>
          </div>
        </div>

      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
