<script setup>
/**
 * CommandSheet.vue
 *
 * Bottom sheet daftar command dari CommandRegistry.
 * Saat user memilih command, emit 'select' dengan command string
 * → ChatComposer.insertText() memasukkannya ke textarea.
 */

import { computed } from 'vue'

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
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modelValue" class="fixed inset-0 z-50 flex items-end">
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                    @click="close"
                    aria-hidden="true"
                />

                <!-- Sheet panel -->
                <Transition
                    enter-active-class="transition-transform duration-300 ease-out"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                    appear
                >
                    <div
                        v-if="modelValue"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Daftar Perintah"
                        class="relative z-10 w-full max-w-md mx-auto bg-gray-900 border-t border-x border-white/10 rounded-t-3xl shadow-2xl max-h-[70vh] flex flex-col"
                    >
                        <!-- Handle -->
                        <div class="flex justify-center pt-3 pb-1 shrink-0">
                            <div class="w-10 h-1 rounded-full bg-white/20" aria-hidden="true" />
                        </div>

                        <!-- Header -->
                        <div class="flex items-center justify-between px-5 py-3 border-b border-white/5 shrink-0">
                            <div>
                                <h2 class="text-sm font-bold text-white">Perintah Cepat</h2>
                                <p class="text-2xs text-gray-500 mt-0.5">Pilih perintah untuk memasukkannya ke chat</p>
                            </div>
                            <button
                                @click="close"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/8 transition-colors"
                                aria-label="Tutup"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Scrollable command list -->
                        <div class="overflow-y-auto flex-1 py-2"
                            style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom, 0.5rem));">

                            <template v-for="cat in categories" :key="cat">
                                <!-- Category heading -->
                                <div class="px-5 pt-3 pb-1.5">
                                    <p class="text-2xs font-black text-gray-600 uppercase tracking-[0.15em]">
                                        {{ categoryLabels[cat] ?? cat }}
                                    </p>
                                </div>

                                <!-- Commands in category -->
                                <div class="px-3">
                                    <button
                                        v-for="cmd in commandsByCategory[cat]"
                                        :key="cmd.command"
                                        type="button"
                                        @click="selectCommand(cmd)"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/5 active:bg-white/8 transition-colors text-left group"
                                    >
                                        <!-- Icon -->
                                        <span class="text-lg w-7 text-center shrink-0 leading-none">{{ cmd.icon }}</span>

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
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
