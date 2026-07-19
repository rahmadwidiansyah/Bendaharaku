<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
const props = defineProps({ component: { type: Object, required: true } })
</script>

<template>
    <div class="bg-gray-900 border border-white/6 rounded-lg p-3">
        <div class="flex items-center gap-3 mb-2">
            <div class="text-lg">{{ component.emoji || '📋' }}</div>
            <div class="flex-1">
                <div class="text-sm font-semibold text-white">
                    {{ component.title || (component.translationKey ? t(component.translationKey) : '') }}
                </div>
            </div>
        </div>
        <ul class="list-inside list-disc text-2xs text-gray-300 space-y-1">
            <li v-for="(item, idx) in component.items" :key="idx" class="truncate">{{ item }}</li>
        </ul>
    </div>
</template>
