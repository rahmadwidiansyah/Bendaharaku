<script setup>
/**
 * M3QuickWalletPicker — pilihan dompet cepat untuk draft yang needs_wallet
 * (paritas QuickWalletPicker Default; chip M3, warna non-finansial).
 */
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import AppIcon from '@/Components/AppIcon.vue'
import M3Chip from '@/Material/Components/M3Chip.vue'

const { t } = useI18n()

const props = defineProps({
    transactionId: { type: Number, required: true },
    loading:       { type: Boolean, default: false },
})

const emit = defineEmits(['select'])

const wallets    = ref([])
const isFetching = ref(true)
const selectedId = ref(null)
const fetchError = ref(null)

onMounted(async () => {
    try {
        const { data } = await axios.get(route('chat.wallets'))
        wallets.value = data.wallets ?? []
    } catch (e) {
        fetchError.value = t('chatTransaction.walletLoadFailed')
    } finally {
        isFetching.value = false
    }
})

function select(wallet) {
    if (props.loading || selectedId.value) return
    selectedId.value = wallet.id
    emit('select', { walletId: wallet.id })
}
</script>

<template>
    <div class="m3-qwp">
        <p class="m3-qwp__label">
            <AppIcon icon="wallet" iconClass="m3-qwp__label-icon" />
            {{ t('transaction.chooseWallet') }}
        </p>

        <div v-if="isFetching" class="m3-qwp__chips" aria-hidden="true">
            <span v-for="i in 4" :key="i" class="m3-qwp__chip-skeleton" />
        </div>

        <p v-else-if="fetchError" class="m3-qwp__error" role="alert">{{ fetchError }}</p>

        <div v-else class="m3-qwp__chips">
            <M3Chip
                v-for="wallet in wallets"
                :key="wallet.id"
                variant="filter"
                :selected="selectedId === wallet.id"
                :disabled="loading || !!selectedId"
                @click="select(wallet)"
            >
                <span v-if="loading && selectedId === wallet.id" class="m3-qwp__spin" aria-hidden="true" />
                {{ wallet.name }}
            </M3Chip>
        </div>
    </div>
</template>

<style scoped>
.m3-qwp {
    margin-top: var(--m3-space-2);
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-qwp__label {
    display: flex;
    align-items: center;
    gap: var(--m3-space-1-5);
    margin: 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    font-weight: var(--m3-type-emphasized-label-small-weight);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--m3-color-on-surface-variant);
}

.m3-qwp__label-icon {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    flex-shrink: 0;
    color: var(--m3-color-primary);
}

.m3-qwp__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--m3-space-1-5);
}

.m3-qwp__chip-skeleton {
    display: inline-block;
    width: 4rem;
    height: 2rem;
    border-radius: var(--m3-radius-full);
    background: color-mix(in srgb, var(--m3-color-on-surface) 8%, transparent);
    animation: m3-qwp-pulse 1.6s ease-in-out infinite;
}

.m3-qwp__error {
    margin: 0;
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-error);
}

.m3-qwp__spin {
    display: inline-block;
    width: 0.75rem;
    height: 0.75rem;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: var(--m3-radius-full);
    animation: m3-qwp-spin 0.8s linear infinite;
}

@keyframes m3-qwp-spin {
    to { transform: rotate(360deg); }
}

@keyframes m3-qwp-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.45; }
}
</style>
