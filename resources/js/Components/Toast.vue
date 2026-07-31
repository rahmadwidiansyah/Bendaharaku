<script setup>
import { watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const { message, toastType, visible, showToast } = useToast();

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        showToast(flash.success, 'success');
    }
    if (flash?.error) {
        showToast(flash.error, 'error');
    }
}, { deep: true, immediate: true });

onMounted(() => {
    if (page.props.flash?.success) {
        showToast(page.props.flash.success, 'success');
    }
    if (page.props.flash?.error) {
        showToast(page.props.flash.error, 'error');
    }
});
</script>

<template>
    <transition name="toast-slide">
        <div v-if="visible" class="fixed top-16 lg:top-5 left-1/2 -translate-x-1/2 z-[100] w-[90%] sm:max-w-md lg:max-w-lg">
            <div :class="[
                'rounded-xl p-4 shadow-xl border flex items-center gap-3',
                toastType === 'success' ? 'bg-gradient-to-br from-success-deep to-gray-900 border-green-500/30' : 'bg-gradient-to-br from-danger-deep to-gray-900 border-red-500/30'
            ]">
                <div :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center shrink-0',
                    toastType === 'success' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'
                ]">
                    <svg v-if="toastType === 'success'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <p class="text-sm font-bold text-white tracking-tight">{{ message }}</p>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.toast-slide-enter-active,
.toast-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-slide-enter-from,
.toast-slide-leave-to {
    opacity: 0;
    transform: translate(-50%, -20px);
}
</style>
