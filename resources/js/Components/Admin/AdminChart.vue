<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
import { m3ChartChrome, m3CssVar, m3TooltipOptions } from '@/Material/utils/m3Chart'

Chart.register(...registerables)

const props = defineProps({
    type: { type: String, default: 'line' },
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    height: { type: Number, default: 260 },
    legend: { type: Boolean, default: false },
    yLabel: { type: String, default: '' },
    // Tick Y ringkas (K/M) untuk kartu mini — flag false = perilaku admin utuh.
    yCompact: { type: Boolean, default: false },
})

const compact = (value) => {
    const n = Number(value) || 0
    const abs = Math.abs(n)
    if (abs >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'M'
    if (abs >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'jt'
    if (abs >= 1e3) return (n / 1e3).toFixed(0) + 'rb'
    return String(n)
}

const canvas = ref(null)
let chart = null
let observer = null

const ALPHA = 0.16

const hexToRgba = (hex, alpha) => {
    if (typeof hex !== 'string' || !hex.startsWith('#') || hex.length !== 7) return hex
    const r = parseInt(hex.slice(1, 3), 16)
    const g = parseInt(hex.slice(3, 5), 16)
    const b = parseInt(hex.slice(5, 7), 16)
    return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

const roleColor = (role) =>
    m3CssVar(`--m3-color-${role || 'primary'}`, role === 'error' ? '#ffb4ab' : '#82d5ca')

const build = async () => {
    await nextTick()
    const el = canvas.value
    if (!el) return

    if (chart) {
        chart.destroy()
        chart = null
    }

    const chrome = m3ChartChrome()
    const isDoughnut = props.type === 'doughnut' || props.type === 'pie'
    const datasets = props.series.map((serie) => {
        const color = roleColor(serie.role)
        return {
            label: serie.label,
            data: serie.data,
            borderColor: isDoughnut ? chrome.surface : color,
            backgroundColor: isDoughnut
                ? (serie.data || []).map((_, index) => {
                      const palette = ['primary', 'tertiary', 'secondary', 'error']
                      return roleColor(palette[index % palette.length])
                  })
                : props.type === 'bar'
                  ? hexToRgba(color, 0.75)
                  : hexToRgba(color, ALPHA),
            borderWidth: isDoughnut ? 2 : 2,
            borderRadius: props.type === 'bar' ? 6 : 0,
            tension: 0.35,
            fill: props.type === 'line',
            pointRadius: 0,
            pointHoverRadius: 4,
            pointHitRadius: 16,
            maxBarThickness: 34,
        }
    })

    chart = new Chart(el.getContext('2d'), {
        type: props.type,
        data: { labels: props.labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: isDoughnut ? 'nearest' : 'index', intersect: isDoughnut },
            plugins: {
                legend: {
                    display: props.legend || isDoughnut,
                    position: 'bottom',
                    labels: {
                        color: chrome.labels,
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        font: { size: 12 },
                    },
                },
                tooltip: m3TooltipOptions(chrome, { swatch: true }),
            },
            scales: isDoughnut
                ? {}
                : {
                      x: {
                          grid: { display: false },
                          border: { display: false },
                          ticks: {
                              color: chrome.ticks,
                              maxRotation: 0,
                              autoSkip: true,
                              maxTicksLimit: 8,
                              font: { size: 11 },
                          },
                      },
                      y: {
                          beginAtZero: true,
                          grid: { color: chrome.grid },
                          border: { display: false },
                          ticks: {
                              color: chrome.ticks,
                              precision: 0,
                              font: { size: 11 },
                              callback: (value) => (props.yCompact ? compact(value) : props.yLabel ? `${value}${props.yLabel}` : value),
                          },
                      },
                  },
        },
    })
}

const onThemeChange = () => build()

onMounted(() => {
    build()
    observer = new MutationObserver(onThemeChange)
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onBeforeUnmount(() => {
    observer?.disconnect()
    chart?.destroy()
    chart = null
})

watch(() => [props.labels, props.series, props.type], build, { deep: true })
</script>

<template>
    <div :style="{ height: `${height}px` }" class="relative w-full">
        <canvas ref="canvas" />
    </div>
</template>
