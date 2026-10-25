<script setup>
/**
 * Material/Pages/Settings/Finance/Defaults.vue — logika transaksi (tema M3).
 *
 * Paritas perilaku halaman Default: toggle `allow_negative_balance` langsung
 * disimpan, dengan revert bila request gagal.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Switch from '@/Material/Components/M3Switch.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const { t } = useI18n()
const page = usePage()
const { showToast } = useToast()

const allowNegativeBalance = ref(Boolean(page.props.auth?.user?.allow_negative_balance))
const saving = ref(false)

async function handleToggle() {
    saving.value = true
    try {
        await axios.patch(route('settings.finance.logic.update'), {
            allow_negative_balance: allowNegativeBalance.value,
        })
        if (page.props.auth?.user) {
            page.props.auth.user.allow_negative_balance = allowNegativeBalance.value
        }
        showToast(t('toast.updated'), 'success')
    } catch (error) {
        allowNegativeBalance.value = !allowNegativeBalance.value
        showToast(error.response?.data?.message || t('errors.generic'), 'error')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <M3Layout :title="t('settings.finance.defaults.transaction_logic.title')">
        <Head :title="t('settings.finance.defaults.transaction_logic.title')" />

        <M3SettingsPage
            :title="t('settings.finance.defaults.transaction_logic.title')"
            :subtitle="t('settings.finance.defaults.transaction_logic.description')"
            back-route="settings.index"
        >
            <!-- Satu-satunya seksi: judul seksi == judul halaman, cukup sekali -->
            <M3SettingsSection>
                <M3Card variant="outlined" padding="none" class="m3-finance__card">
                    <M3ListItem>
                        <template #icon>
                            <span class="m3-finance__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.finance.defaults.transaction_logic.label') }}
                        <template #supporting>
                            {{ allowNegativeBalance ? t('settings.finance.defaults.transaction_logic.on') : t('settings.finance.defaults.transaction_logic.off') }}
                        </template>
                        <template #trailing>
                            <M3Switch
                                v-model="allowNegativeBalance"
                                :disabled="saving"
                                :aria-label="t('settings.finance.defaults.transaction_logic.label')"
                                @update:model-value="handleToggle"
                            />
                        </template>
                    </M3ListItem>
                </M3Card>
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
</style>
