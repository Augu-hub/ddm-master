<template>
    <AuthLayout show-footer>
        <Head title="Reset password" />

        <h3 class="fw-semibold mb-2">Create New Password</h3>

        <p class="text-muted mb-2">Please create your new password.</p>
        <p class="mb-4">Need password suggestion ? <a href="#!" class="link-dark fw-semibold text-decoration-underline">Suggestion</a></p>

        <div class="d-flex justify-content-center mb-3 gap-2">
            <a href="#!" class="btn btn-soft-danger avatar-lg"><i class="ti ti-brand-google-filled fs-24"></i></a>
            <a href="#!" class="btn btn-soft-success avatar-lg"><i class="ti ti-brand-apple fs-24"></i></a>
            <a href="#!" class="btn btn-soft-primary avatar-lg"><i class="ti ti-brand-facebook fs-24"></i></a>
            <a href="#!" class="btn btn-soft-info avatar-lg"><i class="ti ti-brand-linkedin fs-24"></i></a>
        </div>

        <p class="fs-13 fw-semibold">Or</p>

        <b-form @submit.prevent="submit" class="mb-3 text-start">
            <b-form-group label="Email" class="mb-3">
                <b-form-input v-model="form.email" type="email" placeholder="Enter your email" />
                <p v-if="form.errors.email" class="text-danger">
                    {{ form.errors.email }}
                </p>
            </b-form-group>

            <b-form-group label="Password" class="mb-3">
                <b-form-input v-model="form.password" type="password" placeholder="Enter your password" />
                <p v-if="form.errors.password" class="text-danger">
                    {{ form.errors.password }}
                </p>
            </b-form-group>

            <b-form-group label="Confirm password" class="mb-3">
                <b-form-input v-model="form.password_confirmation" type="password" placeholder="Confirm your password" />
                <p v-if="form.errors.password_confirmation" class="text-danger">
                    {{ form.errors.password_confirmation }}
                </p>
            </b-form-group>

            <div class="d-grid mb-2">
                <b-button variant="primary" type="submit" :disabled="form.processing">Create New Password</b-button>
            </div>
        </b-form>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';

import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>
