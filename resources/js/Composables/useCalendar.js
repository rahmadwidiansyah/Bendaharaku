import { ref, computed, watch } from 'vue'

/**
 * Composable kalender Dashboard.
 *
 * Menyediakan state dan logika navigasi bulan, pemilihan tanggal,
 * filter tampilan (total / income / expense), dan grid hari kalender.
 *
 * @param {Object} options
 * @param {Function} options.onNavigate  — callback(startDate, endDate) dipanggil saat bulan berubah
 * @param {string}   options.initialDate — string tanggal "YYYY-MM-DD" untuk inisialisasi bulan (dari props.startDate)
 * @param {import('vue').ComputedRef}    options.groupedTransactions — computed dari Dashboard
 */
export function useCalendar({ onNavigate, initialDate, groupedTransactions }) {
    const getInitialDateValue = () => typeof initialDate === 'object' && initialDate?.value ? initialDate.value : initialDate
    // ─── Helper ──────────────────────────────────────────────────
    const getLocalYMD = (d) =>
        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

    // Parse "YYYY-MM-DD" sebagai tanggal LOKAL, bukan UTC.
    // `new Date('YYYY-MM-DD')` di spec JS selalu di-parse sebagai UTC midnight —
    // di timezone tertentu ini bisa mundur satu hari saat dikonversi balik ke waktu
    // lokal, jadi kalau startDate = tanggal 1 bulan ini, bisa ke-parse jadi
    // tanggal terakhir bulan sebelumnya (nama bulan di header jadi salah).
    const parseLocalYMD = (dateStr) => {
        const [y, m, d] = dateStr.split('-').map(Number)
        return new Date(y, m - 1, d)
    }

    // ─── State ────────────────────────────────────────────────────
    const selectedCalendarDate = ref(getLocalYMD(new Date()))
    const currentCalendarMonth = ref(parseLocalYMD(getInitialDateValue()))
    const calendarFilter       = ref('total')

    // Sinkronkan kalender saat startDate prop berubah dari luar (navigasi URL / DateModal)
    watch(
        () => getInitialDateValue(),
        (v) => {
            currentCalendarMonth.value = parseLocalYMD(v)
            if (!selectedCalendarDate.value?.startsWith(v.slice(0, 7))) {
                selectedCalendarDate.value = v
            }
        },
        { immediate: true },
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
        return parseLocalYMD(selectedCalendarDate.value).toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        })
    })

    // Badge angka per tanggal, mengikuti calendarFilter:
    // - 'income'  → total income hari itu saja (hijau)
    // - 'expense' → total expense hari itu saja (merah)
    // - 'total'   → net = income - expense (hijau kalau surplus, merah kalau defisit,
    //                tidak muncul kalau pas 0 atau hari itu tidak ada transaksi)
    const calendarDays = computed(() => {
        const year              = currentCalendarMonth.value.getFullYear()
        const month             = currentCalendarMonth.value.getMonth()
        const startingDayOfWeek = new Date(year, month, 1).getDay()
        const daysInMonth       = new Date(year, month + 1, 0).getDate()
        const filter            = calendarFilter.value
        const days              = []

        for (let i = 0; i < startingDayOfWeek; i++) days.push({ empty: true })

        for (let i = 1; i <= daysInMonth; i++) {
            const dateStr = getLocalYMD(new Date(year, month, i))
            const dayData = groupedTransactions.value[dateStr]
            let largestType   = null
            let largestAmount = 0

            if (dayData) {
                if (filter === 'income') {
                    if (dayData.income > 0) {
                        largestType   = 'income'
                        largestAmount = dayData.income
                    }
                } else if (filter === 'expense') {
                    if (dayData.expense > 0) {
                        largestType   = 'expense'
                        largestAmount = dayData.expense
                    }
                } else {
                    // 'total' → net income - expense hari itu
                    const net = dayData.income - dayData.expense
                    if (net > 0) {
                        largestType   = 'income'
                        largestAmount = net
                    } else if (net < 0) {
                        largestType   = 'expense'
                        largestAmount = Math.abs(net)
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