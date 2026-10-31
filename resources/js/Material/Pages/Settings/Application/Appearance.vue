<script setup>
/**
 * Material/Pages/Settings/Application/Appearance.vue — tampilan (tema M3).
 *
 * Paritas perilaku Pages/Settings/Application/Appearance.vue: auto-save
 * (debounce 500ms) untuk tema UI, skema M3, mode terang/gelap, ikon kategori,
 * dan warna aksen; revert + toast saat gagal; reload hanya jika ui_theme
 * benar-benar berubah dan tersimpan.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Switch from '@/Material/Components/M3Switch.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head } from '@inertiajs/vue3'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { applyAccentColor, saveAccentColor, ACCENT_PALETTES, isCustomColor, isValidHex, getColorValue } from '@/Composables/useAccentColor.js'
import { setCategoryIconColored } from '@/Composables/useIcon.js'
import { useTheme } from '@/Composables/useTheme'
import { M3_SCHEME_KEYS, DEFAULT_M3_COLOR_SCHEME, isValidM3ColorScheme, applyM3ColorScheme, getM3SchemePreview } from '@/Composables/useM3ColorScheme.js'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    userAccentColor: { type: String, default: 'teal' },
    userTheme: { type: String, default: 'dark' },
    categoryIconColored: { type: Boolean, default: true },
    userUiTheme: { type: String, default: 'default' },
    userM3ColorScheme: { type: String, default: DEFAULT_M3_COLOR_SCHEME },
})

const { t } = useI18n()
const { showToast } = useToast()
const { setTheme } = useTheme()

const theme = ref(props.userTheme ?? 'system')
const accentColor = ref(props.userAccentColor)
const customHex = ref('')
const showCustomPicker = ref(false)
const saving = ref(false)
const iconColored = ref(props.categoryIconColored)
const uiTheme = ref(props.userUiTheme ?? 'default')
const savedUiTheme = ref(uiTheme.value)
const m3ColorScheme = ref(isValidM3ColorScheme(props.userM3ColorScheme) ? props.userM3ColorScheme : DEFAULT_M3_COLOR_SCHEME)
const savedM3ColorScheme = ref(m3ColorScheme.value)

const m3PreviewMode = computed(() => {
    void theme.value
    if (typeof document === 'undefined') return 'dark'
    return document.documentElement.classList.contains('light') ? 'light' : 'dark'
})

const isM3Active = computed(() => uiTheme.value === 'm3')
const isCustom = computed(() => isCustomColor(accentColor.value))
const currentDisplayColor = computed(() => {
    if (isCustom.value) return getColorValue(accentColor.value)
    return ACCENT_PALETTES[accentColor.value]?.[600] || '#9333ea'
})
const customHexValid = computed(() => isValidHex(customHex.value))

function selectPredefined(color) {
    accentColor.value = color
    customHex.value = ''
    showCustomPicker.value = false
}

function handlePickerInput(event) {
    customHex.value = event.target.value
}

watch(customHex, (hex) => {
    if (isValidHex(hex)) accentColor.value = `custom:${hex}`
})

watch(accentColor, (color) => {
    applyAccentColor(color)
    saveAppearance()
})

watch(theme, (newTheme) => {
    setTheme(newTheme)
    saveAppearance()
})

watch(iconColored, () => saveAppearance())

let revertingM3Scheme = false
watch(m3ColorScheme, (scheme) => {
    if (!isM3Active.value) return
    applyM3ColorScheme(scheme)
    if (revertingM3Scheme) return
    saveAppearance()
})

let revertingUiTheme = false
watch(uiTheme, () => {
    if (revertingUiTheme) return
    saveAppearance()
})

onMounted(() => {
    applyAccentColor(accentColor.value)
    if (isCustom.value) customHex.value = getColorValue(accentColor.value)
})

let saveTimer = null
const saveAppearance = () => {
    if (saveTimer) clearTimeout(saveTimer)
    saveTimer = setTimeout(async () => {
        saving.value = true
        try {
            await axios.patch(route('settings.application.appearance.update'), {
                theme: theme.value,
                accent_color: accentColor.value,
                category_icon_colored: iconColored.value,
                ui_theme: uiTheme.value,
                ui_m3_color_scheme: m3ColorScheme.value,
            })
            saveAccentColor(accentColor.value)
            setCategoryIconColored(iconColored.value)
            savedM3ColorScheme.value = m3ColorScheme.value
            showToast(t('toast.updated'), 'success')
            if (uiTheme.value !== savedUiTheme.value) {
                savedUiTheme.value = uiTheme.value
                window.location.reload()
            }
        } catch (error) {
            revertingUiTheme = true
            uiTheme.value = savedUiTheme.value
            nextTick(() => { revertingUiTheme = false })
            revertingM3Scheme = true
            m3ColorScheme.value = savedM3ColorScheme.value
            applyM3ColorScheme(savedM3ColorScheme.value)
            nextTick(() => { revertingM3Scheme = false })
            showToast(error.response?.data?.message || t('errors.generic'), 'error')
        } finally {
            saving.value = false
        }
    }, 500)
}

const uiThemeOptions = [
    { value: 'default', labelKey: 'settings.application.appearance.ui_theme.default', descKey: 'settings.application.appearance.ui_theme.default_desc' },
    { value: 'm3', labelKey: 'settings.application.appearance.ui_theme.m3', descKey: 'settings.application.appearance.ui_theme.m3_desc' },
]
const themeOptions = ['light', 'dark', 'system']
</script>

<template>
    <M3Layout :title="t('settings.application.appearance.title')">
        <Head :title="t('settings.application.appearance.title')" />

        <M3SettingsPage
            :title="t('settings.application.appearance.title')"
            :subtitle="t('settings.application.appearance.description')"
            back-route="settings.index"
            width="content"
            shell
        >
            <div class="m3-settings-cols">
            <M3SettingsSection :title="t('settings.application.appearance.ui_theme.title')">
                <M3Card variant="outlined" padding="none" class="m3-appearance__options">
                    <button
                        v-for="(option, index) in uiThemeOptions"
                        :key="option.value"
                        type="button"
                        :class="['m3-appearance__option', uiTheme.value === option.value ? 'm3-appearance__option--selected' : '', index < uiThemeOptions.length - 1 ? 'm3-appearance__option--divider' : '']"
                        :aria-pressed="uiTheme === option.value"
                        @click="uiTheme = option.value"
                    >
                        <span class="m3-appearance__option-text">
                            <span class="m3-appearance__option-label">{{ t(option.labelKey) }}</span>
                            <span class="m3-appearance__option-desc">{{ t(option.descKey) }}</span>
                        </span>
                        <svg v-if="uiTheme === option.value" class="m3-appearance__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </button>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.application.appearance.theme.title')">
                <M3Card variant="outlined" padding="none" class="m3-appearance__options">
                    <button
                        v-for="(value, index) in themeOptions"
                        :key="value"
                        type="button"
                        :class="['m3-appearance__option', theme === value ? 'm3-appearance__option--selected' : '', index < themeOptions.length - 1 ? 'm3-appearance__option--divider' : '']"
                        :aria-pressed="theme === value"
                        @click="theme = value"
                    >
                        <span class="m3-appearance__option-label">{{ t(`settings.application.appearance.theme.${value}`) }}</span>
                        <svg v-if="theme === value" class="m3-appearance__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </button>
                </M3Card>
            </M3SettingsSection>
            </div>

            <M3SettingsSection
                v-if="isM3Active"
                :title="t('settings.application.appearance.m3_color_scheme.title')"
            >
                <M3Card variant="outlined" padding="lg">
                    <div class="m3-appearance__schemes">
                        <button
                            v-for="key in M3_SCHEME_KEYS"
                            :key="key"
                            type="button"
                            :class="['m3-appearance__scheme', m3ColorScheme === key ? 'm3-appearance__scheme--selected' : '']"
                            :aria-pressed="m3ColorScheme === key"
                            @click="m3ColorScheme = key"
                        >
                            <span
                                class="m3-appearance__scheme-preview"
                                :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].surface }"
                                aria-hidden="true"
                            >
                                <span :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].primary }" />
                                <span :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].secondary }" />
                                <span :style="{ backgroundColor: getM3SchemePreview(key)[m3PreviewMode].tertiary }" />
                            </span>
                            <span class="m3-appearance__scheme-label">{{ t(`settings.application.appearance.m3_color_scheme.${key}`) }}</span>
                            <span class="m3-appearance__scheme-desc">{{ t(`settings.application.appearance.m3_color_scheme.${key}_desc`) }}</span>
                        </button>
                    </div>
                </M3Card>
            </M3SettingsSection>

<div class="m3-settings-cols">
            <M3SettingsSection :title="t('settings.application.appearance.category_icon_color.title')">
                <M3Card variant="outlined" padding="lg">
                    <div class="m3-appearance__switch-row">
                        <div class="m3-appearance__switch-text">
                            <p class="m3-appearance__option-label">{{ t('settings.application.appearance.category_icon_color.label') }}</p>
                            <p class="m3-appearance__option-desc">
                                {{ iconColored ? t('settings.application.appearance.category_icon_color.on') : t('settings.application.appearance.category_icon_color.off') }}
                            </p>
                        </div>
                        <M3Switch
                            v-model="iconColored"
                            :aria-label="t('settings.application.appearance.category_icon_color.label')"
                        />
                    </div>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.application.appearance.accent_color.title')">
                <M3Card variant="outlined" padding="lg">
                    <p class="m3-appearance__option-desc">{{ t('settings.application.appearance.accent_color.description') }}</p>
                    <div class="m3-appearance__swatches">
                        <button
                            v-for="color in Object.keys(ACCENT_PALETTES)"
                            :key="color"
                            type="button"
                            :class="['m3-appearance__swatch', !isCustom && accentColor === color ? 'm3-appearance__swatch--selected' : '']"
                            :style="{ backgroundColor: ACCENT_PALETTES[color][600] }"
                            :aria-label="t('settings.application.appearance.accent_color.setAccent', { name: color })"
                            :aria-pressed="!isCustom && accentColor === color"
                            @click="selectPredefined(color)"
                        />
                    </div>

                    <button
                        type="button"
                        :class="['m3-appearance__custom-toggle', isCustom ? 'm3-appearance__custom-toggle--selected' : '']"
                        :aria-expanded="showCustomPicker"
                        @click="showCustomPicker = !showCustomPicker"
                    >
                        <span class="m3-appearance__custom-dot" :style="{ backgroundColor: isCustom ? currentDisplayColor : 'var(--m3-color-outline)' }" />
                        <span class="m3-appearance__custom-label">
                            {{ isCustom ? currentDisplayColor : t('settings.application.appearance.accent_color.custom') }}
                        </span>
                        <svg class="m3-appearance__custom-chevron" :class="{ 'm3-appearance__custom-chevron--open': showCustomPicker }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div v-if="showCustomPicker" class="m3-appearance__custom">
                        <input
                            type="color"
                            class="m3-appearance__color-input"
                            :value="isCustom ? currentDisplayColor : '#8B5CF6'"
                            :aria-label="t('settings.application.appearance.accent_color.custom')"
                            @input="handlePickerInput"
                        />
                        <M3TextField
                            v-model="customHex"
                            :label="t('settings.application.appearance.accent_color.title')"
                            placeholder="#8B5CF6"
                            :error="customHex && !customHexValid ? 'Format HEX tidak valid. Gunakan #RRGGBB atau #RGB.' : ''"
                        />
                    </div>

                    <p class="m3-appearance__preview">
                        {{ t('settings.application.appearance.accent_color.title') }}:
                        <strong>{{ isCustom ? currentDisplayColor : accentColor }}</strong>
                    </p>
                </M3Card>
            </M3SettingsSection>
            </div>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-appearance__options {
    overflow: hidden;
}

.m3-appearance__option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-4);
    width: 100%;
    padding: var(--m3-space-4);
    border: none;
    background: transparent;
    color: var(--m3-color-on-surface);
    text-align: start;
    cursor: pointer;
}

.m3-appearance__option:hover {
    background: var(--m3-color-surface-container-highest);
}

.m3-appearance__option--selected {
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-appearance__option--divider {
    border-bottom: 1px solid var(--m3-color-outline-variant);
}

.m3-appearance__option-text {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-0-5);
}

.m3-appearance__option-label {
    margin: 0;
    font-size: var(--m3-type-body-large-size);
    line-height: var(--m3-type-body-large-line-height);
    font-weight: var(--m3-type-emphasized-body-large-weight);
    color: inherit;
}

.m3-appearance__option-desc {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-appearance__option--selected .m3-appearance__option-desc {
    color: var(--m3-color-on-secondary-container);
}

.m3-appearance__check {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-appearance__option--selected .m3-appearance__check {
    color: var(--m3-color-on-secondary-container);
}

.m3-appearance__schemes {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--m3-space-3);
}

.m3-appearance__scheme {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-1);
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface);
    cursor: pointer;
    text-align: center;
}

.m3-appearance__scheme--selected {
    border-color: var(--m3-color-primary);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-appearance__scheme-preview {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--m3-space-1);
    width: 3.5rem;
    height: 3.5rem;
    margin-bottom: var(--m3-space-1);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-full);
}

.m3-appearance__scheme-preview span:nth-child(1) {
    width: 1rem;
    height: 1rem;
    border-radius: var(--m3-radius-full);
}

.m3-appearance__scheme-preview span:nth-child(2),
.m3-appearance__scheme-preview span:nth-child(3) {
    width: 0.625rem;
    height: 0.625rem;
    border-radius: var(--m3-radius-full);
}

.m3-appearance__scheme-label {
    font-size: var(--m3-type-label-large-size);
    line-height: var(--m3-type-label-large-line-height);
    font-weight: var(--m3-type-emphasized-label-large-weight);
}

.m3-appearance__scheme-desc {
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-appearance__scheme--selected .m3-appearance__scheme-desc {
    color: var(--m3-color-on-secondary-container);
}

.m3-appearance__switch-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-4);
}

.m3-appearance__switch-text {
    min-width: 0;
}

.m3-appearance__swatches {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-3);
}

.m3-appearance__swatch {
    width: 100%;
    aspect-ratio: 1;
    border: 2px solid transparent;
    border-radius: var(--m3-radius-md);
    cursor: pointer;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial), opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-appearance__swatch:hover {
    transform: scale(1.05);
}

.m3-appearance__swatch--selected {
    border-color: var(--m3-color-on-surface);
    box-shadow: 0 0 0 2px var(--m3-color-surface);
}

.m3-appearance__custom-toggle {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    width: 100%;
    margin-top: var(--m3-space-4);
    padding: var(--m3-space-3) var(--m3-space-4);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
    background: transparent;
    color: var(--m3-color-on-surface);
    cursor: pointer;
    text-align: start;
}

.m3-appearance__custom-toggle--selected {
    border-color: var(--m3-color-primary);
}

.m3-appearance__custom-dot {
    width: 1.5rem;
    height: 1.5rem;
    flex-shrink: 0;
    border-radius: var(--m3-radius-sm);
}

.m3-appearance__custom-label {
    flex: 1;
    min-width: 0;
    font-size: var(--m3-type-body-medium-size);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
}

.m3-appearance__custom-chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-appearance__custom-chevron--open {
    transform: rotate(180deg);
}

.m3-appearance__custom {
    display: flex;
    align-items: center;
    gap: var(--m3-space-3);
    margin-top: var(--m3-space-3);
    padding: var(--m3-space-3);
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-md);
}

.m3-appearance__color-input {
    width: 3rem;
    height: 3rem;
    flex-shrink: 0;
    padding: 0;
    border: 1px solid var(--m3-color-outline-variant);
    border-radius: var(--m3-radius-sm);
    background: transparent;
    cursor: pointer;
}

.m3-appearance__preview {
    margin: var(--m3-space-4) 0 0;
    font-size: var(--m3-type-body-medium-size);
    color: var(--m3-color-on-surface-variant);
}

.m3-appearance__preview strong {
    color: var(--m3-color-on-surface);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
}

@media (min-width: 600px) {
    .m3-appearance__schemes {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (min-width: 75rem) {
    .m3-settings-page {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
        max-width: var(--m3-size-content-max);
    }

    .m3-settings-page :deep(.m3-settings-page__back) {
        grid-column: 1 / -1;
    }

    .m3-settings-page > :deep(.m3-settings-section) {
        min-width: 0;
    }
}
</style>
