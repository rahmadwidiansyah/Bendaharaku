<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateModal from '@/Components/DateModal.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, shallowRef, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import { formatNumber, formatDate, parseDateOnly } from '@/utils/format.js';
import { useI18n } from 'vue-i18n';
import AppIcon from '@/Components/AppIcon.vue';
import { getCategoryIconColor } from '@/Composables/useIcon.js';
import { InboxIcon } from 'lucide-vue-next';

const { t } = useI18n();

Chart.register(...registerables);

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
    allDailyLabels: Array,
    allDailyDates: Array,
    allDailyIncome: Array,
    allDailyExpense: Array,
    allDailyDebt: Array,
    allDailyReceivable: Array
});

const charts = shallowRef({});
const barView = ref('harian');
const categoryView = ref('expense');
const barChartRef = ref(null);
const cumulativeChartRef = ref(null);
const cumulativeContainerRef = ref(null); // ref ke div container canvas kumulatif
const doughnutChartRef = ref(null);
const barChartKey = ref(0);
const cumulativeChartKey = ref(0);
const doughnutChartKey = ref(0);

// State doughnut: false = sedang loading/render, true = sudah siap
const doughnutReady = ref(false);

// ─── Paging chart arus kas ────────────────────────────────────────
// Satu canvas selebar seluruh riwayat melewati batas keras canvas
// Chromium/Skia (~32.767px per dimensi): canvas gagal dirender dan tampil
// putih blank di Android. Karena itu label dipotong per halaman — halaman
// 0 adalah yang paling baru — dan lebar canvas di-clamp sebagai pengaman.
const isMobileViewport = () => typeof window !== 'undefined' && window.innerWidth < 640;
const BAR_PAGE_SIZE = {
    // Halaman disesuaikan agar satu halaman muat penuh di lebar kontainer
    // (canvas fixed-width ala chart Admin — y axis selalu terlihat).
    harian: () => (isMobileViewport() ? 16 : 31),
    mingguan: () => 26,
    bulanan: () => 36,
};
const CANVAS_MAX_WIDTH = 16000;
const barPage = ref(0);
const barPager = ref({ page: 0, count: 1 });

const sliceBarPage = (data, view) => {
    const size = (BAR_PAGE_SIZE[view] && BAR_PAGE_SIZE[view]()) || 60;
    const total = data.labels.length;
    const pageCount = Math.max(1, Math.ceil(total / size));
    const page = Math.min(barPage.value, pageCount - 1);
    const end = Math.max(0, total - page * size);
    const start = Math.max(0, end - size);
    return {
        pageCount,
        page,
        labels: data.labels.slice(start, end),
        incomes: data.incomes.slice(start, end),
        expenses: data.expenses.slice(start, end),
        debts: data.debts.slice(start, end),
        receivables: data.receivables.slice(start, end)
    };
};

const goOlderBarPage = () => {
    barPage.value += 1;
    renderBarChart(barView.value, true);
};

const goNewerBarPage = () => {
    barPage.value = Math.max(0, barPage.value - 1);
    renderBarChart(barView.value, true);
};

const isDateFilterActive = computed(() => usePage().url.includes('start_date='));

const cssVar = (name, fallback) =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;

// Chrome chart & tooltip theme-aware (paritas dengan AdminChart/m3Chart):
// tooltip memakai pasangan inverse agar kontras di kedua tema, grid/tick
// mengikuti tema, surface dipakai sebagai warna pemisah irisan doughnut.
const isLightScheme = () => document.documentElement.classList.contains('light');

const chartChrome = () => {
    const light = isLightScheme();
    return {
        grid: light ? 'rgba(15, 23, 42, 0.07)' : 'rgba(255,255,255,0.05)',
        ticks: light ? '#64748B' : '#9CA3AF',
        labels: light ? '#475569' : '#D1D5DB',
        surface: cssVar('--color-surface-raised', light ? '#FFFFFF' : '#121212'),
        brand: cssVar('--color-brand', '#a855f7'),
    };
};

// Tooltip gaya AdminChart untuk Default UI: inverse surface (kontras di
// light & dark), judul redup, body tebal, swatch opsional untuk multi-seri.
const buildChartTooltip = ({ swatch = false } = {}) => ({
    enabled: true,
    backgroundColor: isLightScheme() ? 'rgba(241, 245, 249, 0.98)' : 'rgba(18, 18, 18, 0.95)',
    titleColor: isLightScheme() ? 'rgba(15, 23, 42, 0.7)' : '#9CA3AF',
    titleFont: { size: 11, weight: 'bold' },
    bodyColor: isLightScheme() ? '#0F172A' : '#FFFFFF',
    bodyFont: { size: 13, weight: 'bold' },
    padding: { x: 12, y: 8 },
    cornerRadius: 8,
    displayColors: swatch,
    boxWidth: 12,
    boxHeight: 12,
    boxPadding: 4,
});

// Warna arus kas — baca token CSS (otomatis mengikuti tema dark/light):
// masuk hijau, keluar merah, hutang kuning, piutang ungu (statis)
const FLOW_COLORS = computed(() => ({
    income: cssVar('--color-income-chart', '#34D399'),
    expense: cssVar('--color-expense-chart', '#F87171'),
    debt: cssVar('--color-debt-chart', '#FBBF24'),
    receivable: cssVar('--color-receivable-chart', '#C084FC'),
}));

const barViews = [
    { key: 'harian', label: () => t('analytics.view.daily') },
    { key: 'mingguan', label: () => t('analytics.view.weekly') },
    { key: 'bulanan', label: () => t('analytics.view.monthly') },
];

const categoryViews = [
    { key: 'expense', label: () => t('analytics.categoryTab.expenseShort') },
    { key: 'income', label: () => t('analytics.categoryTab.incomeShort') },
    { key: 'debt', label: () => t('analytics.categoryTab.debtShort') },
    { key: 'receivable', label: () => t('analytics.categoryTab.receivableShort') },
];

const destroyChart = (id) => {
    if (charts.value[id]) {
        charts.value[id].destroy();
        delete charts.value[id];
    }
};

const waitForChartFrame = async () => {
    await nextTick();
    await new Promise((resolve) => requestAnimationFrame(resolve));
};

const initCumulativeChart = async () => {
    try {
        destroyChart('cumulative');

        // Tunggu DOM ter-paint sebelum mengukur ukuran container
        await nextTick();
        await new Promise((resolve) => requestAnimationFrame(resolve));

        const canvas = cumulativeChartRef.value;
        if (!canvas) {
            console.warn('Cumulative canvas ref not found');
            return;
        }

        // Validasi data
        if (!props.dailyLabels?.length || !props.cumulativeData?.length) {
            return;
        }

        const container = cumulativeContainerRef.value || canvas.parentElement;

        // Fungsi untuk melakukan render setelah ukuran container diketahui
        const doRender = (w, h) => {
            // Pastikan tidak ada chart lama (bisa terjadi kalau ResizeObserver lambat)
            destroyChart('cumulative');

            canvas.width = w;
            canvas.height = h;
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            const brand = cssVar('--color-brand', '#a855f7');
            const chrome = chartChrome();

            // Data kumulatif PAKAI hasil server (openingBalance + arus harian
            // rentang terpilih, props dailyLabels/cumulativeData) — konsisten
            // dengan angka hero. Recompute dari allDaily* mengabaikan saldo
            // awal wallet dan menghasilkan nilai salah pada data ber-saldo awal.
            const allLabels = props.dailyLabels || [];
            const allData = props.cumulativeData || [];
            const take = Math.min(31, allLabels.length);
            const visibleLabels = allLabels.slice(-take);
            const visibleData = allData.slice(-take);

            // Area fill flat lembut seperti AdminChart (bukan gradient).
            const softFill = hexToRgba(brand, 0.16);

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
                        // Bahasa dataset mengikuti AdminChart: garis bersih
                        // tanpa titik, titik hanya muncul saat hover.
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
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    animation: { duration: 600 },
                    layout: { padding: { left: 4, right: 4, top: 10, bottom: 4 } },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            ...buildChartTooltip(),
                            callbacks: {
                                title: (items) => {
                                    const d = parseDateOnly(items[0]?.label);
                                    return d?.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) ?? (items[0]?.label ?? '');
                                },
                                label: (item) => 'Rp ' + Number(item.raw).toLocaleString('id-ID'),
                            }
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
                                color: tickColor,
                                font: { size: 11 },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8,
                            },
                        },
                        y: {
                            display: true,
                            grid: { display: true, color: gridColor },
                            border: { display: false },
                            ticks: {
                                display: true,
                                color: tickColor,
                                font: { size: 11 },
                                callback: (value) => formatCompact(value),
                            },
                        }
                    }
                }
            });

            charts.value['cumulative'] = chart;
        };

        const w = container?.clientWidth || 0;
        // Tinggi di-hardcode dari CSS class h-[120px]/h-[140px] — clientHeight bisa 0 saat animasi awal
        const h = container?.clientHeight || 120;

        if (w > 10) {
            doRender(w, h);
        } else {
            // Lebar belum tersedia (layout belum selesai) — observe card parent yang lebih besar
            const cardEl = canvas.closest('[class*="rounded-xl"]') || container?.parentElement || container;
            const ro = new ResizeObserver(() => {
                const fw = container?.clientWidth || 0;
                const fh = container?.clientHeight || 120;
                if (fw > 10) {
                    ro.disconnect();
                    doRender(fw, fh);
                }
            });
            if (cardEl) ro.observe(cardEl);
        }
    } catch (error) {
        console.error('Failed to initialize cumulative chart:', error);
    }
};

function formatCompact(n) {
    // Satuan Indonesia: rb (ribu), jt (juta), M (miliar) — konsisten
    // dengan tick chart lain (AdminChart yCompact).
    const abs = Math.abs(n);
    if (abs >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'M';
    if (abs >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'jt';
    if (abs >= 1e3) return (n / 1e3).toFixed(0) + 'rb';
    return n.toString();
}

/**
 * Bangun dataset arus kas untuk seluruh periode yang tersedia.
 * Kontainer chart menyediakan scroll horizontal agar periode lama tetap dapat diakses.
 */
function buildBarData(view) {
    // Chart arus kas mengikuti rentang tanggal terpilih (paritas dengan KPI &
    // doughnut). Label ISO 'YYYY-MM-DD' aman dibandingkan sebagai string.
    const startDate = props.startDate || '';
    const endDate = props.endDate || '';
    const inRange = (date) => (!startDate || date >= startDate) && (!endDate || date <= endDate);

    const allDates = props.allDailyDates || [];
    const idx = [];
    allDates.forEach((date, i) => {
        if (inRange(date)) idx.push(i);
    });
    const dates = idx.map(i => allDates[i]);
    const fIncome = idx.map(i => props.allDailyIncome?.[i] || 0);
    const fExpense = idx.map(i => props.allDailyExpense?.[i] || 0);
    const fDebt = idx.map(i => props.allDailyDebt?.[i] || 0);
    const fReceivable = idx.map(i => props.allDailyReceivable?.[i] || 0);

    const labels = [], incomes = [], expenses = [], debts = [], receivables = [];

    function pushNonEmpty(l, i, e, d, r) {
        if (i || e || d || r) {
            labels.push(l); incomes.push(i); expenses.push(e); debts.push(d); receivables.push(r);
        }
    }

    if (view === 'harian') {
        // Tampilkan hari-hari bertransaksi dalam rentang terpilih
        dates.forEach((date, i) => {
            const inc = Math.abs(fIncome[i]);
            const exp = Math.abs(fExpense[i]);
            const debt = Math.abs(fDebt[i]);
            const rec = Math.abs(fReceivable[i]);
            pushNonEmpty(
                formatDate(date, { day: '2-digit', month: 'short' }),
                inc, exp, debt, rec
            );
        });
        return { labels, incomes, expenses, debts, receivables };
    }

    if (view === 'mingguan') {
        let bucket = null;
        dates.forEach((date, i) => {
            const d = parseDateOnly(date);
            if (!bucket) {
                bucket = {
                    start: d.getDate(),
                    end: d.getDate(),
                    inc: 0,
                    exp: 0,
                    debt: 0,
                    rec: 0,
                    count: 0,
                };
            }

            bucket.end = d.getDate();
            bucket.inc += Math.abs(fIncome[i]);
            bucket.exp += Math.abs(fExpense[i]);
            bucket.debt += Math.abs(fDebt[i]);
            bucket.rec += Math.abs(fReceivable[i]);
            bucket.count++;

            if (bucket.count === 7 || i === dates.length - 1) {
                pushNonEmpty(`${bucket.start}-${bucket.end}`, bucket.inc, bucket.exp, bucket.debt, bucket.rec);
                bucket = null;
            }
        });
        return { labels, incomes, expenses, debts, receivables };
    }

    // bulanan — seluruh periode yang tersedia
    const monthMap = new Map();
    dates.forEach((dateStr, i) => {
        const d = parseDateOnly(dateStr);
        if (!d) return;
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        if (!monthMap.has(key)) {
            monthMap.set(key, {
                label: d.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }),
                inc: 0, exp: 0, debt: 0, rec: 0,
            });
        }
        const m = monthMap.get(key);
        m.inc += Math.abs(fIncome[i]);
        m.exp += Math.abs(fExpense[i]);
        m.debt += Math.abs(fDebt[i]);
        m.rec += Math.abs(fReceivable[i]);
    });

    Array.from(monthMap.values()).forEach((m) => {
        pushNonEmpty(m.label, m.inc, m.exp, m.debt, m.rec);
    });

    return { labels, incomes, expenses, debts, receivables };
}

const renderBarChart = async (view, keepPage = false) => {
    barView.value = view;
    if (!keepPage) barPage.value = 0;
    const full = buildBarData(view);
    const { labels, incomes, expenses, debts, receivables, pageCount, page } = sliceBarPage(full, view);
    barPager.value = { page, count: pageCount };

    destroyChart('bar');
    barChartKey.value++;
    await nextTick();

    const canvas = barChartRef.value;
    if (!canvas) return;

    // Set canvas size eksplisit agar responsive:false tidak bikin chart kosong.
    // Lebar canvas = lebar kontainer (tanpa scroll horizontal): struktur ala
    // chart Admin — y axis selalu terlihat, label X ditata autoSkip, dan
    // navigasi rentang memakai pager.
    const container = canvas.parentElement;
    const canvasWidth = Math.min(container?.offsetWidth || 600, CANVAS_MAX_WIDTH);
    const canvasHeight = container?.offsetHeight || 220;
    canvas.width = canvasWidth;
    canvas.height = canvasHeight;
    canvas.style.width = canvasWidth + 'px';
    canvas.style.height = canvasHeight + 'px';

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Warna canvas light-aware (tema aplikasi, bukan OS)
    const isLight = document.documentElement.classList.contains('light');
    const tickColor = isLight ? '#64748B' : '#9CA3AF';
    const gridColor = isLight ? 'rgba(15, 23, 42, 0.07)' : 'rgba(255,255,255,0.05)';
    const labelColor = isLight ? '#475569' : '#D1D5DB';

    // Y max dari seluruh label halaman aktif (satu halaman = satu layar penuh)
    const pageVals = [incomes, expenses, debts, receivables].flatMap(arr => arr.filter(v => v > 0));
    const initialYMax = pageVals.length ? Math.max(...pageVals) * 1.25 : 1000;

    charts.value['bar'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: t('analytics.chartLabels.income'), data: incomes, backgroundColor: hexToRgba(FLOW_COLORS.value.income, 0.75), hoverBackgroundColor: FLOW_COLORS.value.income, borderRadius: 6, maxBarThickness: 12, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.expense'), data: expenses, backgroundColor: hexToRgba(FLOW_COLORS.value.expense, 0.75), hoverBackgroundColor: FLOW_COLORS.value.expense, borderRadius: 6, maxBarThickness: 12, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.debt'), data: debts, backgroundColor: hexToRgba(FLOW_COLORS.value.debt, 0.75), hoverBackgroundColor: FLOW_COLORS.value.debt, borderRadius: 6, maxBarThickness: 12, barPercentage: 0.9, categoryPercentage: 0.85 },
                { label: t('analytics.chartLabels.receivable'), data: receivables, backgroundColor: hexToRgba(FLOW_COLORS.value.receivable, 0.75), hoverBackgroundColor: FLOW_COLORS.value.receivable, borderRadius: 6, maxBarThickness: 12, barPercentage: 0.9, categoryPercentage: 0.85 }
            ]
        },
        options: {
            // responsive: false — cegah Chart.js re-render/resize saat scroll container bergerak
            responsive: false,
            maintainAspectRatio: false,
            animation: { duration: 400 },
            plugins: {
                // Legend bawaan di bawah-tengah (gaya chart Admin): titik
                // round, klik untuk sembunyikan/tampilkan seri.
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: labelColor,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        font: { size: 12 },
                    },
                },
                // Tooltip gaya AdminChart (sebelumnya tooltip bawaan tanpa gaya)
                tooltip: {
                    ...buildChartTooltip({ swatch: true }),
                    callbacks: {
                        label: (item) => `${item.dataset.label}: Rp ${Number(item.raw).toLocaleString('id-ID')}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: tickColor, font: { size: 12, weight: 'bold' }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }
                },
                y: {
                    display: true,
                    min: 0,
                    max: initialYMax,
                    // Grid lines sebagai garis bantu di background, axis tanpa garis
                    grid: {
                        display: true,
                        color: gridColor,
                        drawBorder: false,
                    },
                    border: { display: false },
                    ticks: {
                        display: true,
                        color: tickColor,
                        font: { size: 11 },
                        precision: 0,
                        callback: (value) => formatCompact(value)
                    }
                }
            }
        },
    });

};

const cumulativeChartMinWidth = computed(() => {
    // Cumulative chart selalu tampil 4 hari terakhir, tidak perlu minWidth besar
    return 300;
});

const activeCategoryData = computed(() => {
    function sortDesc(items) {
        return [...items].sort((a, b) => b.total - a.total);
    }
    if (categoryView.value === 'expense') {
        const s = sortDesc(props.expensesByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalExpense, labelName: t('analytics.totalExpense') };
    } else if (categoryView.value === 'income') {
        const s = sortDesc(props.incomesByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: props.totalIncome, labelName: t('analytics.totalIncome') };
    } else if (categoryView.value === 'debt') {
        const s = sortDesc(props.debtsByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: s.reduce((a, b) => a + b.total, 0), labelName: t('analytics.totalDebt') };
    } else {
        const s = sortDesc(props.receivablesByCategory);
        return { labels: s.map(x => x.name), values: s.map(x => x.total), ids: s.map(x => x.id), icons: s.map(x => x.icon), total: s.reduce((a, b) => a + b.total, 0), labelName: t('analytics.totalReceivable') };
    }
});

const activeColors = computed(() => {
    const base = {
        expense: () => cssVar('--color-expense-chart', '#F87171'),
        income: () => cssVar('--color-income-chart', '#34D399'),
        debt: () => cssVar('--color-debt-chart', '#FBBF24'),
        receivable: () => cssVar('--color-receivable-chart', '#C084FC'),
    }[categoryView.value]();
    return buildPalette(base);
});

// Ramp 6 shade dari satu warna base: terang → gelap (urutan rank tetap sama)
function buildPalette(baseHex) {
    const mix = (hex, weight) => {
        const { r, g, b } = hexRgb(hex);
        const t = (v) => {
            const n = weight > 0 ? Math.round(v + (255 - v) * weight) : Math.round(v * (1 + weight));
            return Math.max(0, Math.min(255, n));
        };
        return `#${[t(r), t(g), t(b)].map((n) => n.toString(16).padStart(2, '0')).join('')}`;
    };
    return [mix(baseHex, 0.40), mix(baseHex, 0.22), baseHex, mix(baseHex, -0.15), mix(baseHex, -0.30), mix(baseHex, -0.45)];
}

const iconColorClass = computed(() => {
    const map = { income: 'Income', expense: 'Expense', debt: 'Debt', receivable: 'Receivable' };
    return getCategoryIconColor(map[categoryView.value]);
});

const categoryColors = computed(() => {
    const data = activeCategoryData.value;
    if (!data.labels.length) return [];
    const palette = activeColors.value;
    const sorted = data.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v);
    return data.labels.map((_, i) => {
        const rank = sorted.findIndex(s => s.i === i);
        return palette[Math.round((rank / Math.max(sorted.length - 1, 1)) * (palette.length - 1))];
    });
});

function hexRgb(hex) {
    const v = parseInt(hex.slice(1), 16);
    return { r: v >> 16, g: (v >> 8) & 255, b: v & 255 };
}

function hexToRgba(hex, alpha) {
    const { r, g, b } = hexRgb(hex);
    return `rgba(${r},${g},${b},${alpha})`;
}

function applyGradients(chart, colors, labels) {
    if (!chart) return;

    const cvs = chart.canvas;
    if (!cvs || !cvs.width || !cvs.height) {
        if (cvs) chart.update('none');
        return;
    }

    const cx = cvs.width / 2;
    const cy = cvs.height / 2;
    const outerR = Math.min(cvs.width, cvs.height) / 2;
    const innerR = outerR * 0.7;
    const ctx = cvs.getContext('2d');

    chart.data.datasets[0].backgroundColor = labels.map((_, i) => {
        const c = colors[i % colors.length];
        const rgb = hexRgb(c);
        const g = ctx.createRadialGradient(cx, cy, innerR, cx, cy, outerR);
        g.addColorStop(0, `rgba(${rgb.r},${rgb.g},${rgb.b},0.35)`);
        g.addColorStop(1, c);
        return g;
    });
    chart.update('none');
}

const renderDoughnutChart = async () => {
    const dataObj = activeCategoryData.value;
    destroyChart('doughnut');
    doughnutReady.value = false; // tampilkan spinner

    // Canvas selalu ada di DOM (tidak pakai :key), tunggu satu frame agar spinner muncul
    await nextTick();

    const ctx = doughnutChartRef.value?.getContext('2d');
    if (!ctx || !dataObj.labels.length) {
        doughnutReady.value = true; // tidak ada data, tampilkan empty state
        return;
    }

    const palette = activeColors.value;
    const sorted = dataObj.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v);
    const paint = dataObj.labels.map((_, i) => {
        const rank = sorted.findIndex(s => s.i === i);
        return palette[Math.round((rank / Math.max(sorted.length - 1, 1)) * (palette.length - 1))];
    });

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dataObj.labels,
            datasets: [{
                data: dataObj.values,
                backgroundColor: paint,
                // Pemisah antar irisan memakai warna kartu (gaya AdminChart),
                // bukan ring gelap yang menabrak di light scheme.
                borderWidth: 2,
                borderColor: chartChrome().surface,
                spacing: 2,
                borderRadius: 4,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            // Animasi memutar searah jarum jam dari tengah
            animation: {
                duration: 700,
                easing: 'easeOutQuart',
                animateRotate: true,
                animateScale: false,
                onComplete: () => {
                    // Tampilkan label & list setelah animasi chart selesai
                    doughnutReady.value = true;
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...buildChartTooltip({ swatch: true }),
                    callbacks: {
                        label: (item) => `Rp ${Number(item.raw).toLocaleString('id-ID')}`,
                    },
                },
            },
            onResize: (chart) => {
                const d = activeCategoryData.value;
                const p = activeColors.value;
                const s = d.values.map((v, i) => ({ v, i })).sort((a, b) => a.v - b.v);
                const paint2 = d.labels.map((_, i) => {
                    const rank = s.findIndex(x => x.i === i);
                    return p[Math.round((rank / Math.max(s.length - 1, 1)) * (p.length - 1))];
                });
                applyGradients(chart, paint2, d.labels);
            }
        }
    });

    chart.resize();
    charts.value['doughnut'] = chart;

    requestAnimationFrame(() => applyGradients(chart, paint, dataObj.labels));
};

const switchCategory = (type) => {
    categoryView.value = type;
    renderDoughnutChart();
};

onMounted(() => {
    initCumulativeChart();
    renderBarChart('harian');
    renderDoughnutChart();

});

// Reinitialize cumulative chart when data changes
watch(() => props.dailyLabels, () => {
    initCumulativeChart();
});
watch(() => props.cumulativeData, () => {
    initCumulativeChart();
});

// Chart arus kas menyaring per rentang tanggal — render ulang saat rentang
// berubah. start/endDate ikut dipantau karena array allDaily* bisa sama
// referensinya (Inertia mempertahankan prop identik) padahal rentang berubah.
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
        renderBarChart(barView.value);
    },
);

onBeforeUnmount(() => {
    clearTimeout(barScrollTimer);
    Object.keys(charts.value).forEach(destroyChart);
});
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">

        <Head title="Analitik" />

        <div class="px-4 sm:px-5 pb-40 w-full lg:max-w-7xl mx-auto lg:px-8 relative z-10 overflow-x-hidden">

            <!-- Date bar -->
            <div class="flex items-center justify-between pt-3 pb-1">
<p class="text-xs text-[var(--color-text-primary)] font-medium">
    {{ $t('analytics.showingData') }}
    <span class="text-gray-300">{{ formatDate(startDate, { day: 'numeric', month: 'long' }) }} – {{ formatDate(endDate, { day: 'numeric', month: 'long' }) }}</span>
</p>
<div class="relative">
    <DateModal :action="route('analytics.index')" :start-date="startDate" :end-date="endDate" />
</div>
</div>

<header class="flex items-center justify-between mb-4 lg:mb-6 animate-fade-in-up">
    <div class="hidden lg:block">
<p class="text-2xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
    {{ $t('analytics.subtitle') }}
</p>
        <h1 class="text-2xl sm:text-3xl font-bold text-[var(--color-text-primary)] tracking-tight leading-tight">{{ $t('analytics.title') }}</h1>
    </div>
</header>

            <div class="grid grid-cols-2 gap-3 mb-5 lg:mb-6 animate-fade-in-up delay-100">
                <div class="bg-linear-to-br from-success-deep to-success-mid/50 p-3 lg:p-4 rounded-xl border border-[var(--color-border-default)] relative overflow-hidden group">
                    <div class="flex items-center gap-1.5 mb-1 lg:mb-2">
                        <div class="w-1 h-1.5 lg:w-1.5 lg:h-1.5 rounded-full bg-green-400"></div>
                        <p class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">{{ $t('types.income') }}</p>
                    </div>
                    <p class="text-sm lg:text-md font-black text-green-400 tracking-tighter wrap-break-words relative z-10 leading-tight">
                        <span class="text-2xs mr-0.5 opacity-70">+Rp</span>{{ formatNumber(totalIncome) }}
                    </p>
                </div>
                <div class="bg-linear-to-br from-danger-deep to-danger-mid/50 p-3 lg:p-4 rounded-xl border border-[var(--color-border-default)] relative overflow-hidden group">
                    <div class="flex items-center gap-1.5 mb-1 lg:mb-2">
                        <div class="w-1 h-1.5 lg:w-1.5 lg:h-1.5 rounded-full bg-red-400"></div>
                        <p class="text-2xs font-bold text-[var(--color-text-secondary)] uppercase tracking-widest">{{ $t('types.expense') }}</p>
                    </div>
                    <p class="text-sm lg:text-md font-black text-red-400 tracking-tighter wrap-break-words relative z-10 leading-tight">
                        <span class="text-red-400 text-2xs mr-0.5 opacity-70">-Rp</span>{{ formatNumber(totalExpense) }}
                    </p>
                </div>
            </div>

            <!-- CUMULATIVE CHART -->
            <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-200 relative overflow-hidden group">
                <div class="flex justify-between items-start mb-4 lg:mb-6 relative z-10">
                    <div>
                <p class="text-2xs lg:text-xs font-bold text-[var(--color-text-primary)] uppercase tracking-[0.2em] mb-1">{{ $t('analytics.cumulativeBalance') }}</p>
                        <p class="text-2xs text-[var(--color-text-muted)] font-medium">{{ $t('analytics.cumulativeDesc') }}</p>
                    </div>
                    <p class="text-base lg:text-lg font-black text-[var(--color-text-primary)] tracking-tight px-2.5 lg:px-3 py-1 lg:py-1.5 rounded-xl">
                        <span class="text-2xs text-[var(--color-text-muted)] mr-1">Rp</span>{{ formatNumber(cumulativeBalance) }}
                    </p>
                </div>
                <div class="w-full h-[150px] lg:h-[176px] relative z-10" ref="cumulativeContainerRef">
                    <canvas ref="cumulativeChartRef" width="300" height="120" style="display:block;width:100%;height:100%"></canvas>
                </div>
            </div>

            <!-- BAR CHART -->
            <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-300 relative overflow-hidden group">
                <div class="flex items-center justify-between gap-2 mb-3 relative z-10">
                    <h2 class="text-2xs lg:text-sm font-bold text-[var(--color-text-primary)] uppercase tracking-widest shrink-0">{{ $t('analytics.cashflow') }}</h2>

                    <!-- Toggle tanpa sliding-indicator: styling langsung di tombol aktif -->
                    <div class="flex bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-lg p-1 gap-1">
                        <button v-for="v in barViews" :key="v.key" @click="renderBarChart(v.key)"
                            :class="[
                                'text-2xs font-bold uppercase tracking-widest px-2 lg:px-3 py-1.5 rounded-md transition-colors duration-200',
                                barView === v.key
                                    ? 'bg-linear-to-br from-brand-soft to-brand-deep text-[var(--color-text-primary)] shadow-sm'
                                    : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'
                            ]">{{ v.label() }}</button>
                    </div>
                </div>

                <!-- Chart container dengan horizontal scroll -->
                <div :style="{ height: '220px' }">
                    <canvas ref="barChartRef" :key="barChartKey"></canvas>
                </div>

                <!-- Paging chart (muncul saat label melebihi kapasitas satu halaman) -->
                <div v-if="barPager.count > 1" class="flex items-center justify-center gap-3 mt-3">
                    <button type="button" :disabled="barPager.page >= barPager.count - 1" @click="goOlderBarPage"
                        :aria-label="$t('analytics.chartPrevPage')"
                        class="w-9 h-9 flex items-center justify-center rounded-full border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] text-[var(--color-text-secondary)] disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                        </svg>
                    </button>
                    <span class="text-xs font-bold text-[var(--color-text-secondary)] min-w-[4rem] text-center">{{ $t('analytics.chartPage', { current: barPager.page + 1, total: barPager.count }) }}</span>
                    <button type="button" :disabled="barPager.page <= 0" @click="goNewerBarPage"
                        :aria-label="$t('analytics.chartNextPage')"
                        class="w-9 h-9 flex items-center justify-center rounded-full border border-[var(--color-border-default)] bg-[var(--color-surface-raised)] text-[var(--color-text-secondary)] disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- CATEGORY SECTION -->
            <div class="flex items-center gap-2 mb-3 lg:mb-4 px-1 animate-fade-in-up delay-400">
                <h2 class="text-2xs font-bold text-[var(--color-text-primary)] uppercase tracking-widest">{{ $t('analytics.categoryBreakdown') }}</h2>
                <div class="flex-1 h-px bg-linear-to-r from-purple-500 to-transparent"></div>
            </div>

            <!-- Toggle tanpa sliding-indicator -->
            <div class="grid grid-cols-4 gap-1 bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] rounded-xl p-1 mb-4 lg:mb-5 animate-fade-in-up delay-400">
                <button v-for="c in categoryViews" :key="c.key" @click="switchCategory(c.key)"
                    :class="[
                        'text-2xs font-bold uppercase tracking-widest py-2 lg:py-3 rounded-lg transition-colors duration-200',
                        categoryView === c.key
                            ? 'bg-linear-to-br from-brand-soft to-brand-deep text-[var(--color-text-primary)] shadow-sm'
                            : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'
                    ]">{{ c.label() }}</button>
            </div>

            <!-- DOUGHNUT CHART -->
            <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] p-4 lg:p-6 rounded-xl mb-5 lg:mb-8 animate-fade-in-up delay-500 relative overflow-hidden group">

                <!-- No data state -->
                <div v-if="doughnutReady && !activeCategoryData.labels.length" class="flex flex-col items-center justify-center py-8 lg:py-10">
                    <div class="w-10 h-10 lg:w-12 lg:h-12 bg-[var(--color-surface-muted)] rounded-xl flex items-center justify-center mb-3 border border-[var(--color-border-default)]">
                        <InboxIcon class="w-5 h-5 lg:w-6 lg:h-6 text-[var(--color-text-muted)]" :stroke-width="1.5" />
                    </div>
                    <p class="text-2xs font-bold text-[var(--color-text-primary)] uppercase tracking-widest">{{ $t('analytics.noData') }}</p>
                </div>

                <!-- Chart area: canvas selalu di DOM agar ref bisa diakses sebelum render -->
                <div v-show="!doughnutReady || activeCategoryData.labels.length">
                    <!-- Canvas container dengan spinner overlay -->
                    <div class="relative w-full h-48 lg:h-56 mb-5 lg:mb-6">
                        <!-- Spinner overlay saat loading -->
                        <div v-if="!doughnutReady" class="absolute inset-0 flex flex-col items-center justify-center z-20 bg-transparent">
                            <div class="w-10 h-10 rounded-full border-2 border-gray-700 border-t-purple-500 animate-spin"></div>
                        </div>
                        <!-- Canvas selalu ada agar ref tidak null saat renderDoughnutChart dipanggil -->
                        <canvas ref="doughnutChartRef" class="relative z-10 w-full h-full"></canvas>
                        <!-- Center label -->
                        <div v-if="doughnutReady && activeCategoryData.labels.length" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center pointer-events-none z-0">
                            <div class="w-[100px] lg:w-[110px] h-[100px] lg:h-[110px] rounded-full bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] flex flex-col items-center justify-center text-center px-1">
                                <span class="text-[10px] lg:text-2xs text-[var(--color-text-muted)] font-bold uppercase tracking-widest mb-1">{{ activeCategoryData.labelName }}</span>
                                <span class="text-2xs lg:text-2xs font-black text-[var(--color-text-primary)] tracking-tighter leading-tight w-full wrap-break-word">Rp {{ formatNumber(activeCategoryData.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Category list -->
                    <div v-if="doughnutReady && activeCategoryData.labels.length" class="space-y-3 lg:space-y-4">
                        <template v-for="(label, i) in activeCategoryData.labels" :key="i">
                            <Link v-if="activeCategoryData.ids[i] != null"
                                :href="route('categories.show', {
                                    category: activeCategoryData.ids[i],
                                    start_date: startDate,
                                    end_date: endDate,
                                })"
                                class="relative flex items-center justify-between bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] p-2.5 lg:p-3 rounded-xl overflow-hidden group hover:border-purple-500/30 transition-all duration-300">
                                <div class="flex items-center gap-2.5 lg:gap-3 relative z-10 w-full">
                                    <div class="w-1 lg:w-1.5 h-5 lg:h-6 rounded-full" :style="{ backgroundColor: categoryColors[i] }"></div>
                                    <AppIcon :icon="activeCategoryData.icons[i]" :class="['w-5 h-5 lg:w-6 lg:h-6 shrink-0', iconColorClass]" />
                                    <div class="flex-1 min-w-0 pr-1 lg:pr-2">
                                        <p class="text-xs font-bold text-gray-200 truncate">{{ label }}</p>
                                        <p class="text-2xs text-[var(--color-text-muted)] font-bold">{{ activeCategoryData.total > 0 ? ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0 }}%</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-2xs lg:text-xs font-black text-[var(--color-text-primary)] block">Rp {{ formatNumber(activeCategoryData.values[i]) }}</span>
                                    </div>
                                </div>
                            </Link>
                            <div v-else
                                class="relative flex items-center justify-between bg-[var(--color-surface-raised)] border border-[var(--color-border-default)] p-2.5 lg:p-3 rounded-xl overflow-hidden">
                                <div class="flex items-center gap-2.5 lg:gap-3 relative z-10 w-full">
                                    <div class="w-1 lg:w-1.5 h-5 lg:h-6 rounded-full" :style="{ backgroundColor: categoryColors[i] }"></div>
                                    <AppIcon :icon="activeCategoryData.icons[i]" :class="['w-5 h-5 lg:w-6 lg:h-6 shrink-0', iconColorClass]" />
                                    <div class="flex-1 min-w-0 pr-1 lg:pr-2">
                                        <p class="text-xs font-bold text-gray-200 truncate">{{ label }}</p>
                                        <p class="text-2xs text-[var(--color-text-muted)] font-bold">{{ activeCategoryData.total > 0 ? ((activeCategoryData.values[i] / activeCategoryData.total) * 100).toFixed(1) : 0 }}%</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-2xs lg:text-xs font-black text-[var(--color-text-primary)] block">Rp {{ formatNumber(activeCategoryData.values[i]) }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in-up {
    0% { opacity: 0; transform: translateY(15px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up { animation: fade-in-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
.delay-100 { animation-delay: 100ms; }
.delay-200 { animation-delay: 200ms; }
.delay-300 { animation-delay: 300ms; }
.delay-400 { animation-delay: 400ms; }
.delay-500 { animation-delay: 500ms; }
</style>

<style>
/* Custom scrollbar styling for bar chart */
.scrollbar-custom {
    scrollbar-width: thin;
    scrollbar-color: rgba(139, 92, 246, 0.3) rgba(31, 41, 55, 0.5);
}

.scrollbar-custom::-webkit-scrollbar {
    height: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.5);
    border-radius: 3px;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.3);
    border-radius: 3px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.5);
}
</style>
