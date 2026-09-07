<script setup>
import { Head } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    title: {
        type: String,
        default: 'Bendaharaku',
    },
});
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-[var(--color-surface-base)] text-[var(--color-text-primary)] font-sans antialiased selection:bg-[var(--color-brand)] selection:text-white flex flex-col items-center justify-center px-4 sm:px-6 py-10">
        <div class="w-full max-w-md animate-slide-up">
            <div class="text-center mb-8">
                <ApplicationLogo class="w-14 h-14 rounded-xl mx-auto mb-4 shadow-sm" />
                <slot name="header" />
            </div>
            <slot />
            <p class="text-center text-2xs text-[var(--color-text-muted)] mt-8">
                &copy; {{ new Date().getFullYear() }} Bendaharaku
            </p>
        </div>
    </div>
</template>
