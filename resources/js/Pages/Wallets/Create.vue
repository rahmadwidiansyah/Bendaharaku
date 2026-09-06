<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import IconPicker from '@/Components/IconPicker.vue';
import { computed, ref, nextTick, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    name: '',
    balance: '0',
    icon: 'wallet',
    icon_file: null,
    keyword: '',
    group_type: 'Liquid',
});

const keywordRef = ref(null);
const autoResizeKeyword = () => {
    nextTick(() => {
        const el = keywordRef.value;
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 160) + 'px';
    });
};
onMounted(() => autoResizeKeyword());

const submit = () => {
    form.post(route('wallets.store'));
};

const setGroup = (group) => {
    form.group_type = group;
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
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="t('wallet.titleCreate')" />
        <div class="p-5 w-full lg:max-w-7xl mx-auto lg:px-8 relative animate-slide-up opacity-0"
            style="animation-delay: 50ms;">

            <header class="hidden lg:block mb-8 pt-4">
                <h1 class="text-2xl font-bold text-[var(--color-text-primary)] tracking-tight">{{ t('wallet.titleCreate') }}</h1>
            </header>

            <div class="grid grid-cols-2 gap-2 mb-6 lg:mb-8 p-1 bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl animate-slide-up opacity-0"
                style="animation-delay: 100ms;">
                <button type="button" @click="setGroup('Liquid')"
                    :class="form.group_type === 'Liquid' ? 'w-full text-2xs font-semibold py-2.5 lg:py-3 rounded-lg bg-linear-to-br from-gray-800 to-gray-700 text-[var(--color-text-primary)] shadow-sm transition-all border border-[var(--color-border-default)]' : 'w-full text-2xs font-semibold py-2.5 lg:py-3 rounded-lg text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-all'">
                    {{ t('wallet.groupTypes.liquid') }}
                </button>
                <button type="button" @click="setGroup('Asset')"
                    :class="form.group_type === 'Asset' ? 'w-full text-2xs font-semibold py-2.5 lg:py-3 rounded-lg bg-linear-to-br from-gray-800 to-gray-700 text-[var(--color-text-primary)] shadow-sm transition-all border border-[var(--color-border-default)]' : 'w-full text-2xs font-semibold py-2.5 lg:py-3 rounded-lg text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-all'">
                    {{ t('wallet.groupTypes.asset') }}
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-5 lg:space-y-6">

                <div class="flex flex-col animate-slide-up opacity-0 relative z-10" style="animation-delay: 150ms;">
                    <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('wallet.balance') }}</label>
                    <div
                        class="h-[52px] lg:h-[60px] bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl px-4 lg:px-5 flex items-center group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all">
                        <span class="text-base font-bold text-purple-500 mr-3 opacity-80">Rp</span>
                        <input type="text" inputmode="numeric" required :placeholder="t('wallet.balancePlaceholder')" v-model="displayAmount"
                            class="w-full bg-transparent border-none text-[var(--color-text-primary)] p-0 text-lg lg:text-xl font-bold placeholder-gray-700 focus:ring-0 focus:outline-none caret-purple-500">
                    </div>
                    <div v-if="form.errors.balance" class="text-red-500 text-2xs mt-1">{{ form.errors.balance }}</div>
                </div>

                <div class="flex gap-3 items-end animate-slide-up opacity-0 relative z-50"
                    style="animation-delay: 200ms;">
                    <div class="flex-none">
                        <IconPicker v-model="form.icon" @file-selected="(file) => form.icon_file = file" />
                    </div>

                    <div class="flex-1 flex flex-col justify-end">
                        <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('wallet.name') }}</label>
                        <div
                            class="h-[52px] lg:h-[60px] bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl px-4 lg:px-5 flex items-center group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all caret-purple-500">
                            <input type="text" v-model="form.name" required :placeholder="t('wallet.namePlaceholder')"
                                class="w-full bg-transparent border-none text-[var(--color-text-primary)] p-0 text-sm lg:text-base font-medium placeholder-gray-600 focus:ring-0 focus:outline-none">
                        </div>
                        <div v-if="form.errors.name" class="text-red-500 text-2xs mt-1">{{ form.errors.name }}</div>
                    </div>
                </div>

                <div class="flex flex-col animate-slide-up opacity-0 relative z-40" style="animation-delay: 250ms;">
                    <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('wallet.keyword') }}</label>
                    <div
                        class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl px-3 lg:px-4 py-3 group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all caret-purple-500 min-h-[52px] lg:min-h-[60px] flex items-center">
                        <textarea ref="keywordRef" v-model="form.keyword" :placeholder="t('wallet.keywordHint')" rows="1"
                            @input="autoResizeKeyword"
                            class="w-full bg-transparent border-none text-[var(--color-text-primary)] p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none resize-none overflow-hidden leading-5 min-h-[20px] max-h-[160px] break-words whitespace-pre-wrap"></textarea>
                    </div>
                    <p class="text-2xs text-[var(--color-text-muted)] mt-1.5 lg:mt-2 ml-1 italic">{{ t('wallet.keywordHint') }}</p>
                </div>

                <div class="pt-3 lg:pt-4 animate-slide-up opacity-0 relative z-30" style="animation-delay: 275ms;">
                    <button type="submit" :disabled="form.processing"
                        class="w-full bg-linear-to-br from-brand-deep to-brand-mid text-[var(--color-text-primary)] font-bold text-sm tracking-wide py-3.5 lg:py-4 rounded-xl hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
                        {{ form.processing ? t('btn.saving') : t('btn.save') }}
                    </button>
                </div>
            </form>
        </div>
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
