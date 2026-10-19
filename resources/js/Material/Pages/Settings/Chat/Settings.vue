<script setup>
/**
 * Material/Pages/Settings/Chat/Settings.vue — hub pengaturan chat (tema M3).
 *
 * Titik masuk dari header chat (ikon gear). Semua baris mengarah ke halaman
 * existing; satu-satunya kontrol langsung adalah toggle notifikasi push
 * (paritas dengan Settings/Chat/Settings.vue versi Default).
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3Switch from '@/Material/Components/M3Switch.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'
import { usePushNotifications } from '@/Composables/usePushNotifications'

const props = defineProps({
    emailNotifications: { type: Boolean, default: true },
    pushNotifications: { type: Boolean, default: true },
})

const page = usePage()
const { t } = useI18n()
const { showToast } = useToast()
const push = usePushNotifications()

const vapidPublicKey = computed(() => page.props.vapidPublicKey ?? null)
const pushEnabled = ref(props.pushNotifications)
const pushAvailable = computed(() => push.isSupported && !!vapidPublicKey.value)
const permissionHint = computed(() => {
    if (!push.isSupported) return t('settings.application.notifications.push.unsupported')
    if (!vapidPublicKey.value) return t('settings.application.notifications.push.vapid_missing')
    return pushEnabled.value
        ? t('settings.chat.hub.notifications.on')
        : t('settings.chat.hub.notifications.off')
})

const saveNotificationPrefs = async () => {
    try {
        await axios.patch(route('settings.application.notifications.update'), {
            email_notifications: props.emailNotifications,
            push_notifications: pushEnabled.value,
        })
        showToast(t('toast.updated'), 'success')
    } catch (error) {
        showToast(error.response?.data?.message || t('errors.generic'), 'error')
    }
}

watch(pushEnabled, async (enabled) => {
    if (enabled) {
        const ok = await push.enablePush()
        if (!ok) {
            pushEnabled.value = false
            return
        }
    } else {
        await push.disablePush()
    }
    await saveNotificationPrefs()
})

onMounted(async () => {
    push.setVapidKey(vapidPublicKey.value)
    push.startPresence()
    await push.updateState()
})

onUnmounted(() => {
    push.stopPresence()
})

const chevron = 'M8.25 4.5l7.5 7.5-7.5 7.5'
</script>

<template>
    <M3Layout :title="$t('settings.chat.hub.head')">
        <Head :title="$t('settings.chat.hub.head')" />

        <div class="m3-hub">
            <M3PageTitle :title="$t('settings.chat.hub.title')" />

            <section class="m3-hub__section">
                <h2 class="m3-hub__section-title">{{ $t('settings.chat.hub.section.bot') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-hub__card">
                    <M3ListItem :href="route('settings.ai.bot')" divider>
                        <template #icon>
                            <span class="m3-hub__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('settings.chat.hub.bot.title') }}
                        <template #supporting>{{ $t('settings.chat.hub.bot.description') }}</template>
                        <template #trailing>
                            <svg class="m3-hub__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                            </svg>
                        </template>
                    </M3ListItem>

                    <M3ListItem :href="route('settings.ai.memory')" divider>
                        <template #icon>
                            <span class="m3-hub__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('settings.chat.hub.memory.title') }}
                        <template #supporting>{{ $t('settings.chat.hub.memory.description') }}</template>
                        <template #trailing>
                            <svg class="m3-hub__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                            </svg>
                        </template>
                    </M3ListItem>

                    <M3ListItem :href="route('settings.ai.models')">
                        <template #icon>
                            <span class="m3-hub__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('settings.chat.hub.providers.title') }}
                        <template #supporting>{{ $t('settings.chat.hub.providers.description') }}</template>
                        <template #trailing>
                            <svg class="m3-hub__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                            </svg>
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>

            <section class="m3-hub__section">
                <h2 class="m3-hub__section-title">{{ $t('settings.chat.hub.section.notifications') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-hub__card">
                    <M3ListItem>
                        <template #icon>
                            <span class="m3-hub__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('settings.chat.hub.notifications.label') }}
                        <template #supporting>{{ permissionHint }}</template>
                        <template #trailing>
                            <M3Switch
                                v-model="pushEnabled"
                                :disabled="!pushAvailable || push.busy.value"
                                :aria-label="$t('settings.chat.hub.notifications.label')"
                            />
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>

            <section class="m3-hub__section">
                <h2 class="m3-hub__section-title">{{ $t('settings.chat.hub.section.activity') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-hub__card">
                    <M3ListItem :href="route('settings.privacy.logs', { filter: 'ai' })" divider>
                        <template #icon>
                            <span class="m3-hub__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('settings.chat.hub.logs.title') }}
                        <template #supporting>{{ $t('settings.chat.hub.logs.description') }}</template>
                        <template #trailing>
                            <svg class="m3-hub__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                            </svg>
                        </template>
                    </M3ListItem>

                    <M3ListItem :href="route('settings.ai.status')">
                        <template #icon>
                            <span class="m3-hub__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('settings.chat.hub.status.title') }}
                        <template #supporting>{{ $t('settings.chat.hub.status.description') }}</template>
                        <template #trailing>
                            <svg class="m3-hub__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="chevron" />
                            </svg>
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-hub {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    width: 100%;
    max-width: var(--m3-size-list-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-hub__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-hub__section-title {
    margin: 0;
    padding-left: var(--m3-space-4);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-hub__card {
    overflow: hidden;
}

.m3-hub__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-hub__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-hub__chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    color: var(--m3-color-on-surface-variant);
}
</style>
