<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3IconPicker from '@/Material/Components/M3IconPicker.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Textarea from '@/Material/Components/M3Textarea.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import AppIcon from '@/Components/AppIcon.vue'

const { t } = useI18n()

const props = defineProps({
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit'].includes(value),
    },
    wallet: { type: Object, default: null },
})

const isEdit = computed(() => props.mode === 'edit')

const form = useForm({
    name: props.wallet?.name ?? '',
    balance: props.wallet?.balance ?? '0',
    icon: props.wallet?.icon || 'wallet',
    icon_file: null,
    keyword: props.wallet?.keyword ?? '',
    group_type: 'Liquid',
})

const displayAmount = computed({
    get() {
        const raw = String(form.balance ?? '').trim()
        if (!raw) return ''
        return new Intl.NumberFormat('id-ID').format(Number(raw))
    },
    set(value) {
        form.balance = String(value).replace(/\D/g, '')
    },
})

const groups = computed(() => [
    { value: 'Liquid', label: t('wallet.groupTypes.liquid'), icon: 'wallet' },
    { value: 'Asset', label: t('wallet.groupTypes.asset'), icon: 'trending-up' },
])

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
        form.transform(({ group_type, ...data }) => ({
            ...data,
            _method: 'PUT',
        })).post(route('wallets.update', props.wallet.id))
        return
    }

    form.post(route('wallets.store'))
}

const showDeleteConfirm = ref(false)
const deleteForm = useForm({})

const confirmDelete = () => {
    if (!isEdit.value) return

    deleteForm.delete(route('wallets.destroy', props.wallet.id), {
        preserveScroll: true,
        onFinish: () => {
            showDeleteConfirm.value = false
        },
    })
}
</script>

<template>
    <form class="m3-wallet-form" @submit.prevent="submit">
        <M3Card variant="outlined" padding="lg" class="m3-wallet-form__card">
            <section v-if="!isEdit" class="m3-wallet-form__section">
                <h2 class="m3-wallet-form__section-title">{{ t('wallet.groupType') }}</h2>

                <div class="m3-wallet-form__segmented" role="group" :aria-label="t('wallet.groupType')">
                    <button
                        v-for="group in groups"
                        :key="group.value"
                        type="button"
                        class="m3-wallet-form__segment"
                        :class="{ 'm3-wallet-form__segment--selected': form.group_type === group.value }"
                        :aria-pressed="form.group_type === group.value"
                        @click="form.group_type = group.value"
                    >
                        <AppIcon :icon="group.icon" class="m3-wallet-form__segment-icon" />
                        <span>{{ group.label }}</span>
                        <AppIcon
                            v-if="form.group_type === group.value"
                            icon="check"
                            class="m3-wallet-form__segment-check"
                        />
                    </button>
                </div>

                <p v-if="form.errors.group_type" class="m3-wallet-form__error" role="alert">
                    {{ form.errors.group_type }}
                </p>
            </section>

            <section class="m3-wallet-form__section" :class="{ 'm3-wallet-form__section--divided': !isEdit }">
                <M3TextField
                    v-model="displayAmount"
                    :label="`${t('wallet.balance')} (${t('common.currency')})`"
                    inputmode="numeric"
                    :error="form.errors.balance"
                />
            </section>

            <section class="m3-wallet-form__section m3-wallet-form__section--divided">
                <div class="m3-wallet-form__identity">
                    <M3IconPicker
                        :model-value="form.icon"
                        :default-icon="wallet?.icon || 'wallet'"
                        @update:model-value="onIconSelected"
                        @file-selected="onFileSelected"
                    />

                    <div class="m3-wallet-form__identity-field">
                        <M3TextField
                            v-model="form.name"
                            :label="t('wallet.name')"
                            :placeholder="t('wallet.namePlaceholder')"
                            :error="form.errors.name"
                            autocomplete="off"
                        />
                    </div>
                </div>

                <p v-if="form.errors.icon_file" class="m3-wallet-form__error" role="alert">
                    {{ form.errors.icon_file }}
                </p>
                <p v-if="form.errors.icon" class="m3-wallet-form__error" role="alert">
                    {{ form.errors.icon }}
                </p>
            </section>

            <section class="m3-wallet-form__section m3-wallet-form__section--divided">
                <M3Textarea
                    v-model="form.keyword"
                    :label="t('wallet.keyword')"
                    :hint="t('wallet.keywordHint')"
                    :error="form.errors.keyword"
                />
            </section>
        </M3Card>

        <div class="m3-wallet-form__actions">
            <M3Button
                v-if="isEdit"
                variant="text"
                type="button"
                class="m3-wallet-form__delete"
                :disabled="deleteForm.processing || form.processing"
                @click="showDeleteConfirm = true"
            >
                <template #leading>
                    <AppIcon icon="trash-2" />
                </template>
                {{ t('btn.delete') }}
            </M3Button>

            <M3Button
                type="submit"
                size="lg"
                class="m3-wallet-form__save"
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
        :title="t('wallet.deleteTitle')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="sm"
        @close="showDeleteConfirm = false"
    >
        <p class="m3-wallet-form__dialog-text">{{ t('wallet.deleteMsg') }}</p>
        <template #footer>
            <M3Button variant="text" :disabled="deleteForm.processing" @click="showDeleteConfirm = false">
                {{ t('common.cancel') }}
            </M3Button>
            <M3Button variant="error" :disabled="deleteForm.processing" @click="confirmDelete">
                <template v-if="deleteForm.processing" #leading>
                    <M3CircularProgress size="sm" />
                </template>
                {{ deleteForm.processing ? t('btn.deleting') : t('btn.delete') }}
            </M3Button>
        </template>
    </M3Modal>
</template>

<style scoped>
.m3-wallet-form {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
}

.m3-wallet-form__card {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
}

.m3-wallet-form__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-wallet-form__section--divided {
    padding-top: var(--m3-space-5);
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-wallet-form__section-title {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
}

.m3-wallet-form__segmented {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: minmax(0, 1fr);
    gap: var(--m3-space-1);
    padding: var(--m3-space-1);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-highest);
}

.m3-wallet-form__segment {
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
    text-align: left;
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-wallet-form__segment:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-wallet-form__segment--selected {
    border-color: var(--m3-color-primary);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-wallet-form__segment-icon,
.m3-wallet-form__segment-check {
    flex-shrink: 0;
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-wallet-form__segment-check {
    margin-left: auto;
}

.m3-wallet-form__identity {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-wallet-form__identity-field {
    flex: 1;
    min-width: 0;
}

.m3-wallet-form__error {
    margin: 0;
    padding: 0 var(--m3-space-1);
    color: var(--m3-color-error);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

.m3-wallet-form__actions {
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

.m3-wallet-form__delete {
    flex: 0 0 auto;
    color: var(--m3-color-error);
}

.m3-wallet-form__save {
    flex: 1;
}

.m3-wallet-form__dialog-text {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

@media (max-width: 26rem) {
    .m3-wallet-form__identity {
        flex-direction: column;
        align-items: stretch;
    }

    .m3-wallet-form__segment {
        flex-wrap: wrap;
        justify-content: flex-start;
        padding: var(--m3-space-2);
    }

    .m3-wallet-form__segment-check {
        margin-left: 0;
    }
}

@media (min-width: 64rem) {
    .m3-wallet-form__actions {
        position: static;
        justify-content: flex-end;
        padding: 0;
        background: transparent;
        box-shadow: none;
    }

    .m3-wallet-form__save {
        flex: 0 0 auto;
        min-width: 12rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-wallet-form__segment {
        transition: none;
    }
}
</style>
