<script setup>
/**
 * Material/Pages/Budgeting/Index.vue — Budgeting Material Design 3 Expressive.
 *
 * Kontrak data & perilaku IDENTIK dengan Pages/Budgeting/Index.vue Default:
 * props yang sama dari route /budgeting, endpoint axios yang sama
 * (/api/v1/budget/{y}/{m}, /api/v1/budget/generate, /generate/status),
 * polling 2s + timeout 5 menit, gating isCurrentPeriod, toast, dan
 * derivasi summary/group yang sama. Presentasi memakai komponen M3:
 * M3Card, M3Button, M3IconButton, M3Modal (AI notes + konfirmasi refresh),
 * M3ProgressBar. Warna "Sisa" positif netral (aturan warna finansial:
 * hijau hanya untuk pemasukan) — expense hanya saat over budget.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3CircularProgress from '@/Material/Components/M3CircularProgress.vue'
import M3ProgressBar from '@/Material/Components/M3ProgressBar.vue'
import M3BudgetSkeleton from '@/Material/Components/Skeleton/M3BudgetSkeleton.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { useBotAvatar } from '@/Composables/useBotAvatar.js'
import { useToast } from '@/Composables/useToast.js'
import { Head } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { formatRupiah, formatDate } from '@/utils/format.js'

const { t } = useI18n()
const { showToast } = useToast()

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    expenseGroups: {
        type: Object,
        default: () => ({}),
    },
    botName: {
        type: String,
        default: 'Ken-Chan',
    },
    botAvatar: {
        type: String,
        default: null,
    },
})

const { avatarFailed, initials, onAvatarError } = useBotAvatar(() => props.botName)

const budgetData = ref(null)
const isLoading = ref(true)
const loadError = ref(false)
const isGenerating = ref(false)
const showAiNotes = ref(false)
const showRegenerateConfirm = ref(false)
const expandedGroups = ref(new Set())
const generateError = ref(null)
const generationTimer = ref(null)

// Batas maksimal polling status generate — sinkron dengan job timeout (300s).
const POLL_MAX_MS = 5 * 60 * 1000
const generationStartedAt = ref(0)

const toggleGroup = (key) => {
    const next = new Set(expandedGroups.value)
    if (next.has(key)) next.delete(key)
    else next.add(key)
    expandedGroups.value = next
}

const currentMonth = ref(new Date().getMonth() + 1)
const currentYear = ref(new Date().getFullYear())

// ─── Data fetching ─────────────────────────────────────────────────
const fetchBudget = async () => {
    isLoading.value = true
    loadError.value = false
    try {
        const response = await axios.get(`/api/v1/budget/${currentYear.value}/${currentMonth.value}`)
        budgetData.value = response.data
        if (!budgetData.value) fetchLastBudget()
    } catch (error) {
        if (error.response && error.response.status === 404) {
            budgetData.value = null
            fetchLastBudget()
        } else {
            loadError.value = true
            console.error('Error fetching budget:', error)
        }
    } finally {
        isLoading.value = false
    }
}

// ─── Snapshot periode aktif terakhir (saat periode berjalan kosong) ──
// Mencari mundur maksimal 3 bulan lewat endpoint yang sama; kartu muncul
// di bawah empty-state agar halaman tidak kosong tanpa kontinuitas.
const lastBudget = ref(null)

const fetchLastBudget = async () => {
    let m = currentMonth.value
    let y = currentYear.value
    for (let i = 0; i < 3; i++) {
        m -= 1
        if (m === 0) { m = 12; y -= 1 }
        try {
            const { data } = await axios.get(`/api/v1/budget/${y}/${m}`)
            if (data && (Number(data.total_budget_amount) > 0 || (data.expense_groups ?? []).length > 0)) {
                lastBudget.value = { ...data, month: m, year: y }
                return
            }
        } catch { /* 404 dsb — lanjut mundur */ }
    }
    lastBudget.value = null
}

const lastBudgetLabel = computed(() =>
    lastBudget.value
        ? new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(
            new Date(lastBudget.value.year, lastBudget.value.month - 1, 1),
        )
        : ''
)

const lastBudgetSpent = computed(() => {
    const map = lastBudget.value?.summary ?? {}
    return Object.values(map).reduce((acc, s) => acc + (Number(s?.spent) || 0), 0)
})

const openLastBudget = () => {
    if (!lastBudget.value) return
    currentMonth.value = lastBudget.value.month
    currentYear.value = lastBudget.value.year
    lastBudget.value = null
    fetchBudget()
}

// ─── Generate AI (async: request hanya dispatch job, polling status) ──
const stopGenerationPolling = () => {
    if (generationTimer.value) {
        clearInterval(generationTimer.value)
        generationTimer.value = null
    }
}

const fetchGenerationStatus = async () => {
    try {
        const { data } = await axios.get('/api/v1/budget/generate/status', {
            params: { month: currentMonth.value, year: currentYear.value },
        })
        return data
    } catch (error) {
        return null
    }
}

const startGenerationPolling = () => {
    stopGenerationPolling()
    generationStartedAt.value = Date.now()
    generationTimer.value = setInterval(async () => {
        const status = await fetchGenerationStatus()
        if (!status || status.status === 'processing' || status.status === 'pending') {
            if (Date.now() - generationStartedAt.value > POLL_MAX_MS) {
                stopGenerationPolling()
                isGenerating.value = false
                generateError.value = t('budgeting.timeout')
                showToast(t('budgeting.timeout'), 'error')
                await fetchBudget()
            }
            return
        }

        stopGenerationPolling()
        isGenerating.value = false

        if (status.status === 'completed') {
            generateError.value = null
            showToast(t('budgeting.generated'))
            await fetchBudget()
        } else if (status.status === 'failed') {
            generateError.value = status.error_message || t('budgeting.aiError', { bot: botName })
            showToast(t('budgeting.aiError', { bot: botName }), 'error')
            await fetchBudget()
        }
    }, 2000)
}

const generateBudget = async () => {
    if (isGenerating.value) return
    isGenerating.value = true
    generateError.value = null
    try {
        await axios.post('/api/v1/budget/generate', {
            month: currentMonth.value,
            year: currentYear.value,
        })
        startGenerationPolling()
    } catch (error) {
        isGenerating.value = false
        showToast(error.response?.data?.message || t('budgeting.aiError', { bot: botName }), 'error')
        console.error('Error dispatching budget generation:', error)
    }
}

const checkStatusAndFetch = async () => {
    if (!isCurrentPeriod.value) {
        await fetchBudget()
        return
    }
    const status = await fetchGenerationStatus()

    if (status && (status.status === 'pending' || status.status === 'processing')) {
        isGenerating.value = true
        startGenerationPolling()
        await fetchBudget()
    } else if (status && status.status === 'failed' && status.error_message) {
        generateError.value = status.error_message
        await fetchBudget()
    } else {
        await fetchBudget()
    }
}

const requestRegenerate = () => {
    showRegenerateConfirm.value = true
}

const confirmRegenerate = () => {
    showRegenerateConfirm.value = false
    generateBudget()
}

// ─── Period navigation ─────────────────────────────────────────────
const shiftMonth = (delta) => {
    const date = new Date(currentYear.value, currentMonth.value - 1 + delta, 1)
    currentYear.value = date.getFullYear()
    currentMonth.value = date.getMonth() + 1
}

const periodLabel = computed(() =>
    new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(
        new Date(currentYear.value, currentMonth.value - 1, 1),
    ),
)

// Versi singkat label periode untuk toolbar satu baris di mobile ("Jan 2026").
const periodLabelShort = computed(() =>
    new Intl.DateTimeFormat('id-ID', { month: 'short', year: 'numeric' }).format(
        new Date(currentYear.value, currentMonth.value - 1, 1),
    ),
)

// Generate AI hanya untuk bulan berjalan; bulan lain (lampau/mendatang) hanya bisa dilihat & diedit manual
const isCurrentPeriod = computed(() => {
    const now = new Date()
    return currentYear.value === now.getFullYear() && currentMonth.value === now.getMonth() + 1
})

watch([currentMonth, currentYear], () => {
    stopGenerationPolling()
    generateError.value = null
    checkStatusAndFetch()
})

// ─── Derived data ──────────────────────────────────────────────────
const summaryMap = computed(() => budgetData.value?.summary ?? {})
const budgetItems = computed(() => budgetData.value?.items ?? [])

const mergeRows = (list) =>
    list.map((entry) => {
        const item = budgetItems.value.find(
            (i) => i.budgetable_type?.endsWith('Category') && i.budgetable_id === entry.id,
        )
        const summary = item ? summaryMap.value[item.id] ?? {} : {}
        return {
            ...entry,
            name: entry.category_name,
            item,
            target: Number(summary.target ?? item?.target_amount ?? 0),
            spent: Number(summary.spent ?? 0),
            remaining: Number(summary.remaining ?? 0),
        }
    })

// Kategori terisi (budget/transaksi) tampil duluan, sisanya alfabetis
const categoryRows = computed(() => {
    const rows = mergeRows(props.categories)
    return [...rows].sort((a, b) => {
        const aActive = a.target > 0 || a.spent > 0
        const bActive = b.target > 0 || b.spent > 0
        if (aActive !== bActive) return aActive ? -1 : 1
        if (aActive) return b.spent - a.spent
        return String(a.name).localeCompare(String(b.name))
    })
})

// Grup pengeluaran (Fix Cost / Additional Cost) — dari AI, per periode budget
const groupRows = computed(() => {
    const configOrder = Object.keys(props.expenseGroups)
    const configNames = props.expenseGroups
    const groups = budgetData.value?.expense_groups ?? []

    return [...groups]
        .sort((a, b) => configOrder.indexOf(a.group_key) - configOrder.indexOf(b.group_key))
        .map((group) => {
            const members = categoryRows.value.filter((row) => (group.category_ids ?? []).includes(row.id))
            const target = members.reduce((sum, row) => sum + row.target, 0)
            const spent = members.reduce((sum, row) => sum + row.spent, 0)
            return {
                key: group.group_key,
                name: configNames[group.group_key] ?? group.group_name,
                members,
                target,
                spent,
                remaining: target - spent,
            }
        })
})

const visibleRows = computed(() => groupRows.value)

const totalBudget = computed(() => Number(budgetData.value?.total_budget_amount ?? 0))
const totalSpent = computed(() =>
    budgetItems.value
        .filter((item) => item.budgetable_type?.endsWith('Category'))
        .reduce((acc, item) => acc + (Number(summaryMap.value[item.id]?.spent) || 0), 0),
)
const totalRemaining = computed(() => totalBudget.value - totalSpent.value)
const totalPct = computed(() =>
    totalBudget.value > 0 ? Math.min(100, Math.round((totalSpent.value / totalBudget.value) * 100)) : 0,
)

const remainingHint = computed(() => {
    if (totalRemaining.value < 0) {
        return `${t('budgeting.overBudget')} ${formatRupiah(Math.abs(totalRemaining.value))}`
    }
    if (totalBudget.value > 0) {
        return t('budgeting.remainingPct', {
            pct: Math.round((totalRemaining.value / totalBudget.value) * 100),
        })
    }
    return ''
})

const pct = (row) => (row.target > 0 ? Math.min(100, Math.round((row.spent / row.target) * 100)) : 0)
const isOver = (row) => row.target > 0 && row.spent > row.target

const createdLabel = computed(() => (budgetData.value?.created_at ? formatDate(budgetData.value.created_at) : ''))

onMounted(checkStatusAndFetch)
onUnmounted(stopGenerationPolling)
</script>

<template>
    <M3Layout :title="t('budgeting.title')">
        <Head :title="t('budgeting.title')" />

        <div class="m3-bud">
            <M3PageTitle :title="t('budgeting.title')">
                <template #actions>
                    <M3IconButton :href="route('chat.index')" :aria-label="t('nav.chat')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z" />
                        </svg>
                    </M3IconButton>
                </template>
            </M3PageTitle>

            <!-- ── Toolbar: navigasi periode + aksi kontekstual, satu baris ── -->
            <div class="m3-bud__toolbar">
                <div class="m3-bud__period" role="group" :aria-label="t('budgeting.period')">
                    <M3IconButton :aria-label="t('btn.prev')" @click="shiftMonth(-1)">
                        <AppIcon icon="chevron-left" />
                    </M3IconButton>
                    <span class="m3-bud__period-label" aria-live="polite">
                        <AppIcon icon="calendar" class="m3-bud__period-icon" />
                        <!-- Label singkat di mobile (satu baris), lengkap di ≥640px -->
                        <span class="m3-bud__period-value m3-bud__period-value--short">{{ periodLabelShort }}</span>
                        <span class="m3-bud__period-value m3-bud__period-value--long">{{ periodLabel }}</span>
                    </span>
                    <M3IconButton :aria-label="t('btn.next')" @click="shiftMonth(1)">
                        <AppIcon icon="chevron-right" />
                    </M3IconButton>
                </div>

                <div v-if="isCurrentPeriod" class="m3-bud__actions">
                    <!-- Belum ada budget: CTA buat manual — icon-only di mobile, berlabel ≥640px -->
                    <template v-if="!budgetData && isCurrentPeriod">
                        <M3IconButton
                            variant="tonal"
                            class="m3-bud__create-icon-only"
                            :href="route('budgeting.create')"
                            :aria-label="t('budgeting.createManual')"
                            :title="t('budgeting.createManual')"
                        >
                            <AppIcon icon="plus" />
                        </M3IconButton>
                        <M3Button
                            variant="tonal"
                            size="sm"
                            class="m3-bud__create-labeled"
                            :href="route('budgeting.create')"
                        >
                            <template #leading><AppIcon icon="plus" /></template>
                            {{ t('budgeting.createManual') }}
                        </M3Button>
                    </template>
                    <template v-if="budgetData && isCurrentPeriod">
                        <M3IconButton
                            variant="tonal"
                            :href="route('budgeting.create')"
                            :aria-label="t('budgeting.edit')"
                            :title="t('budgeting.edit')"
                        >
                            <AppIcon icon="pencil" />
                        </M3IconButton>
                        <M3IconButton
                            variant="standard"
                            :disabled="isGenerating"
                            :aria-label="t('budgeting.refresh')"
                            :title="t('budgeting.refresh')"
                            @click="requestRegenerate"
                        >
                            <M3CircularProgress v-if="isGenerating" size="sm" />
                            <AppIcon v-else icon="refresh-cw" />
                        </M3IconButton>
                    </template>
                </div>
            </div>

            <!-- ── Loading: skeleton komposisi (tanpa layout shift) ── -->
            <M3BudgetSkeleton v-if="isLoading" />

            <!-- ── Error state ── -->
            <M3Card v-else-if="loadError" variant="outlined" padding="lg" class="m3-bud__error">
                <AppIcon icon="triangle-alert" class="m3-bud__error-icon" />
                <p class="m3-bud__error-text">{{ t('budgeting.loadError') }}</p>
                <M3Button variant="tonal" size="sm" @click="fetchBudget">
                    {{ t('budgeting.retry') }}
                </M3Button>
            </M3Card>

            <template v-else>
                <!-- ── Empty state: belum ada budget periode ini ── -->
                <M3Card v-if="!budgetData" variant="filled" padding="lg" class="m3-bud__empty">
                    <div class="m3-bud__empty-hero">
                        <img
                            v-if="botAvatar && !avatarFailed"
                            :src="botAvatar"
                            alt=""
                            class="m3-bud__avatar m3-bud__avatar--lg"
                            @error="onAvatarError"
                        >
                        <span v-else class="m3-bud__avatar m3-bud__avatar--lg m3-bud__avatar--fallback" aria-hidden="true">
                            {{ initials }}
                        </span>
                        <div class="m3-bud__empty-text">
                            <p class="m3-bud__empty-bot">{{ botName }}</p>
                            <p class="m3-bud__empty-desc">{{ t('budgeting.emptyDesc') }}</p>
                        </div>
                    </div>

                    <h2 class="m3-bud__empty-title">{{ t('budgeting.emptyTitle', { bot: botName }) }}</h2>

                    <M3Button
                        v-if="isCurrentPeriod"
                        variant="filled"
                        class="m3-bud__empty-cta"
                        :disabled="isGenerating"
                        @click="generateBudget"
                    >
                        <template #leading>
                            <M3CircularProgress v-if="isGenerating" size="sm" />
                            <AppIcon v-else icon="sparkles" />
                        </template>
                        {{ isGenerating ? t('budgeting.generating') : t('budgeting.generate', { bot: botName }) }}
                    </M3Button>
                    <div v-else class="m3-bud__lock">
                        <AppIcon icon="lock" class="m3-bud__lock-icon" />
                        <span>{{ t('budgeting.pastPeriod') }}</span>
                    </div>

                    <div v-if="generateError" class="m3-bud__banner m3-bud__banner--error">
                        <p class="m3-bud__banner-text">{{ generateError }}</p>
                        <M3Button variant="tonal" size="sm" @click="generateBudget">
                            {{ t('budgeting.retry') }}
                        </M3Button>
                    </div>
                </M3Card>

                <!-- ── Snapshot periode aktif terakhir (kontinuitas saat periode kosong) ── -->
                <M3Card v-if="lastBudget" variant="outlined" padding="lg" class="m3-bud__last">
                    <div class="m3-bud__last-head">
                        <span class="m3-bud__last-icon" aria-hidden="true">
                            <AppIcon icon="calendar-days" class="m3-bud__last-glyph" />
                        </span>
                        <div class="m3-bud__last-titles">
                            <p class="m3-bud__last-label">{{ t('budgeting.lastPeriod') }}</p>
                            <p class="m3-bud__last-month">{{ lastBudgetLabel }}</p>
                        </div>
                        <M3Button variant="tonal" size="sm" @click="openLastBudget">
                            {{ t('budgeting.openLastPeriod') }}
                        </M3Button>
                    </div>
                    <div class="m3-bud__last-stats">
                        <div class="m3-bud__last-stat">
                            <span class="m3-bud__last-stat-label">{{ t('budgeting.totalBudget') }}</span>
                            <span class="m3-bud__last-stat-value">{{ formatRupiah(lastBudget.total_budget_amount) }}</span>
                        </div>
                        <div class="m3-bud__last-stat">
                            <span class="m3-bud__last-stat-label">{{ t('budgeting.totalSpent') }}</span>
                            <span class="m3-bud__last-stat-value">{{ formatRupiah(lastBudgetSpent) }}</span>
                        </div>
                    </div>
                </M3Card>

                <!-- ── Active state: budget ada ── -->
                <template v-else>
                    <!-- Summary card -->
                    <M3Card variant="outlined" padding="lg" class="m3-bud__summary">
                        <div class="m3-bud__summary-head">
                            <img
                                v-if="botAvatar && !avatarFailed"
                                :src="botAvatar"
                                alt=""
                                class="m3-bud__avatar"
                                @error="onAvatarError"
                            >
                            <span v-else class="m3-bud__avatar m3-bud__avatar--fallback" aria-hidden="true">
                                {{ initials }}
                            </span>
                            <div class="m3-bud__summary-text">
                                <h2 class="m3-bud__summary-title">{{ t('budgeting.monthlyTitle') }}</h2>
                                <p class="m3-bud__summary-meta">
                                    <span class="m3-bud__summary-bot">{{ botName }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ periodLabel }}<span v-if="createdLabel"> · {{ createdLabel }}</span></span>
                                </p>
                            </div>
                            <M3IconButton :aria-label="t('budgeting.aiNotes')" :title="t('budgeting.aiNotes')" @click="showAiNotes = true">
                                <AppIcon icon="lightbulb" />
                            </M3IconButton>
                        </div>

                        <div
                            class="m3-bud__summary-hero"
                            :class="{ 'm3-bud__summary-hero--over': totalRemaining < 0 }"
                        >
                            <span class="m3-bud__summary-hero-label">{{ t('budgeting.totalRemaining') }}</span>
                            <span class="m3-bud__summary-hero-value">{{ formatRupiah(totalRemaining) }}</span>
                            <span class="m3-bud__summary-hero-hint">{{ remainingHint }}</span>
                        </div>

                        <div class="m3-bud__summary-bar">
                            <M3ProgressBar
                                :value="totalPct"
                                :over="totalRemaining < 0"
                                :aria-label="t('budgeting.totalSpent')"
                            />
                        </div>

                        <div class="m3-bud__facts">
                            <div class="m3-bud__fact">
                                <span class="m3-bud__fact-label">{{ t('budgeting.totalBudget') }}</span>
                                <span class="m3-bud__fact-value">{{ formatRupiah(totalBudget) }}</span>
                            </div>
                            <span class="m3-bud__fact-divider" aria-hidden="true"></span>
                            <div class="m3-bud__fact">
                                <span class="m3-bud__fact-label">{{ t('budgeting.totalSpent') }}</span>
                                <span class="m3-bud__fact-value">{{ formatRupiah(totalSpent) }}</span>
                            </div>
                        </div>

                        <p v-if="!isCurrentPeriod" class="m3-bud__hint">{{ t('budgeting.pastPeriodHint') }}</p>
                        <div v-if="generateError" class="m3-bud__banner m3-bud__banner--error">
                            <p class="m3-bud__banner-text">{{ generateError }}</p>
                            <M3Button variant="tonal" size="sm" @click="generateBudget">
                                {{ t('budgeting.retry') }}
                            </M3Button>
                        </div>
                    </M3Card>

                    <!-- Daftar grup pengeluaran (accordion) -->
                    <div class="m3-bud__groups">
                        <M3Card
                            v-for="row in visibleRows"
                            :key="row.key"
                            variant="outlined"
                            padding="none"
                            class="m3-bud__group"
                        >
                            <button
                                type="button"
                                class="m3-bud__group-head"
                                :aria-expanded="expandedGroups.has(row.key)"
                                :aria-controls="expandedGroups.has(row.key) ? `budget-group-members-${row.key}` : undefined"
                                @click="toggleGroup(row.key)"
                            >
                                <span class="m3-bud__group-ico">
                                    <AppIcon icon="layers" />
                                </span>
                                <span class="m3-bud__group-text">
                                    <span class="m3-bud__group-name">{{ row.name }}</span>
                                    <span class="m3-bud__group-sub">
                                        {{ row.members.length }} {{ t('budgeting.categories') }}
                                        <template v-if="isOver(row)">
                                            <span aria-hidden="true"> · </span>
                                            <span class="m3-bud__group-over">{{ t('budgeting.overBudget') }}</span>
                                        </template>
                                    </span>
                                </span>
                                <span class="m3-bud__group-total">{{ formatRupiah(row.target) }}</span>
                                <span
                                    class="m3-bud__group-chevron"
                                    :class="{ 'm3-bud__group-chevron--open': expandedGroups.has(row.key) }"
                                    aria-hidden="true"
                                >
                                    <AppIcon icon="chevron-down" />
                                </span>
                            </button>

                            <!-- Anggota grup (expand) -->
                            <Transition name="m3-bud-expand">
                                <div
                                    v-if="expandedGroups.has(row.key)"
                                    :id="`budget-group-members-${row.key}`"
                                    class="m3-bud__members"
                                >
                                    <div v-for="m in row.members" :key="m.id" class="m3-bud__member">
                                        <span class="m3-bud__member-ico">
                                            <AppIcon
                                                :icon="m.icon"
                                                fallback="folder"
                                                :class="getM3CategoryIconClass(m.type?.name)"
                                            />
                                        </span>
                                        <div class="m3-bud__member-main">
                                            <div class="m3-bud__member-top">
                                                <p class="m3-bud__member-name" :title="m.name">{{ m.name }}</p>
                                                <span
                                                    class="m3-bud__member-pct"
                                                    :class="{ 'm3-bud__member-pct--over': isOver(m) }"
                                                >{{ pct(m) }}%</span>
                                            </div>
                                            <div class="m3-bud__member-bar">
                                                <M3ProgressBar :value="pct(m)" :over="isOver(m)" size="sm" :aria-label="m.name" />
                                            </div>
                                            <p class="m3-bud__member-amounts">
                                                <span
                                                    class="m3-bud__member-spent"
                                                    :class="{ 'm3-bud__member-spent--over': isOver(m) }"
                                                >
                                                    {{ formatRupiah(m.spent) }}
                                                </span>
                                                <span class="m3-bud__member-target">/ {{ formatRupiah(m.target) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <p v-if="row.members.length === 0" class="m3-bud__members-empty">
                                        — {{ t('common.noData') }} —
                                    </p>
                                </div>
                            </Transition>
                        </M3Card>
                    </div>
                </template>
            </template>

            <!-- ── AI Insights modal ── -->
            <M3Modal
                :show="showAiNotes"
                :title="t('budgeting.aiNotesTitle', { bot: botName })"
                variant="dialog"
                mobile-variant="sheet"
                max-width="sm"
                @close="showAiNotes = false"
            >
                <div class="m3-bud__notes-meta">
                    <span>{{ periodLabel }}<span v-if="createdLabel"> · {{ createdLabel }}</span></span>
                </div>
                <div class="m3-bud__notes-panel">
                    <p v-if="budgetData?.ai_notes" class="m3-bud__notes-text">{{ budgetData.ai_notes }}</p>
                    <p v-else class="m3-bud__notes-empty">—</p>
                </div>
                <template #footer>
                    <M3Button variant="tonal" size="sm" @click="showAiNotes = false">
                        {{ t('budgeting.close') }}
                    </M3Button>
                </template>
            </M3Modal>

            <!-- ── Konfirmasi generate ulang ── -->
            <M3Modal
                :show="showRegenerateConfirm"
                :title="t('budgeting.refreshConfirmTitle')"
                variant="dialog"
                mobile-variant="sheet"
                max-width="sm"
                @close="showRegenerateConfirm = false"
            >
                <p class="m3-bud__confirm-text">{{ t('budgeting.refreshConfirmMsg') }}</p>
                <template #footer>
                    <M3Button variant="text" size="sm" @click="showRegenerateConfirm = false">
                        {{ t('common.cancel') }}
                    </M3Button>
                    <M3Button variant="filled" size="sm" @click="confirmRegenerate">
                        {{ t('budgeting.refreshConfirmCta') }}
                    </M3Button>
                </template>
            </M3Modal>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-bud {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

/* ── Toolbar ── */
.m3-bud__toolbar {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-bud__period {
    display: flex;
    flex: 0 1 auto;
    align-items: center;
    justify-content: flex-start;
    gap: var(--m3-space-1);
}

.m3-bud__period-label {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-height: var(--m3-size-touch-target);
    padding: 0 var(--m3-space-4);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
    white-space: nowrap;
}

.m3-bud__period-icon {
    color: var(--m3-color-primary);
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-bud__period-value {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
}

/* Label periode: singkat di mobile ("Jan 2026"), lengkap di layar lebar */
.m3-bud__period-value--short {
    display: inline;
}

.m3-bud__period-value--long {
    display: none;
}

.m3-bud__actions {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: flex-end;
    gap: var(--m3-space-2);
}

/* CTA "Buat manual": icon-only di mobile, berlabel mulai 640px (paritas
   dengan tema default) */
.m3-bud__create-labeled {
    display: none;
}

/* ── States ── */
.m3-bud__error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-3);
    text-align: center;
}

.m3-bud__error-icon {
    width: var(--m3-size-icon-xl);
    height: var(--m3-size-icon-xl);
    color: var(--m3-color-expense);
}

.m3-bud__error-text {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
}

/* ── Empty hero ── */
.m3-bud__empty-hero {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-bud__empty-text {
    min-width: 0;
}

.m3-bud__empty-bot {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
}

.m3-bud__empty-desc {
    margin-top: var(--m3-space-1);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__empty-title {
    margin-top: var(--m3-space-4);
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
}

.m3-bud__empty-cta {
    margin-top: var(--m3-space-5);
}

/* Label CTA boleh wrap — nowrap bawaan M3Button meluber di viewport
   320px ("Ask Ken-Chan to generate budget" melewati tepi layar). */
.m3-bud__empty-cta :deep(.m3-btn__label) {
    white-space: normal;
    text-align: start;
}

/* ── Snapshot periode aktif terakhir ── */
.m3-bud__last-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-bud__last-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    flex-shrink: 0;
}

.m3-bud__last-glyph {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-bud__last-titles {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
    min-width: 0;
    flex: 1;
}

.m3-bud__last-label {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__last-month {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-bud__last-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-4);
}

.m3-bud__last-stat {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container);
}

.m3-bud__last-stat-label {
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__last-stat-value {
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-bud__lock {
    display: inline-flex;
    align-items: flex-start;
    gap: var(--m3-space-2);    margin-top: var(--m3-space-5);
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
}

.m3-bud__lock-icon {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

/* ── Avatar ── */
.m3-bud__avatar {
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 0 0 1px var(--m3-color-outline-variant);
}

.m3-bud__avatar--lg {
    width: var(--m3-size-touch-target-lg);
    height: var(--m3-size-touch-target-lg);
}

.m3-bud__avatar--fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-title-medium-size);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
}

/* ── Summary ── */
.m3-bud__summary-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
}

.m3-bud__summary-text {
    flex: 1;
    min-width: 0;
}

.m3-bud__summary-title {
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    letter-spacing: var(--m3-type-title-medium-tracking);
}

.m3-bud__summary-meta {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1);
    flex-wrap: wrap;
    margin-top: var(--m3-space-1);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__summary-bot {
    color: var(--m3-color-primary);
    font-weight: var(--m3-type-emphasized-body-small-weight);
}

.m3-bud__summary-hero {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    margin-top: var(--m3-space-4);
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
}

.m3-bud__summary-hero--over {
    background: var(--m3-color-expense-container);
}

.m3-bud__summary-hero-label {
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-medium-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__summary-hero-value {
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    letter-spacing: var(--m3-type-headline-small-tracking);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.m3-bud__summary-hero-hint {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__summary-hero--over .m3-bud__summary-hero-label,
.m3-bud__summary-hero--over .m3-bud__summary-hero-hint {
    color: color-mix(in srgb, var(--m3-color-expense) 72%, transparent);
}

.m3-bud__summary-hero--over .m3-bud__summary-hero-value {
    color: var(--m3-color-expense);
}

.m3-bud__facts {
    display: flex;
    align-items: stretch;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-3);
}

.m3-bud__fact {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
}

.m3-bud__fact-label {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__fact-value {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.m3-bud__fact-divider {
    width: 1px;
    flex-shrink: 0;
    background: var(--m3-color-outline-variant);
}

.m3-bud__summary-bar {
    display: flex;
    align-items: center;
    margin-top: var(--m3-space-3);
}

.m3-bud__summary-bar > :deep(.m3-progress) {
    flex: 1;
    min-width: 0;
}

.m3-bud__hint {
    margin-top: var(--m3-space-3);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

/* ── Banner error (generate) ── */
.m3-bud__banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-4);
    padding: var(--m3-space-3) var(--m3-space-4);
    border-radius: var(--m3-radius-md);
}

.m3-bud__banner--error {
    background: var(--m3-color-expense-container);
}

.m3-bud__banner-text {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-expense);
    overflow-wrap: anywhere;
}

/* ── Daftar grup (accordion) ── */
.m3-bud__groups {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-bud__group {
    min-width: 0;
    overflow: hidden;
}

.m3-bud__group-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    width: 100%;
    min-width: 0;
    min-height: var(--m3-size-touch-target-lg);
    padding: var(--m3-space-2) var(--m3-space-3);
    border: none;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-bud__group-head:hover {
    background: color-mix(in srgb, var(--m3-color-on-surface) 8%, transparent);
}

.m3-bud__group-head:active {
    background: color-mix(in srgb, var(--m3-color-on-surface) 12%, transparent);
}

.m3-bud__group-head:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: -2px;
}

.m3-bud__group-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-bud__group-ico :deep(svg) {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-bud__group-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
}

.m3-bud__group-name {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-bud__group-sub {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-bud__group-over {
    color: var(--m3-color-expense);
    font-weight: var(--m3-type-emphasized-body-small-weight);
}

.m3-bud__group-total {
    flex-shrink: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.m3-bud__group-chevron {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-bud__group-chevron--open {
    transform: rotate(180deg);
}

/* ── Anggota grup ── */
.m3-bud__members {
    min-width: 0;
    padding: var(--m3-space-1) var(--m3-space-3) var(--m3-space-3);
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-bud__member {
    display: flex;
    align-items: flex-start;
    gap: var(--m3-space-3);
    min-width: 0;
    padding: var(--m3-space-2) 0;
}

.m3-bud__member + .m3-bud__member {
    margin-top: var(--m3-space-1);
}

.m3-bud__member-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    flex-shrink: 0;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-bud__member-ico :deep(svg) {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
}

.m3-bud__member-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-bud__member-top {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-bud__member-name {
    flex: 1;
    min-width: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.m3-bud__member-pct {
    flex-shrink: 0;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-emphasized-label-medium-weight);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__member-pct--over {
    color: var(--m3-color-expense);
}

.m3-bud__member-bar {
    min-width: 0;
}

.m3-bud__member-amounts {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-variant-numeric: tabular-nums;
    color: var(--m3-color-on-surface-variant);
    overflow-wrap: anywhere;
}

.m3-bud__member-spent {
    font-weight: var(--m3-type-emphasized-body-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-bud__member-spent--over {
    color: var(--m3-color-expense);
}

.m3-bud__member-target {
    white-space: nowrap;
}

.m3-bud__members-empty {
    padding: var(--m3-space-3) 0;
    text-align: center;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

/* ── Modal konten ── */
.m3-bud__notes-meta {
    margin-bottom: var(--m3-space-2);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__notes-panel {
    max-height: 16rem;
    overflow-y: auto;
    padding: var(--m3-space-4);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-highest);
}

.m3-bud__notes-text {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface);
    white-space: pre-line;
}

.m3-bud__notes-empty {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bud__confirm-text {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

/* ── Expand transition ── */
.m3-bud-expand-enter-active,
.m3-bud-expand-leave-active {
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-bud-expand-enter-from,
.m3-bud-expand-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .m3-bud__group-chevron,
    .m3-bud__group-head {
        transition: none;
    }

    .m3-bud-expand-enter-active,
    .m3-bud-expand-leave-active {
        transition: none;
    }
}

/* ── Responsif ── */
@media (min-width: 640px) {
    /* Label periode lengkap + CTA "Buat manual" berlabel di layar lebar.
       Specificity .m3-bud__toolbar... ditingkatkan agar menang atas
       display komponen (.m3-btn / .m3-icon-btn). */
    .m3-bud__period-value--short {
        display: none;
    }

    .m3-bud__period-value--long {
        display: inline;
    }

    .m3-bud__toolbar .m3-bud__create-icon-only {
        display: none;
    }

    .m3-bud__toolbar .m3-bud__create-labeled {
        display: inline-flex;
    }

    .m3-bud__summary-hero-value {
        font-size: var(--m3-type-headline-medium-size);
        line-height: var(--m3-type-headline-medium-line-height);
        font-weight: var(--m3-type-emphasized-headline-medium-weight);
        letter-spacing: var(--m3-type-headline-medium-tracking);
    }
}
</style>
