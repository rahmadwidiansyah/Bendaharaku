<script setup>
/**
 * M3MiniCashflowChart — bar chart arus kas 7 hari terakhir untuk kolom kiri
 * dashboard M3. Data dari props dailyTotals ({'Y-m-d': {income, expense}})
 * yang sudah dikirim DashboardController — tanpa query baru.
 *
 * Memakai AdminChart (M3-token driven, light/dark aware). Kartu disembunyikan
 * bila tidak ada hari bertransaksi.
 */
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminChart from '@/Components/Admin/AdminChart.vue'
import M3Card from '@/Material/Components/M3Card.vue'

const props = defineProps({
    dailyTotals: { type: Object, default: () => ({}) },
    isVisible: { type: Boolean, default: true },
})

const { t } = useI18n()

const DAYS = 7

const chart = computed(() => {
    const entries = Object.entries(props.dailyTotals || {})
        .map(([date, v]) => ({ date, income: Math.abs(Number(v?.income) || 0), expense: Math.abs(Number(v?.expense) || 0) }))
        .filter((e) => e.income > 0 || e.expense > 0)
        .sort((a, b) => a.date.localeCompare(b.date))
        .slice(-DAYS)

    if (!entries.length) return null

    const fmt = (dateStr) => {
        const d = new Date(dateStr + 'T00:00:00')
        return isNaN(d) ? dateStr : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
    }

    return {
        labels: entries.map((e) => fmt(e.date)),
        series: [
            { label: t('dashboard.widgets.income'), data: entries.map((e) => e.income), role: 'income' },
            { label: t('dashboard.widgets.expense'), data: entries.map((e) => e.expense), role: 'expense' },
        ],
    }
})
</script>

<template>
    <M3Card v-if="chart && isVisible" variant="outlined" padding="md" class="m3-miniflow">
        <p class="m3-miniflow__title">{{ $t('dashboard.widgets.cashflowTitle') }}</p>
        <AdminChart type="bar" :labels="chart.labels" :series="chart.series" :height="180" legend y-compact />
    </M3Card>
</template>

<style scoped>
.m3-miniflow__title {
    margin: 0 0 var(--m3-space-2);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}
</style>
