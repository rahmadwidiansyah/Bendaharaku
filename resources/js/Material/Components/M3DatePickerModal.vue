<script setup>
/**
 * M3DatePickerModal — pemilih tanggal tunggal Material Design 3 Expressive.
 * Paritas perilaku dengan modal tanggal di Transactions/Create|Edit:
 * preset Hari ini/Kemarin, navigasi bulan, grid 7 kolom, hari terpilih
 * langsung dipakai form (tanpa tombol apply terpisah).
 * Presentasi: dialog di desktop, bottom sheet di mobile (M3Modal).
 */
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import { formatLocalYMD, parseDateOnly } from '@/utils/format.js'

const props = defineProps({
    show: Boolean,
    modelValue: { type: String, default: '' },
    title: { type: String, default: '' },
    maxDate: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'close'])

const { t, locale } = useI18n()

const viewMonth = ref(new Date())
const showYearPicker = ref(false)

const intlLocale = computed(() => (locale.value === 'en' ? 'en-US' : 'id-ID'))

const monthLabel = computed(() =>
    viewMonth.value.toLocaleDateString(intlLocale.value, { month: 'long', year: 'numeric' })
)

const todayYMD = computed(() => formatLocalYMD(new Date()))

watch(() => props.show, (visible) => {
    if (!visible) return
    const current = parseDateOnly(props.modelValue) ?? new Date()
    viewMonth.value = new Date(current.getFullYear(), current.getMonth(), 1)
    showYearPicker.value = false
})

// ── Navigasi ─────────────────────────────────────────────────────
const prevMonth = () => {
    viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() - 1, 1)
}

const maxDateValue = computed(() => (props.maxDate ? parseDateOnly(props.maxDate) : null))

const nextMonth = () => {
    const next = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + 1, 1)
    if (maxDateValue.value && next > maxDateValue.value) return
    viewMonth.value = next
}

const canGoNextMonth = computed(() => {
    if (!maxDateValue.value) return true
    const next = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + 1, 1)
    return next <= maxDateValue.value
})

const pickYear = (year) => {
    viewMonth.value = new Date(year, viewMonth.value.getMonth(), 1)
    showYearPicker.value = false
}

const yearOptions = computed(() => {
    const current = new Date().getFullYear()
    const start = current - 5
    return Array.from({ length: 11 }, (_, i) => start + i)
})

// ── Grid ─────────────────────────────────────────────────────────
const weekdayLabels = computed(() =>
    locale.value === 'en'
        ? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        : ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
)

const dayCells = computed(() => {
    const year = viewMonth.value.getFullYear()
    const month = viewMonth.value.getMonth()
    const firstWeekday = new Date(year, month, 1).getDay()
    const leading = firstWeekday === 0 ? 6 : firstWeekday - 1
    const total = new Date(year, month + 1, 0).getDate()
    const cells = Array.from({ length: leading }, () => null)
    for (let day = 1; day <= total; day++) {
        cells.push({ day, ymd: formatLocalYMD(new Date(year, month, day)) })
    }
    return cells
})

const isSelected = (ymd) => ymd === props.modelValue
const isDisabled = (ymd) => maxDateValue.value && parseDateOnly(ymd) > maxDateValue.value

const pickDate = (ymd) => {
    if (isDisabled(ymd)) return
    emit('update:modelValue', ymd)
    emit('close')
}

const pickPreset = (offsetDays) => {
    const d = new Date()
    d.setDate(d.getDate() + offsetDays)
    pickDate(formatLocalYMD(d))
}

const isPreset = (offsetDays) => {
    const d = new Date()
    d.setDate(d.getDate() + offsetDays)
    return props.modelValue === formatLocalYMD(d)
}
</script>

<template>
    <M3Modal
        :show="show"
        :title="title || t('transaction.chooseDate')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="sm"
        @close="emit('close')"
    >
        <div class="m3-dpm">
            <div class="m3-dpm__presets" role="group" :aria-label="t('transaction.chooseDate')">
                <M3Chip :selected="isPreset(0)" @click="pickPreset(0)">{{ t('transaction.today') }}</M3Chip>
                <M3Chip :selected="isPreset(-1)" @click="pickPreset(-1)">{{ t('transaction.yesterday') }}</M3Chip>
            </div>

            <div class="m3-dpm__head">
                <M3IconButton :aria-label="t('common.back')" @click="prevMonth">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </M3IconButton>
                <button
                    type="button"
                    class="m3-dpm__month"
                    :aria-expanded="showYearPicker"
                    :aria-label="t('common.selectDate')"
                    @click="showYearPicker = !showYearPicker"
                >
                    {{ monthLabel }}
                    <svg class="m3-dpm__month-caret" :class="showYearPicker ? 'm3-dpm__month-caret--open' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <M3IconButton :aria-label="t('common.next')" :disabled="!canGoNextMonth" @click="nextMonth">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </M3IconButton>
            </div>

            <div v-if="showYearPicker" class="m3-dpm__years" role="listbox" :aria-label="t('common.selectYear')">
                <button
                    v-for="year in yearOptions"
                    :key="year"
                    type="button"
                    role="option"
                    :aria-selected="year === viewMonth.getFullYear()"
                    :class="['m3-dpm__year', year === viewMonth.getFullYear() ? 'm3-dpm__year--active' : '']"
                    @click="pickYear(year)"
                >{{ year }}</button>
            </div>

            <template v-else>
                <div class="m3-dpm__weekdays" aria-hidden="true">
                    <span v-for="label in weekdayLabels" :key="label" class="m3-dpm__weekday">{{ label }}</span>
                </div>
                <div class="m3-dpm__grid">
                    <template v-for="(cell, index) in dayCells" :key="index">
                        <span v-if="!cell" class="m3-dpm__blank" />
                        <button
                            v-else
                            type="button"
                            :class="[
                                'm3-dpm__day',
                                isSelected(cell.ymd) ? 'm3-dpm__day--selected' : '',
                                cell.ymd === todayYMD ? 'm3-dpm__day--today' : '',
                            ]"
                            :disabled="isDisabled(cell.ymd)"
                            :aria-pressed="isSelected(cell.ymd)"
                            @click="pickDate(cell.ymd)"
                        >{{ cell.day }}</button>
                    </template>
                </div>
            </template>
        </div>

        <template #footer>
            <M3Button variant="text" @click="emit('close')">
                {{ t('common.cancel') }}
            </M3Button>
        </template>
    </M3Modal>
</template>

<style scoped>
.m3-dpm {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-dpm__presets {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
}

.m3-dpm__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-dpm__month {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    min-height: var(--m3-size-touch-target);
    padding: 0 var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    font-family: inherit;
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    color: var(--m3-color-on-surface);
    text-transform: capitalize;
    cursor: pointer;
    transition: background var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-dpm__month:hover {
    background: var(--m3-color-surface-container);
}

.m3-dpm__month:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-dpm__month-caret {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    color: var(--m3-color-on-surface-variant);
    transition: transform var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-dpm__month-caret--open {
    transform: rotate(180deg);
}

.m3-dpm__years {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--m3-space-2);
    max-height: 16rem;
    overflow-y: auto;
}

.m3-dpm__year {
    min-height: var(--m3-size-touch-target);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: transparent;
    font-family: inherit;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    cursor: pointer;
    transition: background var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-dpm__year:hover {
    background: var(--m3-color-surface-container);
}

.m3-dpm__year--active,
.m3-dpm__year--active:hover {
    background: var(--m3-color-primary);
    border-color: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-dpm__year:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-dpm__weekdays,
.m3-dpm__grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: var(--m3-space-1);
}

.m3-dpm__weekday {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: var(--m3-size-touch-target);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    color: var(--m3-color-on-surface-variant);
    text-transform: uppercase;
}

.m3-dpm__blank {
    min-height: var(--m3-size-touch-target);
}

.m3-dpm__day {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: var(--m3-size-touch-target);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    font-family: inherit;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    font-variant-numeric: tabular-nums;
    cursor: pointer;
    transition: background var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-dpm__day:hover:not(:disabled) {
    background: var(--m3-color-surface-container);
}

.m3-dpm__day--today:not(.m3-dpm__day--selected) {
    color: var(--m3-color-primary);
}

.m3-dpm__day--selected {
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-dpm__day--selected:hover {
    background: var(--m3-color-primary);
}

.m3-dpm__day:disabled {
    color: var(--m3-color-on-surface-variant);
    opacity: 0.38;
    cursor: not-allowed;
}

.m3-dpm__day:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}
</style>
