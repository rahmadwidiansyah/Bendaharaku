<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import SettingsSidebar from '@/Components/Settings/SettingsSidebar.vue';
import { useScrollRestore } from '@/Composables/useScrollRestore.js';

interface Props {
  title?: string;
  description?: string;
}

withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
});

// Simpan & pulihkan scroll tiap sub-page settings secara otomatis
useScrollRestore();

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
    PENTING: Tidak ada `min-h-screen` atau background eksplisit di sini.
    Background datang dari AuthenticatedLayout (bg-gray-800) sehingga
    konsisten dengan semua halaman lain (Dashboard, Transactions, dll).
    
    Desktop: flex-row — sidebar kiri + konten kanan
    Mobile : block biasa — sidebar overlay via fixed positioning
  -->
  <div class="flex flex-row w-full">

    <!-- Mobile overlay backdrop (hanya saat sidebar drawer terbuka) -->
    <div
      v-if="isSidebarOpen && !isDesktop"
      class="fixed inset-0 bg-black/60 z-30 lg:hidden"
      @click="closeSidebar"
    />

    <!-- Sidebar — desktop: sticky column, mobile: fixed drawer -->
    <SettingsSidebar
      :is-open="isSidebarOpen || isDesktop"
      :is-desktop="isDesktop"
      @close="closeSidebar"
    />

    <!-- Main content — tidak ada background sendiri, inherit dari AuthenticatedLayout -->
    <div class="flex-1 min-w-0 flex flex-col">

      <!-- Mobile: hamburger (tanpa breadcrumb) untuk membuka drawer sidebar -->
      <div class="flex items-center px-4 pt-4 pb-0 lg:hidden">
        <button
          @click="isSidebarOpen = true"
          class="p-2 -ml-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition-colors"
          aria-label="Open settings menu"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>

      <!-- Desktop: page title (tanpa breadcrumb — sidebar sudah menunjukkan navigasi) -->
      <div v-if="title" class="hidden lg:block px-6 pt-6 pb-0">
        <h1 class="text-lg font-bold text-white leading-tight">{{ title }}</h1>
        <p v-if="description" class="text-sm text-gray-400 mt-1">{{ description }}</p>
      </div>

      <!-- Page content -->
      <div class="px-4 sm:px-6 py-5 space-y-4">
        <slot />
      </div>

    </div>

  </div>
</template>
