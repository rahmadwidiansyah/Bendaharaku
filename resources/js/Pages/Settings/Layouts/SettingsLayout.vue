<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import SettingsSidebar from '@/Components/Settings/SettingsSidebar.vue';
import SettingsBreadcrumb from '@/Components/Settings/SettingsBreadcrumb.vue';

interface Breadcrumb {
  label: string;
  href?: string;
}

interface Props {
  title?: string;
  description?: string;
  breadcrumbs?: Breadcrumb[];
}

withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
});

const isSidebarOpen = ref(false);
const isDesktop = ref(typeof window !== 'undefined' && window.innerWidth >= 1024);

const handleResize = () => {
  if (typeof window !== 'undefined') {
    isDesktop.value = window.innerWidth >= 1024;
    if (isDesktop.value) isSidebarOpen.value = false;
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

const closeSidebar = () => { isSidebarOpen.value = false; };
</script>

<template>
  <!--
    Root: min-h-screen so page always fills viewport.
    flex-col on mobile (sidebar slides over from fixed pos),
    lg:flex-row on desktop (sidebar | content side by side).
  -->
  <div class="min-h-screen bg-gray-950 flex flex-col lg:flex-row">

    <!-- Mobile overlay (closes sidebar when tapping outside) -->
    <div
      v-if="isSidebarOpen && !isDesktop"
      class="fixed inset-0 bg-black/60 z-30 lg:hidden"
      @click="closeSidebar"
    />

    <!-- Sidebar (fixed on mobile, sticky on desktop) -->
    <SettingsSidebar
      :is-open="isSidebarOpen || isDesktop"
      :is-desktop="isDesktop"
      @close="closeSidebar"
    />

    <!--
      Main content column.
      flex-1 + min-w-0 = takes all remaining width without overflowing.
    -->
    <div class="flex-1 min-w-0 flex flex-col">

      <!-- Page title (desktop only, inside content area) -->
      <div v-if="title" class="hidden lg:block px-6 pt-6 pb-1">
        <h1 class="text-lg font-bold text-white">{{ title }}</h1>
        <p v-if="description" class="text-sm text-gray-400 mt-0.5">{{ description }}</p>
      </div>

      <!-- Breadcrumb -->
      <div
        v-if="breadcrumbs && breadcrumbs.length"
        class="px-4 sm:px-6 pt-4 pb-0 lg:hidden"
      >
        <SettingsBreadcrumb :breadcrumbs="breadcrumbs" />
      </div>

      <!-- Page content: full width, cards expand naturally -->
      <div class="px-4 sm:px-6 py-6 space-y-5">
        <slot />
      </div>

    </div>
    <!-- End main content column -->

  </div>
</template>

<style scoped>
/* nothing needed — sidebar scroll handled in SettingsSidebar.vue */
</style>
