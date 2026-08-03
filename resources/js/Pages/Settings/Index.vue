<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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

const initials = computed(() => {
  const name: string = (user.value as any)?.name ?? '';
  if (!name) return '?';
  return name.split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase();
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
  labelKey: string;
  svgPath: string;
  gradientFrom: string;
  gradientTo: string;
  items: SectionItem[];
}

const sections: Section[] = [
  // ── Account ──────────────────────────────────────────────────────
  {
    id: 'account',
    labelKey: 'settings.index.section.account',
    svgPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
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

  // ── Appearance ────────────────────────────────────────────────────
  {
    id: 'appearance',
    labelKey: 'settings.index.section.appearance',
    svgPath: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z',
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

  // ── Finance ─────────────────────────────────────────────────────
  {
    id: 'finance',
    labelKey: 'settings.index.section.finance',
    svgPath: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
    gradientFrom: '#059669',
    gradientTo: '#047857',
    items: [
      {
        labelKey: 'settings.finance.defaults.transaction_logic.title',
        descKey:  'settings.finance.defaults.transaction_logic.description',
        route:    'settings.finance.logic',
        accent:   'emerald',
        iconPath: 'M13 10V3L4 14h7v7l9-11h-7z',
      },
      {
        labelKey: 'settings.finance.budget.title',
        descKey:  'settings.finance.budget.description',
        route:    'settings.finance.budget',
        accent:   'amber',
        iconPath: 'M23 6l-9.5 9.5-5-5L1 18',
      },
    ],
  },

  // ── AI ────────────────────────────────────────────────────────────
  {
    id: 'ai',
    labelKey: 'settings.index.section.ai',
    svgPath: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM8 11l-1 1 1 1m8-2l-1-1-1 1m-4 2l2-4',
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
    labelKey: 'settings.index.section.notifications',
    svgPath: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
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
    labelKey: 'settings.index.section.privacy',
    svgPath: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
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
    labelKey: 'settings.index.section.danger',
    svgPath: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
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

const handleLogout = () => {
  router.post(route('logout'));
};
</script>

<template>
  <AuthenticatedLayout :fullWidth="true">
    <Head :title="t('settings.title')" />

    <SettingsLayout>
      <div class="space-y-2">

        <!-- ── USER HERO CARD ─────────────────────────────────────── -->
        <div class="relative overflow-hidden rounded-2xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] p-5 mb-6">
          <div class="pointer-events-none absolute -top-12 -right-12 w-56 h-56 rounded-full bg-[var(--color-brand)]/10 blur-3xl" />
          <div class="relative flex items-center gap-4">

            <!-- Avatar -->
            <div class="relative shrink-0">
              <div class="w-14 h-14 rounded-xl overflow-hidden border border-[var(--color-brand)]/30 bg-gradient-to-br from-[var(--color-brand)] to-[var(--color-brand-pressed)] flex items-center justify-center">
                <img v-if="avatarSrc" :src="avatarSrc" :alt="user.name" class="w-full h-full object-cover" />
                <span v-else class="text-lg font-black text-white/80 select-none">{{ initials }}</span>
              </div>
              <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-500 rounded-full border-2 border-[var(--color-surface-raised)]" />
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-2xs sm:text-sm font-bold text-[var(--color-text-primary)] truncate">{{ user.name || '—' }}</p>
              <p class="text-xs text-[var(--color-text-secondary)] truncate">{{ user.email || '—' }}</p>
              <div class="mt-1.5 flex flex-wrap gap-1.5">
                <span v-if="user.whatsapp_number" class="px-2 py-0.5 bg-green-500/10 border border-green-500/20 text-green-400 text-2xs font-bold rounded-md">WhatsApp</span>
                <span v-if="user.telegram_id"     class="px-2 py-0.5 bg-sky-500/10   border border-sky-500/20   text-sky-400   text-2xs font-bold rounded-md">Telegram</span>
                <span v-if="user.google_id"       class="px-2 py-0.5 bg-red-500/10   border border-red-500/20   text-red-400   text-2xs font-bold rounded-md">Google</span>
              </div>
            </div>

            <!-- Edit shortcut -->
            <Link
              :href="route('settings.account.profile')"
              class="shrink-0 p-2 rounded-lg bg-[var(--color-surface-muted)] hover:bg-[var(--color-surface-overlay)] border border-[var(--color-border-default)] transition-colors"
              :aria-label="t('settings.account.profile.title')"
            >
              <svg class="w-4 h-4 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
            'rounded-lg border overflow-hidden transition-colors',
            sec.id === 'danger'
              ? 'border-[var(--color-expense-border)] bg-[var(--color-expense-bg)]'
              : 'border-[var(--color-border-subtle)] bg-[var(--color-surface-muted)]',
          ]"
        >
          <!-- Section Header -->
          <button
            type="button"
            class="w-full flex items-center gap-3 px-3 sm:px-4 py-3 group"
            @click="toggle(sec.id)"
          >
            <!-- Icon with gradient -->
            <div
              class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center shrink-0"
              :style="{ background: `linear-gradient(135deg, ${sec.gradientFrom}, ${sec.gradientTo})` }"
            >
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" :d="sec.svgPath" />
              </svg>
            </div>

            <!-- Label -->
            <span
              :class="[
                'flex-1 text-left text-xs sm:text-sm font-bold tracking-tight transition-colors',
                sec.id === 'danger' ? 'text-[var(--color-expense-text)]' : 'text-[var(--color-text-primary)] group-hover:text-[var(--color-text-primary)]',
              ]"
            >
              {{ t(sec.labelKey) }}
            </span>

            <!-- Item count -->
            <span class="text-2xs text-[var(--color-text-muted)] font-semibold tabular-nums">
              {{ sec.items.length }} {{ t(sec.items.length === 1 ? 'settings.index.item' : 'settings.index.items') }}
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
            <div v-if="!collapsed.has(sec.id)" class="border-t border-[var(--color-border-subtle)]">
              <div class="p-2 sm:p-3 space-y-1">
                <Link
                  v-for="item in sec.items"
                  :key="item.route"
                  :href="route(item.route)"
                  :class="[
                    'group flex items-center gap-3 px-2.5 sm:px-3 py-2.5 sm:py-3 rounded-lg',
                    'border border-transparent',
                    'hover:bg-[var(--color-surface-overlay)] active:scale-[0.99]',
                    'transition-all duration-150',
                    a(item.accent).hover,
                  ]"
                >
                  <!-- Icon badge -->
                  <div :class="['w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center shrink-0 border', a(item.accent).bg, a(item.accent).border]">
                    <svg :class="['w-3.5 h-3.5 sm:w-4.5 sm:h-4.5', a(item.accent).text]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath" />
                    </svg>
                  </div>

                  <!-- Text -->
                  <div class="flex-1 min-w-0">
                    <p class="text-xs sm:text-sm font-semibold text-[var(--color-text-primary)] leading-tight">{{ t(item.labelKey) }}</p>
                    <p class="text-2xs text-[var(--color-text-muted)] mt-0.5 truncate hidden sm:block">{{ t(item.descKey) }}</p>
                  </div>

                  <!-- Arrow -->
                  <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-secondary)] shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </div>
            </div>
          </Transition>
        </section>

        <!-- ── LOGOUT ─────────────────────────────────────────────── -->
        <button
          type="button"
          @click="handleLogout"
          class="mt-1 w-full flex items-center gap-3 rounded-lg border border-[var(--color-expense-border)] bg-[var(--color-expense-bg)] px-3 sm:px-4 py-3 sm:py-3.5 transition-colors active:scale-[0.99] focus:outline-none focus-visible:ring-1 focus-visible:ring-[var(--color-expense-text)]"
          :aria-label="t('profile.logout')"
        >
          <!-- Icon badge -->
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center shrink-0 bg-[var(--color-expense-bg)] border border-[var(--color-expense-border)]">
            <svg class="w-3.5 h-3.5 sm:w-4.5 sm:h-4.5 text-[var(--color-expense-text)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </div>

          <!-- Label -->
          <span class="flex-1 text-left text-xs sm:text-sm font-bold text-[var(--color-expense-text)]">
            {{ t('profile.logout') }}
          </span>
        </button>

      </div>
    </SettingsLayout>
  </AuthenticatedLayout>
</template>
