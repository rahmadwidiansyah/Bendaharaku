<script setup>
/**
 * Material/Pages/More.vue — hub destinasi "Lainnya".
 *
 * Menampung halaman-halaman yang tidak mendapat slot di navigation bar
 * mobile (label, tanggungan, chat, pencarian, pengaturan). Semua tujuan
 * memakai route existing; halaman ini statis (tanpa fetch data).
 * Header kanan memuat avatar akun (mobile) yang mengarah ke halaman profil.
 */
import M3Layout from '@/Material/Layouts/AuthenticatedLayout.vue'
import M3PageTitle from '@/Material/Components/M3PageTitle.vue'
import M3Card from '@/Material/Components/M3Card.vue'
import M3ListItem from '@/Material/Components/M3ListItem.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const avatarUrl = computed(() => page.props.auth?.user?.avatar_url ?? '')
const avatarInitial = computed(() => (page.props.auth?.user?.name || 'B').charAt(0).toUpperCase())
</script>

<template>
    <M3Layout :title="$t('nav.more')">
        <Head :title="$t('nav.more')" />

        <div class="m3-more">
            <!-- Judul halaman in-content (mobile; desktop memakai app bar) -->
            <M3PageTitle :title="$t('nav.more')">
                <template #actions>
                    <Link
                        :href="route('settings.account.profile')"
                        class="m3-more__avatar"
                        :aria-label="$t('nav.profile')"
                    >
                        <img v-if="avatarUrl" :src="avatarUrl" alt="" class="m3-more__avatar-img" />
                        <span v-else aria-hidden="true">{{ avatarInitial }}</span>
                    </Link>
                </template>
            </M3PageTitle>

            <section class="m3-more__section">
                <h2 class="m3-more__section-title">{{ $t('more.finance') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-more__card">
                    <M3ListItem :href="route('categories.index')" divider>
                        <template #icon>
                            <span class="m3-more__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('nav.label') }}
                        <template #supporting>{{ $t('more.categoriesDesc') }}</template>
                        <template #trailing>
                            <svg class="m3-more__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </template>
                    </M3ListItem>

                    <M3ListItem :href="route('loans.index', { type: 'debt' })">
                        <template #icon>
                            <span class="m3-more__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('nav.loan') }}
                        <template #supporting>{{ $t('more.loansDesc') }}</template>
                        <template #trailing>
                            <svg class="m3-more__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>

            <section class="m3-more__section">
                <h2 class="m3-more__section-title">{{ $t('more.app') }}</h2>
                <M3Card variant="outlined" padding="none" class="m3-more__card">
                    <M3ListItem :href="route('chat.index')" divider>
                        <template #icon>
                            <span class="m3-more__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('nav.chat') }}
                        <template #supporting>{{ $t('nav.chatDesc') }}</template>
                        <template #trailing>
                            <svg class="m3-more__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </template>
                    </M3ListItem>

                    <M3ListItem :href="route('search.page')" divider>
                        <template #icon>
                            <span class="m3-more__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('nav.search') }}
                        <template #supporting>{{ $t('more.searchDesc') }}</template>
                        <template #trailing>
                            <svg class="m3-more__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </template>
                    </M3ListItem>

                    <M3ListItem :href="route('settings.index')">
                        <template #icon>
                            <span class="m3-more__ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                        </template>
                        {{ $t('nav.settings') }}
                        <template #supporting>{{ $t('nav.settingsDesc') }}</template>
                        <template #trailing>
                            <svg class="m3-more__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </template>
                    </M3ListItem>
                </M3Card>
            </section>
        </div>
    </M3Layout>
</template>

<style scoped>
.m3-more {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-6);
    width: 100%;
    max-width: var(--m3-size-list-max);
    margin: 0 auto;
    padding: var(--m3-space-4);
}

.m3-more__section {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-2);
}

.m3-more__section-title {
    margin: 0;
    padding-left: var(--m3-space-4);
    font-size: var(--m3-type-title-small-size);
    line-height: var(--m3-type-title-small-line-height);
    font-weight: var(--m3-type-emphasized-title-small-weight);
    letter-spacing: var(--m3-type-title-small-tracking);
    color: var(--m3-color-on-surface-variant);
}

.m3-more__card {
    overflow: hidden;
}

.m3-more__ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    background: var(--m3-color-secondary-container);
    color: var(--m3-color-on-secondary-container);
}

.m3-more__ico svg {
    width: var(--m3-size-icon-xs);
    height: var(--m3-size-icon-xs);
}

.m3-more__chevron {
    width: var(--m3-size-icon-3xs);
    height: var(--m3-size-icon-3xs);
    color: var(--m3-color-on-surface-variant);
}

/* Avatar akun di header — geometri & warna mengikuti avatar app bar desktop */
.m3-more__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--m3-size-touch-target);
    height: var(--m3-size-touch-target);
    border-radius: var(--m3-radius-full);
    overflow: hidden;
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    font-size: var(--m3-type-label-large-size);
    font-weight: var(--m3-type-emphasized-title-medium-weight);
    text-decoration: none;
    flex-shrink: 0;
}

.m3-more__avatar:focus-visible {
    outline: 2px solid var(--m3-color-primary);
    outline-offset: 2px;
}

.m3-more__avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
