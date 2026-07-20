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

interface CategoryItem {
  labelKey: string;
  descKey: string;
  route: string;
  accent: string;
  iconPath: string;
}

interface Category {
  id: string;
  labelKey: string;
  accentColor: string;
  gradientFrom: string;
  gradientTo: string;
  items: CategoryItem[];
}

const categories: Category[] = [
  {
    id: 'account',
    labelKey: 'settings.account.title',
    accentColor: 'purple',
    gradientFrom: '#7C3AED',
    gradientTo: '#4F46E5',
    items: [
      {
        labelKey: 'settings.account.profile.title',
        descKey: 'settings.account.profile.description',
        route: 'settings.account.profile',
        accent: 'purple',
        iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
      },
      {
        labelKey: 'settings.account.security.title',
        descKey: 'settings.account.security.description',
        route: 'settings.account.security',
        accent: 'blue',
        iconPath: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
      },
      {
        labelKey: 'settings.account.sessions.title',
        descKey: 'settings.account.sessions.description',
        route: 'settings.account.sessions',
        accent: 'cyan',
        iconPath: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
      },
      {
        labelKey: 'settings.account.preferences.title',
        descKey: 'settings.account.preferences.description',
        route: 'settings.account.preferences',
        accent: 'orange',
        iconPath: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
      },
    ],
  },
  {
    id: 'application',
    labelKey: 'settings.application.title',
    accentColor: 'emerald',
    gradientFrom: '#059669',
    gradientTo: '#0891B2',
    items: [
      {
        labelKey: 'settings.application.appearance.title',
        descKey: 'settings.application.appearance.description',
        route: 'settings.application.appearance',
        accent: 'purple',
        iconPath: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
      },
      {
        labelKey: 'settings.application.language.title',
        descKey: 'settings.application.language.description',
        route: 'settings.application.language',
        accent: 'green',
        iconPath: 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129',
      },
      {
        labelKey: 'settings.application.notifications.title',
        descKey: 'settings.application.notifications.description',
        route: 'settings.application.notifications',
        accent: 'yellow',
        iconPath: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
      },
    ],
  },
  {
    id: 'finance',
    labelKey: 'settings.finance.title',
    accentColor: 'amber',
    gradientFrom: '#D97706',
    gradientTo: '#B45309',
    items: [
      {
        labelKey: 'settings.finance.defaults.title',
        descKey: 'settings.finance.defaults.description',
        route: 'settings.finance.defaults',
        accent: 'amber',
        iconPath: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
      },
      {
        labelKey: 'settings.finance.categories.title',
        descKey: 'settings.finance.categories.description',
        route: 'settings.finance.categories',
        accent: 'pink',
        iconPath: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
      },
      {
        labelKey: 'settings.finance.wallets.title',
        descKey: 'settings.finance.wallets.description',
        route: 'settings.finance.wallets',
        accent: 'teal',
        iconPath: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
      },
      {
        labelKey: 'settings.finance.budget.title',
        descKey: 'settings.finance.budget.description',
        route: 'settings.finance.budget',
        accent: 'lime',
        iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
      },
    ],
  },
  {
    id: 'ai',
    labelKey: 'settings.ai.title',
    accentColor: 'violet',
    gradientFrom: '#7C3AED',
    gradientTo: '#DB2777',
    items: [
      {
        labelKey: 'settings.ai.models.title',
        descKey: 'settings.ai.models.description',
        route: 'settings.ai.models',
        accent: 'indigo',
        iconPath: 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18',
      },
      {
        labelKey: 'settings.ai.bot.title',
        descKey: 'settings.ai.bot.description',
        route: 'settings.ai.bot',
        accent: 'violet',
        iconPath: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      },
      {
        labelKey: 'settings.ai.memory.title',
        descKey: 'settings.ai.memory.description',
        route: 'settings.ai.memory',
        accent: 'fuchsia',
        iconPath: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
      },
      {
        labelKey: 'settings.ai.integrations.title',
        descKey: 'settings.ai.integrations.description',
        route: 'settings.ai.integrations',
        accent: 'sky',
        iconPath: 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z',
      },
    ],
  },
  {
    id: 'privacy',
    labelKey: 'settings.privacy.title',
    accentColor: 'emerald',
    gradientFrom: '#065F46',
    gradientTo: '#1E3A5F',
    items: [
      {
        labelKey: 'settings.privacy.settings.title',
        descKey: 'settings.privacy.settings.description',
        route: 'settings.privacy.settings',
        accent: 'emerald',
        iconPath: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
      },
      {
        labelKey: 'settings.privacy.data.title',
        descKey: 'settings.privacy.data.description',
        route: 'settings.privacy.data',
        accent: 'yellow',
        iconPath: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
      },
      {
        labelKey: 'settings.privacy.danger.title',
        descKey: 'settings.privacy.danger.description',
        route: 'settings.privacy.danger',
        accent: 'red',
        iconPath: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
      },
    ],
  },
  {
    id: 'system',
    labelKey: 'settings.system.title',
    accentColor: 'slate',
    gradientFrom: '#334155',
    gradientTo: '#1E293B',
    items: [
      {
        labelKey: 'settings.system.about.title',
        descKey: 'settings.system.about.description',
        route: 'settings.system.about',
        accent: 'slate',
        iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
      },
    ],
  },
];

// Accent color map → Tailwind classes
const accentMap: Record<string, { bg: string; text: string; border: string; hover: string; chevron: string }> = {
  purple:  { bg: 'bg-purple-500/10',  text: 'text-purple-400',  border: 'border-purple-500/20',  hover: 'hover:border-purple-500/40',  chevron: 'group-hover:text-purple-400' },
  blue:    { bg: 'bg-blue-500/10',    text: 'text-blue-400',    border: 'border-blue-500/20',    hover: 'hover:border-blue-500/40',    chevron: 'group-hover:text-blue-400' },
  cyan:    { bg: 'bg-cyan-500/10',    text: 'text-cyan-400',    border: 'border-cyan-500/20',    hover: 'hover:border-cyan-500/40',    chevron: 'group-hover:text-cyan-400' },
  orange:  { bg: 'bg-orange-500/10',  text: 'text-orange-400',  border: 'border-orange-500/20',  hover: 'hover:border-orange-500/40',  chevron: 'group-hover:text-orange-400' },
  green:   { bg: 'bg-green-500/10',   text: 'text-green-400',   border: 'border-green-500/20',   hover: 'hover:border-green-500/40',   chevron: 'group-hover:text-green-400' },
  yellow:  { bg: 'bg-yellow-500/10',  text: 'text-yellow-400',  border: 'border-yellow-500/20',  hover: 'hover:border-yellow-500/40',  chevron: 'group-hover:text-yellow-400' },
  amber:   { bg: 'bg-amber-500/10',   text: 'text-amber-400',   border: 'border-amber-500/20',   hover: 'hover:border-amber-500/40',   chevron: 'group-hover:text-amber-400' },
  pink:    { bg: 'bg-pink-500/10',    text: 'text-pink-400',    border: 'border-pink-500/20',    hover: 'hover:border-pink-500/40',    chevron: 'group-hover:text-pink-400' },
  teal:    { bg: 'bg-teal-500/10',    text: 'text-teal-400',    border: 'border-teal-500/20',    hover: 'hover:border-teal-500/40',    chevron: 'group-hover:text-teal-400' },
  lime:    { bg: 'bg-lime-500/10',    text: 'text-lime-400',    border: 'border-lime-500/20',    hover: 'hover:border-lime-500/40',    chevron: 'group-hover:text-lime-400' },
  indigo:  { bg: 'bg-indigo-500/10',  text: 'text-indigo-400',  border: 'border-indigo-500/20',  hover: 'hover:border-indigo-500/40',  chevron: 'group-hover:text-indigo-400' },
  violet:  { bg: 'bg-violet-500/10',  text: 'text-violet-400',  border: 'border-violet-500/20',  hover: 'hover:border-violet-500/40',  chevron: 'group-hover:text-violet-400' },
  fuchsia: { bg: 'bg-fuchsia-500/10', text: 'text-fuchsia-400', border: 'border-fuchsia-500/20', hover: 'hover:border-fuchsia-500/40', chevron: 'group-hover:text-fuchsia-400' },
  sky:     { bg: 'bg-sky-500/10',     text: 'text-sky-400',     border: 'border-sky-500/20',     hover: 'hover:border-sky-500/40',     chevron: 'group-hover:text-sky-400' },
  rose:    { bg: 'bg-rose-500/10',    text: 'text-rose-400',    border: 'border-rose-500/20',    hover: 'hover:border-rose-500/40',    chevron: 'group-hover:text-rose-400' },
  emerald: { bg: 'bg-emerald-500/10', text: 'text-emerald-400', border: 'border-emerald-500/20', hover: 'hover:border-emerald-500/40', chevron: 'group-hover:text-emerald-400' },
  red:     { bg: 'bg-red-500/10',     text: 'text-red-400',     border: 'border-red-500/10',     hover: 'hover:border-red-500/40',     chevron: 'group-hover:text-red-400' },
  slate:   { bg: 'bg-slate-500/10',   text: 'text-slate-400',   border: 'border-slate-500/20',   hover: 'hover:border-slate-400/40',   chevron: 'group-hover:text-slate-400' },
  gray:    { bg: 'bg-gray-500/10',    text: 'text-gray-400',    border: 'border-gray-500/20',    hover: 'hover:border-gray-400/40',    chevron: 'group-hover:text-gray-400' },
};

function accent(color: string) {
  return accentMap[color] || accentMap.gray;
}
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.title')" />

    <SettingsLayout
      :title="t('settings.title')"
      :description="t('settings.subtitle')"
    >
      <div class="space-y-10">

        <!-- ── USER HERO CARD ───────────────────────────────────────── -->
        <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-purple-900/40 via-gray-900 to-gray-900 p-6">
          <!-- Decorative glow -->
          <div class="pointer-events-none absolute -top-16 -right-16 w-72 h-72 rounded-full bg-purple-600/10 blur-3xl" />
          <div class="pointer-events-none absolute -bottom-10 -left-10 w-48 h-48 rounded-full bg-indigo-600/10 blur-2xl" />

          <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <!-- Avatar -->
            <div class="relative shrink-0">
              <div class="w-20 h-20 rounded-2xl overflow-hidden border-2 border-purple-500/30 shadow-lg shadow-purple-500/10 bg-gradient-to-br from-purple-600 to-indigo-900 flex items-center justify-center">
                <img v-if="avatarSrc" :src="avatarSrc" :alt="user.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <svg class="w-10 h-10 text-white/60" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 rounded-full border-2 border-gray-900 flex items-center justify-center">
                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <h2 class="text-xl font-black text-white tracking-tight truncate">{{ user.name || '—' }}</h2>
              <p class="text-sm text-gray-400 mt-0.5 truncate">{{ user.email || '—' }}</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-if="user.whatsapp_number" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-500/10 border border-green-500/20 text-green-400 text-2xs font-bold rounded-lg">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12.04 2C6.474 2 1.997 6.477 1.997 12.043c0 1.843.478 3.58 1.375 5.098L2 22l4.996-1.31C8.465 21.54 10.218 22 12.04 22c5.566 0 10.043-4.477 10.043-10.043C22.083 6.477 17.606 2 12.04 2z"/></svg>
                  WhatsApp
                </span>
                <span v-if="user.telegram_id" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-sky-500/10 border border-sky-500/20 text-sky-400 text-2xs font-bold rounded-lg">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                  Telegram
                </span>
                <span v-if="user.google_id" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-500/10 border border-red-500/20 text-red-400 text-2xs font-bold rounded-lg">
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                  Google
                </span>
              </div>
            </div>

          </div>
        </div>

        <!-- ── CATEGORY SECTIONS ────────────────────────────────────── -->
        <section v-for="cat in categories" :key="cat.id" class="space-y-3">
          <!-- Section Header -->
          <div class="flex items-center gap-3 mb-1">
            <div
              class="w-1.5 h-5 rounded-full"
              :style="`background: linear-gradient(to bottom, ${cat.gradientFrom}, ${cat.gradientTo})`"
            />
            <h2 class="text-2xs font-black text-gray-400 uppercase tracking-[0.2em]">
              {{ t(cat.labelKey) }}
            </h2>
            <div class="flex-1 h-px bg-white/5" />
          </div>

          <!-- Items Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <Link
              v-for="item in cat.items"
              :key="item.route"
              :href="route(item.route)"
              :class="[
                'group flex items-center gap-4 p-4 rounded-xl',
                'bg-gray-900/60 border border-white/[0.07]',
                'hover:bg-gray-800/70 active:scale-[0.99]',
                'transition-all duration-150',
                accent(item.accent).hover,
              ]"
            >
              <!-- Icon Badge -->
              <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', accent(item.accent).bg, accent(item.accent).border, 'border']">
                <svg :class="['w-5 h-5', accent(item.accent).text]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
                </svg>
              </div>

              <!-- Text -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-white leading-tight truncate">{{ t(item.labelKey) }}</p>
                <p class="text-2xs text-gray-500 mt-0.5 truncate">{{ t(item.descKey) }}</p>
              </div>

              <!-- Chevron -->
              <svg
                :class="['w-4 h-4 text-gray-600 transition-colors shrink-0', accent(item.accent).chevron]"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </Link>
          </div>
        </section>

        <!-- ── FOOTER NOTE ──────────────────────────────────────────── -->
        <div class="flex items-center gap-2 text-2xs text-gray-600 pt-2">
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ t('settings.subtitle') }} — Bendaharaku</span>
        </div>

      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth card transition */
a {
  will-change: transform, border-color;
}
</style>
