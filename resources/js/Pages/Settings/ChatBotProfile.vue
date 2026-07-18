<script setup>
/**
 * Pages/Settings/ChatBotProfile.vue
 *
 * Halaman pengaturan Bot Profile per-user.
 * - Nama bot (text input, default Ken-Chan)
 * - Foto bot (upload + crop via ImageCropModal)
 * - Hapus foto (reset ke placeholder)
 */

import { ref, computed }  from 'vue'
import { Head, useForm }  from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ImageCropModal      from '@/Components/ImageCropModal.vue'
import Button              from '@/Components/Button.vue'

const props = defineProps({
    botName:   { type: String, default: 'Ken-Chan' },
    botAvatar: { type: String, default: null },
})

// ── Form state ────────────────────────────────────────────────────
const form = useForm({
    bot_name:   props.botName ?? 'Ken-Chan',
    bot_avatar: null,   // File object dari crop result
})

// ── Avatar preview state ──────────────────────────────────────────
const showCropper     = ref(false)
const avatarPreview   = ref(props.botAvatar ?? null)
const pendingBlob     = ref(null)

// Initials fallback
const initials = computed(() =>
    (form.bot_name || 'K').trim().split(/\s+/).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
)

// ── Crop result handler ───────────────────────────────────────────
function onCropped(blob) {
    pendingBlob.value   = blob
    avatarPreview.value = URL.createObjectURL(blob)
    // Convert blob → File untuk form submission
    form.bot_avatar = new File([blob], 'bot-avatar.webp', { type: 'image/webp' })
}

// ── Remove avatar ─────────────────────────────────────────────────
function removeAvatar() {
    avatarPreview.value = null
    pendingBlob.value   = null
    form.bot_avatar     = null
    // POST DELETE ke server
    form.delete(route('settings.chat.bot-avatar.destroy'), {
        preserveScroll: true,
    })
}

// ── Submit ────────────────────────────────────────────────────────
function submit() {
    form.patch(route('settings.chat.bot-profile.update'), {
        preserveScroll: true,
        forceFormData: true,    // Wajib untuk file upload (method spoofing otomatis)
    })
}

// ── Success flash ─────────────────────────────────────────────────
const justSaved = computed(() => form.wasSuccessful)
</script>

<template>
    <AuthenticatedLayout :fullWidth="true">
        <Head title="Bot Profile" />

        <div class="p-5 w-full lg:max-w-2xl mx-auto lg:px-8 animate-slide-up min-h-screen">

            <!-- Page header -->
            <header class="pt-4 mb-8">
                <p class="text-2xs text-purple-500 font-black mb-1.5 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                    Pengaturan AI
                </p>
                <h1 class="text-2xl font-black text-white tracking-tight">Bot Profile</h1>
                <p class="text-sm text-gray-500 mt-1">Personalisasi nama dan foto bot AI kamu.</p>
            </header>

            <form @submit.prevent="submit" class="space-y-8">

                <!-- ── Avatar section ──────────────────────────────────── -->
                <section class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-5">Foto Bot</h2>

                    <div class="flex items-center gap-6">
                        <!-- Preview avatar -->
                        <div class="relative shrink-0">
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-800 border-2 border-white/10 flex items-center justify-center">
                                <img
                                    v-if="avatarPreview"
                                    :src="avatarPreview"
                                    alt="Bot avatar"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-2xl font-black text-purple-400 select-none">
                                    {{ initials }}
                                </span>
                            </div>
                            <!-- Remove button overlay -->
                            <button
                                v-if="avatarPreview"
                                type="button"
                                @click="removeAvatar"
                                class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-400 transition-colors shadow-lg"
                                aria-label="Hapus foto"
                            >
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Upload actions -->
                        <div class="flex-1">
                            <button
                                type="button"
                                @click="showCropper = true"
                                class="flex items-center gap-2 px-4 py-2.5 bg-purple-500/10 text-purple-400 border border-purple-500/25 text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-purple-500/20 hover:border-purple-500/40 transition-all"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                {{ avatarPreview ? 'Ganti Foto' : 'Upload Foto' }}
                            </button>
                            <p class="text-2xs text-gray-600 mt-2">JPG, PNG, WebP — Maks. 2MB</p>
                        </div>
                    </div>
                </section>

                <!-- ── Bot Name section ────────────────────────────────── -->
                <section class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-6">
                    <h2 class="text-sm font-bold text-white mb-1">Nama Bot</h2>
                    <p class="text-2xs text-gray-500 mb-5">Nama yang tampil di header Chat.</p>

                    <div class="space-y-2">
                        <input
                            v-model="form.bot_name"
                            type="text"
                            maxlength="50"
                            placeholder="Ken-Chan"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-600 outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/30 transition-all"
                        />
                        <p class="text-2xs text-gray-600">
                            Kosongkan untuk menggunakan nama default: Ken-Chan
                        </p>
                        <p v-if="form.errors.bot_name" class="text-2xs text-red-400">
                            {{ form.errors.bot_name }}
                        </p>
                    </div>

                    <!-- Name suggestions -->
                    <div class="flex flex-wrap gap-2 mt-4">
                        <button
                            v-for="name in ['Ken-Chan', 'KeuanganKu', 'Assistant', 'Bendahara', 'FinBot']"
                            :key="name"
                            type="button"
                            @click="form.bot_name = name"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-2xs font-bold uppercase tracking-widest transition-all border',
                                form.bot_name === name
                                    ? 'bg-purple-500/20 text-purple-400 border-purple-500/30'
                                    : 'bg-gray-800 text-gray-500 border-white/5 hover:border-purple-500/30 hover:text-gray-300'
                            ]"
                        >
                            {{ name }}
                        </button>
                    </div>
                </section>

                <!-- ── Future placeholders ─────────────────────────────── -->
                <section class="bg-gradient-to-br from-gray-900 to-gray-800 border border-white/10 rounded-2xl p-6 opacity-50">
                    <div class="flex items-center gap-3 mb-4">
                        <h2 class="text-sm font-bold text-gray-400">Fitur Mendatang</h2>
                        <span class="px-2 py-0.5 rounded-full bg-gray-700 text-2xs font-bold text-gray-500 uppercase tracking-widest">Soon</span>
                    </div>
                    <div class="space-y-3">
                        <div v-for="item in ['Personality / Gaya Bicara', 'Bahasa Chat', 'AI Provider', 'Conversation Memory']"
                            :key="item"
                            class="flex items-center gap-3 py-2.5 border-b border-white/5 last:border-0">
                            <div class="w-2 h-2 rounded-full bg-gray-700 shrink-0"></div>
                            <p class="text-sm text-gray-600">{{ item }}</p>
                        </div>
                    </div>
                </section>

                <!-- ── Submit ──────────────────────────────────────────── -->
                <div class="flex items-center gap-3 pb-6">
                    <Button
                        type="submit"
                        :loading="form.processing"
                        :disabled="form.processing"
                        fullWidth
                    >
                        Simpan Perubahan
                    </Button>
                </div>

                <!-- Success flash -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="justSaved"
                        class="flex items-center gap-2 text-emerald-400 text-sm font-semibold"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Perubahan disimpan
                    </div>
                </Transition>

            </form>
        </div>

        <!-- Image Crop Modal -->
        <ImageCropModal
            v-model="showCropper"
            title="Atur Foto Bot"
            :aspectRatio="1"
            :circle="true"
            @cropped="onCropped"
        />
    </AuthenticatedLayout>
</template>
