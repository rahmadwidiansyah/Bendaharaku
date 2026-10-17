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
</script>

<template>
    <M3Card variant="filled" padding="sm">
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
                    :aria-label="day.dateStr"
                    @click="$emit('select', day.dateStr)"
                >
                    <span class="m3-cal__number">{{ day.day }}</span>
                    <span
                        v-if="day.largestType"
                        class="m3-cal__badge"
                        :class="day.largestType === 'income' ? 'm3-cal__badge--income' : 'm3-cal__badge--expense'"
                    >{{ day.largestType === 'income' ? '+' : '-' }}{{ formatCompact(day.largestAmount) }}</span>
                </button>
            </div>
        </div>
    </M3Card>
</template>

<style scoped>
.m3-cal__filters {
    display: flex;
    gap: var(--m3-space-2);
    justify-content: center;
    margin-bottom: var(--m3-space-3);
    flex-wrap: wrap;
}

.m3-cal__weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: var(--m3-space-1);
    text-align: center;
    margin-bottom: var(--m3-space-1-5);
}

.m3-cal__weekday {
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
    padding: var(--m3-space-1) 0;
}

.m3-cal__grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: var(--m3-space-1);
}

.m3-cal__cell {
    min-height: var(--m3-size-calendar-cell);
}

.m3-cal__day {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    min-height: var(--m3-size-calendar-cell);
    padding: var(--m3-space-0-5);
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
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    line-height: 1;
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
