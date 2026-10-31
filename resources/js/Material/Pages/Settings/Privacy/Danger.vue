<script setup>
/**
 * Material/Pages/Settings/Privacy/Danger.vue — zona berbahaya (tema M3).
 *
 * Paritas perilaku halaman Default: konfirmasi lalu POST /api/account/delete
 * dan redirect ke halaman utama.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const { t } = useI18n()
const { showToast } = useToast()

const isDeleting = ref(false)
const showDeleteConfirm = ref(false)

async function handleDeleteAccount() {
    isDeleting.value = true
    try {
        await axios.post('/api/account/delete')
        window.location.href = '/'
    } catch (error) {
        showToast(error.response?.data?.message || t('errors.generic'), 'error')
        isDeleting.value = false
    }
}
</script>

<template>
    <M3Layout :title="t('settings.privacy.danger.title')">
        <Head :title="t('settings.privacy.danger.title')" />

        <M3SettingsPage
            :title="t('settings.privacy.danger.title')"
            :subtitle="t('settings.privacy.danger.description')"
            back-route="settings.index"
            shell
        >
            <M3Card variant="outlined" padding="lg" class="m3-danger">
                <div class="m3-danger__head">
                    <span class="m3-danger__ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="m3-danger__title">{{ t('settings.privacy.danger.delete_account.title') }}</h2>
                        <p class="m3-danger__desc">{{ t('settings.privacy.danger.delete_account.description') }}</p>
                    </div>
                </div>

                <p class="m3-danger__warning">{{ t('settings.privacy.danger.delete_account.warning') }}</p>

                <M3Button
                    variant="error"
                    :disabled="isDeleting"
                    @click="showDeleteConfirm = true"
                >
                    <template #leading>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </template>
                    {{ t('settings.privacy.danger.delete_account.button') }}
                </M3Button>
            </M3Card>
        </M3SettingsPage>

        <M3Modal
            :show="showDeleteConfirm"
            :title="t('settings.privacy.danger.delete_account.confirm_title')"
            max-width="sm"
            mobile-variant="sheet"
            :closeable="!isDeleting"
            @close="showDeleteConfirm = false"
        >
            <p class="m3-danger__confirm-desc">{{ t('settings.privacy.danger.delete_account.confirm_description') }}</p>
            <template #footer>
                <M3Button
                    variant="text"
                    :disabled="isDeleting"
                    @click="showDeleteConfirm = false"
                >
                    {{ t('common.cancel') }}
                </M3Button>
                <M3Button
                    variant="error"
                    :disabled="isDeleting"
                    @click="handleDeleteAccount"
                >
                    {{ isDeleting ? t('common.deleting') : t('settings.privacy.danger.delete_account.confirm_button') }}
                </M3Button>
            </template>
        </M3Modal>
    </M3Layout>
</template>

<style scoped>
.m3-danger {
    border-color: var(--m3-color-error);
}

.m3-danger__head {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-3);
}

.m3-danger__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-danger__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-danger__title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-danger__desc {
    margin: var(--m3-space-1) 0 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-danger__warning {
    margin: var(--m3-space-4) 0;
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-danger__confirm-desc {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
