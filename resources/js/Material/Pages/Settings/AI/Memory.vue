<script setup>
/**
 * Material/Pages/Settings/AI/Memory.vue — memori bot (tema M3).
 *
 * Paritas Pages/Settings/AI/Memory.vue (Default): toggle riwayat percakapan
 * disimpan lokal (localStorage `ai_memory_settings`) + entry ke halaman
 * pengelolaan memori. Sub-halaman memory.manage/detail tetap tema Default.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3SettingsBack from '@/Material/Components/Settings/M3SettingsBack.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3Switch from '@/Material/Components/M3Switch.vue'
import { Head } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'

const { t } = useI18n()
const { showToast } = useToast()

const enableConversationHistory = ref(true)

onMounted(() => {
    try {
        const stored = localStorage.getItem('ai_memory_settings')
        if (stored) {
            const parsed = JSON.parse(stored)
            if (typeof parsed.enable_conversation_history === 'boolean') {
                enableConversationHistory.value = parsed.enable_conversation_history
            }
        }
    } catch (e) {
        // localStorage tidak tersedia / data lama rusak — pakai default
    }
})

const onHistoryToggle = (value) => {
    enableConversationHistory.value = value
    try {
        localStorage.setItem('ai_memory_settings', JSON.stringify({ enable_conversation_history: value }))
        showToast(t('toast.updated'), 'success')
    } catch (error) {
        showToast(t('errors.generic'), 'error')
    }
}

const chevron = 'M8.25 4.5l7.5 7.5-7.5 7.5'
</script>

<template>
    <M3Layout :title="t('settings.ai.memory.title')">
        <Head :title="t('settings.ai.memory.title')" />

        <div class="m3-memory mx-auto w-full max-w-4xl">
            <M3PageTitle
                :title="t('settings.ai.memory.title')"
                :subtitle="t('settings.ai.memory.description')"
            >
                <template #leading>
                    <M3SettingsBack inline />
                </template>
            </M3PageTitle>

            <!-- Bar kembali untuk desktop (judul in-content disembunyikan) -->
            <M3SettingsBack />

            <section class="m3-memory__section">
                <M3Card variant="outlined" padding="none" class="m3-memory__card">
                    <M3ListItem :href="route('settings.ai.memory.manage')">
                        <template #icon>
                            <span class="m3-memory__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.ai.memory.manage.title') }}
                        <template #supporting>{{ t('settings.ai.memory.manage.description') }}</template>
                        <template #trailing>
                            <svg class="m3-memory__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                            </svg>
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>

            <section class="m3-memory__section">
                <M3Card variant="outlined" padding="none" class="m3-memory__card">
                    <M3ListItem>
                        <template #icon>
                            <span class="m3-memory__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.ai.memory.conversation_history.label') }}
                        <template #supporting>{{ t('settings.ai.memory.conversation_history.description') }}</template>
                        <template #trailing>
                            <M3Switch
                                :model-value="enableConversationHistory"
                                :aria-label="t('settings.ai.memory.conversation_history.enable')"
                                @update:model-value="onHistoryToggle"
                            />
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>

            <p class="m3-memory__help">{{ t('settings.ai.memory.help_text') }}</p>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-memory {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    padding: var(--m3-space-4);
}

.m3-memory__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-memory__card {
    overflow: hidden;
}

.m3-memory__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-memory__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-memory__chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    color: var(--m3-color-on-surface-variant);
}

.m3-memory__help {
    margin: 0;
    padding: 0 var(--m3-space-4);
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}
</style>
