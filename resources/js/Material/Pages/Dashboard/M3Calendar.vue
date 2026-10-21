<script setup>
/**
 * M3Calendar — filter chips (income/total/expense) + grid kalender.
 * Data & seleksi identik kalender Default (useCalendar di halaman induk;
 * formatCompact untuk badge nominal). Chips memakai M3Chip filter.
 */
import { formatCompact } from '@/utils/format.js'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'

defineProps({
    days: { type: Array, default: () => [] },
    dayNames: { type: Array, default: () => [] },
    selectedDate: { type: String, default: '' },
    today: { type: String, default: '' },
    filter: { type: String, default: 'total' }, // income | total | expense
})

defineEmits(['select', 'filter'])

// Badge kalender harus muat di kolom 1/7 layar mobile. Nominal ringkas
// (tanpa desimal untuk ribuan) mencegah min-content melebarkan grid.
const compactBadgeAmount = (value) => {
    const abs = Math.abs(value ?? 0)
    if (abs >= 1_000_000_000) return `${(abs / 1_000_000_000).toFixed(1).replace('.0', '')}M`
    if (abs >= 1_000_000) return `${(abs / 1_000_000).toFixed(1).replace('.0', '')}Jt`
    if (abs >= 1_000) return `${Math.round(abs / 1_000)}Rb`
    return String(Math.round(abs))
}

const badgeSign = (day) => (day.largestType === 'income' ? '+' : '-')

const badgeAriaLabel = (day) => day.largestType
    ? `${day.dateStr}, ${badgeSign(day)}${formatCompact(day.largestAmount)}`
    : day.dateStr
</script>

<template>
    <M3Card variant="filled" padding="sm" class="m3-cal">
        <div class="m3-cal__filters" role="group" :aria-label="$t('dashboard.calendarTab')">
            <M3Chip
                variant="filter"
                :selected="filter === 'income'"
                @click="$emit('filter', 'income')"
            >{{ $t('dashboard.calendarFilter.income') }}</M3Chip>
            <M3Chip
                variant="filter"
                :selected="filter === 'total'"
                @click="$emit('filter', 'total')"
            >{{ $t('dashboard.calendarFilter.total') }}</M3Chip>
            <M3Chip
                variant="filter"
                :selected="filter === 'expense'"
                @click="$emit('filter', 'expense')"
            >{{ $t('dashboard.calendarFilter.expense') }}</M3Chip>
        </div>
        <div class="m3-cal__weekdays" aria-hidden="true">
            <span v-for="day in dayNames" :key="day" class="m3-cal__weekday">{{ day }}</span>
        </div>
        <div class="m3-cal__grid" role="grid">
            <div v-for="(day, index) in days" :key="index" class="m3-cal__cell" role="gridcell">
                <button
                    v-if="!day.empty"
                    type="button"
                    class="m3-cal__day"
                    :class="[
                        selectedDate === day.dateStr ? 'm3-cal__day--selected' : '',
                        day.dateStr === today && selectedDate !== day.dateStr ? 'm3-cal__day--today' : '',
                    ]"
                    :aria-pressed="selectedDate === day.dateStr"
                    :aria-label="badgeAriaLabel(day)"
                    @click="$emit('select', day.dateStr)"
                >
                    <span class="m3-cal__number">{{ day.day }}</span>
                    <span
                        v-if="day.largestType"
                        class="m3-cal__badge"
                        :class="day.largestType === 'income' ? 'm3-cal__badge--income' : 'm3-cal__badge--expense'"
                    >{{ badgeSign(day) }}{{ compactBadgeAmount(day.largestAmount) }}</span>
                </button>
            </div>
        </div>
    </M3Card>
</template>

<style scoped>
.m3-cal {
    width: 100%;
    min-width: 0;
}

.m3-cal__filters {
    display: flex;
    gap: var(--m3-space-2);
    justify-content: center;
    margin-bottom: var(--m3-space-3);
    flex-wrap: wrap;
}

.m3-cal__weekdays {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: var(--m3-space-1);
    text-align: center;
    margin-bottom: var(--m3-space-1-5);
}

.m3-cal__weekday {
    min-width: 0;
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
    padding: var(--m3-space-1) 0;
}

.m3-cal__grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: var(--m3-space-1);
}

.m3-cal__cell {
    min-width: 0;
    min-height: var(--m3-size-calendar-cell);
}

.m3-cal__day {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-width: 0;
    height: 100%;
    min-height: var(--m3-size-calendar-cell);
    padding: var(--m3-space-0-5);
    overflow: hidden;
    border-radius: var(--m3-radius-sm);
    border: 1px solid transparent;
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    cursor: pointer;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-cal__day:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-cal__day--selected {
    background: var(--m3-color-primary-container);
    border-color: var(--m3-color-primary);
    color: var(--m3-color-on-primary-container);
}

.m3-cal__day--today {
    border-color: var(--m3-color-outline);
}

.m3-cal__number {
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    line-height: 1;
    margin-bottom: var(--m3-space-0-5);
}

.m3-cal__day--selected .m3-cal__number {
    color: var(--m3-color-on-primary-container);
}

.m3-cal__badge {
    display: block;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    line-height: 1;
    letter-spacing: 0;
    text-align: center;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-cal__badge--income {
    color: var(--m3-color-income);
}

.m3-cal__badge--expense {
    color: var(--m3-color-expense);
}

.m3-cal__day--selected .m3-cal__badge--income,
.m3-cal__day--selected .m3-cal__badge--expense {
    color: var(--m3-color-on-primary-container);
}
</style>
