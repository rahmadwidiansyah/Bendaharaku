<script setup>
/**
 * Material/Pages/Settings/Privacy/Logs.vue — log aktivitas (tema M3).
 *
 * Paritas perilaku halaman Default: filter + statistik + pagination memakai
 * query `filter/page`; timeline memakai data props yang sama.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    logs: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
})

const { t, locale } = useI18n()
const activeFilter = ref(props.filters?.filter || 'all')

const filterOptions = computed(() => [
    { value: 'all', label: t('settings.privacy.logs.filters.all') },
    { value: 'transaction', label: t('settings.privacy.logs.filters.transaction') },
    { value: 'memory', label: t('settings.privacy.logs.filters.memory') },
    { value: 'settings', label: t('settings.privacy.logs.filters.settings') },
    { value: 'ai', label: t('settings.privacy.logs.filters.ai') },
    { value: 'chat', label: t('settings.privacy.logs.filters.chat') },
])

function applyFilter(value) {
    activeFilter.value = value
    router.get(route('settings.privacy.logs'), { filter: value }, { preserveState: false, replace: true })
}

function goPage(page) {
    if (page < 1 || page > props.pagination.total_pages) return
    router.get(
        route('settings.privacy.logs'),
        { filter: activeFilter.value, page },
        { preserveState: false },
    )
}

function formatDate(raw) {
    if (!raw) return ''
    const date = new Date(raw)
    if (Number.isNaN(date.getTime())) return ''
    return date.toLocaleDateString(locale.value === 'en' ? 'en-US' : 'id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function formatTime(raw) {
    if (!raw) return ''
    const date = new Date(raw)
    if (Number.isNaN(date.getTime())) return ''
    return date.toLocaleTimeString(locale.value === 'en' ? 'en-US' : 'id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    })
}

function typeIcon(type) {
    const icons = {
        transaction: 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
        memory: 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125M20.25 6.375c0-2.278-3.694-4.125-8.25-4.125s-8.25 1.847-8.25 4.125m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125',
        settings: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
        ai: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
        chat: 'M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
    }
    return icons[type] || 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z'
}
</script>

<template>
    <M3Layout :title="t('settings.privacy.logs.page_title')">
        <Head :title="t('settings.privacy.logs.page_title')" />

        <M3SettingsPage
            width="content"
            :title="t('settings.privacy.logs.page_title')"
            :subtitle="t('settings.privacy.logs.page_desc')"
            back-route="settings.index"
        >
            <div class="m3-logs__stats">
                <button
                    v-for="option in filterOptions"
                    :key="option.value"
                    type="button"
                    :class="['m3-logs__stat', activeFilter === option.value ? 'm3-logs__stat--selected' : '']"
                    :aria-pressed="activeFilter === option.value"
                    @click="applyFilter(option.value)"
                >
                    <span class="m3-logs__stat-label">{{ option.label }}</span>
                    <span class="m3-logs__stat-value">{{ stats[option.value] ?? 0 }}</span>
                </button>
            </div>

            <div v-if="logs.length > 0" class="m3-logs__list">
                <M3Card
                    v-for="item in logs"
                    :key="item.id"
                    variant="outlined"
                    padding="md"
                    class="m3-logs__item"
                >
                    <div class="m3-logs__item-head">
                        <span class="m3-logs__type">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="typeIcon(item.type)" />
                            </svg>
                            {{ item.type_label }}
                        </span>
                        <span class="m3-logs__date">
                            {{ formatDate(item.created_at_raw) }} · {{ formatTime(item.created_at_raw) }}
                        </span>
                    </div>

                    <h3 class="m3-logs__title">{{ item.title }}</h3>
                    <p class="m3-logs__desc">{{ item.description }}</p>

                    <div v-if="item.metadata" class="m3-logs__meta">
                        <span v-if="item.metadata.amount" class="m3-logs__chip">
                            Rp {{ Number(item.metadata.amount).toLocaleString('id-ID') }}
                        </span>
                        <span v-if="item.metadata.category" class="m3-logs__chip"># {{ item.metadata.category }}</span>
                        <span v-if="item.metadata.source_wallet" class="m3-logs__chip">{{ item.metadata.source_wallet }}</span>
                        <span v-if="item.metadata.source" class="m3-logs__chip">src: {{ item.metadata.source }}</span>
                        <span v-if="item.metadata.provider" class="m3-logs__chip">{{ item.metadata.provider }}</span>
                        <span v-if="item.metadata.fallback_from" class="m3-logs__chip">
                            {{ item.metadata.fallback_from }} → {{ item.metadata.fallback_to }}
                        </span>
                    </div>
                </M3Card>
            </div>

            <M3Card v-else variant="outlined" padding="lg" class="m3-logs__empty">
                <span class="m3-logs__empty-ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <p class="m3-logs__empty-title">{{ t('settings.privacy.logs.empty_title') }}</p>
                <p class="m3-logs__empty-desc">{{ t('settings.privacy.logs.empty_desc') }}</p>
                <button
                    v-if="activeFilter !== 'all'"
                    type="button"
                    class="m3-logs__empty-reset"
                    @click="applyFilter('all')"
                >
                    {{ t('settings.privacy.logs.show_all') }}
                </button>
            </M3Card>

            <div v-if="pagination.total_pages > 1" class="m3-logs__pagination">
                <p class="m3-logs__pagination-info">
                    {{ t('settings.privacy.logs.pagination.page') }} {{ pagination.current_page }}
                    {{ t('settings.privacy.logs.pagination.of') }} {{ pagination.total_pages }}
                    • {{ pagination.total }} {{ t('settings.privacy.logs.pagination.activities') }}
                </p>
                <div class="m3-logs__pagination-actions">
                    <M3Button
                        variant="outlined"
                        size="sm"
                        :disabled="pagination.current_page <= 1"
                        @click="goPage(pagination.current_page - 1)"
                    >
                        Prev
                    </M3Button>
                    <M3Button
                        size="sm"
                        :disabled="!pagination.has_more"
                        @click="goPage(pagination.current_page + 1)"
                    >
                        Next
                    </M3Button>
                </div>
            </div>

            <M3Card
                v-if="activeFilter === 'memory' || (activeFilter === 'all' && (stats.memory ?? 0) === 0)"
                variant="filled"
                padding="md"
                class="m3-logs__hint"
            >
                <p class="m3-logs__hint-title">{{ t('settings.privacy.logs.hint_title') }}</p>
                <p class="m3-logs__hint-desc">{{ t('settings.privacy.logs.hint_desc') }}</p>
            </M3Card>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-logs__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-2);
}

.m3-logs__stat {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: var(--m3-color-surface);
    color: var(--m3-color-on-surface);
    text-align: start;
    cursor: pointer;
    transition: background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-logs__stat:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-logs__stat--selected {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    border-color: var(--m3-color-secondary-container);
}

.m3-logs__stat-label {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
    text-transform: uppercase;
    color: inherit;
}

.m3-logs__stat-value {
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-emphasized-headline-small-weight);
    font-variant-numeric: tabular-nums;
    color: inherit;
}

.m3-logs__list {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-logs__item {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-logs__item-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    flex-wrap: wrap;
}

.m3-logs__type {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    text-transform: uppercase;
}

.m3-logs__type svg {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-logs__date {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    white-space: nowrap;
}

.m3-logs__title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-on-surface);
    overflow-wrap: anywhere;
}

.m3-logs__desc {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
    overflow-wrap: anywhere;
}

.m3-logs__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1);
}

.m3-logs__chip {
    max-width: 100%;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
    overflow-wrap: anywhere;
}

.m3-logs__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-logs__empty-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-logs__empty-ico svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-logs__empty-title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-logs__empty-desc {
    margin: 0;
    max-width: 26rem;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-logs__empty-reset {
    margin-top: var(--m3-space-1);
    border: none;
    background: transparent;
    color: var(--m3-color-primary);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-label-large-weight);
    cursor: pointer;
    text-decoration: underline;
}

.m3-logs__pagination {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-logs__pagination-info {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-logs__pagination-actions {
    display: flex;
    gap: var(--m3-space-2);
}

.m3-logs__hint {
    border: 1px solid var(--m3-color-error);
}

.m3-logs__hint-title {
    margin: 0;
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    color: var(--m3-color-error);
}

.m3-logs__hint-desc {
    margin: var(--m3-space-1) 0 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

@media (min-width: 600px) {
    .m3-logs__stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .m3-logs__pagination {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

@media (min-width: 1024px) {
    .m3-logs__stats {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) and (max-width: 1400px), (min-width: 1024px) and (max-height: 820px) {
    .m3-logs__stats {
        gap: var(--m3-space-1);
    }

    .m3-logs__stat {
        padding: var(--m3-space-2);
    }

    .m3-logs__list {
        gap: var(--m3-space-2);
    }

    .m3-logs__item {
        gap: var(--m3-space-1);
    }
}
</style>
