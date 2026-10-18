<script setup>
/**
 * M3DraftActions — aksi untuk transaksi draft (Edit / Konfirmasi / Batal),
 * paritas DraftActions Default dengan tombol M3.
 */
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import M3Button from '@/Material/Components/M3Button.vue'

const { t } = useI18n()

const props = defineProps({
    transactionId: { type: Number, required: true },
    editUrl:       { type: String, required: true },
})

const emit = defineEmits(['confirm', 'cancel'])

const isConfirming = ref(false)
const isCancelling = ref(false)

function handleConfirm() {
    isConfirming.value = true
    emit('confirm')
    setTimeout(() => { isConfirming.value = false }, 2000)
}

function handleCancel() {
    isCancelling.value = true
    emit('cancel')
    setTimeout(() => { isCancelling.value = false }, 2000)
}
</script>

<template>
    <div class="m3-draft-actions">
        <M3Button variant="outlined" size="sm" :href="editUrl">
            <template #leading>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </template>
            {{ t('common.edit') }}
        </M3Button>

        <M3Button variant="filled" size="sm" :disabled="isConfirming || isCancelling" @click="handleConfirm">
            <template #leading>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </template>
            {{ isConfirming ? t('common.saving') : t('common.confirm') }}
        </M3Button>

        <M3Button
            variant="text"
            size="sm"
            class="m3-draft-actions__cancel"
            :disabled="isConfirming || isCancelling"
            @click="handleCancel"
        >
            <template #leading>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </template>
            {{ t('common.cancel') }}
        </M3Button>
    </div>
</template>

<style scoped>
.m3-draft-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--m3-space-2);
    margin-top: var(--m3-space-1-5);
}

.m3-draft-actions__cancel {
    color: var(--m3-color-error);
}
</style>
