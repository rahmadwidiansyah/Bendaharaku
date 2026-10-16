<script setup>
/**
 * M3UpcomingDebts — daftar tanggungan jatuh tempo (dismissible).
 * Logika identik UpcomingDebts Default: sessionStorage key
 * `dismissed_debts`, urgencyKey, label, dan i18n yang sama.
 */
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatNumber } from '@/utils/format.js'
import M3Card from '@/Material/Components/M3Card.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'

const { t } = useI18n()

const props = defineProps({
    upcomingDebts: { type: Array, default: () => [] },
    isVisible: { type: Boolean, default: true },
})

const dismissed = ref([])

onMounted(() => {
    try {
        const stored = sessionStorage.getItem('dismissed_debts')
        if (stored) dismissed.value = JSON.parse(stored)
    } catch {
        // sessionStorage tidak tersedia — abaikan
    }
})

const dismiss = (key) => {
    dismissed.value.push(key)
    try {
        sessionStorage.setItem('dismissed_debts', JSON.stringify(dismissed.value))
    } catch { /* silent */ }
}

const activeDebts = computed(() =>
    props.upcomingDebts.filter((d) => !dismissed.value.includes(d.subject + d.type)),
)

const urgencyKey = (daysUntil) => {
    if (daysUntil < 0) return 'overdue'
    if (daysUntil === 0) return 'today'
    if (daysUntil <= 3) return 'soon'
    return 'normal'
}

const badgeLabel = (daysUntil) => {
    if (daysUntil < 0) return t('upcomingDebts.overdue')
    if (daysUntil === 0) return t('upcomingDebts.dueIn') + ' 0 ' + t('upcomingDebts.days')
    return `${daysUntil} ${t('upcomingDebts.days')}`
}

const typeLabel = (type) => {
    if (type === 'Debt') return t('upcomingDebts.debt')
    if (type === 'Receivable') return t('upcomingDebts.receivable')
    return type
}
</script>

<template>
    <section v-if="activeDebts.length > 0" :aria-label="$t('upcomingDebts.title')">
        <p class="m3-section-eyebrow">{{ $t('upcomingDebts.title') }}</p>
        <p class="m3-section-sub">{{ $t('upcomingDebts.subtitle') }}</p>
        <div class="m3-debts" role="list">
            <M3Card
                v-for="debt in activeDebts"
                :key="debt.subject + debt.type"
                variant="outlined"
                padding="sm"
                :class="['m3-debt', `m3-debt--${urgencyKey(debt.days_until)}`]"
            >
                <div role="listitem" class="m3-debt__row">
                    <div class="m3-debt__main">
                        <p class="m3-debt__subject">{{ typeLabel(debt.type) }} — {{ debt.subject }}</p>
                        <span class="m3-debt__badge" :aria-label="`${$t('upcomingDebts.dueIn')}: ${badgeLabel(debt.days_until)}`">
                            {{ badgeLabel(debt.days_until) }}
                        </span>
                    </div>
                    <p class="m3-debt__amount" aria-live="polite">Rp {{ isVisible ? formatNumber(debt.remaining) : '••••••••' }}</p>
                    <M3IconButton
                        :aria-label="$t('common.close')"
                        @click="dismiss(debt.subject + debt.type)"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </M3IconButton>
                </div>
            </M3Card>
        </div>
    </section>
</template>

<style scoped>
.m3-section-eyebrow {
    margin: 0 0 0.125rem;
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-section-sub {
    margin: 0 0 0.5rem;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-debts {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.m3-debt__row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.m3-debt__main {
    flex: 1;
    min-width: 0;
}

.m3-debt__subject {
    margin: 0 0 0.25rem;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-debt__badge {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-debt--overdue .m3-debt__badge,
.m3-debt--today .m3-debt__badge,
.m3-debt--soon .m3-debt__badge {
    background: var(--m3-color-expense-container);
    color: var(--m3-color-on-expense-container);
}

.m3-debt__amount {
    margin: 0;
    flex-shrink: 0;
    font-size: var(--m3-type-title-small-size);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-debt);
}
</style>
