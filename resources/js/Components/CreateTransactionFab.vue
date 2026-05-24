<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useLayoutPreference } from '@/Composables/useLayoutPreference';

const isOpen = ref(false);
const { isDesktopLayout } = useLayoutPreference();

const toggleMenu = () => {
    isOpen.value = !isOpen.value;
};
</script>

<template>
    <div :class="['fixed bottom-24 left-1/2 -translate-x-1/2 w-full max-w-md pointer-events-none flex justify-end px-5 z-40', isDesktopLayout ? 'lg:hidden' : '']">
        <div class="flex flex-col gap-4 items-end pointer-events-auto">
            
            <!-- SUB BUTTONS (STAGGERED ANIMATION) -->
            <div class="flex flex-col gap-4 items-end mb-1">
                <!-- TELEGRAM BUTTON -->
                <transition name="fab-sub">
                    <a v-if="isOpen" href="https://t.me/catatwidi_bot" target="_blank" 
                        class="relative group w-11 h-11 bg-blue-500/90 backdrop-blur-sm rounded-xl flex justify-center items-center text-white active:scale-95 transition-all"
                        style="transition-delay: 50ms;">
                        <svg class="w-5 h-5 ml-[-2px] mt-[1px]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z"/>
                        </svg>
                        <div class="absolute right-14 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                            Telegram Bot
                        </div>
                    </a>
                </transition>

                <!-- DIRECT CREATE BUTTON -->
                <transition name="fab-sub">
                    <Link v-if="isOpen" :href="route('transactions.create')" 
                        class="relative group w-11 h-11 bg-white rounded-xl flex justify-center items-center text-black active:scale-95 transition-all"
                        style="transition-delay: 100ms;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <div class="absolute right-14 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-white text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                            Input Manual
                        </div>
                    </Link>
                </transition>
            </div>

            <!-- MAIN TOGGLE BUTTON -->
            <button @click="toggleMenu" 
                class="w-13 h-13 bg-gradient-to-br from-purple-800 to-purple-500 rounded-2xl flex justify-center items-center text-white active:scale-90 transition-all border border-white/10 group relative overflow-hidden"
                :class="isOpen ? 'rotate-45' : ''">
                <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <svg class="w-7 h-7 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
.fab-sub-enter-active,
.fab-sub-leave-active {
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.fab-sub-enter-from,
.fab-sub-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.8);
}

.w-13 { width: 3.25rem; }
.h-13 { height: 3.25rem; }
</style>
