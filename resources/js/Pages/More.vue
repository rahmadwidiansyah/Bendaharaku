<script setup>
/**
 * More.vue — hub destinasi "Lainnya" (tema Default).
 *
 * Menampung halaman-halaman yang tidak mendapat slot di navigation bar
 * mobile (kategori, tanggungan, chat, pencarian, pengaturan). Semua tujuan
 * memakai route existing; halaman ini statis (tanpa fetch data).
 * Konten paritas dengan Material/Pages/More.vue.
 */

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const sections = computed(() => [
    {
        id: 'finance',
        title: t('more.finance'),
        items: [
            {
                id: 'categories',
                href: route('categories.index'),
                label: t('nav.label'),
                description: t('more.categoriesDesc'),
                iconPaths: [
                    'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z',
                    'M6 6h.008v.008H6V6z',
                ],
                accent: 'brand',
            },
            {
                id: 'loans',
                href: route('loans.index', { type: 'debt' }),
                label: t('nav.loan'),
                description: t('more.loansDesc'),
                iconPaths: [
                    'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75',
                ],
                accent: 'debt',
            },
        ],
    },
    {
        id: 'app',
        title: t('more.app'),
        items: [
            {
                id: 'chat',
                href: route('chat.index'),
                label: t('nav.chat'),
                description: t('nav.chatDesc'),
                iconPaths: [
                    'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z',
                ],
                accent: 'violet',
            },
            {
                id: 'search',
                href: route('search.page'),
                label: t('nav.search'),
                description: t('more.searchDesc'),
                iconPaths: [
                    'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
                ],
                accent: 'sky',
            },
            {
                id: 'settings',
                href: route('settings.index'),
                label: t('nav.settings'),
                description: t('nav.settingsDesc'),
                iconPaths: [
                    'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z',
                    'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                ],
                accent: 'neutral',
            },
        ],
    },
])

const accentClasses = {
    brand: {
        badge: 'border-[var(--color-brand)]/20 bg-[var(--color-brand)]/10',
        icon: 'text-[var(--color-brand)]',
    },
    debt: {
        badge: 'border-[var(--color-debt-border)] bg-[var(--color-debt-bg)]',
        icon: 'text-[var(--color-debt-text)]',
    },
    violet: {
        badge: 'border-violet-500/20 bg-violet-500/10',
        icon: 'text-violet-400',
    },
    sky: {
        badge: 'border-sky-500/20 bg-sky-500/10',
        icon: 'text-sky-400',
    },
    neutral: {
        badge: 'border-[var(--color-border-default)] bg-[var(--color-surface-muted)]',
        icon: 'text-[var(--color-text-secondary)]',
    },
}
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head :title="$t('nav.more')" />

        <div class="px-3 sm:px-5 pb-40 w-full lg:max-w-3xl mx-auto lg:px-8 relative z-10">
            <!-- Judul in-content: GlobalHeader hanya tampil < lg -->
            <header class="hidden lg:block pt-3 lg:pt-4 mb-6 lg:mb-8 px-1 animate-fade-in-up">
                <p class="text-2xs text-[var(--color-brand)] font-black uppercase tracking-[0.3em] mb-1 opacity-80">Bendaharaku</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-[var(--color-text-primary)] tracking-tighter leading-tight">{{ $t('nav.more') }}</h1>
            </header>

            <div class="pt-3 lg:pt-0 space-y-6 sm:space-y-8 animate-page-enter">
                <section v-for="section in sections" :key="section.id">
                    <h2 class="px-1 mb-2 text-2xs text-[var(--color-brand)] font-black uppercase tracking-[0.3em] opacity-80">
                        {{ section.title }}
                    </h2>

                    <div class="bg-[var(--color-surface-raised)] border border-[var(--color-border-subtle)] rounded-xl sm:rounded-2xl p-2 sm:p-3 space-y-1 shadow-card">
                        <Link
                            v-for="item in section.items"
                            :key="item.id"
                            :href="item.href"
                            class="group flex items-center gap-3 px-2.5 sm:px-3 py-2.5 sm:py-3 rounded-lg border border-transparent hover:bg-[var(--color-surface-overlay)] active:scale-[0.99] transition-all duration-150"
                        >
                            <span :class="['w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center shrink-0 border', accentClasses[item.accent].badge]">
                                <svg :class="['w-3.5 h-3.5 sm:w-4.5 sm:h-4.5', accentClasses[item.accent].icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path v-for="(path, index) in item.iconPaths" :key="index" stroke-linecap="round" stroke-linejoin="round" :d="path" />
                                </svg>
                            </span>

                            <span class="flex-1 min-w-0">
                                <span class="block text-xs sm:text-sm font-semibold text-[var(--color-text-primary)] leading-tight">{{ item.label }}</span>
                                <span class="block text-2xs text-[var(--color-text-muted)] mt-0.5 truncate">{{ item.description }}</span>
                            </span>

                            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-[var(--color-text-muted)] group-hover:text-[var(--color-text-secondary)] shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
