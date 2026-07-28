<script setup>
/**
 * PortfolioCard.vue
 *
 * Card total kekayaan dengan background chart SVG dekoratif,
 * breakdown Liquid vs Investasi, dan toggle visibility saldo.
 *
 * Diekstrak dari Dashboard.vue — sebelumnya ~60 baris inline di template.
 *
 * Props:
 *   totalPortfolio — Total semua aset (Liquid + Investasi)
 *   totalLiquid    — Total aset liquid
 *   totalInvest    — Total aset investasi
 *   isVisible      — Apakah saldo ditampilkan atau disembunyikan
 *
 * Emits:
 *   toggle-visibility — Request toggle visibilitas saldo
 */

import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatNumber } from '@/utils/format.js'

const { t } = useI18n()

const props = defineProps({
    totalPortfolio: {
        type: Number,
        default: 0,
    },
    totalLiquid: {
        type: Number,
        default: 0,
    },
    totalInvest: {
        type: Number,
        default: 0,
    },
    isVisible: {
        type: Boolean,
        default: true,
    },
})

defineEmits(['toggle-visibility'])

const displayAmount = (n) => props.isVisible ? formatNumber(n) : '••••••••'
const displayShort  = (n) => props.isVisible ? formatNumber(n) : '••••'
</script>

<template>
    <div class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl border border-white/10 overflow-hidden mb-3 sm:mb-5 group animate-fade-in-up delay-200">
        <!-- Hover overlay -->
        <div class="absolute inset-0 bg-gray-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none" aria-hidden="true" />

        <!-- Background decorative chart SVG -->
        <div class="absolute inset-x-0 bottom-0 opacity-20 pointer-events-none h-24" aria-hidden="true">
            <svg viewBox="0 0 400 150" preserveAspectRatio="none" class="w-full h-full">
                <defs>
                    <linearGradient id="portfolioChartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%"   style="stop-color:#fca5ff; stop-opacity:0.4" />
                        <stop offset="100%" style="stop-color:#fca5ff; stop-opacity:0" />
                    </linearGradient>
                </defs>
                <path
                    d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50 L400,150 L0,150 Z"
                    fill="url(#portfolioChartGradient)"
                />
                <path
                    d="M0,100 C50,120 100,60 150,90 C200,120 250,40 300,70 C350,100 400,50 400,50"
                    stroke="#FCA5FF"
                    stroke-width="3"
                    fill="none"
                />
            </svg>
        </div>

        <!-- Content -->
        <div class="relative z-10 p-4 sm:p-7 sm:pb-6">
            <!-- Header row -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-purple-500" aria-hidden="true" />
                    <p class="text-2xs text-gray-400 font-bold uppercase tracking-[0.2em]">{{ $t('portfolio.title') }}</p>
                </div>

                <!-- Toggle visibility button -->
                <button
                    type="button"
                    @click="$emit('toggle-visibility')"
                    class="text-gray-500 hover:text-white transition-colors p-1 -m-1 focus:outline-none focus-visible:ring-1 focus-visible:ring-purple-400 rounded"
                    :aria-label="$t('header.toggleBalance')"
                >
                    <!-- Eye icon — visible -->
                    <svg v-if="isVisible" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <!-- Eye-off icon — hidden -->
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>

            <!-- Total amount -->
            <div class="flex items-baseline gap-1.5 mb-4">
                <span class="text-base sm:text-lg font-medium text-gray-500">Rp</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight" aria-live="polite">
                    {{ displayAmount(totalPortfolio) }}
                </h2>
            </div>

            <!-- Liquid / Investasi breakdown -->
            <div class="flex items-center gap-4 pt-3 border-t border-white/10">
                <!-- Liquid -->
                <div class="flex-1">
                    <div class="flex items-center gap-1.5 mb-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-400" aria-hidden="true" />
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ $t('portfolio.liquid') }}</p>
                    </div>
                    <p class="text-sm font-bold text-white tracking-tight" aria-live="polite">
                        <span class="text-2xs text-gray-500 mr-0.5">Rp</span>{{ displayShort(totalLiquid) }}
                    </p>
                </div>

                <!-- Divider -->
                <div class="w-px h-8 bg-gradient-to-b from-transparent via-white/10 to-transparent" aria-hidden="true" />

                <!-- Investasi -->
                <div class="flex-1">
                    <div class="flex items-center gap-1.5 mb-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-purple-400" aria-hidden="true" />
                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ $t('portfolio.investment') }}</p>
                    </div>
                    <p class="text-sm font-bold text-white tracking-tight" aria-live="polite">
                        <span class="text-2xs text-gray-500 mr-0.5">Rp</span>{{ displayShort(totalInvest) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
