<script setup>
import { ref, computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import FormLabel from '@/Components/FormLabel.vue';
import Button from '@/Components/Button.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);

const passwordMismatch = computed(() => form.password_confirmation && form.password !== form.password_confirmation);
const passwordMatch = computed(() => form.password_confirmation && form.password === form.password_confirmation && form.password.length >= 8);

const submit = () => {
    if (form.password && form.password_confirmation && form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Password tidak cocok. Pastikan keduanya sama.');
        return;
    }
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout title="Daftar">
        <template #header>
            <h1 class="text-2xl font-bold tracking-tight text-[var(--color-text-primary)]">Buat Akun</h1>
            <p class="text-xs font-medium text-[var(--color-text-secondary)] mt-1.5">Mulai kelola keuanganmu</p>
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <FormLabel for="reg-name" required>Nama Lengkap</FormLabel>
                <TextInput id="reg-name" v-model="form.name" type="text" placeholder="Nama lengkap" :error="form.errors.name" required autofocus autocomplete="name">
                    <template #icon-left><AppIcon icon="user" iconClass="w-4 h-4" /></template>
                </TextInput>
            </div>
            <div>
                <FormLabel for="reg-email" required>Email</FormLabel>
                <TextInput id="reg-email" v-model="form.email" type="email" placeholder="email@contoh.com" :error="form.errors.email" required autocomplete="email">
                    <template #icon-left><AppIcon icon="mail" iconClass="w-4 h-4" /></template>
                </TextInput>
            </div>
            <div>
                <FormLabel for="reg-password" required>Password</FormLabel>
                <TextInput id="reg-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Minimal 8 karakter" :error="form.errors.password" required autocomplete="new-password" hint="Minimal 8 karakter">
                    <template #icon-left><AppIcon icon="lock" iconClass="w-4 h-4" /></template>
                    <template #icon-right>
                        <button type="button" @click="showPassword = !showPassword" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] p-1 -mr-1" :aria-label="showPassword ? 'Sembunyikan' : 'Tampilkan'" tabindex="-1">
                            <AppIcon :icon="showPassword ? 'eye-off' : 'eye'" iconClass="w-4 h-4" />
                        </button>
                    </template>
                </TextInput>
            </div>
            <div>
                <FormLabel for="reg-password-confirm" required>Konfirmasi Password</FormLabel>
                <TextInput id="reg-password-confirm" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'" placeholder="Ulangi password" :error="form.errors.password_confirmation" required autocomplete="new-password">
                    <template #icon-left><AppIcon icon="lock" iconClass="w-4 h-4" /></template>
                    <template #icon-right>
                        <button type="button" @click="showConfirm = !showConfirm" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] p-1 -mr-1" :aria-label="showConfirm ? 'Sembunyikan' : 'Tampilkan'" tabindex="-1">
                            <AppIcon :icon="showConfirm ? 'eye-off' : 'eye'" iconClass="w-4 h-4" />
                        </button>
                    </template>
                </TextInput>
                <p v-if="passwordMismatch && !form.errors.password_confirmation" class="text-2xs text-[var(--color-expense-text)] mt-1 ml-1 font-medium">Password belum cocok</p>
                <p v-else-if="passwordMatch" class="text-2xs text-[var(--color-income-text)] mt-1 ml-1 font-medium">Password cocok</p>
            </div>

            <Button type="submit" variant="primary" size="lg" fullWidth :loading="form.processing" class="mt-2">
                {{ form.processing ? 'Mendaftarkan...' : 'Daftar Sekarang' }}
            </Button>
        </form>

        <div class="relative flex items-center justify-center my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[var(--color-border-default)]"></div></div>
            <span class="relative bg-[var(--color-surface-base)] px-4 text-2xs font-bold uppercase tracking-widest text-[var(--color-text-muted)]">Atau</span>
        </div>

        <a :href="route('google.login')" class="w-full flex items-center justify-center gap-3 bg-[var(--color-surface-raised)] text-[var(--color-text-primary)] border border-[var(--color-border-default)] font-bold text-xs uppercase tracking-widest py-3.5 rounded-xl hover:border-[var(--color-brand-border)] active:scale-[0.98] transition-all">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Daftar dengan Google
        </a>

        <p class="text-center text-xs text-[var(--color-text-muted)] mt-6">
            Sudah punya akun?
            <Link :href="route('login')" class="font-semibold text-[var(--color-brand)] hover:text-[var(--color-brand-hover)]">Masuk di sini</Link>
        </p>
    </GuestLayout>
</template>
