<script setup>
/**
 * Material/Pages/Settings/Finance/Budget.vue — anggaran otomatis (tema M3).
 *
 * Paritas perilaku halaman Default: GET/POST /api/v1/budget/settings,
 * toggle auto_budget_enabled dengan rollback saat gagal.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Switch from '@/Material/Components/M3Switch.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const { t } = useI18n()
const { showToast } = useToast()

const autoBudgetEnabled = ref(false)
const isLoading = ref(true)
const isSaving = ref(false)

async function loadSettings() {
    try {
        const response = await axios.get('/api/v1/budget/settings')
        autoBudgetEnabled.value = Boolean(response.data.auto_budget_enabled)
    } catch {
        showToast(t('settings.finance.budget.save_error'), 'error')
    } finally {
        isLoading.value = false
    }
}

async function saveAutoBudget(value) {
    isSaving.value = true
    const previous = autoBudgetEnabled.value
    autoBudgetEnabled.value = value
    try {
        await axios.post('/api/v1/budget/settings', { auto_budget_enabled: value })
        showToast(t('settings.finance.budget.save_success'), 'success')
    } catch {
        autoBudgetEnabled.value = previous
        showToast(t('settings.finance.budget.save_error'), 'error')
    } finally {
        isSaving.value = false
    }
}

onMounted(loadSettings)
</script>

<template>
    <M3Layout :title="t('settings.finance.budget.title')">
        <Head :title="t('settings.finance.budget.title')" />

        <M3SettingsPage
            :title="t('settings.finance.budget.title')"
            :subtitle="t('settings.finance.budget.description')"
            back-route="settings.index"
        >
            <M3SettingsSection :title="t('settings.finance.budget.auto_title')">
                <M3Card variant="outlined" padding="none" class="m3-finance__card">
                    <M3ListItem>
                        <template #icon>
                            <span class="m3-finance__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.finance.budget.auto_title') }}
                        <template #supporting>
                            {{ isLoading ? t('common.loading') : (autoBudgetEnabled ? t('common.active') : t('common.inactive')) }}
                        </template>
                        <template #trailing>
                            <M3Switch
                                :model-value="autoBudgetEnabled"
                                :disabled="isLoading || isSaving"
                                :aria-label="t('settings.finance.budget.auto_title')"
                                @update:model-value="saveAutoBudget"
                            />
                        </template>
                    </M3ListItem>
                </M3Card>
                <p class="m3-finance__hint">{{ t('settings.finance.budget.auto_description') }}</p>
            </M3SettingsSection>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-finance__card {
    overflow: hidden;
}

.m3-finance__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-finance__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-finance__hint {
    margin: 0;
    padding: 0 var(--m3-space-4);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
