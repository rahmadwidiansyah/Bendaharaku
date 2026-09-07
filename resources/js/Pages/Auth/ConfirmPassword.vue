<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import FormLabel from '@/Components/FormLabel.vue';
import Button from '@/Components/Button.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });
const showPassword = ref(false);
const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() });
</script>

<template>
    <GuestLayout title="Konfirmasi Password">
        <template #header>
            <h1 class="text-2xl font-bold tracking-tight text-[var(--color-text-primary)]">Area Aman</h1>
            <p class="text-xs font-medium text-[var(--color-text-secondary)] mt-1.5">Verifikasi identitas Anda</p>
        </template>

        <p class="text-xs text-[var(--color-text-secondary)] text-center mb-6 leading-relaxed">
            Area aman memerlukan konfirmasi password sebelum melanjutkan.
        </p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <FormLabel for="confirm-password" required>Password Anda</FormLabel>
                <TextInput id="confirm-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" :error="form.errors.password" required autocomplete="current-password" autofocus>
                    <template #icon-left><AppIcon icon="lock" iconClass="w-4 h-4" /></template>
                    <template #icon-right>
                        <button type="button" @click="showPassword = !showPassword" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] p-1 -mr-1" tabindex="-1"><AppIcon :icon="showPassword ? 'eye-off' : 'eye'" iconClass="w-4 h-4" /></button>
                    </template>
                </TextInput>
            </div>
            <Button type="submit" variant="primary" size="lg" fullWidth :loading="form.processing" class="mt-2">Konfirmasi Akses</Button>
        </form>
    </GuestLayout>
</template>
