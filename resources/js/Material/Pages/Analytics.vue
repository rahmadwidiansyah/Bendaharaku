<script setup>
/**
 * Material/Pages/Analytics.vue — Analitik Material Design 3 Expressive.
 *
 * Kontrak data & interaksi IDENTIK dengan Pages/Analytics/Index.vue Default:
 * 24 props yang sama dari AnalyticsController, Chart.js sebagai library
 * yang sama, agregasi harian/mingguan/bulanan, doughnut kategori, filter
 * tanggal via DateModal bersama, dan endpoint analytics.history tak berubah.
 *
 * Adaptasi M3 (presentasi saja):
 * - Warna seri dibaca dari token `--m3-*` (semantik sama dengan Default);
 *   chrome chart (grid, tooltip, ticks) sadar light/dark via kelas `.light`.
 * - Ikon kategori memakai Material/utils/m3IconColor (token, bukan utilitas
 *   Tailwind mentah — lihat catatan light-mode di file tersebut).
 * - DateModal digantikan M3DateRangeModal (preset + kalender M3; kontrak
 *   query start_date/end_date dan route analytics.index tidak berubah).
 * - Animasi chart dimatikan bila prefers-reduced-motion aktif.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3CashflowCard from '@/Material/Pages/Dashboard/M3CashflowCard.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3DateRangeModal from '@/Material/Components/M3DateRangeModal.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref, shallowRef, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
import { formatNumber, formatDate, parseDateOnly, formatLocalYMD } from '@/utils/format.js'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import {
    m3CssVar as cssVar,
    m3ChartChrome as chartChrome,
    m3ChartFontFamily,
    m3TooltipOptions,
} from '@/Material/utils/m3Chart.js'
import { InboxIcon } from 'lucide-vue-next'

const { t } = useI18n()

Chart.register(...registerables)

const props = defineProps({
    startDate: String,
    endDate: String,
    totalIncome: Number,
    totalExpense: Number,
    totalDebt: Number,
    totalReceivable: Number,
    cumulativeBalance: Number,
    expensesByCategory: Array,
    incomesByCategory: Array,
    debtsByCategory: Array,
    receivablesByCategory: Array,
    dailyLabels: Array,
    dailyIncome: Array,
    dailyExpense: Array,
    cumulativeData: Array,
    todayIndex: Number,
    historyMode: String,
    openingBalance: Number,
    allDailyLabels: Array,
    allDailyDates: Array,
    allDailyIncome: Array,
    allDailyExpense: Array,
    allDailyDebt: Array,
    allDailyReceivable: Array,
})

const charts = shallowRef({})
const barView = ref(typeof window !== 'undefined' && window.innerWidth < 640 ? 'mingguan' : 'harian')
const categoryView = ref('expense')
const barChartRef = ref(null)
const cumulativeChartRef = ref(null)
const cumulativeContainerRef = ref(null)
const cumulativeCardRef = ref(null)
const doughnutChartRef = ref(null)
const barChartKey = ref(0)
const doughnutReady = ref(false)

// ─── Paging chart arus kas ────────────────────────────────────────
// Satu canvas selebar seluruh riwayat melewati batas keras canvas
// Chromium/Skia (~32.767px per dimensi): canvas gagal dirender dan tampil
// putih blank di Android. Karena itu label dipotong per halaman — halaman
// 0 adalah yang paling baru — dan lebar canvas di-clamp sebagai pengaman.
const isMobileViewport = () => typeof window !== 'undefined' && window.innerWidth < 640
const BAR_PAGE_SIZE = {
    // Halaman disesuaikan agar satu halaman muat penuh di lebar kontainer
    // (canvas fixed-width ala chart Admin — y axis selalu terlihat).
    harian: () => (isMobileViewport() ? 16 : 31),
    mingguan: () => 26,
    bulanan: () => 36,
}
const CANVAS_MAX_WIDTH = 16000
const barPage = ref(0)
const barPager = ref({ page: 0, count: 1 })

const sliceBarPage = (data, view) => {
    const size = BAR_PAGE_SIZE[view]?.() || 60
    const total = data.labels.length
    const pageCount = Math.max(1, Math.ceil(total / size))
    const page = Math.min(barPage.value, pageCount - 1)
    const end = Math.max(0, total - page * size)
    const start = Math.max(0, end - size)
    return {
        pageCount,
        page,
        labels: data.labels.slice(start, end),
        incomes: data.incomes.slice(start, end),
        expenses: data.expenses.slice(start, end),
        debts: data.debts.slice(start, end),
        receivables: data.receivables.slice(start, end),
    }
}

const goOlderBarPage = () => {
    barPage.value += 1
    renderBarChart(barView.value, true)
}

const goNewerBarPage = () => {
    barPage.value = Math.max(0, barPage.value - 1)
    renderBarChart(barView.value, true)
}

// ─── Modal rentang tanggal M3 ─────────────────────────────────────
const showDateModal = ref(false)
// Indikator filter aktif (paritas DateModal Default): start_date bukan
// tanggal 1 bulan berjalan.
const isDateFiltered = computed(() => {
    const now = new Date()
    return props.startDate !== formatLocalYMD(new Date(now.getFullYear(), now.getMonth(), 1))
})

// Hormati prefers-reduced-motion: matikan durasi animasi Chart.js.
const REDUCED_MOTION = typeof window !== 'undefined'
    && typeof window.matchMedia === 'function'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches

// Warna seri dari token M3 (hue semantik sama dengan Default).
const FLOW_COLORS = computed(() => ({
    income: cssVar('--m3-color-income', '#4ade80'),
    expense: cssVar('--m3-color-expense', '#f87171'),
    debt: cssVar('--m3-color-debt', '#facc15'),
    receivable: cssVar('--m3-color-receivable', '#c084fc'),
}))

// Chrome chart & tooltip dibagi lewat Material/utils/m3Chart.js (design.md §11.2).

const barViews = [
    { key: 'harian', label: () => t('analytics.view.daily') },
    { key: 'mingguan', label: () => t('analytics.view.weekly') },
    { key: 'bulanan', label: () => t('analytics.view.monthly') },
]

const categoryViews = [
    { key: 'expense', label: () => t('analytics.categoryTab.expenseShort') },
    { key: 'income', label: () => t('analytics.categoryTab.incomeShort') },
    { key: 'debt', label: () => t('analytics.categoryTab.debtShort') },
    { key: 'receivable', label: () => t('analytics.categoryTab.receivableShort') },
]

const destroyChart = (id) => {
    if (charts.value[id]) {
        charts.value[id].destroy()
        delete charts.value[id]
    }
}

const initCumulativeChart = async () => {
    try {
        destroyChart('cumulative')

        await nextTick()
        await new Promise((resolve) => requestAnimationFrame(resolve))

        const canvas = cumulativeChartRef.value
        if (!canvas) {
            console.warn('Cumulative canvas ref not found')
            return
        }

        if (!props.dailyLabels?.length || !props.cumulativeData?.length) {
            return
        }

        const container = cumulativeContainerRef.value || canvas.parentElement
        const chrome = chartChrome()

        const doRender = (w, h) => {
            destroyChart('cumulative')

            canvas.width = w
            canvas.height = h
            canvas.style.width = w + 'px'
            canvas.style.height = h + 'px'

            const ctx = canvas.getContext('2d')
            if (!ctx) return

            const brand = chrome.brand

            // Model chart ala AdminChart: sumbu Y/X terlihat, label X
            // renggang (autoSkip). Data kumulatif PAKAI hasil server
            // (openingBalance + arus harian rentang terpilih, prop
            // dailyLabels/cumulativeData) — konsisten dengan angka hero.
            // Recompute dari allDaily* mengabaikan saldo awal wallet dan
            // menghasilkan nilai salah pada data ber-saldo awal.
            const allLabels = props.dailyLabels || []
            const allData = props.cumulativeData || []
            const take = Math.min(31, allLabels.length)
            const visibleLabels = allLabels.slice(-take)
            const visibleData = allData.slice(-take)

            // Area fill flat lembut seperti AdminChart (bukan gradient).
            const softFill = hexToRgba(brand, 0.16)

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: visibleLabels,
                datasets: [{
                    data: visibleData,
                    borderColor: brand,
                    borderWidth: 2,
                    backgroundColor: softFill,
                    fill: true,
                    // Bahasa dataset mengikuti AdminChart: garis bersih tanpa
                    // titik, titik hanya muncul saat hover.
                    tension: 0.35,
                    pointRadius: visibleData.length === 1 ? 6 : 0,
                    pointHoverRadius: 5,
                    pointHitRadius: 16,
                    pointBackgroundColor: brand,
                    pointBorderColor: chrome.surface,
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: brand,
                    pointHoverBorderColor: chrome.surface,
                    pointHoverBorderWidth: 2,
                    spanGaps: true,
                }],
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    animation: { duration: REDUCED_MOTION ? 0 : 600 },
                    layout: { padding: { left: 4, right: 8, top: 10, bottom: 4 } },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...m3TooltipOptions(chrome),
                            callbacks: {
                                title: (items) => {
                                    const d = parseDateOnly(items[0]?.label)
                                    return d?.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) ?? (items[0]?.label ?? '')
                                },
                                label: (item) => 'Rp ' + Number(item.raw).toLocaleString('id-ID'),
                            },
                        },
                    },
                    scales: {
                        // Sumbu terlihat ala AdminChart: label X renggang
                        // (autoSkip) dan tick Y ringkas (jt/rb/M).
                        x: {
                            display: true,
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                color: chrome.ticks,
                                font: { size: 11 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8,
                            },
                        },
                        y: {
                            display: true,
                            grid: { display: true, color: chrome.grid },
                            border: { display: false },
                            ticks: {
                                display: true,
                                color: chrome.ticks,
                                font: { size: 11 },
                                callback: (value) => formatCompact(value),
                            },
                        },
                    },
                },
            })

            charts.value['cumulative'] = chart
        }

        const w = container?.clientWidth || 0
        const h = container?.clientHeight || 120

        if (w > 10) {
            doRender(w, h)
        } else {
            const cardEl = cumulativeCardRef.value?.$el || container?.parentElement || container
            const ro = new ResizeObserver(() => {
                const fw = container?.clientWidth || 0
                const fh = container?.clientHeight || 120
                if (fw > 10) {
                    ro.disconnect()
                    doRender(fw, fh)
                }
            })
            if (cardEl) ro.observe(cardEl)
        }
    } catch (error) {
        console.error('Failed to initialize cumulative chart:', error)
    }
}

function formatCompact(n) {
    // Satuan Indonesia: rb (ribu), jt (juta), M (miliar) — konsisten
    // dengan tick chart lain (AdminChart yCompact).
    const abs = Math.abs(n)
    if (abs >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'M'
    if (abs >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'jt'
    if (abs >= 1e3) return (n / 1e3).toFixed(0) + 'rb'
    return n.toString()
}

function buildBarData(view) {
    // Chart arus kas mengikuti rentang tanggal terpilih (paritas dengan KPI &
    // doughnut). Label ISO 'YYYY-MM-DD' aman dibandingkan sebagai string.
    const startDate = props.startDate || ''
    const endDate = props.endDate || ''
    const inRange = (date) => (!startDate || date >= startDate) && (!endDate || date <= endDate)

    const allDates = props.allDailyDates || []
    const idx = []
    allDates.forEach((date, i) => {
        if (inRange(date)) idx.push(i)
    })
    const dates = idx.map(i => allDates[i])
    const fIncome = idx.map(i => props.allDailyIncome?.[i] || 0)
    const fExpense = idx.map(i => props.allDailyExpense?.[i] || 0)
    const fDebt = idx.map(i => props.allDailyDebt?.[i] || 0)
    const fReceivable = idx.map(i => props.allDailyReceivable?.[i] || 0)

    const labels = [], incomes = [], expenses = [], debts = [], receivables = []

    function pushNonEmpty(l, i, e, d, r) {
        if (i || e || d || r) {
            labels.push(l); incomes.push(i); expenses.push(e); debts.push(d); receivables.push(r)
        }
    }

    if (view === 'harian') {
        dates.forEach((date, i) => {
            const inc = Math.abs(fIncome[i])
            const exp = Math.abs(fExpense[i])
            const debt = Math.abs(fDebt[i])
            const rec = Math.abs(fReceivable[i])
            pushNonEmpty(
                formatDate(date, { day: '2-digit', month: 'short' }),
                inc, exp, debt, rec,
            )
        })
        return { labels, incomes, expenses, debts, receivables }
    }

    if (view === 'mingguan') {
        let bucket = null
        dates.forEach((date, i) => {
            const d = parseDateOnly(date)
            if (!bucket) {
                bucket = { start: d.getDate(), end: d.getDate(), inc: 0, exp: 0, debt: 0, rec: 0, count: 0 }
            }

            bucket.end = d.getDate()
            bucket.inc += Math.abs(fIncome[i])
            bucket.exp += Math.abs(fExpense[i])
            bucket.debt += Math.abs(fDebt[i])
            bucket.rec += Math.abs(fReceivable[i])
            bucket.count++

            if (bucket.count === 7 || i === dates.length - 1) {
                pushNonEmpty(`${bucket.start}-${bucket.end}`, bucket.inc, bucket.exp, bucket.debt, bucket.rec)
                bucket = null
            }
        })
        return { labels, incomes, expenses, debts, receivables }
    }

    const monthMap = new Map()
    dates.forEach((dateStr, i) => {
        const d = parseDateOnly(dateStr)
        if (!d) return
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
        if (!monthMap.has(key)) {
            monthMap.set(key, {
                label: d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }),
                inc: 0, exp: 0, debt: 0, rec: 0,
            })
        }
        const m = monthMap.get(key)
        m.inc += Math.abs(fIncome[i])
        m.exp += Math.abs(fExpense[i])
        m.debt += Math.abs(fDebt[i])
        m.rec += Math.abs(fReceivable[i])
    })

    Array.from(monthMap.values()).forEach((m) => {
        pushNonEmpty(m.label, m.inc, m.exp, m.debt, m.rec)
    })

    return { labels, incomes, expenses, debts, receivables }
}

// ─── Legenda bar interaktif (design.md §11.2: legend harus terhubung ke data) ──
const BAR_SERIES = ['income', 'expense', 'debt', 'receivable']
const barHidden = ref({ income: false, expense: false, debt: false, receivable: false })

function toggleBarSeries(key) {
    const i = BAR_SERIES.indexOf(key)
    if (i < 0) return

    barHidden.value = { ...barHidden.value, [key]: !barHidden.value[key] }

    const chart = charts.value['bar']
    if (chart) {
        chart.setDatasetVisibility(i, !barHidden.value[key])
        chart.update()
    }
    updateBarYScale()
}

function applyBarVisibility() {
    const chart = charts.value['bar']
    if (!chart) return
    BAR_SERIES.forEach((key, i) => chart.setDatasetVisibility(i, !barHidden.value[key]))
}

const renderBarChart = async (view, keepPage = false) => {
    barView.value = view
    if (!keepPage) barPage.value = 0
    const full = buildBarData(view)
    const { labels, incomes, expenses, debts, receivables, pageCount, page } = sliceBarPage(full, view)
    barPager.value = { page, count: pageCount }

    destroyChart('bar')
    barChartKey.value++
    await nextTick()

    const canvas = barChartRef.value
    if (!canvas) return

    const container = canvas.parentElement
    // Lebar canvas = lebar kontainer (tanpa scroll horizontal): struktur ala
    // chart Admin — y axis selalu terlihat, label X ditata autoSkip, dan
    // navigasi rentang memakai pager.
    const canvasWidth = Math.min(container?.offsetWidth || 600, CANVAS_MAX_WIDTH)
    const canvasHeight = container?.offsetHeight || 220
    // Ketebalan balok adaptif: membesar saat balok sedikit (gap lebar),
    // tetap ramping saat harian padat. 4 = jumlah seri arus kas.
    const groupWidth = canvasWidth / Math.max(labels.length, 1)
    const adaptiveThickness = Math.round(Math.max(12, Math.min(34, (groupWidth / 4) * 0.72)))
    canvas.width = canvasWidth
    canvas.height = canvasHeight
    canvas.style.width = canvasWidth + 'px'
    canvas.style.height = canvasHeight + 'px'

    const ctx = canvas.getContext('2d')
    if (!ctx) return

    const chrome = chartChrome()
    const chartFont = m3ChartFontFamily()
    // Y max dari seluruh label halaman aktif (satu halaman = satu layar penuh)
    const pageVals = [incomes, expenses, debts, receivables].flatMap((arr) => arr.filter((v) => v > 0))
    const initialYMax = pageVals.length ? Math.max(...pageVals) * 1.25 : 1000

    const seriesData = {
        income: incomes,
        expense: expenses,
        debt: debts,
        receivable: receivables,
    }

    charts.value['bar'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
        datasets: BAR_SERIES.map((key, idx) => ({
            label: t(`analytics.chartLabels.${key}`),
            data: seriesData[key],
            // Struktur mengikuti chart Admin: bar ramping dengan ujung
            // rounded, fill 75%, hover kembali solid.
            backgroundColor: hexToRgba(FLOW_COLORS.value[key], 0.75),
            hoverBackgroundColor: FLOW_COLORS.value[key],
            borderRadius: 6,
            maxBarThickness: adaptiveThickness,
            barPercentage: 0.9,
            categoryPercentage: 0.85,
            animation: { delay: REDUCED_MOTION ? 0 : idx * 45 },
        })),
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            animation: {
                duration: REDUCED_MOTION ? 0 : 450,
                easing: 'easeOutQuart',
            },
            plugins: {
                // Legend bawaan di bawah-tengah (gaya Admin): titik round,
                // klik untuk sembunyikan seri — tetap terhubung ke data.
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: chrome.labels,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        font: { size: 12 },
                    },
                    onClick: (_e, item) => toggleBarSeries(BAR_SERIES[item.datasetIndex]),
                },
                tooltip: {
                    ...m3TooltipOptions(chrome, { swatch: true }),
                    callbacks: {
                        label: (item) => `${item.dataset.label}: Rp ${Number(item.raw).toLocaleString('id-ID')}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: chrome.ticks,
                        font: { size: 11, weight: 'bold', family: chartFont },
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8,
                    },
                },
                y: {
                    display: true,
                    min: 0,
                    max: initialYMax,
                    grid: { display: true, color: chrome.grid },
                    border: { display: false },
                    ticks: {
                        display: true,
                        color: chrome.ticks,
                        font: { size: 11 },
                        precision: 0,
                        callback: (value) => formatCompact(value),
                    },
                },
            },
        },
    })

    applyBarVisibility()
}

const activeCategoryData = computed(() => {
    function sortDesc(items) {
        return [...items].sort((a, b) => b.total - a.total)
    }
    if (categoryView.value === 'expense') {
        const s = sortDesc(props.expensesByCategory)
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalExpense, labelName: t('analytics.totalExpense') }
    } else if (categoryView.value === 'income') {
        const s = sortDesc(props.incomesByCategory)
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalIncome, labelName: t('analytics.totalIncome') }
    } else if (categoryView.value === 'debt') {
        const s = sortDesc(props.debtsByCategory)
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: s.reduce((a, b) => a + b.total, 0), labelName: t('analytics.totalDebt') }
    } else {
        const s = sortDesc(props.receivablesByCategory)
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: s.reduce((a, b) => a + b.total, 0), labelName: t('analytics.totalReceivable') }
    }
})

// ── Daftar kategori: 5 teratas + "Lihat lainnya" (doughnut tetap semua) ──
const CAT_VISIBLE = 5
const showAllCategories = ref(false)
const visibleCategoryData = computed(() => {
    const d = activeCategoryData.value
    if (showAllCategories.value) return d
    return {
        labels: d.labels.slice(0, CAT_VISIBLE),
        values: d.values.slice(0, CAT_VISIBLE),
        ids: d.ids.slice(0, CAT_VISIBLE),
        icons: d.icons.slice(0, CAT_VISIBLE),
        total: d.total,
        labelName: d.labelName,
    }
})
const hiddenCategoryCount = computed(() =>
    Math.max(0, activeCategoryData.value.labels.length - CAT_VISIBLE),
)
const toggleAllCategories = () => { showAllCategories.value = !showAllCategories.value }

const activeColors = computed(() => {
    const base = {
        expense: () => cssVar('--m3-color-expense', '#F87171'),
        income: () => cssVar('--m3-color-income', '#4ade80'),
        debt: () => cssVar('--m3-color-debt', '#facc15'),
        receivable: () => cssVar('--m3-color-receivable', '#c084fc'),
    }[categoryView.value]()
    return buildPalette(base)
})

function mixHex(hex, weight) {
    const { r, g, b } = hexRgb(hex)
    const tone = (v) => {
        const n = weight > 0 ? Math.round(v + (255 - v) * weight) : Math.round(v * (1 + weight))
        return Math.max(0, Math.min(255, n))
    }
    return `#${[tone(r), tone(g), tone(b)].map((n) => n.toString(16).padStart(2, '0')).join('')}`
}

function buildPalette(baseHex) {
    return [mixHex(baseHex, 0.40), mixHex(baseHex, 0.22), baseHex, mixHex(baseHex, -0.15), mixHex(baseHex, -0.30), mixHex(baseHex, -0.45)]
}

const iconColorClass = computed(() => {
    const map = { income: 'Income', expense: 'Expense', debt: 'Debt', receivable: 'Receivable' }
    return getM3CategoryIconClass(map[categoryView.value])
})

const categoryColors = computed(() => {
    const data = activeCategoryData.value
    if (!data.labels.length) return []
    const palette = activeColors.value
    const sorted = data.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v)
    return data.labels.map((_, i) => {
        const rank = sorted.findIndex(s => s.i === i)
        return palette[Math.round((rank / Math.max(sorted.length - 1, 1)) * (palette.length - 1))]
    })
})

function hexRgb(hex) {
    const v = parseInt(hex.slice(1), 16)
    return { r: v >> 16, g: (v >> 8) & 255, b: v & 255 }
}

function hexToRgba(hex, alpha) {
    const { r, g, b } = hexRgb(hex)
    return `rgba(${r},${g},${b},${alpha})`
}

const renderDoughnutChart = async () => {
    const dataObj = activeCategoryData.value
    destroyChart('doughnut')
    doughnutReady.value = false

    await nextTick()

    const ctx = doughnutChartRef.value?.getContext('2d')
    if (!ctx || !dataObj.labels.length) {
        doughnutReady.value = true
        return
    }

    const chrome = chartChrome()
    const palette = activeColors.value
    const sorted = dataObj.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v)
    const paint = dataObj.labels.map((_, i) => {
        const rank = sorted.findIndex(s => s.i === i)
        return palette[Math.round((rank / Math.max(sorted.length - 1, 1)) * (palette.length - 1))]
    })

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dataObj.labels,
        datasets: [{
            data: dataObj.values,
            backgroundColor: paint,
            // Pemisah antar irisan memakai warna kartu (gaya AdminChart),
            // bukan border gelap yang menabrak di light scheme.
            borderWidth: 2,
            borderColor: chrome.surface,
            spacing: 2,
            borderRadius: 4,
            hoverOffset: 6,
        }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            animation: REDUCED_MOTION
                ? false
                : {
                    duration: 650,
                    easing: 'easeOutQuart',
                    animateRotate: true,
                    animateScale: true,
                    onComplete: () => {
                        doughnutReady.value = true
                    },
                },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...m3TooltipOptions(chrome, { swatch: true }),
                    callbacks: {
                        label: (item) => `Rp ${Number(item.raw).toLocaleString('id-ID')}`,
                    },
                },
            },
        },
    })

    chart.resize()
    charts.value['doughnut'] = chart

    if (REDUCED_MOTION) {
        doughnutReady.value = true
    }
}

const switchCategory = (type) => {
    categoryView.value = type
    renderDoughnutChart()
}

onMounted(() => {
    initCumulativeChart()
    renderBarChart(barView.value)
    renderDoughnutChart()

    nextTick(() => {
    })
})

watch(() => props.dailyLabels, () => {
    initCumulativeChart()
})
watch(() => props.cumulativeData, () => {
    initCumulativeChart()
})

// Rentang tanggal berubah (router.get Inertia) → komponen dipakai ulang.
// Inertia mempertahankan referensi prop yang isinya identik, jadi watcher
// ini hanya menyala saat data full-history benar-benar berubah; watcher
// doughnut menyala saat agregat per kategori pada rentang baru berbeda.
// start/endDate ikut dipantau karena buildBarData menyaring per rentang —
// array allDaily* bisa sama referensinya padahal rentang berubah.
watch(
    () => [
        props.allDailyDates,
        props.allDailyIncome,
        props.allDailyExpense,
        props.allDailyDebt,
        props.allDailyReceivable,
        props.startDate,
        props.endDate,
    ],
    () => {
        renderBarChart(barView.value)
    },
)

watch(
    () => [
        props.expensesByCategory,
        props.incomesByCategory,
        props.debtsByCategory,
        props.receivablesByCategory,
    ],
    () => {
        renderDoughnutChart()
    },
)

onBeforeUnmount(() => {
    clearTimeout(barScrollTimer)
    Object.keys(charts.value).forEach(destroyChart)
})
</script>

<template>
    <M3Layout :title="$t('analytics.title')">
        <Head title="Analitik" />

        <div class="m3-ana">
            <!-- Judul halaman in-content (mobile; desktop memakai app bar) -->
            <M3PageTitle class="m3-ana__head" :title="$t('analytics.title')">
                <template #actions>
                    <M3IconButton :href="route('chat.index')" :aria-label="$t('nav.chat')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z" />
                        </svg>
                    </M3IconButton>
                </template>
            </M3PageTitle>

            <!-- Bar tanggal + pemilih rentang -->
            <div class="m3-ana__datebar">
                <p class="m3-ana__range">
                    {{ $t('analytics.showingData') }}
                    <span class="m3-ana__range-dates">{{ formatDate(startDate, { day: 'numeric', month: 'long' }) }} – {{ formatDate(endDate, { day: 'numeric', month: 'long' }) }}</span>
                </p>
                <M3Button
                    variant="outlined"
                    size="sm"
                    :class="['m3-ana__date-btn', isDateFiltered ? 'm3-ana__date-btn--active' : '']"
                    :aria-label="$t('common.dateRange')"
                    @click="showDateModal = true"
                >
                    <template #leading>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </template>
                    {{ $t('common.period') }}
                </M3Button>
            </div>

            <M3DateRangeModal
                :show="showDateModal"
                :start-date="startDate"
                :end-date="endDate"
                :action="route('analytics.index')"
                @close="showDateModal = false"
            />

            <!-- KPI pemasukan/pengeluaran (anak langsung — lihat grid areas) -->

          <!-- Panel pendukung: rincian kategori — F-pattern: doughnut di band tengah (di atas fold <100rem) -->

            <!-- Kolom utama (F): KPI + grafik — satu kolom kiri di >= 100rem -->
            <div class="m3-ana__grid">
            <div class="m3-ana__left">
            <M3CashflowCard class="m3-ana__kpi m3-ana__enter m3-ana__enter--1" :income="totalIncome" :expense="totalExpense" :is-visible="true" />
          <!-- Kolom utama: grafik -->
          <div class="m3-ana__primary">
            <!-- Saldo kumulatif -->
            <M3Card ref="cumulativeCardRef" variant="filled" padding="md" class="m3-ana__enter m3-ana__enter--2">
                <div class="m3-ana__card-head m3-ana__card-head--balance">
                    <div class="m3-ana__card-titles">
                        <p class="m3-ana__card-title">{{ $t('analytics.cumulativeBalance') }}</p>
                        <p class="m3-ana__card-sub">{{ $t('analytics.cumulativeDesc') }}</p>
                    </div>
                    <p class="m3-ana__hero">Rp {{ formatNumber(cumulativeBalance) }}</p>
                </div>
                <div ref="cumulativeContainerRef" class="m3-ana__cumulative-wrap">
                    <canvas ref="cumulativeChartRef" class="m3-ana__canvas" role="img" :aria-label="$t('analytics.chartAria.cumulative')" />
                </div>
            </M3Card>

            <!-- Arus kas -->
            <M3Card variant="filled" padding="md" class="m3-ana__enter m3-ana__enter--3">
                <div class="m3-ana__card-head">
                    <p class="m3-ana__card-title">{{ $t('analytics.cashflow') }}</p>
                    <div class="m3-ana__seg" role="tablist">
                        <button
                            v-for="v in barViews"
                            :key="v.key"
                            type="button"
                            role="tab"
                            :aria-selected="barView === v.key"
                            :class="['m3-ana__seg-btn', barView === v.key ? 'm3-ana__seg-btn--active' : '']"
                            @click="renderBarChart(v.key)"
                        >{{ v.label() }}</button>
                    </div>
                </div>
                <div class="m3-ana__bar-box">
                    <canvas ref="barChartRef" :key="barChartKey" class="m3-ana__canvas" role="img" :aria-label="$t('analytics.chartAria.cashflow')" />
                </div>
                <div v-if="barPager.count > 1" class="m3-ana__pager">
                    <M3IconButton
                        variant="tonal"
                        :aria-label="$t('analytics.chartPrevPage')"
                        :disabled="barPager.page >= barPager.count - 1"
                        @click="goOlderBarPage"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                        </svg>
                    </M3IconButton>
                    <span class="m3-ana__pager-label">{{ $t('analytics.chartPage', { current: barPager.page + 1, total: barPager.count }) }}</span>
                    <M3IconButton
                        variant="tonal"
                        :aria-label="$t('analytics.chartNextPage')"
                        :disabled="barPager.page <= 0"
                        @click="goNewerBarPage"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                        </svg>
                    </M3IconButton>
                </div>
            </M3Card>
            </div>
            </div>

            <!-- Panel pendukung: rincian kategori — kolom kanan >= 100rem, seksi bawah < 100rem -->
          <aside class="m3-ana__aside" :aria-label="$t('analytics.categoryBreakdown')">
            <!-- Rincian kategori -->
            <h2 class="m3-ana__section-title">{{ $t('analytics.categoryBreakdown') }}</h2>
            <div class="m3-ana__seg m3-ana__seg--block" role="tablist">
                <button
                    v-for="c in categoryViews"
                    :key="c.key"
                    type="button"
                    role="tab"
                    :aria-selected="categoryView === c.key"
                    :class="['m3-ana__seg-btn', categoryView === c.key ? 'm3-ana__seg-btn--active' : '']"
                    @click="switchCategory(c.key)"
                >{{ c.label() }}</button>
            </div>

            <M3Card variant="filled" padding="md" class="m3-ana__enter m3-ana__enter--4">
                <div v-if="doughnutReady && !activeCategoryData.labels.length" class="m3-ana__empty">
                    <span class="m3-ana__empty-icon" aria-hidden="true">
                        <InboxIcon :stroke-width="1.5" />
                    </span>
                    <p class="m3-ana__empty-text">{{ $t('analytics.noData') }}</p>
                </div>
                <div v-show="!doughnutReady || activeCategoryData.labels.length">
                    <div class="m3-ana__doughnut-wrap">
                        <div v-if="!doughnutReady" class="m3-ana__spinner" role="status" :aria-label="$t('common.loading')">
                            <M3CircularProgress size="lg" />
                        </div>
                        <canvas ref="doughnutChartRef" class="m3-ana__canvas m3-ana__canvas--front" role="img" :aria-label="$t('analytics.chartAria.breakdown')" />
                        <div v-if="doughnutReady && activeCategoryData.labels.length" class="m3-ana__doughnut-center" aria-hidden="true">
                            <span class="m3-ana__doughnut-label">{{ activeCategoryData.labelName }}</span>
                            <span class="m3-ana__doughnut-total">Rp {{ formatNumber(activeCategoryData.total) }}</span>
                        </div>
                    </div>
                    <div v-if="doughnutReady && activeCategoryData.labels.length" class="m3-ana__catlist">
                        <template v-for="(label, i) in visibleCategoryData.labels" :key="i">
                            <Link
                                v-if="visibleCategoryData.ids[i] != null"
                                :href="route('categories.show', { category: visibleCategoryData.ids[i], start_date: startDate, end_date: endDate })"
                                class="m3-ana__cat"
                            >
                                <span class="m3-ana__cat-bar" :style="{ backgroundColor: categoryColors[i] }" aria-hidden="true" />
                                <AppIcon :icon="visibleCategoryData.icons[i]" :class="['m3-ana__cat-icon', iconColorClass]" />
                                <span class="m3-ana__cat-main">
                                    <span class="m3-ana__cat-name">{{ label }}</span>
                                    <span class="m3-ana__cat-pct">{{ visibleCategoryData.total > 0 ? ((visibleCategoryData.values[i] / visibleCategoryData.total) * 100).toFixed(1) : 0 }}%</span>
                                </span>
                                <span class="m3-ana__cat-amount">Rp {{ formatNumber(visibleCategoryData.values[i]) }}</span>
                            </Link>
                            <div v-else class="m3-ana__cat">
                                <span class="m3-ana__cat-bar" :style="{ backgroundColor: categoryColors[i] }" aria-hidden="true" />
                                <AppIcon :icon="visibleCategoryData.icons[i]" :class="['m3-ana__cat-icon', iconColorClass]" />
                                <span class="m3-ana__cat-main">
                                    <span class="m3-ana__cat-name">{{ label }}</span>
                                    <span class="m3-ana__cat-pct">{{ visibleCategoryData.total > 0 ? ((visibleCategoryData.values[i] / visibleCategoryData.total) * 100).toFixed(1) : 0 }}%</span>
                                </span>
                                <span class="m3-ana__cat-amount">Rp {{ formatNumber(visibleCategoryData.values[i]) }}</span>
                            </div>
                        </template>
                    </div>
                </div>
                <M3Button
                    v-if="hiddenCategoryCount > 0 || showAllCategories"
                    variant="text"
                    size="sm"
                    class="m3-ana__cat-toggle"
                    @click="toggleAllCategories"
                >
                    {{ showAllCategories ? t('analytics.showLessCats') : t('analytics.showMoreCats', { n: hiddenCategoryCount }) }}
                </M3Button>
            </M3Card>
          </aside>
            </div>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-ana {
    --m3-ana-gap: var(--m3-space-4);
    --m3-ana-grid-gap: var(--m3-space-6);
    --m3-ana-doughnut-height: 12rem;
    display: flex;
    flex-direction: column;
    gap: var(--m3-ana-gap);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-density-page-inset, var(--m3-space-4));
}

/* Kolom utama (KPI + grafik). Wrapper tetap berperilaku seperti
   anak langsung .m3-ana di bawah breakpoint list-detail. */
.m3-ana__primary {
    display: flex;
    flex-direction: column;
    gap: var(--m3-ana-gap);
    min-width: 0;
}

/* Panel pendukung (rincian kategori). Di bawah 100rem tetap tampil
   sebagai seksi normal di alur halaman (jangan display:none — doughnut
   pernah hilang di mobile karena itu); >= 100rem menjadi panel samping. */
.m3-ana__aside {
    display: flex;
    flex-direction: column;
    gap: var(--m3-ana-gap);
    min-width: 0;
}

/* Kolom kiri (F): KPI + grafik — jadi satu kolom kiri di >= 100rem */
.m3-ana__left {
    display: flex;
    flex-direction: column;
    gap: var(--m3-ana-gap);
    min-width: 0;
}

/* Dua kolom di layar lebar: charts kiri, breakdown kanan (sticky).
   Head & datebar tetap di luar grid — full width. */
.m3-ana__grid {
    display: flex;
    flex-direction: column;
    gap: var(--m3-ana-gap);
    min-width: 0;
}

@media (min-width: 96rem) {
    .m3-ana {
        --m3-ana-gap: var(--m3-space-3);
        --m3-ana-grid-gap: var(--m3-space-4);
        --m3-ana-doughnut-height: 9.5rem;
        --m3-density-page-inset: var(--m3-space-3);
        --m3-density-card-padding-sm: var(--m3-space-2-5);
        --m3-density-card-padding-md: var(--m3-space-3);
        --m3-density-card-padding-lg: var(--m3-space-5);
        --m3-density-chart-primary-height: 11rem;
        --m3-density-chart-summary-height: 6rem;
        max-width: none;
    }

    .m3-ana__grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 28rem;
        gap: var(--m3-ana-grid-gap);
        align-items: start;
        justify-content: center;
        max-width: calc(var(--m3-size-content-max) + 28rem + var(--m3-ana-grid-gap));
    }

    .m3-ana__left {
        grid-column: 1;
    }

    .m3-ana__aside {
        grid-column: 2;
        position: sticky;
        top: calc(var(--m3-size-appbar) + var(--m3-space-4));
        max-height: calc(100dvh - var(--m3-size-appbar) - var(--m3-space-8));
        overflow-y: auto;
    }
}

/* ── Bar tanggal ── */
.m3-ana__datebar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-ana__range {
    margin: 0;
    min-width: 0;
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-ana__range-dates {
    color: var(--m3-color-on-surface-variant);
    font-weight: var(--m3-type-body-small-weight);
}

/* Tombol rentang tidak boleh menyusut (label pernah terpotong di locale ID);
   teks tanggal di sebelah kiri yang melakukan ellipsis. */
.m3-ana__date-btn {
    flex-shrink: 0;
}

/* Trigger rentang tanggal saat filter aktif (paritas dot DateModal Default) */
.m3-ana__date-btn--active {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    border-color: transparent;
}

/* ── Kepala kartu grafik ── */
.m3-ana__card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--m3-space-3);
    margin-bottom: var(--m3-space-3);
}

.m3-ana__card-titles {
    min-width: 0;
}

.m3-ana__card-head--balance {
    flex-wrap: wrap;
}

.m3-ana__card-head--balance .m3-ana__card-titles {
    flex: 1 1 8rem;
}

.m3-ana__card-title {
    margin: 0 0 var(--m3-space-1);
    font-size: var(--m3-type-title-small-size);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-ana__card-sub {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-body-small-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-ana__hero {
    margin: 0;
    flex-shrink: 0;
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    letter-spacing: var(--m3-type-headline-small-tracking);
    color: var(--m3-color-on-surface);
    max-width: 100%;
    overflow-wrap: anywhere;
    text-align: right;
    white-space: normal;
}

/* ── Segmented control (M3 connected button group pattern) ── */
.m3-ana__seg {
    display: flex;
    gap: var(--m3-space-1);
    padding: var(--m3-space-1);
    border-radius: var(--m3-radius-full);
    border: 1px solid var(--m3-color-outline-variant);
    background: var(--m3-color-surface-container-low);
    flex-shrink: 0;
}

.m3-ana__seg--block {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}

.m3-ana__seg-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--m3-space-1-5) var(--m3-space-2-5);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-ana__seg-btn::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: currentColor;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-ana__seg-btn:hover::after {
    opacity: 0.08;
}

.m3-ana__seg-btn:active::after {
    opacity: 0.12;
}

.m3-ana__seg-btn--active {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
}

.m3-ana__seg-btn:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

/* Legenda bar memakai legend bawaan Chart.js (bawah-tengah, titik round,
   klik untuk toggle seri) — gaya chart Admin. Markup custom dihapus. */

.m3-ana__bar-box {
    height: var(--m3-density-chart-primary-height, 13.75rem);
}

/* ── Navigasi halaman chart arus kas ── */
.m3-ana__pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-2);
}

.m3-ana__pager-label {
    min-width: 4.5rem;
    text-align: center;
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-label-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-ana__cumulative-wrap {
    width: 100%;
    /* 11rem: sumbu Y/X kini terlihat (model AdminChart) — butuh ruang vertikal */
    height: var(--m3-density-chart-summary-height, 11rem);
}

.m3-ana__canvas {
    display: block;
    width: 100%;
    height: 100%;
}

/* ── Judul seksi ── */
.m3-ana__section-title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
    color: var(--m3-color-on-surface);
}

/* ── Doughnut ── */
.m3-ana__doughnut-wrap {
    position: relative;
    width: 100%;
    height: var(--m3-ana-doughnut-height);
    margin-bottom: var(--m3-ana-gap);
}

.m3-ana__canvas--front {
    position: relative;
    z-index: 10;
}

.m3-ana__spinner {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--m3-color-primary);
}

.m3-ana__doughnut-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    width: 100px;
    height: 100px;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-low);
    border: 1px solid var(--m3-color-outline-variant);
    padding: var(--m3-space-1);
    pointer-events: none;
}

.m3-ana__doughnut-label {
    font-size: var(--m3-type-label-small-size);
    font-weight: var(--m3-type-label-small-weight);
    color: var(--m3-color-on-surface-variant);
    margin-bottom: var(--m3-space-1);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.m3-ana__doughnut-total {
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
    line-height: var(--m3-type-body-medium-line-height);
    overflow-wrap: anywhere;
}

/* ── Daftar kategori (baris polos ber-divider) ── */
.m3-ana__catlist {
    display: flex;
    flex-direction: column;
}

.m3-ana__cat-toggle {
    align-self: center;
    margin-top: var(--m3-space-2);
}

.m3-ana__cat {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-3) 0;
    border-bottom: 1px solid var(--m3-color-outline-variant);
    text-decoration: none;
    color: var(--m3-color-on-surface);
}

.m3-ana__cat:last-child {
    border-bottom: none;
}

.m3-ana__cat-bar {
    width: var(--m3-space-1);
    align-self: stretch;
    min-height: var(--m3-space-5);
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-ana__cat-icon {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    flex-shrink: 0;
}

.m3-ana__cat-main {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    line-height: var(--m3-type-body-medium-line-height);
}

.m3-ana__cat-name {
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-ana__cat-pct {
    font-size: var(--m3-type-body-small-size);
    font-weight: var(--m3-type-body-small-weight);
    color: var(--m3-color-on-surface-variant);
}

.m3-ana__cat-amount {
    flex-shrink: 0;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 45%;
    text-align: right;
}

@media (min-width: 96rem) {
    .m3-ana__card-head {
        margin-bottom: var(--m3-space-2-5);
    }

    .m3-ana__cat {
        padding: var(--m3-space-2-5) 0;
    }
}

/* ── Empty state ── */
.m3-ana__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--m3-space-6) 0;
}

.m3-ana__empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    border: 1px solid var(--m3-color-outline-variant);
    color: var(--m3-color-on-surface-variant);
    margin-bottom: var(--m3-space-3);
}

.m3-ana__empty-icon :deep(svg) {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-ana__empty-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    color: var(--m3-color-on-surface-variant);
}

/* ── Animasi masuk kartu (staggered fade-in-up, design.md §12.1) ── */
.m3-ana__enter {
    animation: m3-ana-rise var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial) both;
}

.m3-ana__enter--1 { animation-delay: 0ms; }
.m3-ana__enter--2 { animation-delay: 75ms; }
.m3-ana__enter--3 { animation-delay: 150ms; }
.m3-ana__enter--4 { animation-delay: 225ms; }

@keyframes m3-ana-rise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-ana__enter {
        animation: none;
    }
}
</style>
