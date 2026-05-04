<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="fixed bottom-24 left-1/2 -translate-x-1/2 w-full max-w-md pointer-events-none flex justify-end px-5 z-40">
        <div class="flex flex-col gap-3 items-end pointer-events-auto">
            
            <a href="https://t.me/catatwidi_bot" target="_blank" class="relative group w-12 h-12 bg-blue-500 rounded-xl flex justify-center items-center text-white active:scale-95 transition-all hover:-translate-y-1">
                <svg class="w-5 h-5 ml-[-2px] mt-[1px]" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.31-.346-.11l-6.4 4.02-2.76-.89c-.6-.188-.612-.6.126-.89l10.814-4.17c.5-.188.948.116.822.885z"/>
                </svg>
                <div class="absolute right-14 bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 text-gray-300 text-xs font-bold px-2.5 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                    Catat via Telegram
                </div>
            </a>

            <Link :href="route('transactions.create')" class="w-12 h-12 bg-gradient-to-br from-purple-800 to-purple-500 rounded-xl flex justify-center items-center text-white active:scale-95 transition-all hover:-translate-y-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </Link>
        </div>
    </div>
</template>
