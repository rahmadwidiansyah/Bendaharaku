<script setup>
/**
 * M3CategoryForm — form Create/Edit kategori versi M3.
 * Memiliki state form, submit, dan konfirmasi hapus sendiri sehingga
 * halaman Create/Edit hanya mengatur layout dan judul. Kontrak field
 * dan endpoint identik dengan Pages/Categories/{Create,Edit}.vue Default.
 *
 * Presentasi: satu kartu outlined dengan seksi bertoken, segmented tipe
 * 40dp, dock aksi sticky di mobile (pola Budgeting/Create), dan aksi
 * destruktif memakai M3Button varian error.
 */
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3Textarea from '@/Material/Components/M3Textarea.vue'
import M3IconPicker from '@/Material/Components/M3IconPicker.vue'
import AppIcon from '@/Components/AppIcon.vue'

const { t } = useI18n()

const props = defineProps({
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit'].includes(value),
    },
    category: { type: Object, default: null },
    types: { type: Array, default: () => [] },
    isSystem: { type: Boolean, default: false },
    defaultType: { type: String, default: 'Expense' },
})

const initialTypeId = props.category?.type_id
    ?? props.types.find((type) => type.name === props.defaultType)?.id
    ?? props.types.find((type) => type.name === 'Expense')?.id
    ?? ''

const form = useForm({
    category_name: props.category?.category_name ?? '',
    type_id: initialTypeId,
    icon: props.category?.icon ?? 'folder',
    icon_file: null,
    keyword: props.category?.keyword ?? '',
})

const isEdit = computed(() => props.mode === 'edit')
const canChangeType = computed(() => !isEdit.value || !props.isSystem)
const showDeleteConfirm = ref(false)

const typeName = (name) => ({
    Income: t('types.income'),
    Expense: t('types.expense'),
    Transfer: t('types.transfer'),
    Debt: t('types.debt'),
    Receivable: t('types.receivable'),
}[name] ?? name)

const typeIcon = (name) => (name === 'Income' ? 'arrow-down' : 'arrow-up')

const onIconSelected = (icon) => {
    form.icon = icon
    form.icon_file = null
}

const onFileSelected = (file) => {
    form.icon_file = file
    form.icon = ''
}

const submit = () => {
    if (isEdit.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('categories.update', props.category.id))
        return
    }

    form.post(route('categories.store'))
}

const requestDelete = () => {
    if (props.isSystem) return
    showDeleteConfirm.value = true
}

const confirmDelete = () => {
    if (props.isSystem) return
    form.delete(route('categories.destroy', props.category.id), {
        preserveScroll: true,
        onFinish: () => {
            showDeleteConfirm.value = false
        },
    })
}
</script>

<template>
    <form class="m3-cat-form" @submit.prevent="submit">
        <M3Card variant="outlined" padding="lg" class="m3-cat-form__card">
            <section class="m3-cat-form__section">
                <h2 class="m3-cat-form__section-title">{{ t('category.type') }}</h2>

                <div v-if="canChangeType" class="m3-cat-form__segmented" role="group" :aria-label="t('category.type')">
                    <button
                        v-for="type in types"
                        :key="type.id"
                        type="button"
                        class="m3-cat-form__segment"
                        :class="[
                            type.name === 'Income' ? 'm3-cat-form__segment--income' : 'm3-cat-form__segment--expense',
                            { 'm3-cat-form__segment--selected': form.type_id === type.id },
                        ]"
                        :aria-pressed="form.type_id === type.id"
                        @click="form.type_id = type.id"
                    >
                        <AppIcon :icon="typeIcon(type.name)" class="m3-cat-form__segment-icon" />
                        <span>{{ typeName(type.name) }}</span>
                        <AppIcon
                            v-if="form.type_id === type.id"
                            icon="check"
                            class="m3-cat-form__segment-check"
                        />
                    </button>
                </div>

                <div v-else class="m3-cat-form__readonly">
                    <span class="m3-cat-form__readonly-value">
                        <AppIcon icon="lock" class="m3-cat-form__lock" />
                        {{ typeName(category.type?.name) }}
                    </span>
                    <span class="m3-cat-form__readonly-hint">{{ t('category.systemCategory') }}</span>
                </div>

                <p v-if="form.errors.type_id" class="m3-cat-form__error" role="alert">
                    {{ form.errors.type_id }}
                </p>
            </section>

            <section class="m3-cat-form__section m3-cat-form__section--divided">
                <div class="m3-cat-form__identity">
                    <M3IconPicker
                        :model-value="form.icon"
                        :default-icon="category?.icon || 'folder'"
                        @update:model-value="onIconSelected"
                        @file-selected="onFileSelected"
                    />

                    <div class="m3-cat-form__identity-field">
                        <M3TextField
                            v-model="form.category_name"
                            :label="t('category.name')"
                            :placeholder="t('category.namePlaceholder')"
                            :error="form.errors.category_name"
                            autocomplete="off"
                        />
                    </div>
                </div>

                <p v-if="form.errors.icon_file" class="m3-cat-form__error" role="alert">
                    {{ form.errors.icon_file }}
                </p>
                <p v-if="form.errors.icon" class="m3-cat-form__error" role="alert">
                    {{ form.errors.icon }}
                </p>
            </section>

            <section class="m3-cat-form__section m3-cat-form__section--divided">
                <M3Textarea
                    v-model="form.keyword"
                    :label="t('category.keyword')"
                    :hint="t('category.keywordHint')"
                    :error="form.errors.keyword"
                />
            </section>
        </M3Card>

        <div class="m3-cat-form__actions">
            <M3Button
                v-if="isEdit && !isSystem"
                variant="text"
                type="button"
                class="m3-cat-form__delete"
                :disabled="form.processing"
                @click="requestDelete"
            >
                <template #leading>
                    <AppIcon icon="trash-2" />
                </template>
                {{ t('btn.delete') }}
            </M3Button>

            <M3Button
                type="submit"
                size="lg"
                class="m3-cat-form__save"
                :disabled="form.processing"
            >
                <template #leading>
                    <M3CircularProgress v-if="form.processing" size="sm" />
                    <AppIcon v-else icon="check" />
                </template>
                {{ form.processing ? t('btn.saving') : (isEdit ? t('btn.update') : t('btn.save')) }}
            </M3Button>
        </div>
    </form>

    <M3Modal
        :show="showDeleteConfirm"
        :title="t('category.deleteTitle')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="sm"
        @close="showDeleteConfirm = false"
    >
        <p class="m3-cat-form__dialog-text">{{ t('category.deleteMsg') }}</p>
        <template #footer>
            <M3Button variant="text" :disabled="form.processing" @click="showDeleteConfirm = false">
                {{ t('common.cancel') }}
            </M3Button>
            <M3Button variant="error" :disabled="form.processing" @click="confirmDelete">
                {{ form.processing ? t('btn.deleting') : t('btn.delete') }}
            </M3Button>
        </template>
    </M3Modal>
</template>

<style scoped>
.m3-cat-form {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    width: 100%;
}

.m3-cat-form__card {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
}

.m3-cat-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-cat-form__section--divided {
    padding-top: var(--m3-space-5);
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-cat-form__section-title {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
}

/* ── Segmented tipe (Income/Expense) ────────────────────────────── */
.m3-cat-form__segmented {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(0, 1fr);
    gap: var(--m3-space-1);
    padding: var(--m3-space-1);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-highest);
}

.m3-cat-form__segment {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-1);
    min-height: var(--m3-size-touch-target);
    padding: 0 var(--m3-space-2);
    border: 1px solid transparent;
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-family: inherit;
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cat-form__segment:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-cat-form__segment-icon,
.m3-cat-form__segment-check {
    flex-shrink: 0;
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-cat-form__segment--income.m3-cat-form__segment--selected {
    border-color: var(--m3-color-income);
    background: var(--m3-color-income-container);
    color: var(--m3-color-on-income-container);
}

.m3-cat-form__segment--expense.m3-cat-form__segment--selected {
    border-color: var(--m3-color-expense);
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-cat-form__readonly {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    min-height: var(--m3-size-text-field);
    padding: 0 var(--m3-space-4);
    border-radius: var(--m3-radius-xs) var(--m3-radius-xs) 0 0;
    background: var(--m3-color-surface-container-highest);
}

.m3-cat-form__readonly-value {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
}

.m3-cat-form__readonly-hint {
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

.m3-cat-form__lock {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
    color: var(--m3-color-on-surface-variant);
}

/* ── Identitas: ikon + nama ─────────────────────────────────────── */
.m3-cat-form__identity {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-cat-form__identity-field {
    flex: 1;
    min-width: 0;
}

.m3-cat-form__error {
    margin: 0;
    padding: 0 var(--m3-space-1);
    color: var(--m3-color-error);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

/* ── Dock aksi ──────────────────────────────────────────────────── */
.m3-cat-form__actions {
    position: sticky;
    bottom: calc(var(--m3-size-navbar) + var(--m3-space-3));
    z-index: 5;
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-high);
    box-shadow: var(--m3-elevation-2);
}

.m3-cat-form__delete {
    flex: 0 0 auto;
    color: var(--m3-color-error);
}

.m3-cat-form__save {
    flex: 1;
}

.m3-cat-form__dialog-text {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

@media (max-width: 26rem) {
    .m3-cat-form__identity {
        flex-direction: column;
        align-items: stretch;
    }
}

@media (min-width: 64rem) {
    .m3-cat-form__actions {
        position: static;
        justify-content: flex-end;
        padding: 0;
        background: transparent;
        box-shadow: none;
    }

    .m3-cat-form__save {
        flex: 0 0 auto;
        min-width: 12rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-cat-form__segment {
        transition: none;
    }
}
</style>
