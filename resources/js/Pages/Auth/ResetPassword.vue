<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import FormLabel from '@/Components/FormLabel.vue';
import Button from '@/Components/Button.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ email: String, token: String });
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });
const showPassword = ref(false);
const showConfirm = ref(false);
const submit = () => form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout title="Reset Password">
        <template #header>
            <h1 class="text-2xl font-bold tracking-tight text-[var(--color-text-primary)]">Reset Password</h1>
            <p class="text-xs font-medium text-[var(--color-text-secondary)] mt-1.5">Buat sandi baru Anda</p>
        </template>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <FormLabel for="reset-email" required>Email</FormLabel>
                <TextInput id="reset-email" v-model="form.email" type="email" :error="form.errors.email" required autocomplete="username">
                    <template #icon-left><AppIcon icon="mail" iconClass="w-4 h-4" /></template>
                </TextInput>
            </div>
            <div>
                <FormLabel for="reset-password" required>Password Baru</FormLabel>
                <TextInput id="reset-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" :error="form.errors.password" required autocomplete="new-password" autofocus>
                    <template #icon-left><AppIcon icon="lock" iconClass="w-4 h-4" /></template>
                    <template #icon-right>
                        <button type="button" @click="showPassword = !showPassword" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] p-1 -mr-1" tabindex="-1"><AppIcon :icon="showPassword ? 'eye-off' : 'eye'" iconClass="w-4 h-4" /></button>
                    </template>
                </TextInput>
            </div>
            <div>
                <FormLabel for="reset-confirm" required>Konfirmasi Password</FormLabel>
                <TextInput id="reset-confirm" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'" :error="form.errors.password_confirmation" required autocomplete="new-password">
                    <template #icon-left><AppIcon icon="lock" iconClass="w-4 h-4" /></template>
                    <template #icon-right>
                        <button type="button" @click="showConfirm = !showConfirm" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-secondary)] p-1 -mr-1" tabindex="-1"><AppIcon :icon="showConfirm ? 'eye-off' : 'eye'" iconClass="w-4 h-4" /></button>
                    </template>
                </TextInput>
            </div>
            <Button type="submit" variant="primary" size="lg" fullWidth :loading="form.processing" class="mt-2">Simpan Password</Button>
        </form>
    </GuestLayout>
</template>
