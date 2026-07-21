<script setup>
import BotAvatar from '@/Components/Chat/BotAvatar.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
    botName:   { type: String, default: 'Ken-Chan' },
    botAvatar: { type: String, default: null },
})

defineEmits(['select'])

const suggestions = [
    { label: 'Makan 20 ribu',              hintKey: 'chat.suggestionExpense' },
    { label: 'Gaji 5 juta BCA',            hintKey: 'chat.suggestionIncome' },
    { label: 'Transfer BCA ke Dana 100rb', hintKey: 'chat.suggestionTransfer' },
    { label: '/saldo',                     hintKey: 'chat.suggestionBalance' },
    { label: '/ringkasan',                 hintKey: 'chat.suggestionSummary' },
    { label: '/laporan',                   hintKey: 'chat.suggestionReport' },
    { label: '/statistik',                 hintKey: 'chat.suggestionStats' },
    { label: '/help',                      hintKey: 'chat.suggestionHelp' },
]
</script>

<template>
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 text-center select-none">

        <!-- Bot avatar (lg + online dot) -->
        <div class="mb-5">
            <BotAvatar
                :src="botAvatar"
                :name="botName"
                size="lg"
                variant="bot"
                shape="rounded"
                :online="true"
                class="shadow-lg shadow-purple-500/10"
            />
        </div>

        <!-- Name + tagline -->
        <h2 class="text-base font-bold text-white mb-0.5">{{ botName }}</h2>
        <p class="text-xs text-gray-500 mb-8 leading-relaxed max-w-xs">
            {{ t('chat.emptyState') }}
        </p>

        <!-- Suggestion chips -->
        <div class="w-full max-w-sm">
            <p class="text-2xs font-bold text-gray-700 uppercase tracking-widest mb-2.5">{{ t('chat.gettingStarted') }}</p>
            <div class="flex flex-wrap gap-1.5 justify-center">
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="s in suggestions"
                        :key="s.label"
                        type="button"
                        @click="$emit('select', s.label)"
                        :title="t(s.hintKey)"
                        class="w-full text-left px-3 py-2 rounded-xl bg-gray-900/85 border border-white/6 text-sm text-gray-200 font-semibold hover:border-white/12 hover:shadow-lg transition-all active:scale-98"
                    >
                        <div class="flex items-center justify-between">
                            <div class="truncate">{{ s.label }}</div>
                            <div class="text-2xs text-gray-400 ml-3">{{ t(s.hintKey) }}</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
