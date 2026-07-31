<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import IconPicker from '@/Components/IconPicker.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    wallet: Object,
});

const form = useForm({
    name: props.wallet.name,
    balance: props.wallet.balance,
    icon: props.wallet.icon || 'wallet',
    icon_file: null,
    keyword: props.wallet.keyword || '',

    _method: 'PUT'
});

const submit = () => {
    form.post(route('wallets.update', props.wallet.id));
};

const displayAmount = computed({
    get() {
        return form.balance ? new Intl.NumberFormat('id-ID').format(form.balance) : '';
    },
    set(val) {
        const num = String(val).replace(/\D/g, '');
        form.balance = num;
    }
});

const deleteForm = useForm({});
const showDeleteConfirm = ref(false);
const deleteWallet = () => {
    showDeleteConfirm.value = true;
};
const confirmDeleteWallet = () => {
    deleteForm.delete(route('wallets.destroy', props.wallet.id));
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="t('wallet.titleEdit')" />
        <div class="p-5 w-full lg:max-w-7xl mx-auto lg:px-8 relative animate-slide-up opacity-0"
            style="animation-delay: 50ms;">

            <header class="hidden lg:block mb-8 pt-4">
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ t('wallet.titleEdit') }}</h1>
            </header>

            <form @submit.prevent="submit" class="space-y-5 lg:space-y-6">

                <div class="flex flex-col animate-slide-up opacity-0 relative z-10" style="animation-delay: 150ms;">
                    <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('wallet.balance') }}</label>
                    <div
                        class="h-[52px] lg:h-[60px] bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl px-4 lg:px-5 flex items-center group focus-within:border-purple-500/40 focus-within:ring-1 focus-within:ring-purple-500/20 transition-all shadow-inner">
                        <span class="text-base font-bold text-purple-500 mr-3 opacity-80">Rp</span>
                        <input type="text" inputmode="numeric" required v-model="displayAmount"
                            class="w-full bg-transparent border-none text-white p-0 text-lg lg:text-xl font-bold placeholder-gray-600 focus:ring-0 focus:outline-none caret-purple-500">
                    </div>
                    <div v-if="form.errors.balance" class="text-red-500 text-2xs mt-1">{{ form.errors.balance }}</div>
                    <p class="text-2xs text-gray-500 mt-1.5 lg:mt-2 ml-1 italic">{{ t('wallet.balancePlaceholder') }}</p>
                </div>

                <div class="flex gap-3 items-end animate-slide-up opacity-0 relative z-50"
                    style="animation-delay: 200ms;">
                    <div class="flex-none">
                        <IconPicker v-model="form.icon" @file-selected="(file) => form.icon_file = file"
                            :defaultIcon="wallet.icon || 'wallet'" />
                    </div>

                    <div class="flex-1 flex flex-col justify-end">
                        <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('wallet.name') }}</label>
                        <div
                            class="h-[52px] lg:h-[60px] bg-linear-to-br from-gray-800 to-gray-900 border border-white/10 rounded-xl px-4 lg:px-5 flex items-center group focus-within:border-purple-500/40 focus-within:ring-1 focus-within:ring-purple-500/20 transition-all shadow-inner">
                            <input type="text" v-model="form.name" required :placeholder="t('wallet.namePlaceholder')"
                                class="w-full bg-transparent border-none text-white p-0 text-sm lg:text-base font-medium focus:ring-0 focus:outline-none">
                        </div>
                        <div v-if="form.errors.name" class="text-red-500 text-2xs mt-1">{{ form.errors.name }}</div>
                    </div>
                </div>

                <div class="flex flex-col animate-slide-up opacity-0 relative z-40" style="animation-delay: 250ms;">
                    <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('wallet.keyword') }}</label>
                    <div
                        class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl p-3 lg:p-4 group focus-within:border-purple-500/40 focus-within:ring-1 focus-within:ring-purple-500/20 transition-all">
                        <input type="text" v-model="form.keyword" :placeholder="t('wallet.keywordHint')"
                            class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none">
                    </div>
                    <div v-if="form.errors.keyword" class="text-red-500 text-2xs mt-1">{{ form.errors.keyword }}</div>
                </div>

                <div class="pt-3 lg:pt-4 space-y-3 animate-slide-up opacity-0 relative z-30" style="animation-delay: 275ms;">
                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-linear-to-br from-brand-deep to-brand-mid text-white font-bold text-sm tracking-wide py-3.5 lg:py-4 rounded-xl hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                        {{ form.processing ? t('btn.saving') : t('btn.update') }}
                    </button>
                </div>
            </form>

            <div class="mt-3 lg:mt-4 animate-slide-up opacity-0 relative z-20"
                style="animation-delay: 350ms;">
                <button type="button" :disabled="deleteForm.processing" @click="deleteWallet"
                    class="w-full bg-linear-to-br from-danger-mid to-danger-deep text-white font-bold text-sm py-3.5 lg:py-4 rounded-xl hover:translate-y-0.5 active:scale-95 transition-all">
                    {{ deleteForm.processing ? t('btn.deleting') : t('btn.delete') }}
                </button>
            </div>
        </div>

        <ConfirmationDialog
            :show="showDeleteConfirm"
            :title="t('wallet.deleteTitle')"
            :message="t('wallet.deleteMsg')"
            :confirm-text="t('btn.delete')"
            :cancel-text="t('common.cancel')"
            variant="danger"
            :loading="deleteForm.processing"
            @close="showDeleteConfirm = false"
            @confirm="confirmDeleteWallet"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
input:focus,
input:active {
    outline: none !important;
    box-shadow: none !important;
}

@keyframes slideUpFade {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }

    100% {
        opacity: 1;
        transform: none;
    }
}

.animate-slide-up {
    animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
