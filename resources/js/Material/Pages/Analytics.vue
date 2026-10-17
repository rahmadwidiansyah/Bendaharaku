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
 * - DateModal dipakai ulang apa adanya (styling Default, fungsional identik
 *   — preseden sama seperti TransactionDetailModal di Dashboard M3).
 * - Animasi chart dimatikan bila prefers-reduced-motion aktif.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3CashflowCard from '@/Material/Pages/Dashboard/M3CashflowCard.vue'
import DateModal from '@/Components/DateModal.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref, shallowRef, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
import { formatNumber, formatDate, parseDateOnly } from '@/utils/format.js'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
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
const barView = ref('harian')
const categoryView = ref('expense')
const barChartRef = ref(null)
const barScrollRef = ref(null)
const cumulativeChartRef = ref(null)
const cumulativeContainerRef = ref(null)
const cumulativeCardRef = ref(null)
const doughnutChartRef = ref(null)
const barChartKey = ref(0)
const doughnutReady = ref(false)

// Hormati prefers-reduced-motion: matikan durasi animasi Chart.js.
const REDUCED_MOTION = typeof window !== 'undefined'
    && typeof window.matchMedia === 'function'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const barChartData = ref({ labels: [], incomes: [], expenses: [], debts: [], receivables: [] })

const cssVar = (name, fallback) =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback

const isLightScheme = () => document.documentElement.classList.contains('light')

// Warna seri dari token M3 (hue semantik sama dengan Default).
const FLOW_COLORS = computed(() => ({
    income: cssVar('--m3-color-income', '#4ade80'),
    expense: cssVar('--m3-color-expense', '#f87171'),
    debt: cssVar('--m3-color-debt', '#facc15'),
    receivable: cssVar('--m3-color-receivable', '#c084fc'),
}))

// Chrome chart yang sadar skema light/dark.
const chartChrome = () => {
    const light = isLightScheme()
    return {
        grid: light ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)',
        ticks: cssVar('--m3-color-on-surface-variant', '#9CA3AF'),
        labels: cssVar('--m3-color-on-surface-variant', '#D1D5DB'),
        tooltipBg: light ? 'rgba(255,255,255,0.97)' : 'rgba(18,18,18,0.95)',
        tooltipTitle: light ? '#44474e' : '#9CA3AF',
        tooltipBody: light ? '#1a1c1e' : '#ffffff',
        surface: cssVar('--m3-color-surface', '#1a1c1e'),
        brand: cssVar('--m3-color-primary', cssVar('--color-brand', '#a855f7')),
    }
}

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

            // Gunakan full history agar selalu ada cukup titik untuk garis;
            // ambil 7 titik terakhir dari seluruh riwayat transaksi.
            let visibleLabels, visibleData
            const allDates = props.allDailyDates || []

            if (allDates.length >= 2) {
                let running = 0
                const allCumulative = allDates.map((_, i) => {
                    const inc = Math.abs(props.allDailyIncome?.[i] || 0)
                    const exp = Math.abs(props.allDailyExpense?.[i] || 0)
                    const debt = props.allDailyDebt?.[i] || 0
                    const rec = props.allDailyReceivable?.[i] || 0
                    running += inc - exp + debt - rec
                    return running
                })

                const take = Math.min(7, allDates.length)
                visibleLabels = allDates.slice(-take).map(d =>
                    formatDate(d, { day: '2-digit', month: 'short' }),
                )
                visibleData = allCumulative.slice(-take)
            } else {
                visibleLabels = props.dailyLabels.slice(-4)
                visibleData = props.cumulativeData.slice(-4)
            }

            const grad = ctx.createLinearGradient(0, 0, 0, h)
            grad.addColorStop(0, hexToRgba(brand, 0.35))
            grad.addColorStop(1, hexToRgba(brand, 0))

            const minVal = Math.min(...visibleData)
            const maxVal = Math.max(...visibleData)
            const range = maxVal - minVal || Math.abs(minVal) * 0.2 || 1000
            const yPad = range * 0.15

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: visibleLabels,
                    datasets: [{
                        data: visibleData,
                        borderColor: brand,
                        borderWidth: 2.5,
                        backgroundColor: grad,
                        fill: true,
                        tension: 0.4,
                        pointRadius: visibleData.length === 1 ? 8 : 3,
                        pointHoverRadius: 9,
                        pointBackgroundColor: brand,
                        pointBorderColor: chrome.surface,
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: brand,
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                        spanGaps: true,
                    }],
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    animation: { duration: REDUCED_MOTION ? 0 : 600 },
                    layout: { padding: { left: 4, right: 4, top: 10, bottom: 4 } },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            backgroundColor: chrome.tooltipBg,
                            titleColor: chrome.tooltipTitle,
                            titleFont: { size: 11, weight: 'bold' },
                            bodyColor: chrome.tooltipBody,
                            bodyFont: { size: 13, weight: 'bold' },
                            padding: { x: 12, y: 8 },
                            cornerRadius: 8,
                            displayColors: false,
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
                        x: { display: false, offset: false, bounds: 'data' },
                        y: { display: false, min: minVal - yPad, max: maxVal + yPad },
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
    if (n >= 1e9) return (n / 1e9).toFixed(1) + 'B'
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M'
    if (n >= 1e3) return (n / 1e3).toFixed(0) + 'K'
    return n.toString()
}

function buildBarData(view) {
    const dates = props.allDailyDates || []
    const labels = [], incomes = [], expenses = [], debts = [], receivables = []

    function pushNonEmpty(l, i, e, d, r) {
        if (i || e || d || r) {
            labels.push(l); incomes.push(i); expenses.push(e); debts.push(d); receivables.push(r)
        }
    }

    if (view === 'harian') {
        dates.forEach((date, i) => {
            const inc = Math.abs(props.allDailyIncome[i] || 0)
            const exp = Math.abs(props.allDailyExpense[i] || 0)
            const debt = Math.abs(props.allDailyDebt[i] || 0)
            const rec = Math.abs(props.allDailyReceivable[i] || 0)
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
            bucket.inc += Math.abs(props.allDailyIncome[i] || 0)
            bucket.exp += Math.abs(props.allDailyExpense[i] || 0)
            bucket.debt += Math.abs(props.allDailyDebt[i] || 0)
            bucket.rec += Math.abs(props.allDailyReceivable[i] || 0)
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
        m.inc += Math.abs(props.allDailyIncome[i] || 0)
        m.exp += Math.abs(props.allDailyExpense[i] || 0)
        m.debt += Math.abs(props.allDailyDebt[i] || 0)
        m.rec += Math.abs(props.allDailyReceivable[i] || 0)
    })

    Array.from(monthMap.values()).forEach((m) => {
        pushNonEmpty(m.label, m.inc, m.exp, m.debt, m.rec)
    })

    return { labels, incomes, expenses, debts, receivables }
}

function getVisibleIndices(scrollEl, chart) {
    if (!scrollEl || !chart) return null
    const scrollLeft = scrollEl.scrollLeft
    const containerWidth = scrollEl.clientWidth
    const scrollRight = scrollLeft + containerWidth

    const meta = chart.getDatasetMeta(0)
    if (!meta?.data?.length) return null

    const visible = []
    meta.data.forEach((el, i) => {
        if (el.x >= scrollLeft && el.x <= scrollRight) {
            visible.push(i)
        }
    })
    return visible.length ? visible : null
}

function updateBarYScale() {
    const chart = charts.value['bar']
    const scrollEl = barScrollRef.value
    if (!chart || !scrollEl) return

    const visibleIdx = getVisibleIndices(scrollEl, chart)
    if (!visibleIdx) return

    const { incomes, expenses, debts, receivables } = barChartData.value
    const vals = visibleIdx.flatMap(i => [
        incomes[i] || 0,
        expenses[i] || 0,
        debts[i] || 0,
        receivables[i] || 0,
    ]).filter(v => v > 0)

    const newMax = vals.length ? Math.max(...vals) * 1.25 : 1000
    if (chart.options.scales.y.max === newMax) return

    chart.options.scales.y.max = newMax
    chart.update('none')
}

let barScrollTimer = null

function onBarScroll() {
    clearTimeout(barScrollTimer)
    barScrollTimer = setTimeout(() => {
        updateBarYScale()
    }, 120)
}

const renderBarChart = async (view) => {
    barView.value = view
    const { labels, incomes, expenses, debts, receivables } = buildBarData(view)

    barChartData.value = { labels, incomes, expenses, debts, receivables }

    destroyChart('bar')
    barChartKey.value++
    await nextTick()

    const canvas = barChartRef.value
    if (!canvas) return

    const container = canvas.parentElement
    const canvasWidth = container?.offsetWidth || Math.max(labels.length * 80, 300)
    const canvasHeight = container?.offsetHeight || 220
    canvas.width = canvasWidth
    canvas.height = canvasHeight
    canvas.style.width = canvasWidth + 'px'
    canvas.style.height = canvasHeight + 'px'

    const ctx = canvas.getContext('2d')
    if (!ctx) return

    const chrome = chartChrome()
    const last4Idx = labels.map((_, i) => i).slice(-4)
    const initialVals = last4Idx.flatMap(i => [incomes[i] || 0, expenses[i] || 0, debts[i] || 0, receivables[i] || 0]).filter(v => v > 0)
    const initialYMax = initialVals.length ? Math.max(...initialVals) * 1.25 : 1000

    charts.value['bar'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: t('analytics.chartLabels.income'), data: incomes, backgroundColor: FLOW_COLORS.value.income, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.expense'), data: expenses, backgroundColor: FLOW_COLORS.value.expense, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.debt'), data: debts, backgroundColor: FLOW_COLORS.value.debt, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.receivable'), data: receivables, backgroundColor: FLOW_COLORS.value.receivable, borderRadius: 5, barPercentage: 0.9, categoryPercentage: 0.85 },
            ],
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            animation: { duration: REDUCED_MOTION ? 0 : 400 },
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: chrome.ticks, font: { size: 11, weight: 'bold' }, maxRotation: 0 },
                },
                y: {
                    display: true,
                    min: 0,
                    max: initialYMax,
                    grid: { display: true, color: chrome.grid, drawBorder: false },
                    border: { display: false, dash: [4, 4] },
                    ticks: { display: false },
                },
            },
        },
        plugins: [{
            id: 'barLabels',
            afterDraw(chart) {
                const ctx2 = chart.ctx
                const isMobile = typeof window !== 'undefined' && window.innerWidth < 640
                const fontSize = isMobile ? 9 : 10
                const placedLabels = []

                ctx2.font = `bold ${fontSize}px sans-serif`
                ctx2.fillStyle = chrome.labels
                ctx2.textAlign = 'center'
                if (!isLightScheme()) {
                    ctx2.shadowColor = 'rgba(0, 0, 0, 0.5)'
                    ctx2.shadowBlur = 2
                    ctx2.shadowOffsetY = 1
                }

                chart.data.datasets.forEach((ds, dsIndex) => {
                    const meta = chart.getDatasetMeta(dsIndex)
                    meta.data.forEach((el, i) => {
                        const val = Number(ds.data[i])
                        if (!val || val <= 0) return

                        const text = formatCompact(val)
                        const textWidth = ctx2.measureText(text).width
                        const textHeight = fontSize

                        const positions = [
                            { x: el.x, y: el.y - 4, baseline: 'bottom' },
                            { x: el.x, y: el.y - 12, baseline: 'bottom' },
                            { x: el.x, y: el.y - 20, baseline: 'bottom' },
                            { x: el.x, y: el.y + 12, baseline: 'top' },
                        ]

                        for (const pos of positions) {
                            const labelBounds = {
                                x: pos.x - textWidth / 2 - 2,
                                y: pos.baseline === 'bottom' ? pos.y - textHeight - 2 : pos.y,
                                width: textWidth + 4,
                                height: textHeight + 4,
                            }

                            const hasCollision = placedLabels.some(placed => !(
                                labelBounds.x + labelBounds.width < placed.x
                                || labelBounds.x > placed.x + placed.width
                                || labelBounds.y + labelBounds.height < placed.y
                                || labelBounds.y > placed.y + placed.height
                            ))

                            if (!hasCollision && pos.y > 10 && pos.y < chart.height - 10) {
                                ctx2.textBaseline = pos.baseline
                                ctx2.fillText(text, pos.x, pos.y)
                                placedLabels.push(labelBounds)
                                break
                            }
                        }
                    })
                })

                ctx2.shadowColor = 'transparent'
            },
        }],
    })

    await nextTick()
    if (barScrollRef.value) {
        barScrollRef.value.scrollLeft = barScrollRef.value.scrollWidth
        setTimeout(() => updateBarYScale(), 50)
    }
}

const chartMinWidth = computed(() => {
    const { labels } = buildBarData(barView.value)
    const minWidthPerLabel = 80
    return Math.max(labels.length * minWidthPerLabel, 300)
})

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

const activeColors = computed(() => {
    const base = {
        expense: () => cssVar('--m3-color-expense', '#F87171'),
        income: () => cssVar('--m3-color-income', '#4ade80'),
        debt: () => cssVar('--m3-color-debt', '#facc15'),
        receivable: () => cssVar('--m3-color-receivable', '#c084fc'),
    }[categoryView.value]()
    return buildPalette(base)
})

function buildPalette(baseHex) {
    const mix = (hex, weight) => {
        const { r, g, b } = hexRgb(hex)
        const tone = (v) => {
            const n = weight > 0 ? Math.round(v + (255 - v) * weight) : Math.round(v * (1 + weight))
            return Math.max(0, Math.min(255, n))
        }
        return `#${[tone(r), tone(g), tone(b)].map((n) => n.toString(16).padStart(2, '0')).join('')}`
    }
    return [mix(baseHex, 0.40), mix(baseHex, 0.22), baseHex, mix(baseHex, -0.15), mix(baseHex, -0.30), mix(baseHex, -0.45)]
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

function applyGradients(chart, colors, labels) {
    if (!chart) return

    const cvs = chart.canvas
    if (!cvs || !cvs.width || !cvs.height) {
        if (cvs) chart.update('none')
        return
    }

    const cx = cvs.width / 2
    const cy = cvs.height / 2
    const outerR = Math.min(cvs.width, cvs.height) / 2
    const innerR = outerR * 0.7
    const ctx = cvs.getContext('2d')

    chart.data.datasets[0].backgroundColor = labels.map((_, i) => {
        const c = colors[i % colors.length]
        const rgb = hexRgb(c)
        const g = ctx.createRadialGradient(cx, cy, innerR, cx, cy, outerR)
        g.addColorStop(0, `rgba(${rgb.r},${rgb.g},${rgb.b},0.35)`)
        g.addColorStop(1, c)
        return g
    })
    chart.update('none')
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
                borderWidth: 2,
                borderColor: chrome.surface,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            animation: REDUCED_MOTION
                ? false
                : {
                    duration: 700,
                    easing: 'easeOutQuart',
                    animateRotate: true,
                    animateScale: false,
                    onComplete: () => {
                        doughnutReady.value = true
                    },
                },
            plugins: { legend: { display: false } },
            onResize: (chart) => {
                const d = activeCategoryData.value
                const p = activeColors.value
                const s = d.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v)
                const paint2 = d.labels.map((_, i) => {
                    const rank = s.findIndex(x => x.i === i)
                    return p[Math.round((rank / Math.max(s.length - 1, 1)) * (p.length - 1))]
                })
                applyGradients(chart, paint2, d.labels)
            },
        },
    })

    chart.resize()
    charts.value['doughnut'] = chart

    if (REDUCED_MOTION) {
        applyGradients(chart, paint, dataObj.labels)
        doughnutReady.value = true
    } else {
        requestAnimationFrame(() => applyGradients(chart, paint, dataObj.labels))
    }
}

const switchCategory = (type) => {
    categoryView.value = type
    renderDoughnutChart()
}

onMounted(() => {
    initCumulativeChart()
    renderBarChart('harian')
    renderDoughnutChart()

    nextTick(() => {
        if (barScrollRef.value) {
            barScrollRef.value.addEventListener('scroll', onBarScroll, { passive: true })
        }
    })
})

watch(() => props.dailyLabels, () => {
    initCumulativeChart()
})
watch(() => props.cumulativeData, () => {
    initCumulativeChart()
})

onBeforeUnmount(() => {
    clearTimeout(barScrollTimer)
    if (barScrollRef.value) {
        barScrollRef.value.removeEventListener('scroll', onBarScroll)
    }
    Object.keys(charts.value).forEach(destroyChart)
})
</script>

<template>
    <M3Layout :title="$t('analytics.title')">
        <Head title="Analitik" />

        <div class="m3-ana">
            <!-- Bar tanggal + pemilih rentang -->
            <div class="m3-ana__datebar">
                <p class="m3-ana__range">
                    {{ $t('analytics.showingData') }}
                    <span class="m3-ana__range-dates">{{ formatDate(startDate, { day: 'numeric', month: 'long' }) }} – {{ formatDate(endDate, { day: 'numeric', month: 'long' }) }}</span>
                </p>
                <DateModal :action="route('analytics.index')" :start-date="startDate" :end-date="endDate" />
            </div>

            <!-- KPI pemasukan/pengeluaran -->
            <M3CashflowCard :income="totalIncome" :expense="totalExpense" :is-visible="true" />

            <!-- Saldo kumulatif -->
            <M3Card ref="cumulativeCardRef" variant="filled" padding="md">
                <div class="m3-ana__card-head">
                    <div class="m3-ana__card-titles">
                        <p class="m3-ana__card-title">{{ $t('analytics.cumulativeBalance') }}</p>
                        <p class="m3-ana__card-sub">{{ $t('analytics.cumulativeDesc') }}</p>
                    </div>
                    <p class="m3-ana__hero">Rp {{ formatNumber(cumulativeBalance) }}</p>
                </div>
                <div ref="cumulativeContainerRef" class="m3-ana__cumulative-wrap">
                    <canvas ref="cumulativeChartRef" class="m3-ana__canvas" />
                </div>
            </M3Card>

            <!-- Arus kas -->
            <M3Card variant="filled" padding="md">
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
                <div class="m3-ana__legend">
                    <span v-for="f in ['income', 'expense', 'debt', 'receivable']" :key="f" class="m3-ana__legend-item">
                        <span class="m3-ana__legend-dot" :style="{ backgroundColor: FLOW_COLORS[f] }" aria-hidden="true" />
                        <span class="m3-ana__legend-label">{{ $t(`analytics.chartLabels.${f}`) }}</span>
                    </span>
                </div>
                <div ref="barScrollRef" class="m3-ana__scroll">
                    <div class="m3-ana__bar-box" :style="{ minWidth: chartMinWidth + 'px' }">
                        <canvas ref="barChartRef" :key="barChartKey" class="m3-ana__canvas" />
                    </div>
                </div>
            </M3Card>

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

            <M3Card variant="filled" padding="md">
                <div v-if="doughnutReady && !activeCategoryData.labels.length" class="m3-ana__empty">
                    <span class="m3-ana__empty-icon" aria-hidden="true">
                        <InboxIcon :stroke-width="1.5" />
                    </span>
                    <p class="m3-ana__empty-text">{{ $t('analytics.noData') }}</p>
                </div>
                <div v-show="!doughnutReady || activeCategoryData.labels.length">
                    <div class="m3-ana__doughnut-wrap">
                        <div v-if="!doughnutReady" class="m3-ana__spinner" role="status" aria-label="Memuat">
                            <span class="m3-ana__spinner-ring" aria-hidden="true" />
                        </div>
                        <canvas ref="doughnutChartRef" class="m3-ana__canvas m3-ana__canvas--front" />
                        <div v-if="doughnutReady && activeCategoryData.labels.length" class="m3-ana__doughnut-center" aria-hidden="true">
                            <span class="m3-ana__doughnut-label">{{ activeCategoryData.labelName }}</span>
                            <span class="m3-ana__doughnut-total">Rp {{ formatNumber(activeCategoryData.total) }}</span>
                        </div>
                    </div>
                    <div v-if="doughnutReady && activeCategoryData.labels.length" class="m3-ana__catlist">
                        <template v-for="(label, i) in activeCategoryData.labels" :key="i">
                            <Link
                                v-if="activeCategoryData.ids[i] != null"
                                :href="route('categories.show', { category: activeCategoryData.ids[i], start_date: startDate, end_date: endDate })"
                                class="m3-ana__cat"
                            >
                                <span class="m3-ana__cat-bar" :style="{ backgroundColor: categoryColors[i] }" aria-hidden="true" />
                                <AppIcon :icon="activeCategoryData.icons[i]" :class="['m3-ana__cat-icon', iconColorClass]" />
                                <span class="m3-ana__cat-main">
                                    <span class="m3-ana__cat-name">{{ label }}</span>
                                    <span class="m3-ana__cat-pct">{{ activeCategoryData.total > 0 ? ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0 }}%</span>
                                </span>
                                <span class="m3-ana__cat-amount">Rp {{ formatNumber(activeCategoryData.values[i]) }}</span>
                            </Link>
                            <div v-else class="m3-ana__cat">
                                <span class="m3-ana__cat-bar" :style="{ backgroundColor: categoryColors[i] }" aria-hidden="true" />
                                <AppIcon :icon="activeCategoryData.icons[i]" :class="['m3-ana__cat-icon', iconColorClass]" />
                                <span class="m3-ana__cat-main">
                                    <span class="m3-ana__cat-name">{{ label }}</span>
                                    <span class="m3-ana__cat-pct">{{ activeCategoryData.total > 0 ? ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0 }}%</span>
                                </span>
                                <span class="m3-ana__cat-amount">Rp {{ formatNumber(activeCategoryData.values[i]) }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </M3Card>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-ana {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    width: 100%;
    max-width: 72rem;
    margin: 0 auto;
    padding: var(--m3-space-4) var(--m3-space-4) var(--m3-space-4);
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
    font-weight: 500;
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-ana__range-dates {
    color: var(--m3-color-on-surface-variant);
    font-weight: 400;
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

.m3-ana__card-title {
    margin: 0 0 var(--m3-space-1);
    font-size: var(--m3-type-title-small-size);
    font-weight: 600;
    color: var(--m3-color-on-surface);
}

.m3-ana__card-sub {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    font-weight: 400;
    color: var(--m3-color-on-surface-variant);
}

.m3-ana__hero {
    margin: 0;
    flex-shrink: 0;
    font-size: 1.5rem;
    line-height: 2rem;
    font-weight: 600;
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 55%;
    text-align: right;
}

/* ── Segmented control (M3 connected button group pattern) ── */
.m3-ana__seg {
    display: flex;
    gap: 0.25rem;
    padding: 0.25rem;
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
    padding: 0.375rem 0.625rem;
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

/* ── Legenda ── */
.m3-ana__legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--m3-space-1) var(--m3-space-4);
    margin-bottom: var(--m3-space-3);
}

.m3-ana__legend-item {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-ana__legend-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-ana__legend-label {
    font-size: var(--m3-type-label-small-size);
    font-weight: 500;
    color: var(--m3-color-on-surface-variant);
}

/* ── Area chart ── */
.m3-ana__scroll {
    overflow-x: auto;
}

.m3-ana__scroll::-webkit-scrollbar {
    height: 6px;
}

.m3-ana__scroll::-webkit-scrollbar-track {
    background: transparent;
}

.m3-ana__scroll::-webkit-scrollbar-thumb {
    background: var(--m3-color-outline-variant);
    border-radius: 3px;
}

.m3-ana__bar-box {
    height: 220px;
}

.m3-ana__cumulative-wrap {
    width: 100%;
    height: 120px;
}

.m3-ana__canvas {
    display: block;
    width: 100%;
    height: 100%;
}

/* ── Judul seksi ── */
.m3-ana__section-title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    font-weight: 600;
    color: var(--m3-color-on-surface);
}

/* ── Doughnut ── */
.m3-ana__doughnut-wrap {
    position: relative;
    width: 100%;
    height: 12rem;
    margin-bottom: var(--m3-space-4);
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
}

.m3-ana__spinner-ring {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--m3-radius-full);
    border: 3px solid var(--m3-color-surface-container-highest);
    border-top-color: var(--m3-color-primary);
    animation: m3-ana-spin 0.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

@keyframes m3-ana-spin {
    to { transform: rotate(360deg); }
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
    font-weight: 500;
    color: var(--m3-color-on-surface-variant);
    margin-bottom: var(--m3-space-1);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.m3-ana__doughnut-total {
    font-size: var(--m3-type-body-small-size);
    font-weight: 600;
    color: var(--m3-color-on-surface);
    line-height: 1.25rem;
    overflow-wrap: anywhere;
}

/* ── Daftar kategori (baris polos ber-divider) ── */
.m3-ana__catlist {
    display: flex;
    flex-direction: column;
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
    width: 0.25rem;
    align-self: stretch;
    min-height: 1.25rem;
    border-radius: var(--m3-radius-full);
    flex-shrink: 0;
}

.m3-ana__cat-icon {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
}

.m3-ana__cat-main {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    line-height: 1.25rem;
}

.m3-ana__cat-name {
    font-size: var(--m3-type-body-medium-size);
    font-weight: 500;
    color: var(--m3-color-on-surface);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-ana__cat-pct {
    font-size: var(--m3-type-body-small-size);
    font-weight: 400;
    color: var(--m3-color-on-surface-variant);
}

.m3-ana__cat-amount {
    flex-shrink: 0;
    font-size: var(--m3-type-body-medium-size);
    font-weight: 600;
    color: var(--m3-color-on-surface);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 45%;
    text-align: right;
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
    width: 2.5rem;
    height: 2.5rem;
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    border: 1px solid var(--m3-color-outline-variant);
    color: var(--m3-color-on-surface-variant);
    margin-bottom: var(--m3-space-3);
}

.m3-ana__empty-icon :deep(svg) {
    width: 1.25rem;
    height: 1.25rem;
}

.m3-ana__empty-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    font-weight: 500;
    color: var(--m3-color-on-surface-variant);
}
</style>
