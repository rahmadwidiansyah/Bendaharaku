<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import IconPicker from '@/Components/IconPicker.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, nextTick, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    category: Object,
    types: Array,
    isSystem: Boolean,
});

const form = useForm({
    category_name: props.category.category_name,
    type_id: props.category.type_id,
    icon: props.category.icon,
    icon_file: null,
    keyword: props.category.keyword,
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

const handleFileSelected = (file) => {
    form.icon_file = file;
    form.icon = '';
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('categories.update', props.category.id));
};

const showDeleteConfirm = ref(false);

const destroy = () => {
    if (props.isSystem) return;
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    form.delete(route('categories.destroy', props.category.id));
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="t('category.titleEdit')" />

        <div class="p-4 sm:p-5 w-full lg:max-w-7xl mx-auto lg:px-8 relative animate-fade-in-up">

            <header class="hidden lg:block mb-8 pt-4">
                <p class="text-2xs text-gray-300 font-semibold mb-1 uppercase tracking-wider">Vault</p>
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ t('category.titleEdit') }}</h1>
            </header>

            <form @submit.prevent="submit" class="space-y-5 lg:space-y-6">

                <div v-if="!isSystem">
                    <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('category.type') }}</label>
                    <div class="p-1 bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl shadow-inner flex gap-1">
                        <button
                            v-for="type in types"
                            :key="type.id"
                            type="button"
                            @click="form.type_id = type.id"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 lg:py-3 rounded-lg text-2xs lg:text-xs font-bold uppercase tracking-widest transition-all"
                            :class="form.type_id === type.id
                                ? (type.name === 'Income' ? 'bg-green-500/20 text-green-400 border border-green-500/40' : 'bg-red-500/20 text-red-400 border border-red-500/40')
                                : 'text-gray-500 border border-transparent hover:text-gray-300'"
                        >
                            <svg v-if="type.name === 'Income'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14m0 0l-7-7m7 7l7-7" />
                            </svg>
                            <svg v-else class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 19V5m0 0l-7 7m7-7l7 7" />
                            </svg>
                            {{ type.name === 'Income' ? t('types.income') : t('types.expense') }}
                        </button>
                    </div>
                </div>
                <div v-else>
                    <label class="block text-2xs lg:text-sm font-medium text-gray-400 mb-1.5 lg:mb-2 ml-1">{{ t('category.type') }}</label>
                    <div class="h-[44px] lg:h-[50px] bg-gray-900/30 border border-white/5 rounded-xl px-4 lg:px-5 flex items-center text-gray-500 text-xs lg:text-sm font-medium select-none">
                        {{ category.type?.name }} (System Kategori)
                    </div>
                </div>

                <div class="flex gap-3 items-end">
                    <IconPicker v-model="form.icon" @file-selected="handleFileSelected" :defaultIcon="category.icon || 'folder'" />

                    <div class="flex-1 flex flex-col justify-end">
                        <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('category.name') }}</label>
                        <div class="h-[52px] lg:h-[60px] bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl px-4 lg:px-5 flex items-center group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all shadow-inner">
                            <input type="text" v-model="form.category_name" required
                                :placeholder="t('category.namePlaceholder')"
                                class="w-full bg-transparent border-none text-white p-0 text-sm lg:text-base font-medium placeholder-gray-600 focus:ring-0 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="block text-2xs lg:text-sm font-medium text-gray-300 mb-1.5 lg:mb-2 ml-1">{{ t('category.keyword') }}</label>
                    <div class="bg-linear-to-br from-gray-900 to-gray-800 border border-white/10 rounded-xl px-3 lg:px-4 py-3 group focus-within:border-purple-500 focus-within:ring-1 focus-within:ring-purple-500 transition-all shadow-inner min-h-[52px] lg:min-h-[60px] flex items-center">
                        <textarea ref="keywordRef" v-model="form.keyword" :placeholder="t('category.keywordHint')" rows="1"
                            @input="autoResizeKeyword"
                            class="w-full bg-transparent border-none text-white p-0 text-sm placeholder-gray-600 focus:ring-0 focus:outline-none resize-none overflow-hidden leading-5 min-h-[20px] max-h-[160px] break-words whitespace-pre-wrap"></textarea>
                    </div>
                    <p class="text-2xs text-gray-500 mt-1.5 lg:mt-2 ml-1 italic">{{ t('category.keywordHint') }}</p>
                </div>

                <div class="flex gap-3 pt-3 lg:pt-4">
                    <button v-if="!isSystem" type="button" @click="destroy"
                        class="flex-1 bg-red-950/30 border border-red-900/50 text-red-500 font-bold text-sm tracking-wide py-3.5 lg:py-4 rounded-xl active:scale-95 transition-all">
                        {{ t('btn.delete') }}
                    </button>
                    <button type="submit" :disabled="form.processing"
                        :class="isSystem ? 'w-full' : 'flex-2'"
                        class="bg-linear-to-br from-brand-deep to-brand-mid text-white font-bold text-sm tracking-wide py-3.5 lg:py-4 rounded-xl shadow-lg active:scale-95 transition-all hover:-translate-y-0.5">
                        {{ form.processing ? t('btn.saving') : t('btn.update') }}
                    </button>
                </div>
            </form>
        </div>

        <ConfirmationDialog
            :show="showDeleteConfirm"
            :title="t('category.deleteTitle')"
            :message="t('category.deleteMsg')"
            :confirm-text="t('btn.delete')"
            :cancel-text="t('common.cancel')"
            variant="danger"
            :loading="form.processing"
            @close="showDeleteConfirm = false"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(15px);
    }

    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
