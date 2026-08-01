<script setup>
import BaseModal from '@/Components/BaseModal.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: null,
    },
    message: {
        type: String,
        default: null,
    },
    confirmText: {
        type: String,
        default: null,
    },
    cancelText: {
        type: String,
        default: null,
    },
    variant: {
        type: String,
        default: 'danger',
        validator: (v) => ['danger', 'warning', 'brand'].includes(v),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close', 'confirm'])

const variantClasses = {
    danger: {
        icon: 'text-red-400 bg-red-500/10 border-red-500/20',
        button: 'bg-red-600 hover:bg-red-500 disabled:bg-gray-700',
        svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />',
    },
    warning: {
        icon: 'text-amber-400 bg-amber-500/10 border-amber-500/20',
        button: 'bg-purple-600 hover:bg-purple-500 disabled:bg-gray-700',
        svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>',
    },
    brand: {
        icon: 'text-purple-400 bg-purple-500/10 border-purple-500/20',
        button: 'bg-purple-600 hover:bg-purple-500 disabled:bg-gray-700',
        svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>',
    },
}
</script>

<template>
    <BaseModal
        :show="show"
        max-width="sm"
        :closeable="!loading"
        :show-close-btn="false"
        padding="md"
        @close="emit('close')"
    >
        <div class="flex flex-col items-center text-center py-2">
            <div
                :class="[
                    'w-14 h-14 rounded-2xl border flex items-center justify-center mb-4',
                    variantClasses[variant].icon,
                ]"
                v-html="variantClasses[variant].svg"
            />
            <h3 class="text-base font-bold text-white leading-tight mb-1.5">
                {{ title }}
            </h3>
            <p v-if="message" class="text-sm text-gray-400 leading-relaxed max-w-xs">
                {{ message }}
            </p>
        </div>

        <template #footer>
            <button
                type="button"
                :disabled="loading"
                class="flex-1 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest bg-gray-800 border border-white/10 text-gray-400 hover:text-white hover:border-white/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                @click="emit('close')"
            >
                {{ cancelText || t('common.cancel') }}
            </button>
            <button
                type="button"
                :disabled="disabled || loading"
                :class="[
                    'flex-1 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-white transition-all flex items-center justify-center gap-2',
                    variantClasses[variant].button,
                    disabled || loading ? 'opacity-50 cursor-not-allowed' : '',
                ]"
                @click="emit('confirm')"
            >
                <svg
                    v-if="loading"
                    class="animate-spin w-4 h-4 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ confirmText }}
            </button>
        </template>
    </BaseModal>
</template>
