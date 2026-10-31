<script setup>
/**
 * Material/Pages/Settings/Privacy/Settings.vue — informasi privasi (tema M3).
 *
 * Paritas konten halaman Default; tidak ada kontrol yang disimpan.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
    <M3Layout :title="t('settings.privacy.settings.title')">
        <Head :title="t('settings.privacy.settings.title')" />

        <M3SettingsPage
            :title="t('settings.privacy.settings.title')"
            :subtitle="t('settings.privacy.settings.description')"
            back-route="settings.index"
            shell
        >
            <M3SettingsSection :title="t('settings.privacy.settings.data_collection.title')">
                <M3Card variant="outlined" padding="lg" class="m3-privacy__card">
                    <div class="m3-privacy__header">
                        <span class="m3-privacy__ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125M20.25 6.375c0-2.278-3.694-4.125-8.25-4.125s-8.25 1.847-8.25 4.125m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                            </svg>
                        </span>
                        <p class="m3-privacy__title">{{ t('settings.privacy.settings.data_collection.title') }}</p>
                    </div>
                    <p class="m3-privacy__body">
                        Semua data transaksi, kategori, dan pengaturan disimpan dalam database aplikasi Anda. Kami tidak membagikan data dengan pihak ketiga tanpa persetujuan.
                    </p>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.privacy.settings.analytics.title')">
                <M3Card variant="outlined" padding="lg" class="m3-privacy__card">
                    <div class="m3-privacy__header">
                        <span class="m3-privacy__ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </span>
                        <p class="m3-privacy__title">{{ t('settings.privacy.settings.analytics.title') }}</p>
                    </div>
                    <p class="m3-privacy__body">
                        Kebijakan privasi organisasi berlaku untuk semua pengguna aplikasi ini. Semua log dan data audit disimpan secara internal untuk keamanan dan compliance.
                    </p>
                </M3Card>
            </M3SettingsSection>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-privacy__card {
}

.m3-privacy__header {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    margin-bottom: var(--m3-space-3);
}

.m3-privacy__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-privacy__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-privacy__title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-privacy__body {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
