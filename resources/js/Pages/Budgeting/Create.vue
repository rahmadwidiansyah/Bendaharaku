<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { getCategoryIconColor } from '@/Composables/useIcon.js';
import { formatRupiah } from '@/utils/format.js';

const { t } = useI18n();

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    expenseGroups: {
        type: Object,
        default: () => ({}),
    },
    existingBudget: {
        type: Object,
        default: null,
    },
});

const groupKeys = computed(() => Object.keys(props.expenseGroups));

const emptyRow = () => ({ category_id: '', group_key: '', target_amount: '', custom_group_name: '' });

const buildInitialRows = () => {
    const rows = [];
    for (const item of props.existingBudget?.items ?? []) {
        if (!item.budgetable_type?.endsWith('Category')) continue;
        const category = props.categories.find((c) => c.id === item.budgetable_id);
        if (!category) continue;
        const group = (props.existingBudget?.expense_groups ?? []).find((g) =>
            (g.category_ids ?? []).includes(item.budgetable_id),
        );
        const isCustomGroup = group && !groupKeys.value.includes(group.group_key);
        rows.push({
            category_id: item.budgetable_id,
            group_key: isCustomGroup ? 'custom' : (group?.group_key ?? ''),
            custom_group_name: isCustomGroup ? (group.group_name ?? '') : '',
            target_amount: String(Math.round(Number(item.target_amount)) || ''),
        });
    }
    return rows.length > 0 ? rows : [emptyRow()];
};

const form = useForm({
    rows: buildInitialRows(),
    delete_ai: false,
});

const isAiExisting = computed(() => props.existingBudget?.generated_by === 'ai');
const isMergeMode = computed(() => !!props.existingBudget && isAiExisting.value && !form.delete_ai);

const addRow = () => {
    form.rows.push(emptyRow());
};

const removeRow = (index) => {
    form.rows.splice(index, 1);
    if (form.rows.length === 0) form.rows.push(emptyRow());
};

// ─── Picker popup (kategori / tipe pengeluaran) ────────────────────
const picker = ref(null); // 'category' | 'group' | null
const pickerIndex = ref(0);
const customName = ref('');
const customInputShown = ref(false);

const openCategoryPicker = (index) => {
    pickerIndex.value = index;
    picker.value = 'category';
};

const openGroupPicker = (index) => {
    pickerIndex.value = index;
    picker.value = 'group';
    customName.value = '';
    customInputShown.value = form.rows[index].group_key === 'custom';
};

const closePicker = () => {
    picker.value = null;
};

const row = computed(() => form.rows[pickerIndex.value]);

const usedCategoryIds = computed(() =>
    new Set(form.rows.filter((_, i) => i !== pickerIndex.value).map((r) => r.category_id)),
);

const categoryOf = (index) =>
    props.categories.find((c) => c.id === form.rows[index].category_id);

const categoryLabelOf = (index) => categoryOf(index)?.category_name ?? '';
const categoryIconOf = (index) => categoryOf(index)?.icon ?? '';

const groupLabelOf = (index) => {
    const r = form.rows[index];
    if (!r.group_key) return '';
    return r.group_key === 'custom' ? r.custom_group_name : (props.expenseGroups[r.group_key] ?? r.group_key);
};

const selectCategory = (cat) => {
    row.value.category_id = cat.id;
    closePicker();
};

const selectGroup = (key) => {
    row.value.group_key = key;
    if (key !== 'custom') row.value.custom_group_name = '';
    closePicker();
};

const addCustomGroup = () => {
    const name = customName.value.trim();
    if (!name) return;
    row.value.group_key = 'custom';
    row.value.custom_group_name = name;
    closePicker();
};

// ─── Nominal & submit — optimasi UX nominal ─────────────────────────
const formatAmount = (value) => {
    if (!value && value !== 0) return '';
    const n = String(value).replace(/\D/g, '');
    if (!n) return '';
    return new Intl.NumberFormat('id-ID').format(Number(n));
};

const onAmountInput = (index, event) => {
    const raw = String(event.target.value).replace(/\D/g, '');
    // batasi 9 digit (maks 999jt) biar ga overflow
    form.rows[index].target_amount = raw.slice(0, 9);
};

const quickAmounts = [50000, 100000, 250000, 500000, 1000000];
const setQuickAmount = (index, amount) => {
    form.rows[index].target_amount = String(amount);
};

const totalBudget = computed(() =>
    form.rows.reduce((sum, r) => sum + (Number(r.target_amount) || 0), 0),
);

const rowError = (index) =>
    form.errors[`rows.${index}.category_id`]
    || form.errors[`rows.${index}.group_key`]
    || form.errors[`rows.${index}.custom_group_name`]
    || form.errors[`rows.${index}.target_amount`];

const submit = () => {
    form.post(route('budgeting.store'));
};
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head :title="t('budgeting.titleCreate')" />

        <div class="p-4 sm:p-5 w-full lg:max-w-7xl mx-auto lg:px-8 relative animate-fade-in-up">

            <header class="hidden lg:block mb-8 pt-4">
                <p class="text-2xs text-gray-300 font-semibold mb-1 uppercase tracking-wider">{{ t('budgeting.title') }}</p>
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ t('budgeting.titleCreate') }}</h1>
            </header>

            <form @submit.prevent="submit" class="space-y-5 lg:space-y-6">

                <!-- Existing budget banner -->
                <div
                    v-if="existingBudget"
                    class="flex items-start gap-2.5 rounded-xl border px-4 py-3 animate-fade-in-up"
                    :class="isMergeMode ? 'border-purple-500/30 bg-purple-500/10' : 'border-amber-500/30 bg-amber-500/10'"
                >
                    <svg class="w-4 h-4 shrink-0 mt-px" :class="isMergeMode ? 'text-purple-400' : 'text-amber-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-2xs sm:text-xs text-gray-300 leading-relaxed">
                        {{ isMergeMode ? t('budgeting.mergeHint') : t('budgeting.replaceHint') }}
                    </p>
                </div>

                <!-- Replace AI checkbox -->
                <label
                    v-if="isAiExisting"
                    class="flex items-start gap-2.5 cursor-pointer select-none rounded-xl border border-white/10 bg-linear-to-br from-gray-900 to-gray-800 px-4 py-3 transition-colors hover:border-white/20"
                >
                    <input type="checkbox" v-model="form.delete_ai" class="mt-0.5 accent-purple-500 w-4 h-4 shrink-0">
                    <span class="text-2xs sm:text-xs font-semibold text-gray-300">{{ t('budgeting.replaceAi') }}</span>
                </label>

                <div v-if="form.errors.rows" class="text-red-500 text-2xs">{{ form.errors.rows }}</div>

                <!-- Rows -->
                <div class="space-y-3">
                    <div class="hidden lg:grid grid-cols-12 gap-3 px-1">
                        <span class="col-span-4 text-2xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('budgeting.categoryLabel') }}</span>
                        <span class="col-span-3 text-2xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('budgeting.groupLabel') }}</span>
                        <span class="col-span-4 text-2xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('budgeting.amountLabel') }}</span>
                    </div>

                    <div
                        v-for="(r, index) in form.rows"
                        :key="index"
                        class="grid grid-cols-2 lg:grid-cols-12 gap-2.5 lg:gap-3 rounded-xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] p-3 lg:p-3.5 shadow-card animate-fade-in-up hover:border-[var(--color-brand-border)] transition-colors"
                    >
                        <!-- Kategori -->
                        <div class="col-span-2 lg:col-span-4">
                            <label class="block lg:hidden text-2xs font-semibold text-gray-500 mb-1 ml-1">{{ t('budgeting.categoryLabel') }}</label>
                            <button
                                type="button"
                                class="w-full min-h-[44px] lg:h-[48px] px-3 flex items-center gap-2 rounded-xl bg-gray-900 border border-white/15 text-left transition-all hover:border-purple-500/50 focus:outline-none focus:border-purple-500 active:scale-[0.98]"
                                :class="form.errors[`rows.${index}.category_id`] ? 'border-red-500' : ''"
                                :aria-label="t('budgeting.categoryLabel')"
                                @click="openCategoryPicker(index)"
                            >
                                <AppIcon
                                    v-if="categoryOf(index)"
                                    :icon="categoryIconOf(index)"
                                    fallback="folder"
                                    class="w-4 h-4 shrink-0"
                                    :class="getCategoryIconColor('Expense')"
                                />
                                <span v-else class="w-4 h-4 shrink-0 text-gray-600">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" />
                                    </svg>
                                </span>
                                <span class="flex-1 min-w-0 truncate text-sm font-medium" :class="categoryLabelOf(index) ? 'text-white' : 'text-gray-500'">
                                    {{ categoryLabelOf(index) || t('budgeting.selectCategory') }}
                                </span>
                                <svg class="w-4 h-4 shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                        </div>

                        <!-- Tipe pengeluaran -->
                        <div class="col-span-1 lg:col-span-3">
                            <label class="block lg:hidden text-2xs font-semibold text-gray-500 mb-1 ml-1">{{ t('budgeting.groupLabel') }}</label>
                            <button
                                type="button"
                                class="w-full min-h-[44px] lg:h-[48px] px-3 flex items-center gap-2 rounded-xl bg-gray-900 border border-white/15 text-left transition-all hover:border-purple-500/50 focus:outline-none focus:border-purple-500 active:scale-[0.98]"
                                :class="form.errors[`rows.${index}.group_key`] ? 'border-red-500' : ''"
                                :aria-label="t('budgeting.groupLabel')"
                                @click="openGroupPicker(index)"
                            >
                                <span class="flex-1 min-w-0 truncate text-sm font-medium" :class="groupLabelOf(index) ? 'text-white' : 'text-gray-500'">
                                    {{ groupLabelOf(index) || t('budgeting.selectGroup') }}
                                </span>
                                <svg class="w-4 h-4 shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>
                            <div
                                v-if="r.group_key === 'custom'"
                                class="flex items-center gap-1.5 mt-2 h-[40px] lg:h-[44px] bg-gray-900 border border-white/15 rounded-xl px-3 focus-within:border-purple-500 transition-all"
                                :class="form.errors[`rows.${index}.custom_group_name`] ? 'border-red-500' : ''"
                            >
                                <input
                                    type="text"
                                    v-model="r.custom_group_name"
                                    :placeholder="t('budgeting.customGroupPlaceholder')"
                                    class="w-full bg-transparent border-none text-white p-0 text-sm font-medium placeholder-gray-500 focus:ring-0 focus:outline-none"
                                >
                            </div>
                        </div>

                        <!-- Nominal — optimasi: tabular, clear, chips -->
                        <div class="col-span-1 lg:col-span-4">
                            <label class="block lg:hidden text-2xs font-semibold tracking-widest uppercase text-gray-500 mb-1 ml-1">{{ t('budgeting.amountLabel') }}</label>
                            <div class="flex items-center gap-1.5 min-h-[44px] lg:h-[48px] bg-[var(--color-surface-raised)] border rounded-xl px-3 transition-all focus-within:border-[var(--color-brand)] focus-within:ring-2 focus-within:ring-[var(--color-brand)]/20" :class="form.errors[`rows.${index}.target_amount`] ? 'border-[var(--color-expense-text)]' : 'border-[var(--color-border-default)]'">
                                <span class="text-xs font-black tracking-widest text-[var(--color-brand)] shrink-0">RP</span>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    :value="formatAmount(r.target_amount)"
                                    @input="onAmountInput(index, $event)"
                                    :placeholder="t('budgeting.amountPlaceholder')"
                                    class="w-full bg-transparent border-none text-[var(--color-text-primary)] p-0 text-sm font-black tabular-nums placeholder:text-[var(--color-text-muted)] focus:ring-0 focus:outline-none text-right"
                                    :aria-label="t('budgeting.amountLabel')"
                                >
                                <button v-if="r.target_amount" type="button" class="w-6 h-6 shrink-0 rounded-full bg-[var(--color-surface-muted)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] flex items-center justify-center transition-colors" @click="form.rows[index].target_amount=''" aria-label="Clear">
                                    <AppIcon icon="x" class="w-3 h-3" />
                                </button>
                            </div>
                            <!-- Quick chips + helper -->
                            <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                                <button v-for="q in quickAmounts.slice(0,3)" :key="q" type="button" class="px-2 py-1 rounded-full border border-[var(--color-border-default)] bg-[var(--color-surface-overlay)] text-2xs font-bold tabular-nums text-[var(--color-text-secondary)] hover:border-[var(--color-brand-border)] hover:text-[var(--color-brand)] hover:bg-[var(--color-brand-subtle)] transition-colors" @click="setQuickAmount(index, q)">{{ formatRupiah(q) }}</button>
                                <span v-if="r.target_amount" class="ml-auto text-2xs tabular-nums text-[var(--color-text-muted)]">{{ formatRupiah(r.target_amount) }}</span>
                            </div>
                        </div>

                        <!-- Hapus -->
                        <div class="col-span-2 lg:col-span-1 flex items-center lg:justify-end">
                            <button
                                type="button"
                                class="w-full lg:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-2.5 lg:h-[48px] rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 text-2xs font-semibold transition-colors hover:bg-red-500/20 active:scale-95"
                                :aria-label="t('budgeting.removeRow')"
                                @click="removeRow(index)"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" />
                                </svg>
                                <span class="lg:hidden">{{ t('budgeting.removeRow') }}</span>
                            </button>
                        </div>

                        <div v-if="rowError(index)" class="col-span-2 lg:col-span-12 text-red-500 text-2xs -mt-0.5 ml-1">
                            {{ rowError(index) }}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-gray-800/60 px-4 py-3 text-2xs sm:text-xs font-semibold text-gray-300 transition-colors hover:border-purple-500/40 hover:text-white active:scale-[0.98]"
                        @click="addRow"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 5v14m0 0l-7-7m7 7l7-7" />
                        </svg>
                        {{ t('budgeting.addRow') }}
                    </button>
                </div>

                <!-- Total summary — sticky di mobile, hierarki nominal diperjelas -->
                <div class="sticky bottom-2 z-10 rounded-2xl border border-[var(--color-border-default)] bg-[var(--color-surface-raised)]/95 backdrop-blur-xl shadow-lg p-4 lg:p-5 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold tracking-widest uppercase text-[var(--color-text-muted)]">{{ t('budgeting.totalLabel') }} · {{ form.rows.length }} {{ t('budgeting.categories') }}</p>
                        <p class="text-2xs tabular-nums text-[var(--color-text-muted)] mt-0.5 hidden sm:block">{{ t('budgeting.totalBudget') }} = Σ nominal per kategori</p>
                    </div>
                    <p class="text-lg sm:text-xl font-black tracking-tight tabular-nums text-[var(--color-text-primary)]" :title="formatRupiah(totalBudget)">{{ formatRupiah(totalBudget) }}</p>
                </div>

                <div class="pt-1 lg:pt-2">
                    <button type="submit" :disabled="form.processing || totalBudget===0"
                        class="w-full bg-gradient-to-br from-[var(--color-brand-deep)] to-[var(--color-brand-mid)] text-white font-black text-sm tracking-wide py-3.5 lg:py-4 rounded-xl shadow-lg shadow-[var(--color-brand)]/20 hover:shadow-xl hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 flex items-center justify-center gap-2">
                        <AppIcon v-if="form.processing" icon="loader-2" class="w-4 h-4 animate-spin" />
                        <AppIcon v-else icon="check" class="w-4 h-4" />
                        {{ form.processing ? t('btn.saving') : t('btn.save') }}
                    </button>
                    <p v-if="totalBudget===0" class="text-center text-2xs text-[var(--color-text-muted)] mt-2">Isi minimal 1 nominal untuk simpan</p>
                </div>
            </form>

            <!-- Picker: Kategori -->
            <BaseModal :show="picker === 'category'" max-width="sm" :show-close-btn="false" @close="closePicker">
                <div class="py-1">
                    <p class="text-sm font-bold text-white">{{ t('budgeting.categoryLabel') }}</p>
                    <p class="text-2xs text-gray-500 mt-0.5">{{ t('budgeting.pickerHint') }}</p>
                </div>
                <div v-if="props.categories.length > 0" class="mt-3 max-h-72 overflow-y-auto -mx-1 px-1 space-y-1.5">
                    <button
                        v-for="cat in props.categories"
                        :key="cat.id"
                        type="button"
                        class="w-full flex items-center gap-3 rounded-xl border px-3.5 py-3 text-left transition-all active:scale-[0.98]"
                        :class="usedCategoryIds.has(cat.id)
                            ? 'border-white/10 bg-gray-800/50 opacity-40 cursor-not-allowed'
                            : row.category_id === cat.id
                                ? 'border-purple-500/50 bg-purple-500/10'
                                : 'border-white/10 bg-gray-800/70 hover:border-purple-500/40'"
                        :disabled="usedCategoryIds.has(cat.id)"
                        @click="selectCategory(cat)"
                    >
                        <AppIcon :icon="cat.icon" fallback="folder" class="w-5 h-5 shrink-0" :class="getCategoryIconColor('Expense')" />
                        <span class="flex-1 min-w-0 truncate text-sm font-medium text-white">{{ cat.category_name }}</span>
                        <svg v-if="row.category_id === cat.id" class="w-4 h-4 shrink-0 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                    </button>
                </div>
                <p v-else class="mt-3 text-2xs text-gray-500 text-center py-4">{{ t('budgeting.noCategories') }}</p>
                <div class="mt-4 flex justify-center">
                    <button type="button" class="px-4 py-2 rounded-xl border border-white/10 bg-gray-800/70 text-2xs font-semibold text-gray-300 transition-colors hover:text-white" @click="closePicker">
                        {{ t('budgeting.close') }}
                    </button>
                </div>
            </BaseModal>

            <!-- Picker: Tipe pengeluaran -->
            <BaseModal :show="picker === 'group'" max-width="sm" :show-close-btn="false" @close="closePicker">
                <div class="py-1">
                    <p class="text-sm font-bold text-white">{{ t('budgeting.groupLabel') }}</p>
                    <p class="text-2xs text-gray-500 mt-0.5">{{ t('budgeting.pickerHint') }}</p>
                </div>
                <div class="mt-3 max-h-72 overflow-y-auto space-y-1.5">
                    <button
                        v-for="key in groupKeys"
                        :key="key"
                        type="button"
                        class="w-full flex items-center gap-3 rounded-xl border px-3.5 py-3 text-left transition-all active:scale-[0.98]"
                        :class="row.group_key === key
                            ? 'border-purple-500/50 bg-purple-500/10'
                            : 'border-white/10 bg-gray-800/70 hover:border-purple-500/40'"
                        @click="selectGroup(key)"
                    >
                        <span class="flex-1 min-w-0 truncate text-sm font-medium text-white">{{ expenseGroups[key] }}</span>
                        <svg v-if="row.group_key === key" class="w-4 h-4 shrink-0 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                    </button>
                </div>

                <!-- Custom type -->
                <div class="mt-3 rounded-xl border border-white/10 bg-gray-800/70 p-3.5">
                    <template v-if="!customInputShown">
                        <button
                            type="button"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-dashed border-purple-500/40 px-3 py-2.5 text-2xs font-semibold text-purple-300 transition-colors hover:bg-purple-500/10 active:scale-[0.98]"
                            @click="customInputShown = true"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5v14m0 0l-7-7m7 7l7-7" />
                            </svg>
                            {{ t('budgeting.customGroup') }}
                        </button>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-1.5 h-[44px] bg-gray-900 border border-white/15 rounded-xl px-3 focus-within:border-purple-500 transition-all">
                            <input
                                type="text"
                                v-model="customName"
                                :placeholder="t('budgeting.customGroupPlaceholder')"
                                class="w-full bg-transparent border-none text-white p-0 text-sm font-medium placeholder-gray-500 focus:ring-0 focus:outline-none"
                                @keydown.enter.prevent="addCustomGroup"
                            >
                        </div>
                        <div class="flex gap-2 mt-2.5">
                            <button
                                type="button"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-linear-to-br from-brand-deep to-brand-mid px-3 py-2.5 text-2xs font-bold text-white transition-all hover:-translate-y-0.5 active:scale-95 disabled:opacity-50"
                                :disabled="!customName.trim()"
                                @click="addCustomGroup"
                            >
                                {{ t('budgeting.addCustomGroup') }}
                            </button>
                            <button
                                type="button"
                                class="px-3 py-2.5 rounded-xl border border-white/10 bg-gray-900 text-2xs font-semibold text-gray-300 transition-colors hover:text-white"
                                @click="customInputShown = false"
                            >
                                {{ t('budgeting.cancel') }}
                            </button>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex justify-center">
                    <button type="button" class="px-4 py-2 rounded-xl border border-white/10 bg-gray-800/70 text-2xs font-semibold text-gray-300 transition-colors hover:text-white" @click="closePicker">
                        {{ t('budgeting.close') }}
                    </button>
                </div>
            </BaseModal>
        </div>
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
