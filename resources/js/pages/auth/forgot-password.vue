<template>
    <AuthLayout show-footer>
        <Head title="Forgot password" />

        <h3 class="fw-semibold mb-2">Reset Your Password</h3>

        <p class="text-muted mb-3">Please enter your email address to request a password reset.</p>

        <p v-if="status" class="text-success mb-3">
            {{ status }}
        </p>

        <b-form @submit.prevent="submit" class="mb-3 text-start">
            <b-form-group label="Email" class="mb-3">
                <b-form-input v-model="form.email" type="email" placeholder="Enter your email" />
                <p v-if="form.errors.email" class="text-danger">
                    {{ form.errors.email }}
                </p>
            </b-form-group>
            <div class="d-grid">
                <b-button variant="primary" type="submit" :disabled="form.processing">Reset Password</b-button>
            </div>
        </b-form>

        <p class="text-danger fs-14 mb-4">
            Back To
            <Link :href="route('login')" class="fw-semibold text-dark ms-1">Login !</Link>
        </p>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>
