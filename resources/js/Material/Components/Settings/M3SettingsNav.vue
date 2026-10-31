<script setup>
/**
 * M3SettingsNav — panel navigasi Settings (master-detail desktop).
 *
 * Menampilkan seluruh kategori & tujuan Settings dari settingsSections.js
 * dengan state aktif via route().current(). Hanya dirender di desktop oleh
 * pemakainya (M3SettingsShell); di mobile tetap memakai navigasi existing.
 *
 * Setiap kategori bisa di-collapse/expand per grup, dipisah pembeda visual.
 * State collapse/expand PERSISTEN di localStorage (per perangkat) sehingga
 * tidak berubah saat refresh maupun pindah halaman. Posisi item SENGAJA
 * stabil: tidak ada auto-collapse/expand saat navigasi — satu-satunya yang
 * mengubah tinggi grup adalah toggle manual user. Section aktif hanya
 * diberi highlight warna, tetap bisa di-collapse.
 */
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { computed, ref } from 'vue'
import { buildSettingsSections } from '@/Material/utils/settingsSections.js'

const { t } = useI18n()

const sections = computed(() => buildSettingsSections(t))

const STORAGE_KEY = 'settings_nav_collapsed'

const isActive = (routeName) => {
    try {
        return route().current(routeName) || route().current(`${routeName}.*`)
    } catch {
        return false
    }
}

const currentSectionId = computed(() => {
    for (const section of sections.value) {
        if (section.items.some((item) => isActive(item.route))) return section.id
    }
    return ''
})

// ── Persistensi collapse/expand ──────────────────────────────────
const readStored = () => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY)
        const parsed = raw ? JSON.parse(raw) : []
        return Array.isArray(parsed) ? parsed.filter((v) => typeof v === 'string') : []
    } catch {
        return []
    }
}

const collapsed = ref(readStored())

// Murni dari preferensi tersimpan — TANPA paksa-buka untuk section aktif,
// agar posisi item tidak melompat saat pindah halaman.
const isCollapsed = (sectionId) => collapsed.value.includes(sectionId)

const toggleSection = (sectionId) => {
    const set = new Set(collapsed.value)
    if (set.has(sectionId)) {
        set.delete(sectionId)
    } else {
        set.add(sectionId)
    }
    collapsed.value = [...set]
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(collapsed.value))
    } catch {
        // Privacy mode / storage penuh: state tetap hidup untuk sesi ini saja
    }
}
</script>

<template>
    <nav class="m3-settings-shellnav" :aria-label="$t('settings.title')">
        <div
            v-for="(section, index) in sections"
            :key="section.id"
            class="m3-settings-shellnav__group"
            :class="{ 'm3-settings-shellnav__group--divided': index > 0 }"
        >
            <button
                type="button"
                class="m3-settings-shellnav__header"
                :class="{ 'm3-settings-shellnav__header--active': currentSectionId === section.id }"
                :aria-expanded="!isCollapsed(section.id)"
                :aria-controls="`m3-settings-nav-${section.id}`"
                @click="toggleSection(section.id)"
            >
                <span class="m3-settings-shellnav__section">{{ section.title }}</span>
                <svg
                    class="m3-settings-shellnav__chevron"
                    :class="{ 'm3-settings-shellnav__chevron--collapsed': isCollapsed(section.id) }"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            <div
                v-show="!isCollapsed(section.id)"
                :id="`m3-settings-nav-${section.id}`"
                class="m3-settings-shellnav__items"
            >
                <Link
                    v-for="item in section.items"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'm3-settings-shellnav__item',
                        isActive(item.route) ? 'm3-settings-shellnav__item--active' : '',
                        item.danger ? 'm3-settings-shellnav__item--danger' : '',
                    ]"
                    :aria-current="isActive(item.route) ? 'page' : undefined"
                >
                    {{ item.label }}
                </Link>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.m3-settings-shellnav {
    display: none;
}

@media (min-width: 1024px) {
    .m3-settings-shellnav {
        display: flex;
        flex-direction: column;
        position: sticky;
        /* 5rem = appbar 4rem + inset 1rem → nav SELALU sejajar dengan tepi
           atas konten body (80px) di semua halaman, scroll maupun tidak.
           Sebelumnya 5.5rem membuat posisi nav berbeda antar halaman
           (clamp kontainer pada halaman berkonten pendek). */
        top: 5rem;
        max-height: calc(100vh - 6rem);
        overflow-y: auto;
        padding: var(--m3-space-2);
        border-radius: var(--m3-radius-lg);
        background: var(--m3-color-surface-container-low);
        scrollbar-width: thin;
        align-self: start;
    }
}

/* Grup kategori + pembeda antar grup */
.m3-settings-shellnav__group {
    padding-block: var(--m3-space-1);
}

.m3-settings-shellnav__group--divided {
    margin-top: var(--m3-space-1);
    border-top: 1px solid var(--m3-color-outline-variant);
}

/* Header grup: tombol toggle collapse/expand */
.m3-settings-shellnav__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    width: 100%;
    padding: var(--m3-space-2) var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-sm);
    background: transparent;
    cursor: pointer;
    text-align: start;
    transition: background var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-settings-shellnav__header:hover {
    background: var(--m3-color-surface-container-high);
}

.m3-settings-shellnav__header:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-settings-shellnav__section {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-label-small-weight);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-settings-shellnav__header--active .m3-settings-shellnav__section {
    color: var(--m3-color-primary);
}

.m3-settings-shellnav__chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
    color: var(--m3-color-on-surface-variant);
    transition: transform var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-settings-shellnav__chevron--collapsed {
    transform: rotate(-90deg);
}

.m3-settings-shellnav__items {
    display: flex;
    flex-direction: column;
    padding-block: var(--m3-space-0-5);
}

.m3-settings-shellnav__item {
    display: block;
    width: 100%;
    padding: var(--m3-space-2) var(--m3-space-3);
    border: none;
    border-radius: var(--m3-radius-full);
    background: transparent;
    color: var(--m3-color-on-surface-variant);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    font-weight: var(--m3-type-body-medium-weight);
    text-align: start;
    text-decoration: none;
    cursor: pointer;
    transition: background var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-settings-shellnav__item:hover {
    background: var(--m3-color-surface-container-high);
    color: var(--m3-color-on-surface);
}

.m3-settings-shellnav__item:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-settings-shellnav__item--active {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
    font-weight: var(--m3-type-emphasized-body-medium-weight, 600);
}

.m3-settings-shellnav__item--danger {
    color: var(--m3-color-error);
}

.m3-settings-shellnav__item--danger:hover {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-settings-shellnav__item--danger.m3-settings-shellnav__item--active {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}
</style>
