<script setup>
/**
 * M3AiUsageCard — pemakaian AI hari ini (data nyata dari ai_parse_logs,
 * dikirim DashboardController sebagai props aiUsage):
 * null | { requests, tokens, successRate }. Kartu disembunyikan bila
 * user belum memakai AI hari ini.
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'
import M3Card from '@/Material/Components/M3Card.vue'

const props = defineProps({
    aiUsage: { type: Object, default: null },
})

const { t } = useI18n()
const page = usePage()

const botName = computed(() => page.props.auth?.user?.bot_name || 'Ken-Chan')

const fmt = (n) => {
    const v = Number(n) || 0
    if (v >= 1e6) return (v / 1e6).toFixed(1) + 'jt'
    if (v >= 1e3) return (v / 1e3).toFixed(1) + 'rb'
    return String(v)
}

const tokensLabel = computed(() => {
    const v = Number(props.aiUsage?.tokens) || 0
    return v > 0 ? t('dashboard.widgets.aiTokens', { tokens: fmt(v) }) : ''
})
</script>

<template>
    <M3Card v-if="aiUsage" variant="outlined" padding="md" class="m3-ai-usage">
        <div class="m3-ai-usage__row">
            <span class="m3-ai-usage__ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                </svg>
            </span>
            <div class="m3-ai-usage__body">
                <p class="m3-ai-usage__title">
                    {{ t('dashboard.widgets.aiTitle', { bot: botName }) }}
                </p>
                <p class="m3-ai-usage__stats">
                    {{ t('dashboard.widgets.aiRequests', { count: aiUsage.requests }) }}
                    <template v-if="tokensLabel"> · {{ tokensLabel }}</template>
                    · {{ aiUsage.successRate }}%
                </p>
            </div>
        </div>
    </M3Card>
</template>

<style scoped>
.m3-ai-usage__row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-ai-usage__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-tertiary-container);
    color: var(--m3-color-on-tertiary-container);
}

.m3-ai-usage__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-ai-usage__body {
    min-width: 0;
}

.m3-ai-usage__title {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight, 600);
    color: var(--m3-color-on-surface);
}

.m3-ai-usage__stats {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}
</style>
