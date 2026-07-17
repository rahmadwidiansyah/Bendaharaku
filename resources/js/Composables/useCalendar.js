import { ref, computed, watch } from 'vue'

/**
 * Composable kalender Dashboard.
 *
 * Menyediakan state dan logika navigasi bulan, pemilihan tanggal,
 * filter tampilan (total / income / expense), dan grid hari kalender.
 *
 * @param {Object} options
 * @param {Function} options.onNavigate  — callback(startDate, endDate) dipanggil saat bulan berubah
 * @param {string}   options.initialDate — ISO date string untuk inisialisasi bulan (dari props.startDate)
 * @param {import('vue').ComputedRef}    options.groupedTransactions — computed dari Dashboard
 */
export function useCalendar({ onNavigate, initialDate, groupedTransactions }) {
    // ─── Helper ──────────────────────────────────────────────────
    const getLocalYMD = (d) =>
        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

    // ─── State ────────────────────────────────────────────────────
    const selectedCalendarDate = ref(getLocalYMD(new Date()))
    const currentCalendarMonth = ref(new Date(initialDate))
    const calendarFilter       = ref('total')

    // Sinkronkan kalender saat startDate prop berubah dari luar (navigasi URL / DateModal)
    watch(
        () => initialDate,
        (v) => { currentCalendarMonth.value = new Date(v) },
    )

    // ─── Computed ─────────────────────────────────────────────────
    const calendarMonthName = computed(() =>
        currentCalendarMonth.value.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
    )

    const canGoNextMonth = computed(() => {
        const today = new Date()
        const cm    = currentCalendarMonth.value
        if (cm.getFullYear() > today.getFullYear()) return false
        if (cm.getFullYear() === today.getFullYear() && cm.getMonth() >= today.getMonth()) return false
        return true
    })

    const selectedDateFormatted = computed(() => {
        if (!selectedCalendarDate.value) return ''
        return new Date(selectedCalendarDate.value).toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        })
    })

    const calendarDays = computed(() => {
        const year              = currentCalendarMonth.value.getFullYear()
        const month             = currentCalendarMonth.value.getMonth()
        const startingDayOfWeek = new Date(year, month, 1).getDay()
        const daysInMonth       = new Date(year, month + 1, 0).getDate()
        const days              = []

        for (let i = 0; i < startingDayOfWeek; i++) days.push({ empty: true })

        for (let i = 1; i <= daysInMonth; i++) {
            const dateStr = getLocalYMD(new Date(year, month, i))
            const dayData = groupedTransactions.value[dateStr]
            let largestType = null, largestAmount = 0

            if (dayData) {
                if (calendarFilter.value === 'income' && dayData.income > 0) {
                    largestType   = 'income'
                    largestAmount = dayData.income
                } else if (calendarFilter.value === 'expense' && dayData.expense > 0) {
                    largestType   = 'expense'
                    largestAmount = dayData.expense
                } else if (calendarFilter.value === 'total') {
                    if (dayData.income > dayData.expense) {
                        largestType = 'income'; largestAmount = dayData.income
                    } else if (dayData.expense > dayData.income) {
                        largestType = 'expense'; largestAmount = dayData.expense
                    } else if (dayData.income > 0 || dayData.expense > 0) {
                        largestType   = dayData.expense > 0 ? 'expense' : 'income'
                        largestAmount = dayData.expense > 0 ? dayData.expense : dayData.income
                    }
                }
            }

            days.push({ empty: false, day: i, dateStr, largestType, largestAmount })
        }

        return days
    })

    // ─── Actions ──────────────────────────────────────────────────
    const prevMonth = () => {
        const cm  = currentCalendarMonth.value
        const d   = new Date(cm.getFullYear(), cm.getMonth() - 1, 1)
        const end = new Date(d.getFullYear(), d.getMonth() + 1, 0)
        onNavigate(getLocalYMD(d), getLocalYMD(end))
    }

    const nextMonth = () => {
        if (!canGoNextMonth.value) return
        const cm  = currentCalendarMonth.value
        const d   = new Date(cm.getFullYear(), cm.getMonth() + 1, 1)
        const end = new Date(d.getFullYear(), d.getMonth() + 1, 0)
        onNavigate(getLocalYMD(d), getLocalYMD(end))
    }

    const selectDate = (dateStr) => { selectedCalendarDate.value = dateStr }

    return {
        // helpers
        getLocalYMD,
        // state
        selectedCalendarDate,
        currentCalendarMonth,
        calendarFilter,
        // computed
        calendarMonthName,
        canGoNextMonth,
        selectedDateFormatted,
        calendarDays,
        // actions
        prevMonth,
        nextMonth,
        selectDate,
    }
}
