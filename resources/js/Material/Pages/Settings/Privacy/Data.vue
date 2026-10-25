<script setup>
/**
 * Material/Pages/Settings/Privacy/Data.vue — manajemen data (tema M3).
 *
 * Paritas perilaku halaman Default: export akun sebagai blob JSON; import dan
 * backup tetap belum tersedia (disabled).
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { formatLocalYMD } from '@/utils/format.js'
import { useToast } from '@/Composables/useToast'

const { t } = useI18n()
const { showToast } = useToast()
const isExporting = ref(false)

async function handleExport() {
    isExporting.value = true
    try {
        const response = await axios.post('/api/account/export', {}, { responseType: 'blob' })
        const blob = new Blob([response.data], { type: 'application/json' })
        const link = document.createElement('a')
        link.href = window.URL.createObjectURL(blob)
        link.download = `bendaharaku-export-${formatLocalYMD()}.json`
        document.body.appendChild(link)
        link.click()
        link.remove()
        showToast(t('settings.privacy.data.export.success'), 'success')
    } catch (error) {
        showToast(error?.response?.data?.message || t('errors.generic'), 'error')
    } finally {
        isExporting.value = false
    }
}
</script>

<template>
    <M3Layout :title="t('settings.privacy.data.title')">
        <Head :title="t('settings.privacy.data.title')" />

        <M3SettingsPage
            :title="t('settings.privacy.data.title')"
            :subtitle="t('settings.privacy.data.description')"
            back-route="settings.index"
        >
            <M3SettingsSection :title="t('settings.privacy.data.export.title')">
                <M3Card variant="outlined" padding="lg" class="m3-data__card">
                    <div class="m3-data__row">
                        <div class="m3-data__text">
                            <p class="m3-data__title">{{ t('settings.privacy.data.export.title') }}</p>
                            <p class="m3-data__desc">{{ t('settings.privacy.data.export.description') }}</p>
                        </div>
                        <M3Button :disabled="isExporting" @click="handleExport">
                            <template #leading>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </template>
                            {{ isExporting ? t('common.processing') : t('settings.privacy.data.export.button') }}
                        </M3Button>
                    </div>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.privacy.data.import.title')">
                <M3Card variant="outlined" padding="lg" class="m3-data__card">
                    <div class="m3-data__row">
                        <div class="m3-data__text">
                            <p class="m3-data__title">{{ t('settings.privacy.data.import.title') }}</p>
                            <p class="m3-data__desc">{{ t('settings.privacy.data.import.description') }}</p>
                        </div>
                        <M3Button variant="outlined" disabled>
                            <template #leading>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 8.25L12 3.75m0 0l4.5 4.5M12 3.75v12" />
                                </svg>
                            </template>
                            {{ t('settings.privacy.data.import.button') }}
                        </M3Button>
                    </div>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.privacy.data.backup.title')">
                <M3Card variant="outlined" padding="lg" class="m3-data__card">
                    <div class="m3-data__row">
                        <div class="m3-data__text">
                            <p class="m3-data__title">{{ t('settings.privacy.data.backup.title') }}</p>
                            <p class="m3-data__desc">{{ t('settings.privacy.data.backup.description') }}</p>
                        </div>
                        <M3Button variant="outlined" disabled>
                            {{ t('settings.privacy.data.backup.button') }}
                        </M3Button>
                    </div>
                </M3Card>
            </M3SettingsSection>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-data__card {
}

.m3-data__row {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-data__text {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-data__title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-data__desc {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

@media (min-width: 600px) {
    .m3-data__row {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
</style>
