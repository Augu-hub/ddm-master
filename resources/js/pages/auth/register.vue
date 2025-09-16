<template>
    <AuthLayout show-footer>
        <Head title="Register" />

        <h3 class="fw-semibold mb-2">Welcome to Osen Admin</h3>

        <p class="text-muted mb-4">Enter your name , email address and password to access account.</p>

        <div class="d-flex justify-content-center mb-3 gap-2">
            <a href="#!" class="btn btn-soft-danger avatar-lg"><i class="ti ti-brand-google-filled fs-24"></i></a>
            <a href="#!" class="btn btn-soft-success avatar-lg"><i class="ti ti-brand-apple fs-24"></i></a>
            <a href="#!" class="btn btn-soft-primary avatar-lg"><i class="ti ti-brand-facebook fs-24"></i></a>
            <a href="#!" class="btn btn-soft-info avatar-lg"><i class="ti ti-brand-linkedin fs-24"></i></a>
        </div>

        <p class="fs-13 fw-semibold">Or Sign Up With Email</p>

        <b-form @submit.prevent="submit" class="mb-3 text-start">
            <b-form-group label="Your Name" class="mb-3">
                <b-form-input v-model="form.name" type="text" placeholder="Enter your name" />
                <p v-if="form.errors.name" class="text-danger">
                    {{ form.errors.name }}
                </p>
            </b-form-group>

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

            <div class="d-flex justify-content-between mb-3">
                <b-form-checkbox>I agree to all Terms & Condition</b-form-checkbox>
            </div>

            <div class="d-grid">
                <b-button variant="primary" type="submit" :disabled="form.processing">Sign Up</b-button>
            </div>
        </b-form>

        <p class="text-danger fs-14 mb-4">
            Already have an account?
            <Link :href="route('login')" class="fw-semibold text-dark ms-1">Login !</Link>
        </p>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';

import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
