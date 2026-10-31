<script setup>
/**
 * Material/Pages/Settings/AI/MemoryManage.vue — kelola memori AI (tema M3).
 *
 * Paritas Settings/AI/MemoryManage (Default): pencarian debounce 300ms,
 * filter all/active/low/high, pagination 20 per halaman, dan tautan detail.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    memories: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const search = ref(props.filters?.search || '')
const activeFilter = ref(props.filters?.filter || 'all')

let debounce = null
watch(search, () => {
    if (debounce) clearTimeout(debounce)
    debounce = setTimeout(() => applyFilters(), 300)
})

onBeforeUnmount(() => {
    if (debounce) clearTimeout(debounce)
})

const filterOptions = computed(() => [
    { value: 'all', label: t('settings.ai.memory.manage.filter_all') },
    { value: 'active', label: t('settings.ai.memory.manage.filter_active') },
    { value: 'low', label: t('settings.ai.memory.manage.filter_low') },
    { value: 'high', label: t('settings.ai.memory.manage.filter_high') },
])

const items = computed(() => props.memories?.data ?? [])
const currentPage = computed(() => props.memories?.current_page ?? 1)
const lastPage = computed(() => props.memories?.last_page ?? 1)

function applyFilters(filter) {
    if (filter !== undefined) activeFilter.value = filter
    router.get(
        route('settings.ai.memory.manage'),
        { search: search.value, filter: activeFilter.value },
        { preserveState: true, replace: true },
    )
}

function goPage(page) {
    if (page < 1 || page > lastPage.value) return
    router.get(
        route('settings.ai.memory.manage'),
        { search: search.value, filter: activeFilter.value, page },
        { preserveScroll: true },
    )
}

function weightLabel(weight) {
    if (weight < 1) return t('settings.ai.memory.manage.weight_low')
    if (weight < 3) return t('settings.ai.memory.manage.weight_medium')
    return t('settings.ai.memory.manage.weight_high')
}

function weightClass(weight) {
    if (weight < 1) return 'm3-memmanage__weight--low'
    if (weight < 3) return 'm3-memmanage__weight--medium'
    return 'm3-memmanage__weight--high'
}

const chevron = 'M8.25 4.5l7.5 7.5-7.5 7.5'
</script>

<template>
    <M3Layout :title="t('settings.ai.memory.manage.title')">
        <Head :title="t('settings.ai.memory.manage.title')" />

        <M3SettingsPage
            :title="t('settings.ai.memory.manage.title')"
            :subtitle="t('settings.ai.memory.manage.description')"
            back-route="settings.ai.memory"
            width="list"
            shell
        >
            <div class="m3-memmanage__toolbar">
                <M3TextField
                    v-model="search"
                    :label="t('settings.ai.memory.manage.search_placeholder')"
                    type="search"
                />

                <div class="m3-memmanage__filters" role="group" :aria-label="t('settings.ai.memory.manage.title')">
                    <M3Chip
                        v-for="option in filterOptions"
                        :key="option.value"
                        :selected="activeFilter === option.value"
                        @click="applyFilters(option.value)"
                    >
                        {{ option.label }}
                    </M3Chip>
                </div>
            </div>

            <M3Card v-if="items.length > 0" variant="outlined" padding="none" class="m3-memmanage__card">
                <M3ListItem
                    v-for="(item, index) in items"
                    :key="item.id"
                    :href="route('settings.ai.memory.detail', { id: item.id })"
                    :divider="index < items.length - 1"
                >
                    <template #icon>
                        <span class="m3-memmanage__ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125M20.25 6.375c0-2.278-3.694-4.125-8.25-4.125s-8.25 1.847-8.25 4.125m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                            </svg>
                        </span>
                    </template>
                    {{ item.keyword }}
                    <template #supporting>
                        <span v-if="item.raw_subject && item.raw_subject !== item.keyword">{{ item.raw_subject }}</span>
                        <span v-else-if="item.category"># {{ item.category }}</span>
                        <span v-else>{{ item.last_applied_at || t('common.never') }}</span>
                    </template>
                    <template #trailing>
                        <span :class="['m3-memmanage__weight', weightClass(item.weight)]">
                            {{ weightLabel(item.weight) }} · {{ item.weight.toFixed(1) }} · {{ item.hit_count }}×
                        </span>
                        <svg class="m3-memmanage__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                        </svg>
                    </template>
                </M3ListItem>
            </M3Card>

            <M3Card v-else variant="outlined" padding="lg" class="m3-memmanage__empty">
                <span class="m3-memmanage__empty-ico">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </span>
                <p class="m3-memmanage__empty-title">{{ t('settings.ai.memory.manage.empty_title') }}</p>
                <p class="m3-memmanage__empty-desc">{{ t('settings.ai.memory.manage.empty_description') }}</p>
            </M3Card>

            <div v-if="lastPage > 1" class="m3-memmanage__pagination">
                <p class="m3-memmanage__pagination-info">
                    {{ t('settings.privacy.logs.pagination.page') }} {{ currentPage }}
                    {{ t('settings.privacy.logs.pagination.of') }} {{ lastPage }}
                </p>
                <div class="m3-memmanage__pagination-actions">
                    <M3Button
                        variant="outlined"
                        size="sm"
                        :disabled="currentPage <= 1"
                        @click="goPage(currentPage - 1)"
                    >
                        {{ t('common.prev') }}
                    </M3Button>
                    <M3Button
                        size="sm"
                        :disabled="currentPage >= lastPage"
                        @click="goPage(currentPage + 1)"
                    >
                        {{ t('common.next') }}
                    </M3Button>
                </div>
            </div>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-memmanage__toolbar {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-memmanage__filters {
    display: flex;
    gap: var(--m3-space-2);
    overflow-x: auto;
    padding-bottom: var(--m3-space-1);
    scrollbar-width: none;
}

.m3-memmanage__filters::-webkit-scrollbar {
    display: none;
}

.m3-memmanage__card {
    overflow: hidden;
}

.m3-memmanage__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-memmanage__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-memmanage__weight {
    display: inline-flex;
    align-items: center;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.m3-memmanage__weight--low {
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-memmanage__weight--medium {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-memmanage__weight--high {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
}

.m3-memmanage__chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    margin-left: var(--m3-space-2);
    color: var(--m3-color-on-surface-variant);
}

.m3-memmanage__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-memmanage__empty-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-memmanage__empty-ico svg {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-memmanage__empty-title {
    margin: 0;
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    color: var(--m3-color-on-surface);
}

.m3-memmanage__empty-desc {
    margin: 0;
    max-width: 26rem;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-memmanage__pagination {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
}

.m3-memmanage__pagination-info {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-memmanage__pagination-actions {
    display: flex;
    gap: var(--m3-space-2);
}

@media (min-width: 600px) {
    .m3-memmanage__pagination {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
</style>
