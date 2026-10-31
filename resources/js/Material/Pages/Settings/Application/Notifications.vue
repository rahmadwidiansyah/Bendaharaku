<script setup>
/**
 * Material/Pages/Settings/Application/Notifications.vue — notifikasi (tema M3).
 *
 * Paritas perilaku halaman Default: email disimpan lewat tombol simpan;
 * push auto-save saat toggle berubah dan meminta izin browser.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3Switch from '@/Material/Components/M3Switch.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3SettingsPage from '@/Material/Components/Settings/M3SettingsPage.vue'
import M3SettingsSection from '@/Material/Components/Settings/M3SettingsSection.vue'
import { Head } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'
import { usePushNotifications } from '@/Composables/usePushNotifications'

const props = defineProps({
    emailNotifications: { type: Boolean, default: true },
    pushNotifications: { type: Boolean, default: true },
    vapidPublicKey: { type: String, default: null },
})

const { t } = useI18n()
const { showToast } = useToast()
const push = usePushNotifications()

const emailEnabled = ref(props.emailNotifications)
const pushEnabled = ref(props.pushNotifications)
const saving = ref(false)
const pushAvailable = computed(() => push.isSupported && !!props.vapidPublicKey)

const permissionHint = computed(() => {
    if (!push.isSupported) return t('settings.application.notifications.push.unsupported')
    if (!props.vapidPublicKey) return t('settings.application.notifications.push.vapid_missing')
    if (push.permission.value === 'denied') return t('settings.application.notifications.push.denied')
    if (push.permission.value === 'granted') return t('settings.application.notifications.push.granted')
    return t('settings.application.notifications.push.default')
})

async function handleSave() {
    saving.value = true
    try {
        await axios.patch(route('settings.application.notifications.update'), {
            email_notifications: emailEnabled.value,
            push_notifications: pushEnabled.value,
        })
        showToast(t('toast.updated'), 'success')
    } catch (error) {
        showToast(error.response?.data?.message || t('errors.generic'), 'error')
    } finally {
        saving.value = false
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
    await handleSave()
})

onMounted(async () => {
    push.setVapidKey(props.vapidPublicKey)
    push.startPresence()
    await push.updateState()
})

onUnmounted(() => {
    push.stopPresence()
})
</script>

<template>
    <M3Layout :title="t('settings.application.notifications.title')">
        <Head :title="t('settings.application.notifications.title')" />

        <M3SettingsPage
            :title="t('settings.application.notifications.title')"
            :subtitle="t('settings.application.notifications.description')"
            back-route="settings.index"
            shell
        >
            <M3SettingsSection :title="t('settings.application.notifications.email.title')">
                <M3Card variant="outlined" padding="none" class="m3-notifications__card">
                    <M3ListItem>
                        <template #icon>
                            <span class="m3-notifications__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.application.notifications.email.label') }}
                        <template #supporting>{{ t('settings.application.notifications.email.description') }}</template>
                        <template #trailing>
                            <M3Switch
                                v-model="emailEnabled"
                                :aria-label="t('settings.application.notifications.email.label')"
                            />
                        </template>
                    </M3ListItem>
                </M3Card>
            </M3SettingsSection>

            <M3SettingsSection :title="t('settings.application.notifications.push.title')">
                <M3Card variant="outlined" padding="none" class="m3-notifications__card">
                    <M3ListItem>
                        <template #icon>
                            <span class="m3-notifications__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            </span>
                        </template>
                        {{ t('settings.application.notifications.push.label') }}
                        <template #supporting>{{ permissionHint }}</template>
                        <template #trailing>
                            <M3Switch
                                v-model="pushEnabled"
                                :disabled="!pushAvailable || push.busy.value"
                                :aria-label="t('settings.application.notifications.push.label')"
                            />
                        </template>
                    </M3ListItem>
                </M3Card>
            </M3SettingsSection>

            <div class="m3-notifications__actions">
                <M3Button :disabled="saving" @click="handleSave">
                    {{ saving ? t('common.saving') : t('common.save') }}
                </M3Button>
            </div>
        </M3SettingsPage>
    </M3Layout>
</template>

<style scoped>
.m3-notifications__card {
    overflow: hidden;
}

.m3-notifications__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-notifications__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-notifications__actions {
    display: flex;
}
</style>
