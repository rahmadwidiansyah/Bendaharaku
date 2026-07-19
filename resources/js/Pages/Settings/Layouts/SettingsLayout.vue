<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import SettingsSidebar from '../Components/SettingsSidebar.vue';
import SettingsHeader from '../Components/SettingsHeader.vue';
import SettingsBreadcrumb from '../Components/SettingsBreadcrumb.vue';
import settingsMenuTree from '../Config/settingsMenu';

interface Breadcrumb {
  label: string;
  href?: string;
}

interface Props {
  title: string;
  description?: string;
  breadcrumbs?: Breadcrumb[];
}

withDefaults(defineProps<Props>(), {
  description: '',
});

const isSidebarOpen = ref(false);
const isDesktop = ref(typeof window !== 'undefined' && window.innerWidth >= 1024);

const handleResize = () => {
  if (typeof window !== 'undefined') {
    isDesktop.value = window.innerWidth >= 1024;
    if (isDesktop.value) {
      isSidebarOpen.value = false;
    }
  }
};

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('resize', handleResize);
  }
});

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', handleResize);
  }
});

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
  isSidebarOpen.value = false;
};

// Settings Search
const searchQuery = ref('');

const flattenMenu = (tree: any[]) => {
  const out: any[] = [];
  const walk = (node: any) => {
    if (!node) return;
    if (node.route) out.push({ id: node.id, label: node.label, description: node.description || '', route: node.route });
    if (node.submenu && node.submenu.length) {
      node.submenu.forEach((c: any) => walk(c));
    }
  };
  tree.forEach((c) => walk(c));
  return out;
};

const flatMenu = flattenMenu(settingsMenuTree as any[]);

const searchResults = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return [];
  return flatMenu.filter((item) => item.label.toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q));
});

const navigateTo = (item: any) => {
  if (!item || !item.route) return;
  try {
    // Ziggy's route helper may be available globally
    // @ts-ignore
    const href = typeof route === 'function' ? route(item.route) : '/settings';
    window.location.href = href;
  } catch (e) {
    // fallback: attempt naive conversion
    window.location.href = `/${item.route.replaceAll('.', '/')}`;
  }
};

const goToFirstMatch = () => {
  if (searchResults.value.length) navigateTo(searchResults.value[0]);
};
</script>

<template>
  <div class="min-h-screen bg-gray-950">
    <!-- Overlay untuk mobile -->
    <div
      v-if="isSidebarOpen && !isDesktop"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="closeSidebar"
    />

    <!-- Sidebar -->
    <SettingsSidebar
      :is-open="isSidebarOpen || isDesktop"
      :is-desktop="isDesktop"
      @close="closeSidebar"
    />

    <!-- Main Content -->
    <div
      class="lg:ml-80 transition-all duration-300"
      @click="closeSidebar"
    >
      <!-- Header -->
      <div class="sticky top-0 z-10 border-b border-white/10 bg-gray-950/95 backdrop-blur-sm">
        <div class="flex items-center gap-4 px-6 py-4">
          <!-- Mobile Menu Button -->
          <button
            v-if="!isDesktop"
            @click.stop="toggleSidebar"
            class="lg:hidden p-2 hover:bg-gray-800 rounded-lg transition-colors"
            aria-label="Toggle sidebar"
          >
            <svg
              class="w-6 h-6 text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
          </button>

          <!-- Title -->
          <div class="flex-1">
            <SettingsHeader :title="title" :description="description" />
          </div>

          <!-- Settings Search (global) -->
          <div class="px-6 pb-3">
            <div class="max-w-2xl">
              <div class="relative">
                <input
                  v-model="searchQuery"
                  @keydown.enter="goToFirstMatch"
                  type="search"
                  placeholder="Search settings..."
                  class="w-full rounded-lg bg-gray-800 border border-white/5 text-sm text-gray-200 placeholder-gray-500 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                />
                <div v-if="searchResults.length && searchQuery.length" class="absolute left-0 right-0 mt-2 bg-gray-900 border border-white/10 rounded-lg shadow-lg z-40 overflow-hidden">
                  <ul>
                    <li v-for="(r, idx) in searchResults.slice(0,8)" :key="r.id" class="p-2 hover:bg-white/5 cursor-pointer" @click="navigateTo(r)">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                          <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6"></path></svg>
                          <div class="text-sm text-gray-200">{{ r.label }}</div>
                        </div>
                        <div class="text-2xs text-gray-500">{{ r.description }}</div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Breadcrumb -->
        <div v-if="breadcrumbs && breadcrumbs.length" class="px-6 py-2 border-t border-white/5">
          <SettingsBreadcrumb :breadcrumbs="breadcrumbs" />
        </div>
      </div>

      <!-- Content Area -->
      <div class="px-6 py-8 max-w-4xl">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Smooth transitions */
:deep(.sidebar-item) {
  @apply transition-all duration-200;
}
</style>
