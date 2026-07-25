<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SettingsLayout from './Layouts/SettingsLayout.vue';
import { useI18n } from 'vue-i18n';
import { computed, ref } from 'vue';

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

// ── Section definitions ───────────────────────────────────────────

interface SectionItem {
  labelKey: string;
  descKey: string;
  route: string;
  accent: string;
  iconPath: string;
}

interface Section {
  id: string;
  label: string;
  icon: string;            // emoji atau karakter untuk identitas visual section
  gradientFrom: string;
  gradientTo: string;
  items: SectionItem[];
}

const sections: Section[] = [
  // ── Account ──────────────────────────────────────────────────────
  {
    id: 'account',
    label: 'Account',
    icon: '👤',
    gradientFrom: '#7C3AED',
    gradientTo: '#4F46E5',
    items: [
      {
        labelKey: 'settings.account.profile.title',
        descKey:  'settings.account.profile.description',
        route:    'settings.account.profile',
        accent:   'purple',
        iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
      },
      {
        labelKey: 'settings.account.security.title',
        descKey:  'settings.account.security.description',
        route:    'settings.account.security',
        accent:   'blue',
        iconPath: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
      },
      {
        labelKey: 'settings.account.preferences.title',
        descKey:  'settings.account.preferences.description',
        route:    'settings.account.preferences',
        accent:   'orange',
        iconPath: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
      },
    ],
  },

  // ── Application ───────────────────────────────────────────────────
  {
    id: 'application',
    label: 'Application',
    icon: '⚙️',
    gradientFrom: '#0891B2',
    gradientTo: '#0284C7',
    items: [
      {
        labelKey: 'settings.application.language.title',
        descKey:  'settings.application.language.description',
        route:    'settings.application.language',
        accent:   'sky',
        iconPath: 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129',
      },
    ],
  },

  // ── Appearance ────────────────────────────────────────────────────
  {
    id: 'appearance',
    label: 'Appearance',
    icon: '🎨',
    gradientFrom: '#7C3AED',
    gradientTo: '#DB2777',
    items: [
      {
        labelKey: 'settings.application.appearance.title',
        descKey:  'settings.application.appearance.description',
        route:    'settings.application.appearance',
        accent:   'purple',
        iconPath: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
      },
    ],
  },

  // ── Keuangan ─────────────────────────────────────────────────────
  {
    id: 'keuangan',
    label: 'Keuangan',
    icon: '💰',
    gradientFrom: '#059669',
    gradientTo: '#047857',
    items: [
      {
        labelKey: 'settings.finance.defaults.transaction_logic.title',
        descKey:  'settings.finance.defaults.transaction_logic.description',
        route:    'settings.keuangan.index',
        accent:   'emerald',
        iconPath: 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
      },
    ],
  },

  // ── AI ────────────────────────────────────────────────────────────
  {
    id: 'ai',
    label: 'Artificial Intelligence',
    icon: '🤖',
    gradientFrom: '#6D28D9',
    gradientTo: '#BE185D',
    items: [
      {
        labelKey: 'settings.ai.models.title',
        descKey:  'settings.ai.models.description',
        route:    'settings.ai.models',
        accent:   'indigo',
        iconPath: 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18',
      },
      {
        labelKey: 'settings.ai.bot.title',
        descKey:  'settings.ai.bot.description',
        route:    'settings.ai.bot',
        accent:   'violet',
        iconPath: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      },
      {
        labelKey: 'settings.ai.memory.title',
        descKey:  'settings.ai.memory.description',
        route:    'settings.ai.memory',
        accent:   'fuchsia',
        iconPath: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
      },
      {
        labelKey: 'settings.ai.integrations.title',
        descKey:  'settings.ai.integrations.description',
        route:    'settings.ai.integrations',
        accent:   'pink',
        iconPath: 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z',
      },
    ],
  },

  // ── Notifications ─────────────────────────────────────────────────
  {
    id: 'notifications',
    label: 'Notifications',
    icon: '🔔',
    gradientFrom: '#D97706',
    gradientTo: '#B45309',
    items: [
      {
        labelKey: 'settings.application.notifications.title',
        descKey:  'settings.application.notifications.description',
        route:    'settings.application.notifications',
        accent:   'yellow',
        iconPath: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
      },
    ],
  },

  // ── Privacy ───────────────────────────────────────────────────────
  {
    id: 'privacy',
    label: 'Privacy',
    icon: '🔒',
    gradientFrom: '#065F46',
    gradientTo: '#047857',
    items: [
      {
        labelKey: 'settings.privacy.settings.title',
        descKey:  'settings.privacy.settings.description',
        route:    'settings.privacy.settings',
        accent:   'emerald',
        iconPath: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
      },
      {
        labelKey: 'settings.privacy.data.title',
        descKey:  'settings.privacy.data.description',
        route:    'settings.privacy.data',
        accent:   'teal',
        iconPath: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
      },
    ],
  },

  // ── Danger Zone ───────────────────────────────────────────────────
  {
    id: 'danger',
    label: 'Danger Zone',
    icon: '⚠️',
    gradientFrom: '#991B1B',
    gradientTo: '#7F1D1D',
    items: [
      {
        labelKey: 'settings.privacy.danger.title',
        descKey:  'settings.privacy.danger.description',
        route:    'settings.privacy.danger',
        accent:   'red',
        iconPath: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
      },
    ],
  },
];

// ── Accent color map ──────────────────────────────────────────────

const accentMap: Record<string, { bg: string; text: string; border: string; hover: string }> = {
  purple:  { bg: 'bg-purple-500/10',  text: 'text-purple-400',  border: 'border-purple-500/20',  hover: 'hover:border-purple-500/40 hover:bg-purple-500/5' },
  blue:    { bg: 'bg-blue-500/10',    text: 'text-blue-400',    border: 'border-blue-500/20',    hover: 'hover:border-blue-500/40 hover:bg-blue-500/5' },
  sky:     { bg: 'bg-sky-500/10',     text: 'text-sky-400',     border: 'border-sky-500/20',     hover: 'hover:border-sky-500/40 hover:bg-sky-500/5' },
  orange:  { bg: 'bg-orange-500/10',  text: 'text-orange-400',  border: 'border-orange-500/20',  hover: 'hover:border-orange-500/40 hover:bg-orange-500/5' },
  green:   { bg: 'bg-green-500/10',   text: 'text-green-400',   border: 'border-green-500/20',   hover: 'hover:border-green-500/40 hover:bg-green-500/5' },
  yellow:  { bg: 'bg-yellow-500/10',  text: 'text-yellow-400',  border: 'border-yellow-500/20',  hover: 'hover:border-yellow-500/40 hover:bg-yellow-500/5' },
  amber:   { bg: 'bg-amber-500/10',   text: 'text-amber-400',   border: 'border-amber-500/20',   hover: 'hover:border-amber-500/40 hover:bg-amber-500/5' },
  teal:    { bg: 'bg-teal-500/10',    text: 'text-teal-400',    border: 'border-teal-500/20',    hover: 'hover:border-teal-500/40 hover:bg-teal-500/5' },
  indigo:  { bg: 'bg-indigo-500/10',  text: 'text-indigo-400',  border: 'border-indigo-500/20',  hover: 'hover:border-indigo-500/40 hover:bg-indigo-500/5' },
  violet:  { bg: 'bg-violet-500/10',  text: 'text-violet-400',  border: 'border-violet-500/20',  hover: 'hover:border-violet-500/40 hover:bg-violet-500/5' },
  fuchsia: { bg: 'bg-fuchsia-500/10', text: 'text-fuchsia-400', border: 'border-fuchsia-500/20', hover: 'hover:border-fuchsia-500/40 hover:bg-fuchsia-500/5' },
  pink:    { bg: 'bg-pink-500/10',    text: 'text-pink-400',    border: 'border-pink-500/20',    hover: 'hover:border-pink-500/40 hover:bg-pink-500/5' },
  emerald: { bg: 'bg-emerald-500/10', text: 'text-emerald-400', border: 'border-emerald-500/20', hover: 'hover:border-emerald-500/40 hover:bg-emerald-500/5' },
  red:     { bg: 'bg-red-500/10',     text: 'text-red-400',     border: 'border-red-500/20',     hover: 'hover:border-red-500/40 hover:bg-red-500/5' },
};

const a = (color: string) => accentMap[color] ?? accentMap.purple;

// ── Collapse state — semua collapsed by default ───────────────────

const collapsed = ref<Set<string>>(new Set(sections.map(s => s.id)));

function toggle(id: string) {
  const next = new Set(collapsed.value);
  next.has(id) ? next.delete(id) : next.add(id);
  collapsed.value = next;
}
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.title')" />

    <SettingsLayout
      :title="t('settings.title')"
      :description="t('settings.subtitle')"
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

        <!-- ── SECTIONS ───────────────────────────────────────────── -->
        <section
          v-for="sec in sections"
          :key="sec.id"
          :class="[
            'rounded-xl border overflow-hidden transition-colors',
            sec.id === 'danger'
              ? 'border-red-500/20 bg-red-950/10'
              : 'border-white/[0.07] bg-gray-900/40',
          ]"
        >
          <!-- Section Header -->
          <button
            type="button"
            class="w-full flex items-center gap-3 px-4 py-3.5 group"
            @click="toggle(sec.id)"
          >
            <!-- Color bar -->
            <div
              class="w-1 h-6 rounded-full shrink-0"
              :style="`background: linear-gradient(to bottom, ${sec.gradientFrom}, ${sec.gradientTo})`"
            />

            <!-- Icon -->
            <span class="text-base leading-none select-none" aria-hidden="true">{{ sec.icon }}</span>

            <!-- Label -->
            <span
              :class="[
                'flex-1 text-left text-sm font-bold tracking-tight transition-colors',
                sec.id === 'danger' ? 'text-red-400' : 'text-white group-hover:text-white',
              ]"
            >
              {{ sec.label }}
            </span>

            <!-- Item count -->
            <span class="text-2xs text-gray-600 font-semibold tabular-nums">
              {{ sec.items.length }} {{ sec.items.length === 1 ? 'item' : 'items' }}
            </span>
          </button>

          <!-- Collapsible content -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out origin-top"
            leave-active-class="transition-all duration-150 ease-in origin-top"
            enter-from-class="opacity-0 scale-y-95"
            enter-to-class="opacity-100 scale-y-100"
            leave-from-class="opacity-100 scale-y-100"
            leave-to-class="opacity-0 scale-y-95"
          >
            <div v-if="!collapsed.has(sec.id)" class="border-t border-white/[0.06]">
              <div class="p-3 space-y-1.5">
                <Link
                  v-for="item in sec.items"
                  :key="item.route"
                  :href="route(item.route)"
                  :class="[
                    'group flex items-center gap-3.5 px-3 py-3 rounded-lg',
                    'border border-transparent',
                    'hover:bg-gray-800/60 active:scale-[0.99]',
                    'transition-all duration-150',
                    a(item.accent).hover,
                  ]"
                >
                  <!-- Icon badge -->
                  <div :class="['w-9 h-9 rounded-lg flex items-center justify-center shrink-0 border', a(item.accent).bg, a(item.accent).border]">
                    <svg :class="['w-4.5 h-4.5', a(item.accent).text]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
                    </svg>
                  </div>

                  <!-- Text -->
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white leading-tight">{{ t(item.labelKey) }}</p>
                    <p class="text-2xs text-gray-500 mt-0.5 truncate">{{ t(item.descKey) }}</p>
                  </div>

                  <!-- Arrow -->
                  <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </div>
            </div>
          </Transition>
        </section>

      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
