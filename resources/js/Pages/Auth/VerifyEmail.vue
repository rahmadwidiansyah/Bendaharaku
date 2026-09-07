<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/Button.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ status: String });
const form = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
const logout = () => useForm().post(route('logout'));
</script>

<template>
    <GuestLayout title="Verifikasi Email">
        <template #header>
            <h1 class="text-2xl font-bold tracking-tight text-[var(--color-text-primary)]">Cek Email Anda</h1>
            <p class="text-xs font-medium text-[var(--color-text-secondary)] mt-1.5">Verifikasi untuk melanjutkan</p>
        </template>

        <p class="text-xs text-[var(--color-text-secondary)] text-center mb-6 leading-relaxed">
            Terima kasih telah mendaftar! Silakan verifikasi email Anda melalui tautan yang kami kirim. Jika belum menerima, kami bisa kirim ulang.
        </p>

        <div v-if="verificationLinkSent" role="alert" class="mb-5 p-3 rounded-xl bg-[var(--color-income-bg)] text-[var(--color-income-text)] border border-[var(--color-income-border)] text-xs font-medium text-center">
            Link verifikasi baru telah dikirim ke email Anda.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <Button type="submit" variant="primary" size="lg" fullWidth :loading="form.processing">
                <template #icon-left><AppIcon icon="mail" iconClass="w-4 h-4" /></template>
                Kirim Ulang Email
            </Button>
        </form>

        <button @click="logout" class="w-full mt-3 text-xs font-semibold text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] uppercase tracking-widest py-3 transition-colors">
            Keluar Akun
        </button>
    </GuestLayout>
</template>
