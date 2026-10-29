<script setup>
/**
 * Material/Pages/Categories/Edit.vue — edit kategori M3.
 * Halaman tipis: state, submit, dan konfirmasi hapus ditangani M3CategoryForm.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3IconButton from '@/Material/Components/M3IconButton.vue'
import M3CategoryForm from '@/Material/Components/Category/M3CategoryForm.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { goBack } from '@/Material/utils/goBack.js'
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
    category: { type: Object, required: true },
    types: { type: Array, default: () => [] },
    isSystem: { type: Boolean, default: false },
})
</script>

<template>
    <M3Layout :title="t('category.titleEdit')">
        <Head :title="t('category.titleEdit')" />

        <div class="m3-cat-edit">
            <header class="m3-cat-edit__topbar">
                <M3IconButton :aria-label="t('common.back')" @click="goBack(route('categories.index'))">
                    <AppIcon icon="arrow-left" />
                </M3IconButton>
                <h1 class="m3-cat-edit__title">{{ t('category.titleEdit') }}</h1>
            </header>

            <M3CategoryForm
                mode="edit"
                :category="category"
                :types="types"
                :is-system="isSystem"
            />
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-cat-edit {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-5);
    width: 100%;
    max-width: var(--m3-size-list-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-cat-edit__topbar {
    display: flex;
    align-items: center;
    gap: var(--m3-space-2);
    min-width: 0;
}

.m3-cat-edit__title {
    margin: 0;
    min-width: 0;
    color: var(--m3-color-on-surface);
    font-size: var(--m3-type-title-large-size);
    line-height: var(--m3-type-title-large-line-height);
    font-weight: var(--m3-type-emphasized-title-large-weight);
    letter-spacing: var(--m3-type-title-large-tracking);
}

@media (min-width: 1024px) {
    .m3-cat-edit__topbar {
        display: none;
    }
}
</style>
