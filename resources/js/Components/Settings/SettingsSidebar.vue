<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage, Link }            from '@inertiajs/vue3';
import { useI18n }                  from 'vue-i18n';
import settingsMenuTree             from '@/Pages/Settings/Config/settingsMenu';

interface Props {
  isOpen:    boolean;
  isDesktop: boolean;
}

defineProps<Props>();
defineEmits<{ close: [] }>();

const { t } = useI18n();

// ── Active route tracking ──────────────────────────────────────────────
const currentUrl = computed(() => usePage().url);

const isActive = (routeName?: string): boolean => {
  if (!routeName) return false;
  return currentUrl.value.includes(routeName.replaceAll('.', '/').replace('settings/', 'settings/'));
};

// ── Expanded categories ────────────────────────────────────────────────
const expandedCategories = ref<Set<string>>(new Set());

const toggleCategory = (id: string) => {
  if (expandedCategories.value.has(id)) expandedCategories.value.delete(id);
  else                                  expandedCategories.value.add(id);
};

// Auto-expand the category that owns the active page
onMounted(() => {
  settingsMenuTree.forEach((cat) => {
    if (cat.submenu?.some((item) => item.route && currentUrl.value.includes(item.id))) {
      expandedCategories.value.add(cat.id);
    }
  });
});

// ── Href builder (Ziggy or fallback) ──────────────────────────────────
const buildHref = (routeName?: string): string => {
  if (!routeName) return '#';
  try {
    // @ts-ignore
    return typeof route === 'function' ? route(routeName) : '#';
  } catch {
    return '#';
  }
};

// ── i18n helpers ──────────────────────────────────────────────────────
const translateCategory = (cat: any): string => {
  const key = `settings.${cat.id}.title`;
  const val = t(key);
  return val !== key ? val : cat.label;
};

const translateItem = (cat: any, item: any): string => {
  const k1 = `settings.${cat.id}.${item.id}.title`;
  const v1 = t(k1);
  if (v1 !== k1) return v1;
  return item.label;
};
</script>

<template>
  <!--
    Mobile : fixed, slides in from the left (translate-x).
    Desktop: sticky, always visible, width w-64, full viewport height.
  -->
  <aside
    :class="[
      'z-40 border-r border-white/[0.07] overflow-y-auto shrink-0',
      // Desktop: sticky column, tinggi penuh content area (bukan min-h-screen)
      isDesktop
        ? 'relative w-64 self-stretch'
        // Mobile: fixed drawer
        : 'fixed inset-y-0 left-0 w-72 bg-gray-800 transition-transform duration-300',
      !isOpen && !isDesktop ? '-translate-x-full' : 'translate-x-0',
    ]"
  >

    <!-- Brand / back link -->
    <div class="sticky top-0 z-10 px-4 py-4 border-b border-white/[0.07] bg-gray-800/95 backdrop-blur-sm">
      <Link href="/settings" class="flex items-center gap-3 group" @click="$emit('close')">
        <div class="w-8 h-8 rounded-lg bg-purple-500/20 border border-purple-500/30 flex items-center justify-center shrink-0">
          <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-white leading-none">{{ t('settings.title') }}</p>
          <p class="text-2xs text-gray-500 mt-0.5">Bendaharaku</p>
        </div>
      </Link>
    </div>

    <!-- Navigation -->
    <nav class="px-2 py-3 space-y-0.5">
      <template v-for="cat in settingsMenuTree" :key="cat.id">

        <!-- ── Category without submenu (direct link) ────────── -->
        <Link
          v-if="!cat.submenu || cat.submenu.length === 0"
          :href="buildHref(cat.route)"
          class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="isActive(cat.route)
            ? 'bg-purple-500/15 text-purple-300'
            : 'text-gray-400 hover:text-gray-200 hover:bg-white/5'"
          @click="$emit('close')"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0 opacity-60"></span>
          <span class="truncate">{{ translateCategory(cat) }}</span>
        </Link>

        <!-- ── Category with submenu (expandable) ────────────── -->
        <div v-else>
          <button
            @click="toggleCategory(cat.id)"
            class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-sm font-medium transition-colors text-gray-400 hover:text-gray-200 hover:bg-white/5"
            :aria-expanded="expandedCategories.has(cat.id)"
          >
            <!-- Category dot indicator -->
            <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0 opacity-60"></span>
            <!-- Label grows to fill space -->
            <span class="flex-1 text-left truncate">{{ translateCategory(cat) }}</span>
            <!-- Chevron: always at right, rotates when open -->
            <svg
              :class="['w-3.5 h-3.5 shrink-0 text-gray-600 transition-transform duration-200', expandedCategories.has(cat.id) ? 'rotate-180' : '']"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Submenu items -->
          <transition
            enter-active-class="transition-all duration-200 overflow-hidden"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-96 opacity-100"
            leave-active-class="transition-all duration-150 overflow-hidden"
            leave-from-class="max-h-96 opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div v-if="expandedCategories.has(cat.id)" class="ml-4 pl-3 border-l border-white/10 mt-0.5 space-y-0.5 mb-1">
              <Link
                v-for="item in cat.submenu"
                :key="item.id"
                :href="buildHref(item.route)"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition-colors"
                :class="isActive(item.route)
                  ? 'text-purple-300 bg-purple-500/10 font-medium'
                  : 'text-gray-500 hover:text-gray-200 hover:bg-white/5 font-normal'"
                @click="$emit('close')"
              >
                <span class="w-1 h-1 rounded-full bg-current shrink-0 opacity-50"></span>
                <span class="truncate">{{ translateItem(cat, item) }}</span>
              </Link>
            </div>
          </transition>
        </div>

      </template>
    </nav>

  </aside>
</template>

<style scoped>
aside {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.08) transparent;
}
aside::-webkit-scrollbar       { width: 4px; }
aside::-webkit-scrollbar-track { background: transparent; }
aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 2px; }
</style>
