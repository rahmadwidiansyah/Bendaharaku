<script setup>
/**
 * Material/Pages/Settings/AI/Integration.vue — integrasi eksternal (tema M3).
 *
 * Paritas Pages/Settings/AI/Integration.vue (Default): status Telegram dari
 * `auth.user.telegram_id` + tautan ke bot. Tanpa data tambahan.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const page = usePage()

const telegramId = computed(() => page.props.auth?.user?.telegram_id || '')
const isTelegramActive = computed(() => !!telegramId.value)
</script>

<template>
    <M3Layout :title="t('settings.ai.integration.title')">
        <Head :title="t('settings.ai.integration.title')" />

        <M3SettingsPage
            :title="t('settings.ai.integration.title')"
            :subtitle="t('settings.ai.integration.description')"
            back-route="settings.index"
        >
            <M3Card variant="outlined" padding="lg" class="m3-integration">
                <div class="m3-integration__head">
                    <span class="m3-integration__ico">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z" />
                        </svg>
                    </span>
                    <div class="m3-integration__head-body">
                        <h2 class="m3-integration__title">{{ t('settings.ai.integration.telegram.title') }}</h2>
                        <p class="m3-integration__desc">{{ t('settings.ai.integration.telegram.description') }}</p>
                    </div>
                </div>

                <div
                    :class="['m3-integration__status', isTelegramActive ? 'm3-integration__status--active' : '']"
                    role="status"
                >
                    <span class="m3-integration__dot" aria-hidden="true" />
                    <div class="m3-integration__status-body">
                        <p class="m3-integration__status-label">
                            {{ isTelegramActive ? t('common.active') : t('common.inactive') }}
                        </p>
                        <p v-if="isTelegramActive" class="m3-integration__status-id">ID: {{ telegramId }}</p>
                    </div>
                </div>

                <a
                    class="m3-integration__cta"
                    href="https://t.me/catatwidi_bot"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z" />
                    </svg>
                    {{ t('common.open') }} Bot
                </a>
            </M3Card>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-integration {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-integration__head {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-3);
}

.m3-integration__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-integration__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-integration__head-body {
    min-width: 0;
}

.m3-integration__title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-integration__desc {
    margin: var(--m3-space-1) 0 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-integration__status {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-3) var(--m3-space-4);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
}

.m3-integration__status--active {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    border-color: transparent;
}

.m3-integration__dot {
    width: var(--m3-space-2);
    height: var(--m3-space-2);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-outline);
}

.m3-integration__status--active .m3-integration__dot {
    background: var(--m3-color-primary);
}

.m3-integration__status-body {
    min-width: 0;
}

.m3-integration__status-label {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: inherit;
}

.m3-integration__status-id {
    margin: var(--m3-space-0-5) 0 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: inherit;
    opacity: 0.8;
    font-variant-numeric: tabular-nums;
}

.m3-integration__cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    align-self: flex-start;
    min-height: var(--m3-size-touch-target);
    padding: 0 var(--m3-space-5);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    text-decoration: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-integration__cta:hover {
    opacity: 0.9;
}

.m3-integration__cta:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-integration__cta svg {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}
</style>
