<script setup>
/**
 * Material/Pages/Settings/AI/Bot.vue — profil bot (tema M3).
 *
 * Paritas perilaku Pages/Settings/AI/Bot.vue (Default): form nama + avatar
 * (crop 1:1 webp ≤800KB), hapus avatar, chips saran nama. Route & payload
 * identik (settings.chat.bot-profile.update, _method PATCH).
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3SettingsBack from '@/Material/Components/Settings/M3SettingsBack.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import M3TextField from '@/Material/Components/M3TextField.vue'
import M3Button from '@/Material/Components/M3Button.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3ImageCropModal from '@/Material/Components/M3ImageCropModal.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
    botName: { type: String, default: 'Ken-Chan' },
    botAvatar: { type: String, default: null },
})

const { t } = useI18n()
const { showToast } = useToast()

const form = useForm({
    bot_name: props.botName,
    bot_avatar: null,
    _method: 'PATCH',
})

const previewFile = ref(null)
const showPhotoDialog = ref(false)
const showCropModal = ref(false)
const showDeleteConfirm = ref(false)

const botAvatarUrl = computed(() => previewFile.value || props.botAvatar)
const suggestedNames = ['Ken-Chan', 'Ken-Dana', 'Assistant', 'Bendaharaku']

const openCropModal = () => {
    showPhotoDialog.value = false
    showCropModal.value = true
}

const handleCropped = (blob) => {
    const file = new File([blob], 'bot-avatar.webp', { type: blob.type })
    form.bot_avatar = file
    const reader = new FileReader()
    reader.onload = (e) => {
        previewFile.value = e.target?.result
    }
    reader.readAsDataURL(blob)
    form.post(route('settings.chat.bot-profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showToast(t('toast.updated'), 'success')
            form.bot_avatar = null
        },
        onError: () => {
            showToast(t('errors.generic'), 'error')
        },
    })
}

const saveBotSettings = () => {
    form.post(route('settings.chat.bot-profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showToast(t('toast.updated'), 'success')
            form.bot_avatar = null
        },
        onError: () => {
            showToast(t('errors.generic'), 'error')
        },
    })
}

const deleteBotAvatar = () => {
    showPhotoDialog.value = false
    showDeleteConfirm.value = true
}

const confirmDeleteBotAvatar = () => {
    router.delete(route('settings.chat.bot-avatar.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false
            previewFile.value = null
            form.bot_avatar = null
            showToast(t('toast.updated'), 'success')
        },
    })
}
</script>

<template>
    <M3Layout :title="t('settings.ai.bot.title')">
        <Head :title="t('settings.ai.bot.title')" />

        <div class="m3-bot mx-auto w-full max-w-4xl">
            <M3PageTitle
                :title="t('settings.ai.bot.title')"
                :subtitle="t('settings.ai.bot.description')"
            >
                <template #leading>
                    <M3SettingsBack inline />
                </template>
            </M3PageTitle>

            <!-- Bar kembali untuk desktop (judul in-content disembunyikan) -->
            <M3SettingsBack />

            <form class="m3-bot__form" @submit.prevent="saveBotSettings">
                <div class="m3-bot__avatar-block">
                    <button
                        type="button"
                        class="m3-bot__avatar"
                        :aria-label="t('settings.ai.bot.avatar.label')"
                        @click="showPhotoDialog = true"
                    >
                        <img v-if="botAvatarUrl" :src="botAvatarUrl" :alt="form.bot_name" class="m3-bot__avatar-img">
                        <span v-else class="m3-bot__avatar-fallback" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                            </svg>
                        </span>
                        <span class="m3-bot__avatar-badge" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                            </svg>
                        </span>
                    </button>
                    <p class="m3-bot__avatar-hint">{{ t('settings.ai.bot.avatar.hint') }}</p>
                    <p v-if="form.errors.bot_avatar" class="m3-bot__avatar-error" role="alert">
                        {{ form.errors.bot_avatar }}
                    </p>
                </div>

                <M3Card variant="outlined" padding="md">
                    <div class="m3-bot__name-fields">
                        <M3TextField
                            v-model="form.bot_name"
                            :label="t('settings.ai.bot.name.label')"
                            :placeholder="t('settings.ai.bot.name.placeholder')"
                            :error="form.errors.bot_name"
                            autocomplete="off"
                        />

                        <div class="m3-bot__suggestions">
                            <p class="m3-bot__suggestions-label">{{ t('settings.ai.bot.name.suggestions') }}</p>
                            <div class="m3-bot__suggestions-row">
                                <M3Chip
                                    v-for="name in suggestedNames"
                                    :key="name"
                                    variant="filter"
                                    :selected="form.bot_name === name"
                                    @click="form.bot_name = name"
                                >
                                    {{ name }}
                                </M3Chip>
                            </div>
                        </div>

                        <p class="m3-bot__hint">{{ t('settings.ai.bot.name.hint') }}</p>
                    </div>
                </M3Card>

                <M3Button type="submit" variant="filled" :disabled="form.processing" full-width>
                    {{ form.processing ? t('common.saving') : t('common.save') }}
                </M3Button>
            </form>
        </div>

        <!-- Aksi foto: dialog di desktop, bottom sheet di mobile -->
        <M3Modal
            :show="showPhotoDialog"
            :title="t('chatBot.photoSection')"
            max-width="sm"
            variant="dialog"
            mobile-variant="sheet"
            @close="showPhotoDialog = false"
        >
            <M3Card variant="outlined" padding="none" class="m3-bot__photo-actions">
                <M3ListItem clickable divider @click="openCropModal">
                    <template #icon>
                        <span class="m3-bot__action-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                            </svg>
                        </span>
                    </template>
                    {{ t('settings.ai.bot.avatar.upload_button') }}
                </M3ListItem>

                <M3ListItem v-if="botAvatarUrl" clickable @click="deleteBotAvatar">
                    <template #icon>
                        <span class="m3-bot__action-ico m3-bot__action-ico--danger" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </span>
                    </template>
                    <span class="m3-bot__danger-text">{{ t('settings.ai.bot.avatar.remove') }}</span>
                </M3ListItem>
            </M3Card>
        </M3Modal>

        <!-- Konfirmasi hapus avatar -->
        <M3Modal :show="showDeleteConfirm" max-width="sm" @close="showDeleteConfirm = false">
            <div class="m3-bot__confirm">
                <h2 class="m3-bot__confirm-title">{{ t('settings.ai.bot.avatar.remove_title') }}</h2>
                <p class="m3-bot__confirm-text">{{ t('settings.ai.bot.avatar.remove_message') }}</p>
            </div>
            <template #footer>
                <M3Button variant="text" @click="showDeleteConfirm = false">
                    {{ t('common.cancel') }}
                </M3Button>
                <M3Button variant="filled" class="m3-bot__danger" @click="confirmDeleteBotAvatar">
                    {{ t('common.delete') }}
                </M3Button>
            </template>
        </M3Modal>

        <M3ImageCropModal
            v-model="showCropModal"
            :title="t('settings.ai.bot.avatar.label')"
            :aspect-ratio="1"
            circle
            :max-size-kb="800"
            output-format="image/webp"
            @cropped="handleCropped"
        />
    </M3Layout>
</template>

<style scoped>
.m3-bot {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    padding: var(--m3-space-4);
}

.m3-bot__form {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-bot__avatar-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--m3-space-2);
}

.m3-bot__avatar {
    position: relative;
    width: calc(var(--m3-space-12) * 2.5);
    height: calc(var(--m3-space-12) * 2.5);
    padding: 0;
    border: none;
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-surface-container-highest);
    cursor: pointer;
    overflow: visible;
    transition: transform var(--m3-motion-fast-spatial-duration) var(--m3-motion-fast-spatial);
}

.m3-bot__avatar:active {
    transform: scale(0.97);
}

.m3-bot__avatar:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 4px;
}

.m3-bot__avatar-img {
    width: 100%;
    height: 100%;
    border-radius: var(--m3-radius-full);
    object-fit: cover;
    display: block;
}

.m3-bot__avatar-fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: var(--m3-radius-full);
    color: var(--m3-color-on-surface-variant);
}

.m3-bot__avatar-fallback svg {
    width: var(--m3-size-icon-xl);
    height: var(--m3-size-icon-xl);
}

.m3-bot__avatar-badge {
    position: absolute;
    right: 0;
    bottom: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    box-shadow: var(--m3-elevation-2);
}

.m3-bot__avatar-badge svg {
    width: var(--m3-size-icon-2xs);
    height: var(--m3-size-icon-2xs);
}

.m3-bot__avatar-hint {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
    text-align: center;
}

.m3-bot__avatar-error {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-error);
    text-align: center;
}

.m3-bot__name-fields {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-4);
}

.m3-bot__suggestions {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-bot__suggestions-label {
    margin: 0;
    font-size: var(--m3-type-label-medium-size);
    line-height: var(--m3-type-label-medium-line-height);
    font-weight: var(--m3-type-label-large-weight);
    letter-spacing: var(--m3-type-label-medium-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-bot__suggestions-row {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
}

.m3-bot__hint {
    margin: 0;
    font-size: var(--m3-type-body-small-size);
    line-height: var(--m3-type-body-small-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bot__photo-actions {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.m3-bot__action-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-bot__action-ico--danger {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
}

.m3-bot__action-ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-bot__danger-text {
    color: var(--m3-color-error);
}

.m3-bot__confirm {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-bot__confirm-title {
    margin: 0;
    font-size: var(--m3-type-headline-small-size);
    line-height: var(--m3-type-headline-small-line-height);
    font-weight: var(--m3-type-headline-small-weight);
    color: var(--m3-color-on-surface);
}

.m3-bot__confirm-text {
    margin: 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    color: var(--m3-color-on-surface-variant);
}

.m3-bot__danger {
    background: var(--m3-color-error);
    color: var(--m3-color-on-error);
}
</style>
