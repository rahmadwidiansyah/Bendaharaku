<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
    paginator: { type: Object, required: true },
})

const cleanLabel = (label) =>
    String(label)
        .replaceAll('&laquo;', '')
        .replaceAll('&raquo;', '')
        .replaceAll('&hellip;', '…')
        .replaceAll('Previous', 'Prev')
        .trim()

const visibleLinks = computed(() => props.paginator.links ?? [])
const hasPages = computed(() => (props.paginator.last_page ?? 1) > 1)
</script>

<template>
    <div
        v-if="hasPages"
        class="flex flex-col gap-3 border-t border-[rgb(var(--m3-color-outline-variant-rgb))] px-4 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6"
    >
        <p class="text-xs text-[rgb(var(--m3-color-on-surface-variant-rgb))]">
            Menampilkan
            <span class="font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ paginator.from ?? 0 }}–{{ paginator.to ?? 0 }}</span>
            dari
            <span class="font-semibold text-[rgb(var(--m3-color-on-surface-rgb))]">{{ paginator.total ?? 0 }}</span>
        </p>

        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            <template v-for="(link, index) in visibleLinks" :key="index">
                <span
                    v-if="!link.url"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm text-[rgb(var(--m3-color-on-surface-variant-rgb))] opacity-50"
                >{{ cleanLabel(link.label) }}</span>
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :class="[
                        'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-medium transition-colors',
                        link.active
                            ? 'bg-[rgb(var(--m3-color-primary-rgb))] text-[rgb(var(--m3-color-on-primary-rgb))]'
                            : 'text-[rgb(var(--m3-color-on-surface-rgb))] hover:bg-[rgb(var(--m3-color-surface-container-highest-rgb))]',
                    ]"
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-label="`Halaman ${cleanLabel(link.label)}`"
                >
                    <ChevronLeft v-if="index === 0" class="h-4 w-4" />
                    <ChevronRight v-else-if="index === visibleLinks.length - 1" class="h-4 w-4" />
                    <template v-else>{{ cleanLabel(link.label) }}</template>
                </Link>
            </template>
        </nav>
    </div>
</template>
