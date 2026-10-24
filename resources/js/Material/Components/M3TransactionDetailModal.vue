<script setup>
/**
 * M3TransactionDetailModal — wadah modal untuk detail transaksi M3.
 *
 * Isi detail (hero, nominal, metadata, aksi, dialog konfirmasi) kini
 * hidup di M3TransactionDetailContent agar bisa dipakai ulang oleh
 * panel list-detail Transactions di layar lebar (>= 100rem).
 * Presentasi: dialog di desktop, bottom sheet di mobile (M3Modal).
 */
import M3Modal from '@/Material/Components/M3Modal.vue'
import M3TransactionDetailContent from '@/Material/Components/M3TransactionDetailContent.vue'

defineProps({
    show: Boolean,
    transaction: Object,
})

const emit = defineEmits(['close'])
</script>

<template>
    <M3Modal
        :show="show"
        :title="$t('transaction.detail.title')"
        variant="dialog"
        mobile-variant="sheet"
        max-width="sm"
        @close="emit('close')"
    >
        <M3TransactionDetailContent :transaction="transaction" @close="emit('close')" />
    </M3Modal>
</template>
