<script setup>
import { useI18n } from 'vue-i18n'
import BaseModal from '@/Components/BaseModal.vue'
import AppIcon from '@/Components/AppIcon.vue'
import { toLucide } from '@/utils/chatIcons.js'

const { t } = useI18n()

const props = defineProps({
    modelValue:          { type: Boolean, required: true },
    commandsByCategory:  { type: Object,  default: () => ({}) },
    categories:          { type: Array,   default: () => [] },
    categoryLabels:      { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'select'])

function close() {
    emit('update:modelValue', false)
}

function selectCommand(cmd) {
    emit('select', cmd.command + ' ')
    close()
}
</script>

<template>
    <BaseModal
        :show="modelValue"
        max-width="xl"
        align="bottom-sheet"
        mobile-only
        @close="close"
    >
        <!-- Header: judul + deskripsi -->
        <template #header>
            <div>
                <h2 class="text-sm font-bold text-white">{{ t('chat.sheetTitle') }}</h2>
                <p class="text-2xs text-gray-500 mt-0.5">{{ t('chat.sheetDesc') }}</p>
            </div>
        </template>

        <!-- Daftar perintah (scrollable) -->
        <div
            class="overflow-y-auto w-full max-h-[calc(100dvh-160px)] pt-1"
            style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0.5rem));"
        >
            <template v-for="cat in categories" :key="cat">
                <!-- Category heading -->
                <div class="px-1 pt-3 pb-1.5">
                    <p class="text-2xs font-black text-gray-600 uppercase tracking-[0.15em]">
                        {{ categoryLabels[cat] ?? cat }}
                    </p>
                </div>

                <!-- Commands in category -->
                <div class="px-1">
                    <button
                        v-for="cmd in commandsByCategory[cat]"
                        :key="cmd.command"
                        type="button"
                        @click="selectCommand(cmd)"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/5 active:bg-white/8 transition-colors text-left group"
                    >
                        <!-- Icon -->
                        <AppIcon :icon="toLucide(cmd.icon)" class="w-5 h-5 shrink-0 text-purple-400" fallback="circle-help" />

                        <!-- Command + description -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white font-mono">{{ cmd.command }}</p>
                            <p v-if="cmd.description" class="text-2xs text-gray-500 truncate mt-0.5">
                                {{ cmd.description }}
                            </p>
                        </div>

                        <!-- Arrow hint -->
                        <svg class="w-4 h-4 text-gray-700 group-hover:text-gray-400 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </BaseModal>
</template>
