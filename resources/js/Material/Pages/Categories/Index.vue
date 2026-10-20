<script setup>
/**
 * Material/Pages/Categories/Index.vue — Vault Kategori M3.
 * Kontrak data sama dengan Pages/Categories/Index.vue Default:
 * groupedCategories + totalCategories.
 *
 * Mobile (<1024px): grid tile (vault) — 3/4/6 kolom.
 * Desktop (>=1024px): daftar per grup memakai M3ListItem, karena tile
 * kecil selebar konten 72rem menghasilkan puluhan ikon tanpa hierarki.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { getM3CategoryIconClass } from '@/Material/utils/m3IconColor.js'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    groupedCategories: { type: Object, default: () => ({}) },
    totalCategories: { type: Number, default: 0 },
})

const desiredOrder = ['Income', 'Expense', 'Transfer', 'Debt', 'Receivable']

const sortedGroups = computed(() =>
    Object.entries(props.groupedCategories).sort((a, b) => {
        const posA = desiredOrder.indexOf(a[0])
        const posB = desiredOrder.indexOf(b[0])
        return (posA === -1 ? 999 : posA) - (posB === -1 ? 999 : posB)
    }),
)

const typeClass = (typeName) => `m3-cat-index__group--${String(typeName ?? '').toLowerCase()}`

const typeIcon = (typeName) => ({
    Income: 'arrow-down',
    Expense: 'arrow-up',
    Transfer: 'arrow-left-right',
    Debt: 'hand-coins',
    Receivable: 'banknote',
}[typeName] ?? 'circle-dot')

const getHeaderText = (typeName) => t('category.typeHeaders.' + typeName) || t('types.other')

const tileLabel = (category) =>
    category.system_key
        ? `${category.category_name} — ${t('category.systemCategory')}`
        : category.category_name

const hasCategories = computed(() => Object.keys(props.groupedCategories).length > 0)
</script>

<template>
    <M3Layout :title="t('category.title')">
        <Head :title="t('category.title')" />

        <div class="m3-cat-index">
            <M3PageTitle
                :title="t('category.title')"
                :subtitle="`${totalCategories} ${t('category.collection')}`"
            />

            <M3Button
                :href="route('categories.create')"
                size="lg"
                full-width
                class="m3-cat-index__mobile-cta"
            >
                <template #leading>
                    <AppIcon icon="plus" class="m3-cat-index__cta-icon" />
                </template>
                {{ t('category.addNew') }}
            </M3Button>

            <div class="m3-cat-index__toolbar">
                <p class="m3-cat-index__toolbar-count">
                    {{ totalCategories }} {{ t('category.collection') }}
                </p>
                <M3Button :href="route('categories.create')" variant="tonal">
                    <template #leading>
                        <AppIcon icon="plus" class="m3-cat-index__cta-icon" />
                    </template>
                    {{ t('category.addNew') }}
                </M3Button>
            </div>

            <section
                v-for="([typeName, categories], index) in sortedGroups"
                :key="typeName"
                class="m3-cat-index__group"
                :class="typeClass(typeName)"
                :style="{ '--enter-index': index }"
            >
                <header class="m3-cat-index__group-head">
                    <span class="m3-cat-index__type-badge" aria-hidden="true">
                        <AppIcon :icon="typeIcon(typeName)" class="m3-cat-index__type-icon" />
                    </span>
                    <h2 class="m3-cat-index__group-title">{{ getHeaderText(typeName) }}</h2>
                    <span
                        class="m3-cat-index__group-count"
                        :aria-label="`${categories.length} ${t('category.collection')}`"
                    >
                        {{ categories.length }}
                    </span>
                </header>

                <div class="m3-cat-index__grid">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="route('categories.show', category.id)"
                        class="m3-cat-index__tile"
                        :aria-label="tileLabel(category)"
                    >
                        <span v-if="category.system_key" class="m3-cat-index__lock" aria-hidden="true">
                            <AppIcon icon="lock" class="m3-cat-index__lock-icon" />
                        </span>

                        <AppIcon
                            :icon="category.icon"
                            fallback="folder"
                            :class="['m3-cat-index__icon', getM3CategoryIconClass(category.type?.name)]"
                        />

                        <span class="m3-cat-index__name">{{ category.category_name }}</span>
                    </Link>
                </div>

                <M3Card variant="outlined" padding="none" class="m3-cat-index__list">
                    <M3ListItem
                        v-for="(category, catIndex) in categories"
                        :key="category.id"
                        :href="route('categories.show', category.id)"
                        :divider="catIndex < categories.length - 1"
                    >
                        <template #icon>
                            <span class="m3-cat-index__list-icon-wrap">
                                <AppIcon
                                    :icon="category.icon"
                                    fallback="folder"
                                    :class="['m3-cat-index__list-icon', getM3CategoryIconClass(category.type?.name)]"
                                />
                            </span>
                        </template>

                        {{ category.category_name }}

                        <template v-if="category.system_key" #supporting>
                            <span class="m3-cat-index__system">
                                <AppIcon icon="lock" class="m3-cat-index__system-icon" />
                                {{ t('category.systemCategory') }}
                            </span>
                        </template>

                        <template #trailing>
                            <AppIcon icon="chevron-right" class="m3-cat-index__chevron" />
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>

            <M3Card v-if="!hasCategories" variant="outlined" padding="lg" class="m3-cat-index__empty">
                <span class="m3-cat-index__empty-icon-wrap" aria-hidden="true">
                    <AppIcon icon="folder-open" class="m3-cat-index__empty-icon" />
                </span>
                <p class="m3-cat-index__empty-title">{{ t('empty.category') }}</p>
                <p class="m3-cat-index__empty-text">{{ t('empty.categoryMsg') }}</p>
                <M3Button :href="route('categories.create')" variant="tonal">
                    <template #leading>
                        <AppIcon icon="plus" />
                    </template>
                    {{ t('category.addNew') }}
                </M3Button>
            </M3Card>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-cat-index {
    --m3-cat-tile-height: 7.5rem;
    --m3-cat-empty-max: 20rem;

    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-content-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-cat-index__cta-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-cat-index__toolbar {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-3);
    min-width: 0;
}

.m3-cat-index__toolbar-count {
    margin: 0;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
}

.m3-cat-index__group {
    --m3-cat-accent: var(--m3-color-primary);
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-3);
    min-width: 0;
    animation: m3-cat-index-enter var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial) both;
    animation-delay: calc(var(--enter-index) * 40ms);
}

.m3-cat-index__group--income { --m3-cat-accent: var(--m3-color-income); }
.m3-cat-index__group--expense { --m3-cat-accent: var(--m3-color-expense); }
.m3-cat-index__group--transfer { --m3-cat-accent: var(--m3-color-transfer); }
.m3-cat-index__group--debt { --m3-cat-accent: var(--m3-color-debt); }
.m3-cat-index__group--receivable { --m3-cat-accent: var(--m3-color-receivable); }

.m3-cat-index__group-head {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    padding: 0 var(--m3-space-1);
    min-width: 0;
}

.m3-cat-index__type-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: var(--m3-space-8);
    height: var(--m3-space-8);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-cat-accent);
}

.m3-cat-index__type-icon {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-cat-index__group-title {
    margin: 0;
    min-width: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
}

.m3-cat-index__group-count {
    flex-shrink: 0;
    padding: var(--m3-space-0-5) var(--m3-space-2);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: var(--m3-type-label-small-tracking);
}

/* ── Mobile: vault tile ─────────────────────────────────────────── */
.m3-cat-index__grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--m3-space-2);
}

.m3-cat-index__tile {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-2);
    min-height: var(--m3-cat-tile-height);
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-lg);
    background: var(--m3-color-surface-container-low);
    color: var(--m3-color-on-surface);
    text-align: center;
    text-decoration: none;
    transition:
        background-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        border-color var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects),
        transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-cat-index__tile:hover {
    border-color: var(--m3-cat-accent);
    background: var(--m3-color-surface-container-high);
}

.m3-cat-index__tile:active {
    transform: scale(0.96);
}

.m3-cat-index__tile:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-cat-index__lock {
    position: absolute;
    top: var(--m3-space-1);
    right: var(--m3-space-1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-space-6);
    height: var(--m3-space-6);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    color: var(--m3-color-on-surface-variant);
}

.m3-cat-index__lock-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-cat-index__icon {
    width: var(--m3-size-icon-xl);
    height: var(--m3-size-icon-xl);
    transition: transform var(--m3-motion-default-spatial-duration) var(--m3-motion-default-spatial);
}

.m3-cat-index__tile:hover .m3-cat-index__icon {
    transform: scale(1.08);
}

.m3-cat-index__name {
    display: -webkit-box;
    overflow: hidden;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    font-weight: var(--m3-type-emphasized-body-small-weight);
    letter-spacing: var(--m3-type-body-small-tracking);
    overflow-wrap: anywhere;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

/* ── Desktop: daftar per grup ────────────────────────────────────── */
.m3-cat-index__list {
    display: none;
    overflow: hidden;
}

.m3-cat-index__list-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
}

.m3-cat-index__list-icon {
    width: var(--m3-size-icon-sm);
    height: var(--m3-size-icon-sm);
}

.m3-cat-index__system {
    display: inline-flex;
    align-items: center;
    gap: var(--m3-space-1);
}

.m3-cat-index__system-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
}

.m3-cat-index__chevron {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    color: var(--m3-color-on-surface-variant);
}

/* ── Empty ──────────────────────────────────────────────────────── */
.m3-cat-index__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
    text-align: center;
}

.m3-cat-index__empty-icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-space-12);
    height: var(--m3-space-12);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-primary);
}

.m3-cat-index__empty-icon {
    width: var(--m3-size-icon);
    height: var(--m3-size-icon);
}

.m3-cat-index__empty-title {
    margin: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-medium-size);
    line-height: var(--m3-type-title-medium-line-height);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
}

.m3-cat-index__empty-text {
    margin: 0;
    max-width: var(--m3-cat-empty-max);
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
}

@media (min-width: 30rem) {
    .m3-cat-index__grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (min-width: 48rem) {
    .m3-cat-index__grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }
}

@media (min-width: 64rem) {
    .m3-cat-index__mobile-cta,
    .m3-cat-index__grid {
        display: none;
    }

    .m3-cat-index__toolbar {
        display: flex;
    }

    .m3-cat-index__list {
        display: flex;
        flex-direction: column;
    }

    .m3-cat-index__group {
        gap: var(--m3-space-2);
    }
}

@keyframes m3-cat-index-enter {
    from {
        opacity: 0;
        transform: translateY(var(--m3-space-2));
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .m3-cat-index__group {
        animation: none;
    }

    .m3-cat-index__tile,
    .m3-cat-index__icon {
        transition: none;
    }
}
</style>
