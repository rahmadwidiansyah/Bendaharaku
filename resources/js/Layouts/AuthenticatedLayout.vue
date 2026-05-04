<script setup>
import BottomNav from '@/Components/BottomNav.vue';
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
        <div class="w-full max-w-md md:max-w-full mx-auto bg-gray-800 min-h-screen flex flex-col border-x border-[#1e1e1e] relative overflow-x-hidden">
            <main id="main-content" class="flex-1 pb-24">
                <slot />
            </main>
            <BottomNav />
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
