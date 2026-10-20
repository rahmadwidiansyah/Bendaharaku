<script setup>
/**
 * Material/Pages/Settings/AI/MemoryDetail.vue — detail memori AI (tema M3).
 *
 * Paritas Settings/AI/MemoryDetail (Default): kartu ringkasan, bobot efektif,
 * grid informasi, kontribusi, kronologi + metadata, dan hapus memori dengan
 * konfirmasi (DELETE settings.ai.memory.destroy).
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    memory: { type: Object, required: true },
    logs: { type: Array, default: () => [] },
})

const { t } = useI18n()
const { showToast } = useToast()

const showDelete = ref(false)
const isDeleting = ref(false)
const openMeta = ref(null)

function confirmDelete() {
    if (!props.memory?.id || isDeleting.value) return
    isDeleting.value = true
    router.delete(route('settings.ai.memory.destroy', { id: props.memory.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showToast(t('settings.ai.memory.detail.deleted'), 'success')
            showDelete.value = false
        },
        onError: () => showToast(t('toast.error'), 'error'),
        onFinish: () => {
            isDeleting.value = false
        },
    })
}

function toggleMeta(id) {
    openMeta.value = openMeta.value === id ? null : id
}

function weightClass(weight) {
    if (weight < 1) return 'm3-memdetail__weight--low'
    if (weight < 3) return 'm3-memdetail__weight--medium'
    return 'm3-memdetail__weight--high'
}

function actionClass(action) {
    if (action === 'CREATED') return 'm3-memdetail__action--created'
    if (action === 'REWARDED') return 'm3-memdetail__action--rewarded'
    if (['REBUILT', 'MERGE'].includes(action)) return 'm3-memdetail__action--rebuilt'
    if (['PRUNED', 'DELETED'].includes(action)) return 'm3-memdetail__action--removed'
    return 'm3-memdetail__action--neutral'
}

function formatWeight(value) {
    return Number(value ?? 0).toFixed(2)
}

const targetLabel = () => props.memory.target_type ?? 'category'
</script>

<template>
    <M3Layout :title="t('settings.ai.memory.detail.title')">
        <Head :title="t('settings.ai.memory.detail.title')" />

        <M3SettingsPage
            :title="t('settings.ai.memory.detail.title')"
            :subtitle="memory.keyword"
            back-route="settings.ai.memory.manage"
            :back-label="t('settings.ai.memory.detail.back')"
        >
            <M3Card variant="outlined" padding="lg" class="m3-memdetail__hero">
                <div class="m3-memdetail__badges">
                    <span class="m3-memdetail__badge">
                        {{ targetLabel() }}
                    </span>
                    <span class="m3-memdetail__badge">{{ memory.algorithm_version }}</span>
                    <span :class="['m3-memdetail__weight', weightClass(memory.weight)]">
                        w {{ formatWeight(memory.weight) }} · {{ memory.hit_count }}×
                    </span>
                </div>

                <h2 class="m3-memdetail__keyword">{{ memory.keyword }}</h2>
                <p v-if="memory.raw_subject && memory.raw_subject !== memory.keyword" class="m3-memdetail__raw">
                    {{ memory.raw_subject }}
                </p>
                <p
                    v-if="memory.normalized_subject && memory.normalized_subject !== memory.keyword"
                    class="m3-memdetail__normalized"
                >
                    → {{ memory.normalized_subject }}
                </p>

                <div class="m3-memdetail__meter">
                    <div class="m3-memdetail__meter-head">
                        <span>{{ t('settings.ai.memory.detail.effective_weight') }}</span>
                        <span class="m3-memdetail__meter-value">
                            {{ formatWeight(memory.effective_weight ?? memory.weight) }} / 10.00
                        </span>
                    </div>
                    <div
                        class="m3-memdetail__meter-track"
                        role="progressbar"
                        :aria-label="t('settings.ai.memory.detail.effective_weight')"
                        :aria-valuenow="Math.min(100, ((memory.effective_weight ?? memory.weight) / 10) * 100)"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <span
                            class="m3-memdetail__meter-fill"
                            :style="{ width: Math.min(100, ((memory.effective_weight ?? memory.weight) / 10) * 100) + '%' }"
                        />
                    </div>
                    <p v-if="memory.days_since !== null && memory.days_since !== undefined" class="m3-memdetail__days">
                        {{ t('settings.ai.memory.detail.days_since', { days: memory.days_since }) }}
                        · {{ memory.last_applied_at ?? memory.created_at }}
                    </p>
                </div>

                <div class="m3-memdetail__chips">
                    <span v-if="memory.category" class="m3-memdetail__chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                        </svg>
                        {{ memory.category }}
                    </span>
                    <span v-if="memory.wallet" class="m3-memdetail__chip">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                        </svg>
                        {{ memory.wallet }}
                    </span>
                    <span v-if="memory.contributions_count !== undefined" class="m3-memdetail__chip">
                        {{ memory.contributions_count }} {{ t('settings.ai.memory.detail.contributions_count') }}
                    </span>
                </div>

                <div class="m3-memdetail__hero-actions">
                    <M3Button variant="error" size="sm" @click="showDelete = true">
                        <template #leading>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </template>
                        {{ t('settings.ai.memory.detail.delete_button') }}
                    </M3Button>
                </div>
            </M3Card>

            <M3Card variant="outlined" padding="lg">
                <h3 class="m3-memdetail__section-title">{{ t('settings.ai.memory.detail.info') }}</h3>
                <dl class="m3-memdetail__grid">
                    <div class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.keyword') }}</dt>
                        <dd>{{ memory.keyword }}</dd>
                    </div>
                    <div v-if="memory.keyword_pattern && memory.keyword_pattern !== memory.keyword" class="m3-memdetail__cell">
                        <dt>Keyword Pattern</dt>
                        <dd class="m3-memdetail__mono">{{ memory.keyword_pattern }}</dd>
                    </div>
                    <div v-if="memory.memory_keyword && memory.memory_keyword !== memory.keyword" class="m3-memdetail__cell">
                        <dt>Memory Keyword</dt>
                        <dd class="m3-memdetail__mono">{{ memory.memory_keyword }}</dd>
                    </div>
                    <div v-if="memory.raw_subject" class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.raw_subject') }}</dt>
                        <dd>{{ memory.raw_subject }}</dd>
                    </div>
                    <div v-if="memory.normalized_subject" class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.normalized_subject') }}</dt>
                        <dd>{{ memory.normalized_subject }}</dd>
                    </div>
                    <div v-if="memory.category" class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.category') }}</dt>
                        <dd>{{ memory.category }}</dd>
                    </div>
                    <div v-if="memory.wallet" class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.wallet') }}</dt>
                        <dd>{{ memory.wallet }}</dd>
                    </div>
                    <div class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.current_weight') }}</dt>
                        <dd>
                            <span :class="['m3-memdetail__weight', weightClass(memory.weight)]">
                                {{ formatWeight(memory.weight) }}
                            </span>
                        </dd>
                    </div>
                    <div class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.effective_weight') }}</dt>
                        <dd class="m3-memdetail__num">{{ formatWeight(memory.effective_weight ?? memory.weight) }}</dd>
                    </div>
                    <div class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.hit_count') }}</dt>
                        <dd class="m3-memdetail__num">{{ memory.hit_count }}×</dd>
                    </div>
                    <div class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.created_at') }}</dt>
                        <dd>{{ memory.created_at }}</dd>
                    </div>
                    <div v-if="memory.last_applied_at" class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.last_used') }}</dt>
                        <dd>{{ memory.last_applied_at }}</dd>
                    </div>
                    <div class="m3-memdetail__cell">
                        <dt>{{ t('settings.ai.memory.detail.algorithm_version') }}</dt>
                        <dd class="m3-memdetail__mono">{{ memory.algorithm_version }}</dd>
                    </div>
                </dl>
            </M3Card>

            <M3Card v-if="memory.contributions && memory.contributions.length > 0" variant="outlined" padding="lg">
                <h3 class="m3-memdetail__section-title">
                    {{ t('settings.ai.memory.detail.contributions_title') }}
                    <span class="m3-memdetail__count">({{ memory.contributions.length }})</span>
                </h3>
                <div class="m3-memdetail__contributions">
                    <div
                        v-for="contribution in memory.contributions"
                        :key="contribution.id"
                        class="m3-memdetail__contribution"
                    >
                        <span class="m3-memdetail__contribution-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                            </svg>
                        </span>
                        <div class="m3-memdetail__contribution-body">
                            <div class="m3-memdetail__contribution-head">
                                <span class="m3-memdetail__mono">{{ contribution.keyword }}</span>
                                <span class="m3-memdetail__badge">{{ contribution.target_name }}</span>
                            </div>
                            <p class="m3-memdetail__contribution-meta">
                                <span v-if="contribution.transaction_id">#{{ contribution.transaction_id }}</span>
                                <span>{{ contribution.source }}</span>
                                <span>· {{ contribution.created_at_diff }}</span>
                            </p>
                        </div>
                        <span class="m3-memdetail__delta">+{{ Number(contribution.weight_delta).toFixed(1) }}</span>
                    </div>
                </div>
            </M3Card>

            <M3Card variant="outlined" padding="lg">
                <h3 class="m3-memdetail__section-title">{{ t('settings.ai.memory.detail.timeline') }}</h3>

                <ol v-if="logs && logs.length > 0" class="m3-memdetail__timeline">
                    <li v-for="log in logs" :key="log.id" class="m3-memdetail__event">
                        <span class="m3-memdetail__event-dot" aria-hidden="true" />
                        <div class="m3-memdetail__event-body">
                            <div class="m3-memdetail__event-head">
                                <span :class="['m3-memdetail__action', actionClass(log.action)]">
                                    {{ t(`settings.ai.memory.detail.action.${log.action}`) }}
                                </span>
                                <span class="m3-memdetail__event-time">{{ log.created_at_diff }}</span>
                                <span v-if="log.transaction_id" class="m3-memdetail__event-time">· #{{ log.transaction_id }}</span>
                            </div>

                            <p v-if="log.raw_subject" class="m3-memdetail__event-text">{{ log.raw_subject }}</p>

                            <p
                                v-if="(log.old_weight !== null && log.new_weight !== null) || (log.old_hit_count !== null && log.new_hit_count !== null)"
                                class="m3-memdetail__event-meta"
                            >
                                <span v-if="log.old_weight !== null && log.new_weight !== null">
                                    {{ t('settings.ai.memory.detail.weight_change', { old: Number(log.old_weight).toFixed(1), new: Number(log.new_weight ?? 0).toFixed(1) }) }}
                                </span>
                                <span v-if="log.old_hit_count !== null && log.new_hit_count !== null">
                                    {{ t('settings.ai.memory.detail.hit_change', { old: log.old_hit_count, new: log.new_hit_count }) }}
                                </span>
                            </p>

                            <p v-if="log.reason" class="m3-memdetail__event-reason">{{ log.reason }}</p>
                            <p v-if="log.source" class="m3-memdetail__event-source">{{ log.source }}</p>

                            <div v-if="log.metadata && Object.keys(log.metadata).length" class="m3-memdetail__meta">
                                <button type="button" class="m3-memdetail__meta-toggle" @click="toggleMeta(log.id)">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                        :class="openMeta === log.id ? 'm3-memdetail__meta-icon--open' : ''"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                    Metadata
                                </button>
                                <pre v-if="openMeta === log.id" class="m3-memdetail__meta-json">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                            </div>
                        </div>
                    </li>
                </ol>

                <p v-else class="m3-memdetail__timeline-empty">{{ t('settings.ai.memory.detail.timeline_empty') }}</p>
            </M3Card>

            <M3Card variant="outlined" padding="lg" class="m3-memdetail__danger">
                <h3 class="m3-memdetail__danger-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    {{ t('settings.ai.memory.detail.danger_title') }}
                </h3>
                <p class="m3-memdetail__danger-desc">{{ t('settings.ai.memory.detail.danger_desc') }}</p>
                <M3Button variant="error" class="m3-memdetail__danger-btn" @click="showDelete = true">
                    {{ t('settings.ai.memory.detail.delete_button') }}
                </M3Button>
            </M3Card>
        </M3SettingsPage>

        <M3Modal
            :show="showDelete"
            :title="t('settings.ai.memory.detail.delete_confirm_title')"
            max-width="sm"
            mobile-variant="sheet"
            :closeable="!isDeleting"
            @close="showDelete = false"
        >
            <p class="m3-memdetail__confirm-desc">
                {{ t('settings.ai.memory.detail.delete_confirm_desc', { keyword: memory.keyword, count: memory.contributions_count ?? 0 }) }}
            </p>
            <p class="m3-memdetail__confirm-warn">{{ t('settings.ai.memory.detail.delete_confirm_warn') }}</p>
            <template #footer>
                <M3Button variant="text" :disabled="isDeleting" @click="showDelete = false">
                    {{ t('common.cancel') }}
                </M3Button>
                <M3Button variant="error" :disabled="isDeleting" @click="confirmDelete">
                    {{ isDeleting ? t('common.deleting') : t('settings.ai.memory.detail.delete_confirm') }}
                </M3Button>
            </template>
        </M3Modal>
    </M3Layout>
</template>

<style scoped>
.m3-memdetail__hero {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-memdetail__badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-memdetail__badge {
    display: inline-flex;
    align-items: center;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__weight {
    display: inline-flex;
    align-items: center;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.m3-memdetail__weight--low {
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__weight--medium {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-memdetail__weight--high {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-memdetail__keyword {
    margin: 0;
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-memdetail__raw {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    overflow-wrap: anywhere;
}

.m3-memdetail__normalized {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    overflow-wrap: anywhere;
}

.m3-memdetail__meter {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-1);
}

.m3-memdetail__meter-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__meter-value {
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    font-variant-numeric: tabular-nums;
}

.m3-memdetail__meter-track {
    height: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    overflow: hidden;
}

.m3-memdetail__meter-fill {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--m3-color-primary);
    transition: width var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-memdetail__days {
    margin: var(--m3-space-1) 0 0;
    font-size: var(--m3-type-label-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1);
}

.m3-memdetail__chip {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-1) var(--m3-space-3);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
}

.m3-memdetail__chip svg {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-memdetail__hero-actions {
    display: flex;
    justify-content: flex-end;
}

.m3-memdetail__section-title {
    display: flex;
    align-items: baseline;
    gap: var(--m3-space-1);
    margin: 0 0 var(--m3-space-4);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-memdetail__count {
    font-weight: var(--m3-type-body-small-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-4);
    margin: 0;
}

.m3-memdetail__cell {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
}

.m3-memdetail__cell dt {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__cell dd {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-memdetail__mono {
    font-family: var(--m3-type-mono-font, ui-monospace, SFMono-Regular, Menlo, monospace);
    font-size: var(--m3-type-body-small-size);
}

.m3-memdetail__num {
    font-variant-numeric: tabular-nums;
}

.m3-memdetail__contributions {
    display: flex;
    flex-direction: column;
}

.m3-memdetail__contribution {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-3) 0;
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-memdetail__contribution:first-child {
    padding-top: 0;
    border-top: none;
}

.m3-memdetail__contribution:last-child {
    padding-bottom: 0;
}

.m3-memdetail__contribution-ico {
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

.m3-memdetail__contribution-ico svg {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-memdetail__contribution-body {
    flex: 1;
    min-width: 0;
}

.m3-memdetail__contribution-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-memdetail__contribution-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
    margin: var(--m3-space-1) 0 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__delta {
    flex-shrink: 0;
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    color: var(--m3-color-primary);
    font-variant-numeric: tabular-nums;
}

.m3-memdetail__timeline {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    margin: 0;
    padding: 0 0 0 var(--m3-space-6);
    list-style: none;
}

.m3-memdetail__timeline::before {
    content: '';
    position: absolute;
    top: var(--m3-space-2);
    bottom: var(--m3-space-2);
    left: calc(var(--m3-space-2) - 1px);
    width: 1px;
    background: var(--m3-color-outline-variant);
}

.m3-memdetail__event {
    position: relative;
}

.m3-memdetail__event-dot {
    position: absolute;
    top: var(--m3-space-1);
    left: calc(-1 * var(--m3-space-6) + var(--m3-space-1));
    width: var(--m3-space-2);
    height: var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    border: 2px solid var(--m3-color-primary);
    background: var(--m3-color-surface);
}

.m3-memdetail__event-body {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    min-width: 0;
}

.m3-memdetail__event-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-memdetail__event-time,
.m3-memdetail__event-text,
.m3-memdetail__event-meta,
.m3-memdetail__event-reason,
.m3-memdetail__event-source {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__event-text {
    margin: 0;
    overflow-wrap: anywhere;
}

.m3-memdetail__event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-3);
    margin: 0;
    font-variant-numeric: tabular-nums;
}

.m3-memdetail__event-reason {
    margin: 0;
    font-style: italic;
    overflow-wrap: anywhere;
}

.m3-memdetail__event-source {
    margin: 0;
    opacity: 0.75;
}

.m3-memdetail__action {
    display: inline-flex;
    align-items: center;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
}

.m3-memdetail__action--created {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-memdetail__action--rewarded {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-memdetail__action--rebuilt {
    background: var(--m3-color-tertiary-container);
    color: var(--m3-color-on-tertiary-container);
}

.m3-memdetail__action--removed {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-memdetail__action--neutral {
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__meta-toggle {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    padding: 0;
    border: none;
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    font-family: inherit;
    cursor: pointer;
}

.m3-memdetail__meta-toggle:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-memdetail__meta-toggle svg {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-memdetail__meta-icon--open {
    transform: rotate(90deg);
}

.m3-memdetail__meta-json {
    margin: var(--m3-space-2) 0 0;
    padding: var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-family: var(--m3-type-mono-font, ui-monospace, SFMono-Regular, Menlo, monospace);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.m3-memdetail__timeline-empty {
    margin: 0;
    padding: var(--m3-space-4) 0;
    text-align: center;
    font-size: var(--m3-type-body-medium-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__danger {
    border-color: var(--m3-color-error);
}

.m3-memdetail__danger-title {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-error);
}

.m3-memdetail__danger-title svg {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-memdetail__danger-desc {
    margin: var(--m3-space-2) 0 var(--m3-space-4);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-memdetail__danger-btn {
    width: 100%;
}

.m3-memdetail__confirm-desc {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-memdetail__confirm-warn {
    margin: var(--m3-space-2) 0 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-error);
}

@media (min-width: 600px) {
    .m3-memdetail__grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .m3-memdetail__danger-btn {
        width: auto;
    }
}
</style>
