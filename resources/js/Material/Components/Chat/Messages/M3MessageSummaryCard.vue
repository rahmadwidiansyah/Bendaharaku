<script setup>
/**
 * M3MessageSummaryCard — ringkasan multi-transaksi / evidence (paritas Default).
 * Status: success → income-container, failed → expense-container,
 * partial → tertiary-container (Default memakai warna debt — tidak tepat).
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'

const { t } = useI18n()

const props = defineProps({
    component: { type: Object, required: true },
    metadata:  { type: Object, default: () => ({}) },
})

const isEvidence = computed(() => !!props.metadata?.evidence_uuid)

const allSuccess = computed(() => props.component.all_success)
const allFailed = computed(() => props.component.all_failed)

const statusClass = computed(() => {
    if (allSuccess.value) return 'm3-summary__status--success'
    if (allFailed.value) return 'm3-summary__status--failed'
    return 'm3-summary__status--partial'
})

const strippedLabel = computed(() => {
    const raw = props.component.label ?? ''
    return String(raw).replace(/^[✅❌]\s*/u, '').replace(/\*\*/g, '').replace(/\*/g, '').trim()
})

const stats = computed(() => [
    { key: 'total', label: t('common.total'), value: props.component.total },
    { key: 'success', label: t('common.success'), value: props.component.success },
    { key: 'failed', label: t('common.error'), value: props.component.failed },
])
</script>

<template>
    <div class="m3-summary">
        <div class="m3-summary__header">
            <div class="m3-summary__title-group">
                <AppIcon v-if="isEvidence" icon="file-text" iconClass="m3-summary__icon" />
                <span v-else class="m3-summary__dot" :class="statusClass" />
                <span class="m3-summary__title">
                    {{ isEvidence ? t('chat.evidence.summaryTitle', { count: component.total }) : t('chat.multi.result') }}
                </span>
            </div>
            <span class="m3-summary__status" :class="statusClass">
                {{ allSuccess ? t('common.success') : allFailed ? t('common.error') : t('common.partial') }}
            </span>
        </div>

        <div class="m3-summary__stats">
            <div v-for="stat in stats" :key="stat.key" class="m3-summary__stat">
                <span class="m3-summary__stat-value" :class="`m3-summary__stat-value--${stat.key}`">{{ stat.value }}</span>
                <span class="m3-summary__stat-label">{{ stat.label }}</span>
            </div>
        </div>

        <p v-if="strippedLabel" class="m3-summary__note">{{ strippedLabel }}</p>
    </div>
</template>

<style scoped>
.m3-summary {
    margin: var(--m3-space-1-5) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-lowest);
    overflow: hidden;
}

.m3-summary__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2-5) var(--m3-space-3);
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-summary__title-group {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-summary__icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-summary__dot {
    width: 0.375rem;
    height: 0.375rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-summary__title {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.m3-summary__status {
    flex-shrink: 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
}

.m3-summary__dot.m3-summary__status--success,
.m3-summary__status.m3-summary__status--success {
    color: var(--m3-color-on-income-container);
    background: var(--m3-color-income-container);
}

.m3-summary__dot.m3-summary__status--success {
    background: var(--m3-color-income);
}

.m3-summary__dot.m3-summary__status--failed,
.m3-summary__status.m3-summary__status--failed {
    color: var(--m3-color-on-expense-container);
    background: var(--m3-color-expense-container);
}

.m3-summary__dot.m3-summary__status--failed {
    background: var(--m3-color-expense);
}

.m3-summary__dot.m3-summary__status--partial,
.m3-summary__status.m3-summary__status--partial {
    color: var(--m3-color-on-tertiary-container);
    background: var(--m3-color-tertiary-container);
}

.m3-summary__dot.m3-summary__status--partial {
    background: var(--m3-color-tertiary);
}

.m3-summary__stats {
    display: flex;
    align-items: stretch;
}

.m3-summary__stat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--m3-space-3) var(--m3-space-1);
}

.m3-summary__stat + .m3-summary__stat {
    border-left: 1px solid var(--m3-color-outline-variant);
}

.m3-summary__stat-value {
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
}

.m3-summary__stat-value--success { color: var(--m3-color-income); }
.m3-summary__stat-value--failed { color: var(--m3-color-expense); }

.m3-summary__stat-label {
    margin-top: var(--m3-space-1);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-summary__note {
    padding: var(--m3-space-1) var(--m3-space-3) var(--m3-space-2-5);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
