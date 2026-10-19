<script setup>
/**
 * Material/Pages/Settings/AI/Status.vue — status provider (tema M3).
 *
 * Read-only: kesehatan akun provider, metode routing, kandidat berikutnya,
 * dan entri yang dilewati. Data disiapkan server-side oleh
 * AiProviderStatusController (tidak ada fetch tambahan).
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    method: { type: String, default: 'unknown' },
    candidates: { type: Array, default: () => [] },
    skipped: { type: Array, default: () => [] },
})

const { t } = useI18n()

const methodLabel = computed(() => {
    const key = `settings.ai.status.method.${props.method}`
    const label = t(key)
    return label === key ? props.method : label
})

const healthLabel = (status) => {
    const key = `settings.ai.status.health.${status}`
    const label = t(key)
    return label === key ? status : label
}

const healthKind = (status) => {
    if (status === 'healthy') return 'ok'
    if (status === 'invalid' || status === 'disabled') return 'bad'
    if (status === 'rate_limited') return 'warn'
    return 'neutral'
}
</script>

<template>
    <M3Layout :title="$t('settings.ai.status.title')">
        <Head :title="$t('settings.ai.status.title')" />

        <div class="m3-status">
            <M3PageTitle
                :title="$t('settings.ai.status.title')"
                :subtitle="$t('settings.ai.status.description')"
            />

            <section class="m3-status__section">
                <h2 class="m3-status__section-title">{{ $t('settings.ai.status.method.label') }}</h2>
                <M3Card variant="outlined">
                    <div class="m3-status__method">
                        <span class="m3-status__badge m3-status__badge--primary">{{ methodLabel }}</span>
                        <span class="m3-status__hint">
                            {{ $t('settings.ai.status.candidates.count', { count: candidates.length }) }}
                            · {{ $t('settings.ai.status.skipped.count', { count: skipped.length }) }}
                        </span>
                    </div>
                </M3Card>
            </section>

            <section class="m3-status__section">
                <h2 class="m3-status__section-title">{{ $t('settings.ai.status.accounts.title') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-status__card">
                    <M3ListItem v-for="(acc, i) in accounts" :key="acc.id + '-' + i" :divider="i < accounts.length - 1" dense>
                        <template #icon>
                            <span class="m3-status__dot" :class="`m3-status__dot--${healthKind(acc.health_status)}`" aria-hidden="true"></span>
                        </template>
                        {{ acc.alias || acc.provider }}
                        <template #supporting>
                            {{ acc.provider }} ·
                            {{ $t('settings.ai.status.accounts.priority') }} {{ acc.priority }} ·
                            {{ acc.model_count }} {{ $t('settings.ai.status.accounts.models') }}
                            <template v-if="acc.last_test_status"> · {{ acc.last_test_status }}</template>
                            <template v-else> · {{ $t('settings.ai.status.accounts.no_test') }}</template>
                        </template>
                        <template #trailing>
                            <span class="m3-status__badge" :class="`m3-status__badge--${healthKind(acc.health_status)}`">
                                {{ healthLabel(acc.health_status) }}
                            </span>
                            <span v-if="acc.in_cooldown" class="m3-status__badge m3-status__badge--neutral">
                                {{ $t('settings.ai.status.health.cooldown') }}
                            </span>
                        </template>
                    </M3ListItem>

                    <p v-if="!accounts.length" class="m3-status__empty">
                        {{ $t('settings.ai.status.accounts.empty') }}
                    </p>
                </M3Card>
            </section>

            <section class="m3-status__section">
                <h2 class="m3-status__section-title">{{ $t('settings.ai.status.candidates.title') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-status__card">
                    <M3ListItem v-for="(c, i) in candidates" :key="c.provider + '-' + c.model + '-' + i" :divider="i < candidates.length - 1" dense>
                        <template #icon>
                            <span class="m3-status__rank" aria-hidden="true">{{ i + 1 }}</span>
                        </template>
                        {{ c.model }}
                        <template #supporting>
                            {{ c.provider }}<template v-if="c.account_alias"> · {{ c.account_alias }}</template>
                        </template>
                    </M3ListItem>

                    <p v-if="!candidates.length" class="m3-status__empty">
                        {{ $t('settings.ai.status.candidates.empty') }}
                    </p>
                </M3Card>
            </section>

            <section v-if="skipped.length" class="m3-status__section">
                <h2 class="m3-status__section-title">{{ $t('settings.ai.status.skipped.title') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-status__card">
                    <M3ListItem v-for="(s, i) in skipped" :key="(s.provider || '') + '-' + (s.model || '') + '-' + i" :divider="i < skipped.length - 1" dense>
                        {{ s.model || '*' }}
                        <template #supporting>
                            {{ s.provider }} · {{ s.reason }}
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-status {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    width: 100%;
    max-width: var(--m3-size-list-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-status__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-status__section-title {
    margin: 0;
    padding-left: var(--m3-space-4);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-status__card {
    overflow: hidden;
}

.m3-status__method {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    flex-wrap: wrap;
}

.m3-status__hint {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-status__badge {
    display: inline-flex;
    align-items: center;
    min-height: 1.5rem;
    padding: 0 var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    border: 1px solid var(--m3-color-outline);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
}

.m3-status__badge--primary {
    background: var(--m3-color-primary-container);
    border-color: transparent;
    color: var(--m3-color-on-primary-container);
}

.m3-status__badge--ok {
    background: var(--m3-color-primary-container);
    border-color: transparent;
    color: var(--m3-color-on-primary-container);
}

.m3-status__badge--bad {
    background: var(--m3-color-error-container);
    border-color: transparent;
    color: var(--m3-color-on-error-container);
}

.m3-status__badge--warn {
    background: var(--m3-color-tertiary-container);
    border-color: transparent;
    color: var(--m3-color-on-tertiary-container);
}

.m3-status__badge--neutral {
    background: var(--m3-color-surface-container-highest);
    border-color: transparent;
    color: var(--m3-color-on-surface-variant);
}

.m3-status__dot {
    display: inline-block;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-outline);
}

.m3-status__dot--ok {
    background: var(--m3-color-primary);
}

.m3-status__dot--bad {
    background: var(--m3-color-error);
}

.m3-status__dot--warn {
    background: var(--m3-color-tertiary);
}

.m3-status__rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-label-large-weight);
}

.m3-status__empty {
    margin: 0;
    padding: var(--m3-space-4);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
