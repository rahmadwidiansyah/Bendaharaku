<script setup>
/**
 * Material/Pages/Settings/System/About.vue — tentang aplikasi (tema M3).
 *
 * Paritas Pages/Settings/System/About.vue (Default): versi, lisensi, dan
 * kredit teknologi dengan konten yang sama.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

defineProps({
    appVersion: { type: String, default: '1.0.0' },
})

const { t } = useI18n()

const credits = [
    'settings.system.about.credits.laravel',
    'settings.system.about.credits.vue',
    'settings.system.about.credits.inertia',
    'settings.system.about.credits.tailwind',
]
</script>

<template>
    <M3Layout :title="t('settings.system.about.title')">
        <Head :title="t('settings.system.about.title')" />

        <M3SettingsPage
            :title="t('settings.system.about.title')"
            :subtitle="t('settings.system.about.description')"
            back-route="settings.index"
        >
            <M3SettingsSection :title="t('settings.system.about.app_name')">
                <M3Card variant="outlined" padding="lg" class="m3-about__card">
                    <p class="m3-about__desc">{{ t('settings.system.about.app_description') }}</p>
                    <dl class="m3-about__row">
                        <dt>{{ t('settings.system.about.version') }}</dt>
                        <dd>{{ appVersion }}</dd>
                    </dl>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.system.about.license.title')">
                <M3Card variant="outlined" padding="lg" class="m3-about__card">
                    <p class="m3-about__desc">{{ t('settings.system.about.license.description') }}</p>
                    <p class="m3-about__value">{{ t('settings.system.about.license.type') }}</p>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.system.about.credits.title')">
                <M3Card variant="outlined" padding="lg" class="m3-about__card">
                    <p class="m3-about__desc">{{ t('settings.system.about.credits.description') }}</p>
                    <ul class="m3-about__credits">
                        <li v-for="key in credits" :key="key" class="m3-about__credit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>{{ t(key) }}</span>
                        </li>
                    </ul>
                </M3Card>
            </M3SettingsSection>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-about__card {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-about__desc {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-about__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    margin: 0;
    padding-top: var(--m3-space-3);
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-about__row dt {
    font-size: var(--m3-type-body-medium-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-about__row dd {
    margin: 0;
    font-size: var(--m3-type-body-large-size);
    font-weight: var(--m3-type-emphasized-body-large-weight);
    color: var(--m3-color-on-surface);
    font-variant-numeric: tabular-nums;
}

.m3-about__value {
    margin: 0;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    color: var(--m3-color-on-surface);
}

.m3-about__credits {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    margin: 0;
    padding: 0;
    list-style: none;
}

.m3-about__credit {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-2);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-about__credit svg {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
    margin-top: var(--m3-space-0-5);
    color: var(--m3-color-primary);
}
</style>
