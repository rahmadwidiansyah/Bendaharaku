<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3AmountKeypad from '@/Material/Components/M3AmountKeypad.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { goBack } from '@/Material/utils/goBack.js'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { formatNumber, formatRupiah } from '@/utils/format.js'

const { t } = useI18n()

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
})

const groupKeys = computed(() => Object.keys(props.expenseGroups))

const emptyRow = () => ({ category_id: '', group_key: '', target_amount: '', custom_group_name: '' })

const buildInitialRows = () => {
    const rows = []
    for (const item of props.existingBudget?.items ?? []) {
        if (!item.budgetable_type?.endsWith('Category')) continue
        const category = props.categories.find((c) => c.id === item.budgetable_id)
        if (!category) continue
        const group = (props.existingBudget?.expense_groups ?? []).find((g) =>
            (g.category_ids ?? []).includes(item.budgetable_id),
        )
        const isCustomGroup = group && !groupKeys.value.includes(group.group_key)
        rows.push({
            category_id: item.budgetable_id,
            group_key: isCustomGroup ? 'custom' : (group?.group_key ?? ''),
            custom_group_name: isCustomGroup ? (group.group_name ?? '') : '',
            target_amount: String(Math.round(Number(item.target_amount)) || ''),
        })
    }
    return rows.length > 0 ? rows : [emptyRow()]
}

const form = useForm({
    rows: buildInitialRows(),
    delete_ai: false,
})

const isAiExisting = computed(() => props.existingBudget?.generated_by === 'ai')
const isMergeMode = computed(() => !!props.existingBudget && isAiExisting.value && !form.delete_ai)

const addRow = () => {
    form.rows.push(emptyRow())
}

const removeRow = (index) => {
    form.rows.splice(index, 1)
    if (form.rows.length === 0) form.rows.push(emptyRow())
}

const picker = ref(null)
const pickerIndex = ref(0)
const customName = ref('')

const openCategoryPicker = (index) => {
    pickerIndex.value = index
    picker.value = 'category'
}

const openGroupPicker = (index) => {
    pickerIndex.value = index
    picker.value = 'group'
    const current = form.rows[index]
    customName.value = current?.group_key === 'custom' ? current.custom_group_name : ''
}

const closePicker = () => {
    picker.value = null
}

const row = computed(() => form.rows[pickerIndex.value])

const usedCategoryIds = computed(() =>
    new Set(form.rows.filter((_, i) => i !== pickerIndex.value).map((r) => r.category_id)),
)

const categoryOf = (index) =>
    props.categories.find((c) => c.id === form.rows[index].category_id)

const categoryLabelOf = (index) => categoryOf(index)?.category_name ?? ''
const categoryIconOf = (index) => categoryOf(index)?.icon ?? ''

const groupLabelOf = (index) => {
    const r = form.rows[index]
    if (!r.group_key) return ''
    return r.group_key === 'custom' ? r.custom_group_name : (props.expenseGroups[r.group_key] ?? r.group_key)
}

const selectCategory = (cat) => {
    row.value.category_id = cat.id
    closePicker()
}

const selectGroup = (key) => {
    row.value.group_key = key
    if (key !== 'custom') row.value.custom_group_name = ''
    closePicker()
}

const addCustomGroup = () => {
    const name = customName.value.trim()
    if (!name) return
    row.value.group_key = 'custom'
    row.value.custom_group_name = name
    closePicker()
}

const quickAmounts = [50000, 100000, 250000, 500000, 1000000]

const amountIndex = ref(null)
const amountRow = computed(() =>
    amountIndex.value === null ? null : form.rows[amountIndex.value],
)

const openAmount = (index) => {
    amountIndex.value = index
}

const closeAmount = () => {
    amountIndex.value = null
}

const setQuickAmount = (amount) => {
    if (amountRow.value) amountRow.value.target_amount = String(amount)
}

const handleKeypad = (key) => {
    if (!amountRow.value) return
    let raw = String(amountRow.value.target_amount || '')
    if (key === 'C') {
        raw = ''
    } else if (key === 'del') {
        raw = raw.slice(0, -1)
    } else {
        raw = (raw + key).replace(/^0+(?=\d)/, '').slice(0, 9)
    }
    amountRow.value.target_amount = raw
}

const totalBudget = computed(() =>
    form.rows.reduce((sum, r) => sum + (Number(r.target_amount) || 0), 0),
)

const rowError = (index) =>
    form.errors[`rows.${index}.category_id`]
    || form.errors[`rows.${index}.group_key`]
    || form.errors[`rows.${index}.custom_group_name`]
    || form.errors[`rows.${index}.target_amount`]

const amountLabel = (index) => {
    const raw = Number(form.rows[index].target_amount || 0)
    return raw > 0 ? formatRupiah(raw) : t('budgeting.amountPlaceholder')
}

const submit = () => {
    form.post(route('budgeting.store'))
}
</script>

<template>
    <M3Layout :title="t('budgeting.titleCreate')">
        <Head :title="t('budgeting.titleCreate')" />

        <div class="m3-bc">
            <header class="m3-bc__topbar">
                <M3IconButton :aria-label="t('btn.back')" @click="goBack(route('budgeting.index'))">
                    <AppIcon icon="arrow-left" />
                </M3IconButton>
                <h1 class="m3-bc__title">{{ t('budgeting.titleCreate') }}</h1>
            </header>

            <form class="m3-bc__form" @submit.prevent="submit">
                <M3Card
                    v-if="existingBudget"
                    variant="filled"
                    padding="md"
                    class="m3-bc__notice"
                    :class="isMergeMode ? 'm3-bc__notice--merge' : 'm3-bc__notice--replace'"
                >
                    <div class="m3-bc__notice-body">
                        <AppIcon
                            :icon="isMergeMode ? 'info' : 'triangle-alert'"
                            class="m3-bc__notice-icon"
                        />
                        <p class="m3-bc__notice-text">
                            {{ isMergeMode ? t('budgeting.mergeHint') : t('budgeting.replaceHint') }}
                        </p>
                    </div>

                    <label v-if="isAiExisting" class="m3-bc__check">
                        <input v-model="form.delete_ai" type="checkbox" class="m3-bc__check-box">
                        <span>{{ t('budgeting.replaceAi') }}</span>
                    </label>
                </M3Card>

                <p v-if="form.errors.rows" class="m3-bc__alert" role="alert">
                    {{ form.errors.rows }}
                </p>

                <div class="m3-bc__list">
                    <M3Card
                        v-for="(r, index) in form.rows"
                        :key="index"
                        variant="outlined"
                        padding="sm"
                        class="m3-bc__row"
                    >
                        <div class="m3-bc__row-head">
                            <span class="m3-bc__row-num" aria-hidden="true">{{ index + 1 }}</span>
                            <span class="m3-bc__row-title">
                                {{ t('budgeting.categoryLabel') }} {{ index + 1 }}
                            </span>
                            <M3IconButton
                                :aria-label="t('budgeting.removeRow')"
                                :title="t('budgeting.removeRow')"
                                class="m3-bc__remove"
                                @click="removeRow(index)"
                            >
                                <AppIcon icon="trash-2" />
                            </M3IconButton>
                        </div>

                        <div class="m3-bc__fields">
                            <button
                                type="button"
                                class="m3-bc__field m3-bc__field--category"
                                :class="{ 'm3-bc__field--error': form.errors[`rows.${index}.category_id`] }"
                                aria-haspopup="dialog"
                                @click="openCategoryPicker(index)"
                            >
                                <span class="m3-bc__field-label">{{ t('budgeting.categoryLabel') }}</span>
                                <span class="m3-bc__field-value">
                                    <AppIcon
                                        v-if="categoryOf(index)"
                                        :icon="categoryIconOf(index)"
                                        fallback="folder"
                                        class="m3-bc__field-icon"
                                        :class="getM3CategoryIconClass('Expense')"
                                    />
                                    <span
                                        class="m3-bc__field-text"
                                        :class="{ 'm3-bc__field-text--placeholder': !categoryLabelOf(index) }"
                                    >
                                        {{ categoryLabelOf(index) || t('budgeting.selectCategory') }}
                                    </span>
                                </span>
                                <AppIcon icon="chevron-down" class="m3-bc__field-trailing" />
                            </button>

                            <button
                                type="button"
                                class="m3-bc__field"
                                :class="{ 'm3-bc__field--error': form.errors[`rows.${index}.group_key`] }"
                                aria-haspopup="dialog"
                                @click="openGroupPicker(index)"
                            >
                                <span class="m3-bc__field-label">{{ t('budgeting.groupLabel') }}</span>
                                <span class="m3-bc__field-value">
                                    <span
                                        class="m3-bc__field-text"
                                        :class="{ 'm3-bc__field-text--placeholder': !groupLabelOf(index) }"
                                    >
                                        {{ groupLabelOf(index) || t('budgeting.selectGroup') }}
                                    </span>
                                </span>
                                <AppIcon icon="chevron-down" class="m3-bc__field-trailing" />
                            </button>

                            <button
                                type="button"
                                class="m3-bc__field m3-bc__field--amount"
                                :class="{ 'm3-bc__field--error': form.errors[`rows.${index}.target_amount`] }"
                                aria-haspopup="dialog"
                                :aria-label="t('budgeting.amountLabel')"
                                @click="openAmount(index)"
                            >
                                <span class="m3-bc__field-label">{{ t('budgeting.amountLabel') }}</span>
                                <span class="m3-bc__field-value">
                                    <span
                                        class="m3-bc__field-text m3-bc__amount-value"
                                        :class="{ 'm3-bc__field-text--placeholder': !r.target_amount }"
                                    >
                                        {{ amountLabel(index) }}
                                    </span>
                                </span>
                                <AppIcon icon="pencil" class="m3-bc__field-trailing" />
                            </button>
                        </div>

                        <M3TextField
                            v-if="r.group_key === 'custom'"
                            :model-value="r.custom_group_name"
                            :label="t('budgeting.customGroup')"
                            :placeholder="t('budgeting.customGroupPlaceholder')"
                            :error="form.errors[`rows.${index}.custom_group_name`]"
                            class="m3-bc__custom-field"
                            @update:model-value="r.custom_group_name = $event"
                        />

                        <p v-if="rowError(index)" class="m3-bc__row-error" role="alert">
                            <AppIcon icon="circle-alert" />
                            <span>{{ rowError(index) }}</span>
                        </p>
                    </M3Card>
                </div>

                <M3Button variant="outlined" full-width @click="addRow">
                    <template #leading><AppIcon icon="plus" /></template>
                    {{ t('budgeting.addRow') }}
                </M3Button>

                <div class="m3-bc__dock">
                    <div class="m3-bc__dock-total">
                        <span class="m3-bc__dock-label">
                            {{ t('budgeting.totalLabel') }} · {{ form.rows.length }} {{ t('budgeting.categories') }}
                        </span>
                        <span class="m3-bc__dock-value" aria-live="polite">
                            {{ formatRupiah(totalBudget) }}
                        </span>
                    </div>
                    <M3Button
                        variant="filled"
                        size="lg"
                        type="submit"
                        :disabled="form.processing || totalBudget === 0"
                    >
                        <template #leading>
                            <M3CircularProgress v-if="form.processing" size="sm" />
                            <AppIcon v-else icon="check" />
                        </template>
                        {{ form.processing ? t('btn.saving') : t('btn.save') }}
                    </M3Button>
                </div>
            </form>
        </div>

        <M3Modal
            :show="picker === 'category'"
            :title="t('budgeting.categoryLabel')"
            variant="dialog"
            mobile-variant="sheet"
            max-width="sm"
            @close="closePicker"
        >
            <div v-if="props.categories.length" class="m3-bc__picker-list">
                <button
                    v-for="cat in props.categories"
                    :key="cat.id"
                    type="button"
                    class="m3-bc__picker-row"
                    :class="{
                        'm3-bc__picker-row--selected': row?.category_id === cat.id,
                        'm3-bc__picker-row--disabled': usedCategoryIds.has(cat.id),
                    }"
                    :disabled="usedCategoryIds.has(cat.id)"
                    :aria-pressed="row?.category_id === cat.id"
                    @click="selectCategory(cat)"
                >
                    <span class="m3-bc__picker-icon" aria-hidden="true">
                        <AppIcon
                            :icon="cat.icon"
                            fallback="folder"
                            :class="getM3CategoryIconClass('Expense')"
                        />
                    </span>
                    <span class="m3-bc__picker-name">{{ cat.category_name }}</span>
                    <AppIcon
                        v-if="row?.category_id === cat.id"
                        icon="check"
                        class="m3-bc__picker-check"
                    />
                </button>
            </div>
            <div v-else class="m3-bc__picker-empty">
                <p>{{ t('budgeting.noCategories') }}</p>
                <M3Button
                    variant="outlined"
                    :href="route('categories.create', { type: 'Expense' })"
                >
                    {{ t('budgeting.addRow') }}
                </M3Button>
            </div>
        </M3Modal>

        <M3Modal
            :show="picker === 'group'"
            :title="t('budgeting.groupLabel')"
            variant="dialog"
            mobile-variant="sheet"
            max-width="sm"
            @close="closePicker"
        >
            <div class="m3-bc__picker-list">
                <button
                    v-for="key in groupKeys"
                    :key="key"
                    type="button"
                    class="m3-bc__picker-row"
                    :class="{ 'm3-bc__picker-row--selected': row?.group_key === key }"
                    :aria-pressed="row?.group_key === key"
                    @click="selectGroup(key)"
                >
                    <span class="m3-bc__picker-name">{{ expenseGroups[key] }}</span>
                    <AppIcon
                        v-if="row?.group_key === key"
                        icon="check"
                        class="m3-bc__picker-check"
                    />
                </button>
            </div>

            <div class="m3-bc__custom">
                <M3TextField
                    v-model="customName"
                    :label="t('budgeting.customGroup')"
                    :placeholder="t('budgeting.customGroupPlaceholder')"
                    @enter="addCustomGroup"
                />
                <M3Button
                    variant="tonal"
                    full-width
                    :disabled="!customName.trim()"
                    @click="addCustomGroup"
                >
                    {{ t('budgeting.addCustomGroup') }}
                </M3Button>
            </div>
        </M3Modal>

        <M3Modal
            :show="amountIndex !== null"
            :title="t('budgeting.amountLabel')"
            variant="dialog"
            mobile-variant="sheet"
            max-width="sm"
            @close="closeAmount"
        >
            <div class="m3-bc__keypad">
                <p class="m3-bc__keypad-amount" aria-live="polite">
                    <span class="m3-bc__keypad-currency">Rp</span>
                    {{ formatNumber(Number(amountRow?.target_amount || 0)) }}
                </p>

                <div class="m3-bc__quick">
                    <M3Chip
                        v-for="q in quickAmounts"
                        :key="q"
                        variant="assist"
                        @click="setQuickAmount(q)"
                    >
                        {{ formatRupiah(q) }}
                    </M3Chip>
                </div>

                <M3AmountKeypad @key="handleKeypad" />

                <M3Button variant="filled" full-width size="lg" @click="closeAmount">
                    {{ t('common.ok') }}
                </M3Button>
            </div>
        </M3Modal>
    </M3Layout>
</template>

<style scoped>
.m3-bc {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-bc__topbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-bc__title {
    margin: 0;
    min-width: 0;
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
    color: var(--m3-color-on-surface);
}

.m3-bc__form {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    min-width: 0;
}

/* ── Existing budget notice ── */
.m3-bc__notice {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-bc__notice--merge {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-bc__notice--replace {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-bc__notice-body {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-bc__notice-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.m3-bc__notice-text {
    margin: 0;
    min-width: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-body-small-weight);
    letter-spacing: var(--m3-type-body-small-tracking);
}

.m3-bc__check {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    min-height: var(--m3-size-touch-target);
    cursor: pointer;
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
}

.m3-bc__check-box {
    appearance: none;
    display: grid;
    place-content: center;
    width: var(--m3-size-checkbox);
    height: var(--m3-size-checkbox);
    flex-shrink: 0;
    border: 2px solid currentColor;
    border-radius: 2px;
    background: transparent;
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-bc__check-box::after {
    content: '';
    width: 0.625rem;
    height: 0.375rem;
    border-left: 2px solid var(--m3-color-on-primary);
    border-bottom: 2px solid var(--m3-color-on-primary);
    transform: rotate(-45deg) scale(0);
    transition: transform var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-bc__check-box:checked {
    background: var(--m3-color-primary);
    border-color: var(--m3-color-primary);
}

.m3-bc__check-box:checked::after {
    transform: rotate(-45deg) scale(1);
}

.m3-bc__check-box:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-bc__alert {
    margin: 0;
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

/* ── Rows ── */
.m3-bc__list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-bc__row {
    min-width: 0;
}

.m3-bc__row-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-bc__row-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

.m3-bc__row-title {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
}

.m3-bc__remove {
    flex-shrink: 0;
    color: var(--m3-color-error);
}

.m3-bc__fields {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-3);
}

.m3-bc__fields > * {
    min-width: 0;
}

.m3-bc__field {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas:
        'label trailing'
        'value trailing';
    gap: 0 var(--m3-space-2);
    align-items: center;
    width: 100%;
    min-height: 3.5rem;
    padding: var(--m3-space-2) var(--m3-space-3);
    border: 1px solid transparent;
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-bc__field:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-bc__field:active {
    transform: scale(0.99);
}

.m3-bc__field:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-bc__field--error {
    border-color: var(--m3-color-error);
}

.m3-bc__field-label {
    grid-area: label;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
}

.m3-bc__field-value {
    grid-area: value;
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-bc__field-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    flex-shrink: 0;
}

.m3-bc__field-text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-body-large-weight);
    letter-spacing: var(--m3-type-body-large-tracking);
}

.m3-bc__field-text--placeholder {
    color: var(--m3-color-on-surface-variant);
}

.m3-bc__field-trailing {
    grid-area: trailing;
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    color: var(--m3-color-on-surface-variant);
}

.m3-bc__field--amount .m3-bc__field-value {
    justify-content: flex-end;
}

.m3-bc__amount-value {
    text-align: right;
    font-weight: var(--m3-type-emphasized-body-large-weight);
    font-variant-numeric: tabular-nums;
}

.m3-bc__custom-field {
    margin-top: var(--m3-space-2);
}

.m3-bc__row-error {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-1);
    margin: var(--m3-space-2) 0 0;
    color: var(--m3-color-error);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

.m3-bc__row-error :deep(svg) {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

/* ── Sticky total dock ── */
.m3-bc__dock {
    position: sticky;
    bottom: calc(var(--m3-size-navbar) + var(--m3-space-3));
    z-index: 5;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--m3-space-3);
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-high);
    box-shadow: var(--m3-elevation-2);
}

.m3-bc__dock-total {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-bc__dock-label {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
}

.m3-bc__dock-value {
    flex-shrink: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    letter-spacing: var(--m3-type-headline-small-tracking);
    font-variant-numeric: tabular-nums;
}

.m3-bc__dock > :deep(.m3-btn) {
    width: 100%;
}

/* ── Picker ── */
.m3-bc__picker-list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    max-height: 50vh;
    overflow-y: auto;
}

.m3-bc__picker-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    width: 100%;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-2) var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface);
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-bc__picker-row:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-bc__picker-row:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-bc__picker-row--selected {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-bc__picker-row--disabled {
    opacity: 0.38;
    cursor: not-allowed;
}

.m3-bc__picker-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
}

.m3-bc__picker-icon :deep(svg) {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-bc__picker-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
}

.m3-bc__picker-check {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-bc__picker-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-4);
    padding: var(--m3-space-4) 0;
    text-align: center;
}

.m3-bc__picker-empty p {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-bc__custom {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-4);
    padding-top: var(--m3-space-4);
    border-top: 1px solid var(--m3-color-outline-variant);
}

/* ── Keypad sheet ── */
.m3-bc__keypad {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-bc__keypad-amount {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-headline-large-size);
    line-height: var(--m3-type-headline-large-line-height);
    font-weight: var(--m3-type-emphasized-headline-large-weight);
    letter-spacing: var(--m3-type-headline-large-tracking);
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.m3-bc__keypad-currency {
    margin-right: var(--m3-space-1);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-title-medium-weight);
}

.m3-bc__quick {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1);
}

@media (min-width: 640px) {
    .m3-bc__fields {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .m3-bc__field--category {
        grid-column: 1 / -1;
    }

    .m3-bc__dock {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
    }

    .m3-bc__dock > :deep(.m3-btn) {
        width: auto;
    }
}

@media (min-width: 1024px) {
    .m3-bc__topbar {
        display: none;
    }

    .m3-bc__fields {
        grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr);
    }

    .m3-bc__field--category {
        grid-column: auto;
    }

    .m3-bc__dock {
        bottom: var(--m3-space-4);
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-bc__field,
    .m3-bc__check-box,
    .m3-bc__check-box::after,
    .m3-bc__picker-row {
        transition: none;
    }
}
</style>
