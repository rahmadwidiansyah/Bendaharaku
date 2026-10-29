<script setup>
/**
 * Material/Pages/Settings/AI/Models.vue — Provider & Model (tema M3).
 *
 * Paritas perilaku Pages/Settings/AI/Models.vue (Default): kelola akun pool
 * (tambah/uji/refresh/aktif-hapus + katalog model per akun), routing
 * (otomatis/manual, kebijakan biaya, fallback manual maks 3), serta kandidat
 * dan entri yang dilewati. Endpoint/payload identik dengan Default.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3SettingsBack from '@/Material/Components/Settings/M3SettingsBack.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3Switch from '@/Material/Components/M3Switch.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3InfoPopover from '@/Material/Components/M3InfoPopover.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    aiAccounts: { type: Array, default: () => [] },
    aiRouting: { type: Object, default: () => ({}) },
    aiCandidates: { type: Array, default: () => [] },
    aiSkipped: { type: Array, default: () => [] },
})

const { t, locale } = useI18n()
const { showToast } = useToast()

const formatDateTime = (iso) => {
    if (!iso) return ''
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return iso
    try {
        return d.toLocaleString(locale.value === 'en' ? 'en-US' : 'id-ID', { dateStyle: 'short', timeStyle: 'short' })
    } catch {
        return iso
    }
}

const providers = ['gemini', 'openai', 'deepseek', 'openai-compatible']

const poolAccounts = ref([...props.aiAccounts])
const poolCandidates = ref([...props.aiCandidates])
const poolSkipped = ref([...props.aiSkipped])
const newAccount = ref({ provider: 'gemini', alias: '', api_key: '', base_url: '', priority: 1 })
const addingAccount = ref(false)
const routingForm = ref({
    mode: props.aiRouting?.mode || 'automatic',
    primary_provider: props.aiRouting?.primary_provider || '',
    primary_model: props.aiRouting?.primary_model || '',
    free_policy: props.aiRouting?.free_policy || 'free_first',
    allow_paid_fallback: !!props.aiRouting?.allow_paid_fallback,
    fallback_models: [...(props.aiRouting?.fallback_models || [])].slice(0, 3),
})
const accountModels = ref({})
const expandedAccount = ref(null)
const loadingModelsFor = ref(null)
const savingRouting = ref(false)
const busyAccountId = ref(null)
const removeTarget = ref(null)
const removingAccount = ref(false)

const healthLabel = (status) => {
    const key = `settings.ai.status.health.${status}`
    const label = t(key)
    return label === key ? status : label
}

const healthKind = (status) => {
    if (status === 'healthy') return 'ok'
    if (status === 'invalid' || status === 'disabled') return 'bad'
    if (status === 'rate_limited') return 'warn'
    return 'neutral'
}

const toneForHealth = (status) => {
    if (status === 'healthy') return 'success'
    if (status === 'invalid' || status === 'disabled') return 'danger'
    if (status === 'rate_limited') return 'warning'
    return 'neutral'
}

const accountNotices = ref({})
const routingNotice = ref(null)

const noticeFor = (id) => accountNotices.value[id] || null

function setAccountNotice(id, notice) {
    accountNotices.value = { ...accountNotices.value, [id]: notice }
}

function clearAccountNotice(id) {
    if (!accountNotices.value[id]) return
    const next = { ...accountNotices.value }
    delete next[id]
    accountNotices.value = next
}

async function refreshPool() {
    try {
        const { data } = await axios.get(route('settings.ai.accounts.index'))
        poolAccounts.value = data.accounts || []
        poolCandidates.value = (data.candidates || []).slice(0, 8)
        poolSkipped.value = (data.skipped || []).slice(0, 20)
        if (data.routing) {
            routingForm.value = {
                mode: data.routing.mode || 'automatic',
                primary_provider: data.routing.primary_provider || '',
                primary_model: data.routing.primary_model || '',
                free_policy: data.routing.free_policy || 'free_first',
                allow_paid_fallback: !!data.routing.allow_paid_fallback,
                fallback_models: [...(data.routing.fallback_models || [])].slice(0, 3),
            }
        }
    } catch (err) {
        showToast(err.response?.data?.message || t('settings.ai.models.messages.load_failed'), 'error')
    }
}

async function addAccount() {
    if (addingAccount.value || !newAccount.value.api_key) return
    addingAccount.value = true
    try {
        await axios.post(route('settings.ai.accounts.store'), { ...newAccount.value })
        showToast(t('settings.ai.models.messages.account_added'), 'success')
        newAccount.value = { provider: 'gemini', alias: '', api_key: '', base_url: '', priority: 1 }
        await refreshPool()
    } catch (err) {
        showToast(err.response?.data?.message || t('settings.ai.models.messages.add_failed'), 'error')
    } finally {
        addingAccount.value = false
    }
}

async function testAccount(id) {
    busyAccountId.value = id
    try {
        const { data } = await axios.post(route('settings.ai.accounts.test', { id }))
        setAccountNotice(id, {
            tone: 'success',
            titleKey: 'settings.ai.models.test_button',
            lines: [
                data.message,
                data.model ? `${data.model}${data.latency_ms != null ? ` · ${data.latency_ms} ms` : ''}` : null,
            ].filter(Boolean),
        })
        showToast(t('settings.ai.models.messages.test_ok'), 'success')
        await refreshPool()
    } catch (err) {
        const data = err.response?.data || {}
        setAccountNotice(id, {
            tone: 'danger',
            titleKey: 'settings.ai.models.test_button',
            lines: [
                data.message || t('settings.ai.models.messages.test_failed'),
                data.model ? `${data.model}${data.latency_ms != null ? ` · ${data.latency_ms} ms` : ''}` : null,
                data.error_code,
            ].filter(Boolean),
        })
        showToast(t('settings.ai.models.messages.test_failed'), 'error')
        await refreshPool()
    } finally {
        busyAccountId.value = null
    }
}

async function refreshDiscovery(id) {
    busyAccountId.value = id
    try {
        const { data } = await axios.post(route('settings.ai.accounts.refresh', { id }))
        const discovery = data.discovery || {}
        if (discovery.error) {
            setAccountNotice(id, {
                tone: 'danger',
                titleKey: 'settings.ai.models.pool.actions.refresh',
                lines: [discovery.error],
            })
            showToast(t('settings.ai.models.messages.refresh_failed'), 'error')
        } else {
            setAccountNotice(id, {
                tone: 'success',
                titleKey: 'settings.ai.models.pool.actions.refresh',
                lines: [
                    t('settings.ai.models.messages.refresh_ok', { count: discovery.eligible ?? 0 }),
                    discovery.source ? `${discovery.source} · ${discovery.discovered ?? 0}` : null,
                ].filter(Boolean),
            })
            showToast(
                t('settings.ai.models.messages.refresh_ok', { count: discovery.eligible ?? 0 }),
                'success',
            )
        }
        await refreshPool()
    } catch (err) {
        const message = err.response?.data?.message || t('settings.ai.models.messages.refresh_failed')
        setAccountNotice(id, {
            tone: 'danger',
            titleKey: 'settings.ai.models.pool.actions.refresh',
            lines: [message],
        })
        showToast(t('settings.ai.models.messages.refresh_failed'), 'error')
    } finally {
        busyAccountId.value = null
    }
}

async function toggleAccountActive(acc) {
    busyAccountId.value = acc.id
    try {
        await axios.patch(route('settings.ai.accounts.update', { id: acc.id }), { is_active: !acc.is_active })
        await refreshPool()
    } catch (err) {
        showToast(err.response?.data?.message || t('settings.ai.models.messages.toggle_failed'), 'error')
    } finally {
        busyAccountId.value = null
    }
}

async function confirmDeleteAccount() {
    if (!removeTarget.value) return
    removingAccount.value = true
    try {
        await axios.delete(route('settings.ai.accounts.destroy', { id: removeTarget.value.id }))
        clearAccountNotice(removeTarget.value.id)
        showToast(t('settings.ai.models.messages.removed'), 'success')
        removeTarget.value = null
        await refreshPool()
    } catch (err) {
        showToast(err.response?.data?.message || t('settings.ai.models.messages.remove_failed'), 'error')
    } finally {
        removingAccount.value = false
    }
}

async function toggleModels(accId) {
    if (expandedAccount.value === accId) {
        expandedAccount.value = null
        return
    }
    expandedAccount.value = accId
    await loadAccountModels(accId, true)
}

async function loadAccountModels(accId, force = false) {
    if (loadingModelsFor.value === accId) return
    if (accountModels.value[accId] && !force) return
    loadingModelsFor.value = accId
    try {
        const { data } = await axios.get(route('settings.ai.accounts.models', { id: accId }))
        accountModels.value = { ...accountModels.value, [accId]: data.models || [] }
    } catch (err) {
        const message = err.response?.data?.message || t('settings.ai.models.messages.models_load_failed')
        setAccountNotice(accId, {
            tone: 'danger',
            titleKey: 'settings.ai.models.pool.actions.view_models',
            lines: [message],
        })
        showToast(t('settings.ai.models.messages.models_load_failed'), 'error')
    } finally {
        loadingModelsFor.value = null
    }
}

async function toggleModelEnabled(accId, modelId, enable) {
    try {
        await axios.patch(route('settings.ai.accounts.models.toggle', { id: accId, model: modelId }), { is_enabled: enable })
        showToast(
            enable
                ? t('settings.ai.models.messages.model_enabled', { model: modelId })
                : t('settings.ai.models.messages.model_disabled', { model: modelId }),
            'success',
        )
        await loadAccountModels(accId, true)
        await refreshPool()
    } catch (err) {
        const message = err.response?.data?.message || t('settings.ai.models.messages.model_failed')
        setAccountNotice(accId, {
            tone: 'danger',
            titleKey: enable
                ? 'settings.ai.models.pool.actions.enable_model'
                : 'settings.ai.models.pool.actions.disable_model',
            lines: [message],
        })
        showToast(t('settings.ai.models.messages.model_failed'), 'error')
    }
}

function moveFallback(index, dir) {
    const list = [...routingForm.value.fallback_models]
    const j = index + dir
    if (j < 0 || j >= list.length) return
    ;[list[index], list[j]] = [list[j], list[index]]
    routingForm.value.fallback_models = list
}

function addFallbackRow() {
    if (routingForm.value.fallback_models.length >= 3) return
    routingForm.value.fallback_models = [...routingForm.value.fallback_models, { provider: 'gemini', model: '' }]
}

function removeFallbackRow(index) {
    routingForm.value.fallback_models = routingForm.value.fallback_models.filter((_, i) => i !== index)
}

async function saveRouting() {
    if (savingRouting.value) return
    savingRouting.value = true
    try {
        const fallbacks = routingForm.value.fallback_models
            .filter((f) => f?.provider && f?.model?.trim())
            .slice(0, 3)
            .map((f) => ({ provider: f.provider, model: f.model.trim() }))
        const payload = {
            mode: routingForm.value.mode,
            primary_provider: routingForm.value.primary_provider || null,
            primary_model: routingForm.value.primary_model || null,
            fallback_models: fallbacks,
            provider_priority: ['gemini', 'openai', 'deepseek', 'openai-compatible'],
            free_policy: routingForm.value.free_policy,
            allow_paid_fallback: routingForm.value.allow_paid_fallback,
        }
        const { data } = await axios.put(route('settings.ai.routing.store'), payload)
        showToast(t('settings.ai.models.messages.routing_saved'), 'success')
        routingNotice.value = data.warnings?.length
            ? {
                tone: 'warning',
                titleKey: 'settings.ai.models.routing.title',
                lines: data.warnings,
            }
            : null
        if (data.warnings?.length) showToast(t('common.warning'), 'error')
        await refreshPool()
    } catch (err) {
        const message = err.response?.data?.message || t('settings.ai.models.messages.routing_failed')
        routingNotice.value = {
            tone: 'danger',
            titleKey: 'settings.ai.models.routing.title',
            lines: [message],
        }
        showToast(t('settings.ai.models.messages.routing_failed'), 'error')
    } finally {
        savingRouting.value = false
    }
}
</script>

<template>
    <M3Layout :title="t('settings.ai.models.title')">
        <Head :title="t('settings.ai.models.title')" />

        <div class="m3-models mx-auto w-full max-w-6xl">
            <M3PageTitle
                :title="t('settings.ai.models.title')"
                :subtitle="t('settings.ai.models.description')"
            >
                <template #leading>
                    <M3SettingsBack inline />
                </template>
            </M3PageTitle>

            <!-- Bar kembali untuk desktop (judul in-content disembunyikan) -->
            <M3SettingsBack />

            <!-- ── Akun API ─────────────────────────────────────── -->
            <section class="m3-models__section">
                <h2 class="m3-models__section-title">{{ t('settings.ai.models.pool.title') }}</h2>
                <M3Card variant="outlined" padding="md">
                    <p class="m3-models__section-desc">{{ t('settings.ai.models.pool.description') }}</p>

                    <p v-if="!poolAccounts.length" class="m3-models__empty">
                        {{ t('settings.ai.models.pool.empty') }}
                    </p>

                    <div v-for="acc in poolAccounts" :key="acc.id" class="m3-models__account">
                        <div class="m3-models__account-head">
                            <span class="m3-models__account-name">{{ acc.alias }}</span>
                            <span class="m3-models__badge">{{ String(acc.provider).toUpperCase() }}</span>
                            <span v-if="acc.key_suffix" class="m3-models__badge">
                                {{ t('settings.ai.models.pool.key_suffix', { suffix: acc.key_suffix }) }}
                            </span>
                            <span class="m3-models__badge" :class="`m3-models__badge--${healthKind(acc.health_status)}`">
                                {{ healthLabel(acc.health_status) }}
                            </span>
                            <span v-if="acc.in_cooldown" class="m3-models__badge m3-models__badge--neutral">
                                {{ t('settings.ai.status.health.cooldown') }}
                            </span>
                            <span v-if="!acc.is_active" class="m3-models__badge m3-models__badge--neutral">
                                {{ t('common.inactive') }}
                            </span>
                            <M3InfoPopover
                                :label="`${t('common.info')} · ${acc.alias}`"
                                :tone="noticeFor(acc.id)?.tone || toneForHealth(acc.health_status)"
                            >
                                <div class="m3-models__details">
                                    <div class="m3-models__detail-block">
                                        <span class="m3-models__detail-label">{{ t('common.status') }}</span>
                                        <p class="m3-models__detail-value">{{ healthLabel(acc.health_status) }}</p>
                                    </div>
                                    <div class="m3-models__detail-block">
                                        <span class="m3-models__detail-label">{{ t('settings.ai.models.test_button') }}</span>
                                        <p class="m3-models__detail-value">
                                            {{ acc.last_test_status || t('settings.ai.status.accounts.no_test') }}
                                        </p>
                                        <p v-if="acc.last_test_message" class="m3-models__detail-note">
                                            {{ acc.last_test_message }}
                                        </p>
                                        <p v-if="acc.last_tested_at" class="m3-models__detail-note">
                                            {{ formatDateTime(acc.last_tested_at) }}
                                        </p>
                                    </div>
                                    <div v-if="acc.last_discovery_at" class="m3-models__detail-block">
                                        <span class="m3-models__detail-label">
                                            {{ t('settings.ai.models.pool.actions.refresh') }}
                                        </span>
                                        <p class="m3-models__detail-note">
                                            {{ t('settings.ai.models.pool.discovery_at', { time: formatDateTime(acc.last_discovery_at) }) }}
                                        </p>
                                        <p v-if="acc.discovery_status" class="m3-models__detail-note">
                                            {{ acc.discovery_status }}
                                        </p>
                                    </div>
                                    <div v-if="acc.in_cooldown" class="m3-models__detail-block">
                                        <span class="m3-models__detail-label">
                                            {{ t('settings.ai.status.health.cooldown') }}
                                        </span>
                                        <p v-if="acc.cooldown_until" class="m3-models__detail-note">
                                            {{ formatDateTime(acc.cooldown_until) }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="noticeFor(acc.id)"
                                        class="m3-models__notice"
                                        :class="`m3-models__notice--${noticeFor(acc.id).tone}`"
                                    >
                                        <p class="m3-models__notice-title">{{ t(noticeFor(acc.id).titleKey) }}</p>
                                        <p
                                            v-for="(line, i) in noticeFor(acc.id).lines"
                                            :key="i"
                                            class="m3-models__detail-note"
                                        >
                                            {{ line }}
                                        </p>
                                    </div>
                                </div>
                            </M3InfoPopover>
                        </div>

                        <p class="m3-models__account-meta">
                            {{ t('settings.ai.models.pool.priority', { priority: acc.priority }) }}
                            · {{ t('settings.ai.models.pool.models_count', { count: acc.model_count }) }}
                        </p>

                        <div class="m3-models__account-actions">
                            <M3Button
                                variant="tonal"
                                size="sm"
                                :disabled="busyAccountId === acc.id || acc.id === 0"
                                @click="testAccount(acc.id)"
                            >
                                {{ t('settings.ai.models.pool.actions.test') }}
                            </M3Button>
                            <M3Button
                                variant="text"
                                size="sm"
                                :disabled="busyAccountId === acc.id || acc.id === 0"
                                @click="refreshDiscovery(acc.id)"
                            >
                                {{ t('settings.ai.models.pool.actions.refresh') }}
                            </M3Button>
                            <M3Button
                                v-if="acc.id !== 0"
                                variant="text"
                                size="sm"
                                :disabled="busyAccountId === acc.id"
                                @click="toggleAccountActive(acc)"
                            >
                                {{ acc.is_active ? t('settings.ai.models.pool.actions.deactivate') : t('settings.ai.models.pool.actions.activate') }}
                            </M3Button>
                            <M3Button
                                v-if="acc.id !== 0"
                                variant="text"
                                size="sm"
                                class="m3-models__danger"
                                @click="removeTarget = acc"
                            >
                                {{ t('settings.ai.models.pool.actions.remove') }}
                            </M3Button>
                        </div>

                        <div v-if="acc.id !== 0" class="m3-models__account-models">
                            <M3Button variant="outlined" size="sm" @click="toggleModels(acc.id)">
                                {{ expandedAccount === acc.id
                                    ? t('settings.ai.models.pool.actions.hide_models')
                                    : (accountModels[acc.id] ? t('settings.ai.models.pool.actions.reload_models') : t('settings.ai.models.pool.actions.view_models')) }}
                            </M3Button>

                            <div v-if="expandedAccount === acc.id" class="m3-models__model-list">
                                <p v-if="loadingModelsFor === acc.id && !accountModels[acc.id]" class="m3-models__empty">
                                    {{ t('common.loading') }}
                                </p>
                                <div
                                    v-for="m in accountModels[acc.id] || []"
                                    :key="m.model_id"
                                    class="m3-models__model-row"
                                >
                                    <div class="m3-models__model-info">
                                        <span class="m3-models__model-id">{{ m.model_id }}</span>
                                        <span class="m3-models__model-meta">{{ m.free_tier }} · {{ m.category }}</span>
                                        <span v-if="!m.is_enabled" class="m3-models__model-state">
                                            <span class="m3-models__badge m3-models__badge--bad">
                                                {{ t('common.inactive') }}
                                            </span>
                                            <M3InfoPopover
                                                v-if="m.last_error"
                                                :label="`${t('common.warning')} · ${m.model_id}`"
                                                tone="danger"
                                            >
                                                <p class="m3-models__detail-note">{{ m.last_error }}</p>
                                            </M3InfoPopover>
                                        </span>
                                    </div>
                                    <M3Button
                                        :variant="m.is_enabled ? 'text' : 'tonal'"
                                        size="sm"
                                        @click="toggleModelEnabled(acc.id, m.model_id, !m.is_enabled)"
                                    >
                                        {{ m.is_enabled ? t('settings.ai.models.pool.actions.disable_model') : t('settings.ai.models.pool.actions.enable_model') }}
                                    </M3Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tambah akun -->
                    <div class="m3-models__add">
                        <h3 class="m3-models__add-title">{{ t('settings.ai.models.pool.add.title') }}</h3>
                        <div class="m3-models__chips">
                            <M3Chip
                                v-for="p in providers"
                                :key="p"
                                variant="filter"
                                :selected="newAccount.provider === p"
                                @click="newAccount.provider = p"
                            >
                                {{ p }}
                            </M3Chip>
                        </div>
                        <M3TextField
                            v-model="newAccount.alias"
                            :label="t('settings.ai.models.pool.add.alias')"
                            :placeholder="t('settings.ai.models.pool.add.alias_placeholder')"
                        />
                        <M3TextField
                            v-model="newAccount.api_key"
                            type="password"
                            :label="t('settings.ai.models.pool.add.api_key')"
                            :placeholder="t('settings.ai.models.pool.add.api_key_placeholder')"
                            autocomplete="new-password"
                        />
                        <M3TextField
                            v-if="newAccount.provider === 'openai-compatible'"
                            v-model="newAccount.base_url"
                            :label="t('settings.ai.models.pool.add.base_url')"
                            :placeholder="t('settings.ai.models.pool.add.base_url_placeholder')"
                        />
                        <M3Button
                            variant="filled"
                            :disabled="addingAccount || !newAccount.api_key"
                            @click="addAccount"
                        >
                            {{ addingAccount ? t('settings.ai.models.pool.add.submitting') : t('settings.ai.models.pool.add.submit') }}
                        </M3Button>
                    </div>
                </M3Card>
            </section>

            <!-- ── Routing ──────────────────────────────────────── -->
            <section class="m3-models__section">
                <h2 class="m3-models__section-title">{{ t('settings.ai.models.routing.title') }}</h2>
                <M3Card variant="outlined" padding="md">
                    <p class="m3-models__section-desc">{{ t('settings.ai.models.routing.description') }}</p>

                    <div class="m3-models__field">
                        <p class="m3-models__field-label">{{ t('settings.ai.models.routing.mode') }}</p>
                        <div class="m3-models__chips">
                            <M3Chip variant="filter" :selected="routingForm.mode === 'automatic'" @click="routingForm.mode = 'automatic'">
                                {{ t('settings.ai.models.routing.mode_automatic') }}
                            </M3Chip>
                            <M3Chip variant="filter" :selected="routingForm.mode === 'manual'" @click="routingForm.mode = 'manual'">
                                {{ t('settings.ai.models.routing.mode_manual') }}
                            </M3Chip>
                        </div>
                    </div>

                    <div class="m3-models__field">
                        <p class="m3-models__field-label">{{ t('settings.ai.models.routing.free_policy') }}</p>
                        <div class="m3-models__chips">
                            <M3Chip variant="filter" :selected="routingForm.free_policy === 'free_only'" @click="routingForm.free_policy = 'free_only'">
                                {{ t('settings.ai.models.routing.free_only') }}
                            </M3Chip>
                            <M3Chip variant="filter" :selected="routingForm.free_policy === 'free_first'" @click="routingForm.free_policy = 'free_first'">
                                {{ t('settings.ai.models.routing.free_first') }}
                            </M3Chip>
                            <M3Chip variant="filter" :selected="routingForm.free_policy === 'paid_allowed'" @click="routingForm.free_policy = 'paid_allowed'">
                                {{ t('settings.ai.models.routing.paid_allowed') }}
                            </M3Chip>
                        </div>
                    </div>

                    <div class="m3-models__switch-row">
                        <div class="m3-models__switch-text">
                            <p class="m3-models__field-label">{{ t('settings.ai.models.routing.allow_paid') }}</p>
                            <p class="m3-models__switch-supporting">{{ t('settings.ai.models.routing.allow_paid_supporting') }}</p>
                        </div>
                        <M3Switch
                            v-model="routingForm.allow_paid_fallback"
                            :aria-label="t('settings.ai.models.routing.allow_paid')"
                        />
                    </div>

                    <div v-if="routingForm.mode === 'manual'" class="m3-models__manual">
                        <M3TextField
                            v-model="routingForm.primary_provider"
                            :label="t('settings.ai.models.routing.primary_provider')"
                        />
                        <M3TextField
                            v-model="routingForm.primary_model"
                            :label="t('settings.ai.models.routing.primary_model')"
                            :placeholder="t('settings.ai.models.routing.primary_model_placeholder')"
                        />

                        <div class="m3-models__field">
                            <p class="m3-models__field-label">{{ t('settings.ai.models.routing.fallbacks') }}</p>
                            <p class="m3-models__switch-supporting">{{ t('settings.ai.models.routing.fallbacks_hint') }}</p>
                        </div>

                        <div v-for="(fb, i) in routingForm.fallback_models" :key="i" class="m3-models__fallback">
                            <p class="m3-models__field-label">{{ t('settings.ai.models.routing.fallback_provider', { n: i + 1 }) }}</p>
                            <div class="m3-models__chips">
                                <M3Chip
                                    v-for="p in providers"
                                    :key="p"
                                    variant="filter"
                                    :selected="fb.provider === p"
                                    @click="fb.provider = p"
                                >
                                    {{ p }}
                                </M3Chip>
                            </div>
                            <M3TextField
                                :model-value="fb.model"
                                :label="t('settings.ai.models.routing.primary_model')"
                                :placeholder="t('settings.ai.models.routing.primary_model_placeholder')"
                                @update:model-value="fb.model = $event"
                            />
                            <div class="m3-models__fallback-actions">
                                <M3IconButton :aria-label="t('settings.ai.models.routing.move_up')" :disabled="i === 0" @click="moveFallback(i, -1)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                </M3IconButton>
                                <M3IconButton :aria-label="t('settings.ai.models.routing.move_down')" :disabled="i === routingForm.fallback_models.length - 1" @click="moveFallback(i, 1)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </M3IconButton>
                                <M3IconButton :aria-label="t('settings.ai.models.routing.remove_fallback')" class="m3-models__danger" @click="removeFallbackRow(i)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </M3IconButton>
                            </div>
                        </div>

                        <M3Button
                            v-if="routingForm.fallback_models.length < 3"
                            variant="text"
                            size="sm"
                            @click="addFallbackRow"
                        >
                            {{ t('settings.ai.models.routing.add_fallback') }}
                        </M3Button>
                    </div>

                    <div class="m3-models__routing-actions">
                        <M3Button variant="filled" :disabled="savingRouting" @click="saveRouting">
                            {{ savingRouting ? t('settings.ai.models.routing.saving') : t('settings.ai.models.routing.save') }}
                        </M3Button>
                        <M3InfoPopover
                            v-if="routingNotice"
                            :label="t('common.warning')"
                            :tone="routingNotice.tone"
                        >
                            <p
                                v-for="(line, i) in routingNotice.lines"
                                :key="i"
                                class="m3-models__detail-note"
                            >
                                {{ line }}
                            </p>
                        </M3InfoPopover>
                    </div>
                </M3Card>
            </section>

            <!-- ── Kandidat ─────────────────────────────────────── -->
            <section class="m3-models__section">
                <h2 class="m3-models__section-title">{{ t('settings.ai.models.candidates.title') }}</h2>
                <M3Card variant="outlined" padding="md">
                    <p class="m3-models__section-desc">
                        {{ t('settings.ai.models.candidates.count', { count: poolCandidates.length }) }}
                    </p>
                    <p v-if="!poolCandidates.length" class="m3-models__empty">
                        {{ t('settings.ai.models.candidates.empty') }}
                    </p>
                    <div v-for="(c, i) in poolCandidates" :key="c.provider + '-' + c.model + '-' + i" class="m3-models__candidate">
                        <span class="m3-models__rank" aria-hidden="true">{{ i + 1 }}</span>
                        <div class="m3-models__candidate-info">
                            <span class="m3-models__candidate-model">{{ c.model }}</span>
                            <span class="m3-models__model-meta">
                                {{ c.provider }}<template v-if="c.account_alias"> · {{ c.account_alias }}</template>
                                <template v-if="c.source"> · {{ c.source }}</template>
                            </span>
                        </div>
                    </div>
                </M3Card>

                <template v-if="poolSkipped.length">
                    <h2 class="m3-models__section-title m3-models__section-title--sub">
                        {{ t('settings.ai.models.candidates.skipped_title') }}
                        <span class="m3-models__section-count">
                            {{ t('settings.ai.models.candidates.skipped_count', { count: poolSkipped.length }) }}
                        </span>
                    </h2>
                    <M3Card variant="outlined" padding="md">
                        <div v-for="(s, i) in poolSkipped" :key="(s.provider || '') + '-' + (s.model || '') + '-' + i" class="m3-models__skipped">
                            <div class="m3-models__skipped-main">
                                <span class="m3-models__skipped-model">{{ s.model || '*' }}</span>
                                <span class="m3-models__model-meta">{{ s.provider }}</span>
                            </div>
                            <M3InfoPopover
                                v-if="s.reason"
                                :label="`${t('settings.ai.models.candidates.skipped_title')} · ${s.model || '*'}`"
                                tone="warning"
                            >
                                <p class="m3-models__detail-note">{{ s.reason }}</p>
                            </M3InfoPopover>
                        </div>
                    </M3Card>
                </template>
            </section>
        </div>

        <!-- Konfirmasi hapus akun -->
        <M3Modal :show="!!removeTarget" max-width="sm" @close="removeTarget = null">
            <div class="m3-models__confirm">
                <h2 class="m3-models__confirm-title">{{ t('settings.ai.models.pool.remove_title') }}</h2>
                <p class="m3-models__confirm-text">
                    {{ removeTarget?.alias }} — {{ t('settings.ai.models.pool.remove_message') }}
                </p>
            </div>
            <template #footer>
                <M3Button variant="text" @click="removeTarget = null">
                    {{ t('common.cancel') }}
                </M3Button>
                <M3Button variant="filled" class="m3-models__danger-fill" :disabled="removingAccount" @click="confirmDeleteAccount">
                    {{ t('common.delete') }}
                </M3Button>
            </template>
        </M3Modal>
    </M3Layout>
</template>

<style scoped>
.m3-models {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    padding: var(--m3-space-4);
}

.m3-models__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-models__section-title {
    margin: 0;
    padding-left: var(--m3-space-4);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-models__section-title--sub {
    margin-top: var(--m3-space-2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
}

.m3-models__section-count {
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-label-medium-weight);
}

.m3-models__section-desc {
    margin: 0 0 var(--m3-space-3);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-models__empty {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

/* ── Akun ── */
.m3-models__account {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    padding: var(--m3-space-3) 0;
    border-top: 1px solid var(--m3-color-outline-variant);
}

.m3-models__account-head {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
}

.m3-models__account-name {
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-models__badge {
    display: inline-flex;
    align-items: center;
    min-height: 1.5rem;
    padding: 0 var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    border: 1px solid var(--m3-color-outline);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
}

.m3-models__badge--ok {
    background: var(--m3-color-primary-container);
    border-color: transparent;
    color: var(--m3-color-on-primary-container);
}

.m3-models__badge--bad {
    background: var(--m3-color-error-container);
    border-color: transparent;
    color: var(--m3-color-on-error-container);
}

.m3-models__badge--warn {
    background: var(--m3-color-tertiary-container);
    border-color: transparent;
    color: var(--m3-color-on-tertiary-container);
}

.m3-models__badge--neutral {
    background: var(--m3-color-surface-container-highest);
    border-color: transparent;
}

.m3-models__account-meta {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-models__details {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-models__detail-block {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-models__detail-label {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    color: var(--m3-color-on-surface);
}

.m3-models__detail-value,
.m3-models__detail-note {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.m3-models__notice {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    padding: var(--m3-space-2);
    border-radius: var(--m3-radius-sm);
}

.m3-models__notice--success {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-models__notice--warning {
    background: var(--m3-color-tertiary-container);
    color: var(--m3-color-on-tertiary-container);
}

.m3-models__notice--danger {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-models__notice--neutral {
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-models__notice-title {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-large-weight);
}

.m3-models__notice .m3-models__detail-note {
    color: inherit;
}

.m3-models__account-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
}

.m3-models__account-models {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--m3-space-2);
}

.m3-models__model-list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    width: 100%;
}

.m3-models__model-row {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-2) 0;
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-models__model-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
    min-width: 0;
}

.m3-models__model-state {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
}

.m3-models__model-id {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface);
    word-break: break-word;
}

.m3-models__model-meta {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

/* ── Tambah akun ── */
.m3-models__add {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-4);
    padding-top: var(--m3-space-4);
    border-top: 1px dashed var(--m3-color-outline-variant);
}

.m3-models__add-title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-models__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
}

/* ── Routing ── */
.m3-models__field {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    margin-bottom: var(--m3-space-4);
}

.m3-models__field-label {
    margin: 0;
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-large-tracking);
    color: var(--m3-color-on-surface);
}

.m3-models__switch-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-4);
    margin-bottom: var(--m3-space-4);
}

.m3-models__switch-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.m3-models__switch-supporting {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-models__manual {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    margin-bottom: var(--m3-space-4);
}

.m3-models__fallback {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
    padding: var(--m3-space-3);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface-container-high);
}

.m3-models__fallback-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--m3-space-1);
}

.m3-models__routing-actions {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
}

/* ── Kandidat ── */
.m3-models__candidate {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    padding: var(--m3-space-2) 0;
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-models__candidate:last-child {
    border-bottom: none;
}

.m3-models__candidate-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.m3-models__candidate-model {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface);
    word-break: break-word;
}

.m3-models__rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-label-medium-size);
    font-weight: var(--m3-type-label-large-weight);
}

.m3-models__skipped {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: var(--m3-space-1) 0;
}

.m3-models__skipped-main {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
    min-width: 0;
}

.m3-models__skipped-model {
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    word-break: break-word;
}

/* ── Konfirmasi ── */
.m3-models__confirm {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-models__confirm-title {
    margin: 0;
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-headline-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-models__confirm-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-models__danger {
    color: var(--m3-color-error);
}

.m3-models__danger-fill {
    background: var(--m3-color-error);
    color: var(--m3-color-on-error);
}
</style>
