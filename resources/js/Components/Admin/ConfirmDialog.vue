<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue'
import { AlertTriangle, X } from 'lucide-vue-next'

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Konfirmasi' },
    description: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Konfirmasi' },
    cancelLabel: { type: String, default: 'Batal' },
    tone: { type: String, default: 'primary' },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open', 'confirm', 'cancel'])

const close = () => {
    if (!props.loading) {
        emit('update:open', false)
        emit('cancel')
    }
}

const onKeydown = (event) => {
    if (event.key === 'Escape' && props.open) close()
}

watch(
    () => props.open,
    (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : ''
    },
)

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex items-end justify-center bg-black/60 p-4 backdrop-blur-sm sm:items-center"
                role="dialog"
                aria-modal="true"
                :aria-label="title"
                @click.self="close"
            >
                <div class="w-full max-w-md rounded-2xl border border-[rgb(var(--m3-color-outline-variant-rgb))] bg-[rgb(var(--m3-color-surface-container-high-rgb))] p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span
                                :class="[
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                    tone === 'danger'
                                        ? 'bg-[rgb(var(--m3-color-error-container-rgb))] text-[rgb(var(--m3-color-on-error-container-rgb))]'
                                        : 'bg-[rgb(var(--m3-color-primary-container-rgb))] text-[rgb(var(--m3-color-on-primary-container-rgb))]',
                                ]"
                            >
                                <AlertTriangle class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ title }}</h2>
                                <p v-if="description" class="mt-1 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
                                    {{ description }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-[rgb(var(--m3-color-on-surface-variant-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]"
                            aria-label="Tutup"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div v-if="$slots.default" class="mt-4">
                        <slot />
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="rounded-xl border border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-2 text-sm font-medium text-[rgb(var(--m3-color-on-surface-rgb))] transition-colors hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))] disabled:opacity-50"
                            :disabled="loading"
                            @click="close"
                        >
                            {{ cancelLabel }}
                        </button>
                        <button
                            type="button"
                            :class="[
                                'rounded-xl px-4 py-2 text-sm font-semibold transition-colors disabled:opacity-50',
                                tone === 'danger'
                                    ? 'bg-[rgb(var(--m3-color-error-rgb))] text-[rgb(var(--m3-color-on-error-rgb))] hover:bg-[rgb(var(--m3-color-error-container-rgb))] hover:text-[rgb(var(--m3-color-on-error-container-rgb))]'
                                    : 'bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))] hover:bg-[rgb(var(--m3-color-primary-container-rgb))] hover:text-[rgb(var(--m3-color-on-primary-container-rgb))]',
                            ]"
                            :disabled="loading"
                            @click="emit('confirm')"
                        >
                            {{ loading ? 'Memproses…' : confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
