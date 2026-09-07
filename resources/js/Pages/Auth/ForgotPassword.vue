<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import FormLabel from '@/Components/FormLabel.vue';
import Button from '@/Components/Button.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps({ status: String });
const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout title="Lupa Password">
        <template #header>
            <h1 class="text-2xl font-bold tracking-tight text-[var(--color-text-primary)]">Lupa Password?</h1>
            <p class="text-xs font-medium text-[var(--color-text-secondary)] mt-1.5">Kami bantu meresetnya</p>
        </template>

        <p class="text-xs text-[var(--color-text-secondary)] text-center mb-6 leading-relaxed">
            Masukkan email Anda, kami akan mengirim tautan reset password.
        </p>

        <div v-if="status" role="alert" class="mb-5 p-3 rounded-xl bg-[var(--color-income-bg)] text-[var(--color-income-text)] border border-[var(--color-income-border)] text-xs font-medium text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <FormLabel for="forgot-email" required>Email</FormLabel>
                <TextInput id="forgot-email" v-model="form.email" type="email" placeholder="email@contoh.com" :error="form.errors.email" required autofocus autocomplete="email">
                    <template #icon-left><AppIcon icon="mail" iconClass="w-4 h-4" /></template>
                </TextInput>
            </div>
            <Button type="submit" variant="primary" size="lg" fullWidth :loading="form.processing" class="mt-2">Kirim Link Reset</Button>
        </form>

        <p class="text-center text-xs text-[var(--color-text-muted)] mt-6">
            Ingat password?
            <Link :href="route('login')" class="font-semibold text-[var(--color-brand)] hover:text-[var(--color-brand-hover)]">Masuk di sini</Link>
        </p>
    </GuestLayout>
</template>
