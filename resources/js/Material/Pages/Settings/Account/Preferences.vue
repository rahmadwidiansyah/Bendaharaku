<script setup>
/**
 * Material/Pages/Settings/Account/Preferences.vue — preferensi akun (tema M3).
 *
 * Paritas perilaku halaman Default: PATCH timezone/date_format/language dan
 * sinkronisasi locale i18n + localStorage.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import M3SelectSheet from '@/Material/Components/Settings/M3SelectSheet.vue'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    userTimezone: { type: String, default: 'Asia/Jakarta' },
    userDateFormat: { type: String, default: 'DD/MM/YYYY' },
    userLanguage: { type: String, default: 'id' },
})

const { t, locale } = useI18n()
const { showToast } = useToast()

const timezone = ref(props.userTimezone)
const dateFormat = ref(props.userDateFormat)
const language = ref(props.userLanguage)
const saving = ref(false)
const picker = ref('')

const languageOptions = computed(() => [
    { value: 'id', label: t('settings.account.preferences.language.id') },
    { value: 'en', label: t('settings.account.preferences.language.en') },
])

const timezoneOptions = [
    { value: 'UTC', label: 'UTC' },
    { value: 'Asia/Jakarta', label: 'Asia/Jakarta' },
    { value: 'Asia/Bangkok', label: 'Asia/Bangkok' },
    { value: 'Asia/Ho_Chi_Minh', label: 'Asia/Ho_Chi_Minh' },
    { value: 'Asia/Singapore', label: 'Asia/Singapore' },
]

const dateFormatOptions = computed(() => [
    { value: 'DD/MM/YYYY', label: t('settings.account.preferences.date_format.ddmmyyyy') },
    { value: 'MM/DD/YYYY', label: t('settings.account.preferences.date_format.mmddyyyy') },
    { value: 'YYYY-MM-DD', label: t('settings.account.preferences.date_format.yyyymmdd') },
])

const pickerTitle = computed(() => {
    if (picker.value === 'language') return t('settings.account.preferences.language.title')
    if (picker.value === 'timezone') return t('settings.account.preferences.timezone.title')
    return t('settings.account.preferences.date_format.title')
})

const pickerValue = computed(() => {
    if (picker.value === 'language') return language.value
    if (picker.value === 'timezone') return timezone.value
    return dateFormat.value
})

const pickerOptions = computed(() => {
    if (picker.value === 'language') return languageOptions.value
    if (picker.value === 'timezone') return timezoneOptions
    return dateFormatOptions.value
})

const languageLabel = computed(() => languageOptions.value.find((o) => o.value === language.value)?.label ?? language.value)
const dateFormatLabel = computed(() => dateFormatOptions.value.find((o) => o.value === dateFormat.value)?.label ?? dateFormat.value)

function onPick(value) {
    if (picker.value === 'language') language.value = value
    else if (picker.value === 'timezone') timezone.value = value
    else dateFormat.value = value
}

async function handleSave() {
    saving.value = true
    try {
        await axios.patch(route('settings.account.preferences.update'), {
            timezone: timezone.value,
            date_format: dateFormat.value,
            language: language.value,
        })

        if (language.value !== locale.value) {
            locale.value = language.value
            localStorage.setItem('locale', language.value)
        }

        showToast(t('toast.updated'), 'success')
    } catch (error) {
        showToast(error.response?.data?.message || t('errors.generic'), 'error')
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <M3Layout :title="t('settings.account.preferences.title')">
        <Head :title="t('settings.account.preferences.title')" />

        <M3SettingsPage
            :title="t('settings.account.preferences.title')"
            :subtitle="t('settings.account.preferences.description')"
            back-route="settings.index"
            shell
        >
            <!-- Seksi tunggal: judul == judul halaman, tak perlu diulang -->
            <M3SettingsSection>
                <M3Card variant="outlined" padding="none" class="m3-preferences__card">
                    <M3ListItem clickable divider @click="picker = 'language'">
                        <template #icon>
                            <span class="m3-preferences__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.account.preferences.language.title') }}
                        <template #supporting>{{ t('settings.account.preferences.language.description') }}</template>
                        <template #trailing>
                            <span class="m3-preferences__value">{{ languageLabel }}</span>
                        </template>
                    </M3ListItem>

                    <M3ListItem clickable divider @click="picker = 'timezone'">
                        <template #icon>
                            <span class="m3-preferences__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0a9 9 0 004.5-9A9 9 0 0012 3m0 18a9 9 0 01-4.5-9A9 9 0 0112 3m-9 9h18" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.account.preferences.timezone.title') }}
                        <template #supporting>{{ t('settings.account.preferences.timezone.description') }}</template>
                        <template #trailing>
                            <span class="m3-preferences__value">{{ timezone }}</span>
                        </template>
                    </M3ListItem>

                    <M3ListItem clickable @click="picker = 'date_format'">
                        <template #icon>
                            <span class="m3-preferences__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.account.preferences.date_format.title') }}
                        <template #supporting>{{ t('settings.account.preferences.date_format.description') }}</template>
                        <template #trailing>
                            <span class="m3-preferences__value">{{ dateFormatLabel }}</span>
                        </template>
                    </M3ListItem>
                </M3Card>
            </M3SettingsSection>

            <div class="m3-preferences__actions">
                <M3Button :disabled="saving" @click="handleSave">
                    {{ saving ? t('common.saving') : t('common.save') }}
                </M3Button>
            </div>
        </M3SettingsPage>

        <M3SelectSheet
            :show="picker !== ''"
            :title="pickerTitle"
            :model-value="pickerValue"
            :options="pickerOptions"
            @update:model-value="onPick"
            @close="picker = ''"
        />
    </M3Layout>
</template>

<style scoped>
.m3-preferences__card {
    overflow: hidden;
}

.m3-preferences__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-preferences__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-preferences__value {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
}

.m3-preferences__actions {
    display: flex;
}
</style>
