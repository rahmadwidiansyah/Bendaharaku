<script setup>
import BottomNav from '@/Components/BottomNav.vue';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';
import { onMounted, ref, computed } from 'vue';

const props = defineProps({
    fullWidth: {
        type: Boolean,
        default: false
    }
});

const isSidebarOpen = ref(true);
const { isDesktopLayout } = useLayoutPreference();

const computedFullWidth = computed(() => {
    return isDesktopLayout.value ? props.fullWidth : false;
});

onMounted(() => {
    // Basic page entry animation logic
    const el = document.getElementById('main-content');
    if (el) {
        el.classList.add('animate-page');
        el.addEventListener('animationend', function handler() {
            el.classList.remove('animate-page');
            el.removeEventListener('animationend', handler);
        });
    }
});
</script>

<template>
    <div class="font-sans antialiased bg-black text-white selection:bg-purple-400 selection:text-black">
        <div class="flex justify-center min-h-screen">
            
            <!-- MAIN CONTENT (Centered) -->
            <div :class="[
                'w-full bg-gray-800 min-h-screen flex flex-col border-x border-[#1e1e1e] relative overflow-x-hidden shadow-2xl shadow-black transition-all duration-300',
                computedFullWidth ? 'max-w-md lg:max-w-full' : 'max-w-md',
                computedFullWidth && isSidebarOpen ? 'lg:pl-64' : (computedFullWidth ? 'lg:pl-20' : '')
            ]">
                <main id="main-content" :class="['flex-1 pb-24', computedFullWidth ? 'lg:pb-8' : '']">
                    <slot />
                </main>
                <BottomNav :is-sidebar-open="isSidebarOpen" @toggle="isSidebarOpen = $event" />
            </div>

        </div>
    </div>
</template>

<style>
@keyframes fadeInPage {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}

.animate-page {
    animation: fadeInPage 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    will-change: opacity, transform;
}

::-webkit-scrollbar { width: 0px; background: transparent; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
