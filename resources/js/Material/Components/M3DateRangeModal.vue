<script setup>
/**
 * M3DateRangeModal — pemilih rentang tanggal versi M3.
 *
 * Paritas perilaku dengan DateModal Default:
 *   - Preset cepat: Tahun Ini / Bulan Ini / Bulan Lalu
 *   - Submit query `start_date`/`end_date` via router.get(action,
 *     {preserveState: true}) — kontrak query identik; endpoint/route tak berubah
 *   - Validasi rentang terbalik ditolak
 * Tambahan: prop `params` (mis. `{ type }` Dashboard) ikut terkirim agar
 * filter lain tidak hilang, dan prop `replace` untuk navigasi gaya
 * prev/next bulan Dashboard.
 * Tambahan M3: kalender bulanan dengan pemilihan rentang (tap tanggal awal →
 * tanggal akhir) dan pemilih tahun via tap header bulan.
 * Presentasi: dialog di desktop, bottom sheet di mobile (M3Modal).
 */
import { ref, reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import { formatLocalYMD, parseDateOnly } from '@/utils/format.js'

const { t, locale } = useI18n()

const props = defineProps({
    show: Boolean,
    startDate: String,
    endDate: String,
    action: { type: String, required: true },
    params: { type: Object, default: () => ({}) },
    replace: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const form = reactive({
    start_date: props.startDate,
    end_date: props.endDate,
})

// ─── State tampilan ───────────────────────────────────────────────
const viewMonth = ref(new Date())
const showYearPicker = ref(false)

const intlLocale = computed(() => (locale.value === 'en' ? 'en-US' : 'id-ID'))

const monthLabel = computed(() =>
    viewMonth.value.toLocaleDateString(intlLocale.value, { month: 'long', year: 'numeric' })
)

// Sinkronkan form + bulan tampilan setiap kali modal dibuka.
watch(() => props.show, (visible) => {
    if (!visible) return
    form.start_date = props.startDate
    form.end_date = props.endDate
    viewMonth.value = parseDateOnly(props.startDate) ?? new Date()
    showYearPicker.value = false
})

// ─── Preset cepat (paritas DateModal) ─────────────────────────────
const presetRange = (type) => {
    const today = new Date()
    if (type === 'thisYear') {
        return [
            formatLocalYMD(new Date(today.getFullYear(), 0, 1)),
            formatLocalYMD(new Date(today.getFullYear(), 11, 31)),
        ]
    }
    if (type === 'thisMonth') {
        return [
            formatLocalYMD(new Date(today.getFullYear(), today.getMonth(), 1)),
            formatLocalYMD(new Date(today.getFullYear(), today.getMonth() + 1, 0)),
        ]
    }
    return [
        formatLocalYMD(new Date(today.getFullYear(), today.getMonth() - 1, 1)),
        formatLocalYMD(new Date(today.getFullYear(), today.getMonth(), 0)),
    ]
}

const setQuickDate = (type) => {
    const [start, end] = presetRange(type)
    form.start_date = start
    form.end_date = end
    viewMonth.value = parseDateOnly(start)
}

const activeQuickDate = computed(() => {
    for (const key of ['thisYear', 'thisMonth', 'lastMonth']) {
        const [s, e] = presetRange(key)
        if (form.start_date === s && form.end_date === e) return key
    }
    return null
})

// ─── Kalender ─────────────────────────────────────────────────────
const todayYMD = formatLocalYMD(new Date())

const weekdayLabels = computed(() => {
    const fmt = new Intl.DateTimeFormat(intlLocale.value, { weekday: 'short' })
    const monday = new Date(2024, 0, 1) // Senin
    return Array.from({ length: 7 }, (_, i) => fmt.format(new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + i)))
})

const canGoNextMonth = computed(() => {
    const now = new Date()
    return viewMonth.value.getFullYear() < now.getFullYear()
        || (viewMonth.value.getFullYear() === now.getFullYear() && viewMonth.value.getMonth() < now.getMonth())
})

const prevMonth = () => {
    viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() - 1, 1)
}

const nextMonth = () => {
    if (!canGoNextMonth.value) return
    viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + 1, 1)
}

const dayCells = computed(() => {
    const year = viewMonth.value.getFullYear()
    const month = viewMonth.value.getMonth()
    const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7 // Senin = 0
    const totalDays = new Date(year, month + 1, 0).getDate()
    const cells = Array.from({ length: firstWeekday }, () => null)
    for (let day = 1; day <= totalDays; day++) {
        cells.push({ day, ymd: formatLocalYMD(new Date(year, month, day)) })
    }
    return cells
})

const yearOptions = computed(() => {
    const current = new Date().getFullYear()
    return Array.from({ length: 12 }, (_, i) => current - 10 + i)
})

const pickYear = (year) => {
    viewMonth.value = new Date(year, viewMonth.value.getMonth(), 1)
    showYearPicker.value = false
}

const pickDate = (ymd) => {
    if (form.start_date && form.end_date) {
        form.start_date = ymd
        form.end_date = null
    } else if (form.start_date) {
        if (ymd < form.start_date) {
            form.start_date = ymd
        } else {
            form.end_date = ymd
        }
    } else {
        form.start_date = ymd
    }
}

const isStart = (ymd) => ymd === form.start_date
const isEnd = (ymd) => ymd === form.end_date
const isInRange = (ymd) =>
    !!form.start_date && !!form.end_date && ymd > form.start_date && ymd < form.end_date

// ─── Validasi & submit (paritas DateModal) ────────────────────────
const isSubmitting = ref(false)
const isIncomplete = computed(() => !form.start_date || !form.end_date)
const isInvalidRange = computed(() => !isIncomplete.value && form.start_date > form.end_date)
const canSubmit = computed(() => !isIncomplete.value && !isInvalidRange.value && !isSubmitting.value)

const submit = () => {
    if (!canSubmit.value) return
    isSubmitting.value = true
    router.get(
        props.action,
        { ...props.params, start_date: form.start_date, end_date: form.end_date },
        {
            preserveState: true,
            replace: props.replace,
            onSuccess: () => emit('close'),
            onFinish: () => { isSubmitting.value = false },
        },
    )
}
</script>

<template>
    <M3Modal
        :show="show"
        :title="$t('common.dateRange')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="sm"
        @close="emit('close')"
    >
        <div class="m3-drm">
            <!-- ── Preset cepat ── -->
            <div class="m3-drm__presets" role="group" :aria-label="$t('common.dateRange')">
                <M3Chip
                    v-for="preset in ['thisYear', 'thisMonth', 'lastMonth']"
                    :key="preset"
                    :selected="activeQuickDate === preset"
                    @click="setQuickDate(preset)"
                >{{ $t(`common.${preset}`) }}</M3Chip>
            </div>

            <!-- ── Header bulan + pemilih tahun ── -->
            <div class="m3-drm__head">
                <M3IconButton :aria-label="$t('common.back')" @click="prevMonth">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </M3IconButton>
                <button
                    type="button"
                    class="m3-drm__month"
                    :aria-expanded="showYearPicker"
                    :aria-label="$t('common.selectDate')"
                    @click="showYearPicker = !showYearPicker"
                >
                    {{ monthLabel }}
                    <svg class="m3-drm__month-caret" :class="showYearPicker ? 'm3-drm__month-caret--open' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <M3IconButton :aria-label="$t('common.next')" :disabled="!canGoNextMonth" @click="nextMonth">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </M3IconButton>
            </div>

            <!-- ── Pemilih tahun ── -->
            <div v-if="showYearPicker" class="m3-drm__years" role="listbox" :aria-label="$t('common.selectYear')">
                <button
                    v-for="year in yearOptions"
                    :key="year"
                    type="button"
                    role="option"
                    :aria-selected="year === viewMonth.getFullYear()"
                    :class="['m3-drm__year', year === viewMonth.getFullYear() ? 'm3-drm__year--active' : '']"
                    @click="pickYear(year)"
                >{{ year }}</button>
            </div>

            <!-- ── Grid kalender ── -->
            <template v-else>
                <div class="m3-drm__weekdays" aria-hidden="true">
                    <span v-for="label in weekdayLabels" :key="label" class="m3-drm__weekday">{{ label }}</span>
                </div>
                <div class="m3-drm__grid">
                    <template v-for="(cell, index) in dayCells" :key="index">
                        <span v-if="!cell" class="m3-drm__blank" />
                        <button
                            v-else
                            type="button"
                            :class="[
                                'm3-drm__day',
                                isStart(cell.ymd) || isEnd(cell.ymd) ? 'm3-drm__day--edge' : '',
                                isInRange(cell.ymd) ? 'm3-drm__day--in-range' : '',
                                cell.ymd === todayYMD ? 'm3-drm__day--today' : '',
                            ]"
                            :aria-pressed="isStart(cell.ymd) || isEnd(cell.ymd)"
                            @click="pickDate(cell.ymd)"
                        >{{ cell.day }}</button>
                    </template>
                </div>
            </template>

            <!-- ── Ringkasan rentang ── -->
            <div class="m3-drm__summary">
                <p class="m3-drm__summary-row">
                    <span class="m3-drm__summary-label">{{ $t('common.from') }}</span>
                    <span class="m3-drm__summary-value">{{ form.start_date || '—' }}</span>
                </p>
                <p class="m3-drm__summary-row">
                    <span class="m3-drm__summary-label">{{ $t('common.to') }}</span>
                    <span class="m3-drm__summary-value">{{ form.end_date || $t('common.selectDate') }}</span>
                </p>
            </div>

            <!-- ── Validasi ── -->
            <p v-if="isInvalidRange" role="alert" class="m3-drm__error">
                {{ $t('common.dateInvalidRange') }}
            </p>
        </div>

        <template #footer>
            <M3Button variant="text" :disabled="isSubmitting" @click="emit('close')">
                {{ $t('common.cancel') }}
            </M3Button>
            <M3Button variant="filled" :disabled="!canSubmit" @click="submit">
                {{ isSubmitting ? $t('common.applying') : $t('common.applyFilter') }}
            </M3Button>
        </template>
    </M3Modal>
</template>

<style scoped>
.m3-drm {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

/* ── Preset ── */
.m3-drm__presets {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
}

/* ── Header bulan ── */
.m3-drm__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-drm__month {
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
    transition: background 150ms ease;
}

.m3-drm__month:hover {
    background: var(--m3-color-surface-container);
}

.m3-drm__month:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-drm__month-caret {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    color: var(--m3-color-on-surface-variant);
    transition: transform 200ms ease;
}

.m3-drm__month-caret--open {
    transform: rotate(180deg);
}

/* ── Pemilih tahun ── */
.m3-drm__years {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--m3-space-2);
    max-height: 16rem;
    overflow-y: auto;
}

.m3-drm__year {
    min-height: var(--m3-size-touch-target);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    background: transparent;
    font-family: inherit;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    cursor: pointer;
    transition: background 150ms ease;
}

.m3-drm__year:hover {
    background: var(--m3-color-surface-container);
}

/* Tahun aktif harus menang atas :hover */
.m3-drm__year--active:hover {
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-drm__year--active {
    background: var(--m3-color-primary);
    border-color: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-drm__year:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

/* ── Grid kalender ── */
.m3-drm__weekdays,
.m3-drm__grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: var(--m3-space-1);
}

.m3-drm__weekday {
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

.m3-drm__blank {
    min-height: var(--m3-size-touch-target);
}

.m3-drm__day {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: var(--m3-size-touch-target);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    font-family: inherit;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
    cursor: pointer;
    transition: background 150ms ease;
}

.m3-drm__day:hover {
    background: var(--m3-color-surface-container);
}

/* State terpilih harus menang atas :hover (specificity 0,2,0) */
.m3-drm__day--in-range:hover {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-drm__day--edge:hover {
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
}

.m3-drm__day--in-range {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    border-radius: 0;
}

.m3-drm__day--edge {
    background: var(--m3-color-primary);
    color: var(--m3-color-on-primary);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
}

.m3-drm__day--today {
    box-shadow: inset 0 0 0 1.5px var(--m3-color-primary);
}

.m3-drm__day:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

/* ── Ringkasan ── */
.m3-drm__summary {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
}

.m3-drm__summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    margin: 0;
}

.m3-drm__summary-label {
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-drm__summary-value {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
}

.m3-drm__error {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-emphasized-body-small-weight, var(--m3-type-emphasized-body-medium-weight));
    color: var(--m3-color-expense);
}
</style>
