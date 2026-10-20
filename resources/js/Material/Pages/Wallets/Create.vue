<script setup>
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3WalletForm from '@/Material/Components/Wallet/M3WalletForm.vue'
import AppIcon from '@/Components/AppIcon.vue'

const { t } = useI18n()
</script>

<template>
    <M3Layout :title="t('wallet.titleCreate')">
        <Head :title="t('wallet.titleCreate')" />

        <div class="m3-wallet-edit">
            <header class="m3-wallet-edit__topbar">
                <M3IconButton :href="route('wallets.index')" :aria-label="t('common.back')">
                    <AppIcon icon="arrow-left" />
                </M3IconButton>
                <h1 class="m3-wallet-edit__title">{{ t('wallet.titleCreate') }}</h1>
            </header>

            <M3WalletForm mode="create" />
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-wallet-edit {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-list-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-wallet-edit__topbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-wallet-edit__title {
    margin: 0;
    min-width: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
}

@media (min-width: 1024px) {
    .m3-wallet-edit__topbar {
        display: none;
    }
}
</style>
