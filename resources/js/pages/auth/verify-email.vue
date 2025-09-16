<template>
    <AuthLayout show-footer>
        <Head title="Email verification" />

        <h3 class="fw-semibold mb-3">Verify your email</h3>

        <p v-if="status === 'verification-link-sent'" class="text-success mb-3">
            A new verification link has been sent to the email address you provided during registration.
        </p>

        <b-form @submit.prevent="submit" class="mb-3 text-start">
            <div class="d-grid">
                <b-button variant="primary" type="submit" :disabled="form.processing">Resend verification email </b-button>
            </div>
        </b-form>

        <Link :href="route('logout')" method="post" class="btn btn-danger fw-semibold mb-3 ms-1">Logout</Link>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>
