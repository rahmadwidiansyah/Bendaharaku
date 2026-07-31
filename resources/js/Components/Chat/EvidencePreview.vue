<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ImagePreview from './ImagePreview.vue'
import StatusBadge from './StatusBadge.vue'
import ProgressOverlay from './ProgressOverlay.vue'
import RetryButton from './RetryButton.vue'

const { t } = useI18n()

const props = defineProps({
    src: { type: String, default: null },
    localPreview: { type: String, default: null },
    state: { type: String, default: 'PENDING' },
    progress: { type: Number, default: 0 },
    evidence: { type: Object, default: null },
})

const emit = defineEmits(['remove', 'review', 'retry', 'delete'])

const displaySrc = computed(() => {
    if (props.localPreview) return props.localPreview
    if (props.evidence?.url) return props.evidence.url
    return props.src
})

const fileName = computed(() => {
    if (props.evidence?.original_name) {
        const name = props.evidence.original_name
        if (name.length <= 20) return name
        const ext = name.split('.').pop()
        return name.slice(0, 15) + '...' + ext
    }
    return ''
})

const fileSize = computed(() => props.evidence?.formatted_size ?? '')

const showProgress = computed(() => props.state === 'UPLOADING' || props.state === 'PENDING')
const showActions = computed(() => props.state === 'UPLOADED' || props.state === 'PROCESSING' || props.state === 'READY')
const showRetry = computed(() => props.state === 'FAILED')
const showReviewBtn = computed(() => props.state === 'READY' || props.evidence?.status === 'RESOLVED' || props.evidence?.status === 'READY')
const isLoading = computed(() => props.state === 'UPLOADING' || props.state === 'PROCESSING')

function onFullscreen() {
    if (displaySrc.value) {
        window.open(displaySrc.value, '_blank')
    }
}
</script>

<template>
    <div
        class="relative flex items-center gap-3 px-3 py-2.5 rounded-2xl border transition-all duration-200"
        :class="[
            state === 'FAILED' ? 'bg-red-950/30 border-red-900/40' :
            state === 'UPLOADING' ? 'bg-gray-800/60 border-purple-500/30' :
            'bg-gray-800/80 border-white/10 hover:border-white/15',
        ]"
        style="max-width: 300px;"
        role="status"
        :aria-label="t('chat.evidencePreview')"
    >
        <!-- Thumbnail -->
        <button
            @click="onFullscreen"
            class="relative w-14 h-14 rounded-xl overflow-hidden shrink-0 border border-white/8 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/50 cursor-pointer"
            :aria-label="t('chat.openFullscreen')"
        >
            <ImagePreview
                :src="displaySrc"
                :alt="evidence?.original_name || ''"
                :loading="isLoading"
            />
            <ProgressOverlay :show="isLoading" :progress="progress" />
        </button>

        <!-- Info -->
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-white truncate">{{ fileName || t('chat.evidence') }}</p>
            <p class="text-2xs text-gray-500 mt-0.5">{{ fileSize }}</p>
            <div class="mt-1">
                <StatusBadge :status="state" :loading="isLoading" :progress="progress" />
            </div>
        </div>

        <!-- Action buttons -->
        <div class="flex flex-col gap-1 shrink-0">
            <button
                v-if="showReviewBtn"
                type="button"
                @click="emit('review', evidence?.uuid)"
                class="w-7 h-7 rounded-lg flex items-center justify-center text-purple-400 hover:text-purple-300 hover:bg-purple-500/10 transition-colors"
                :aria-label="t('chat.reviewEvidence')"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
            <button
                v-if="showActions"
                type="button"
                @click="emit('remove')"
                class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-500 hover:text-expense-text hover:bg-expense-bg-hover transition-colors"
                :aria-label="t('chat.removeEvidence')"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Retry area -->
        <div
            v-if="showRetry"
            class="absolute -bottom-8 left-3"
        >
            <RetryButton @retry="emit('retry')" @delete="emit('delete')" />
        </div>
    </div>
</template>
