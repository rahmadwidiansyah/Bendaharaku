<script setup>
import BottomNav from '@/Components/BottomNav.vue';
import GoogleAd from '@/Components/GoogleAd.vue';
import { onMounted } from 'vue';

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
    <div class="font-sans antialiased bg-black text-white selection:bg-[#FCA5FF] selection:text-black">
        <div class="flex justify-center min-h-screen">
            
            <!-- LEFT AD SIDEBAR (Desktop Only) -->
            <aside class="hidden xl:flex flex-col w-64 p-4 sticky top-0 h-screen justify-center items-center">
                <div class="w-full h-[600px] bg-gray-900/20 border border-white/5 rounded-xl flex items-center justify-center overflow-hidden">
                    <GoogleAd ad-slot="LEFT_SIDEBAR_SLOT" ad-format="vertical" />
                </div>
            </aside>

            <!-- MAIN CONTENT (Centered) -->
            <div class="w-full max-w-md bg-gray-800 min-h-screen flex flex-col border-x border-[#1e1e1e] relative overflow-x-hidden shadow-2xl shadow-black">
                <main id="main-content" class="flex-1 pb-24">
                    <slot />
                </main>
                <BottomNav />
            </div>

            <!-- RIGHT AD SIDEBAR (Desktop Only) -->
            <aside class="hidden xl:flex flex-col w-64 p-4 sticky top-0 h-screen justify-center items-center">
                <div class="w-full h-[600px] bg-gray-900/20 border border-white/5 rounded-xl flex items-center justify-center overflow-hidden">
                    <GoogleAd ad-slot="RIGHT_SIDEBAR_SLOT" ad-format="vertical" />
                </div>
            </aside>

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
