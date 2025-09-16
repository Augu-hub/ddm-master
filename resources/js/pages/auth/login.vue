<template>
    <AuthLayout show-footer>
        <Head title="Log in" />

        <h3 class="fw-semibold mb-2">Login your account</h3>

        <p class="text-muted mb-4">Enter your email address and password to access admin panel.</p>

        <div class="d-flex justify-content-center mb-3 gap-2">
            <a href="#!" class="btn btn-soft-danger avatar-lg"><i class="ti ti-brand-google-filled fs-24"></i></a>
            <a href="#!" class="btn btn-soft-success avatar-lg"><i class="ti ti-brand-apple fs-24"></i></a>
            <a href="#!" class="btn btn-soft-primary avatar-lg"><i class="ti ti-brand-facebook fs-24"></i></a>
            <a href="#!" class="btn btn-soft-info avatar-lg"><i class="ti ti-brand-linkedin fs-24"></i></a>
        </div>

        <p class="fs-13 fw-semibold">Or Login With Email</p>

        <p v-if="status" class="text-success mb-3">
            {{ status }}
        </p>

        <b-form @submit.prevent="submit" class="mb-3 text-start">
            <div v-if="error.length > 0" class="text-danger mb-2">{{ error }}</div>

            <b-form-group label="Email" class="mb-3">
                <b-form-input type="email" id="example-email" name="email" v-model="form.email" placeholder="Enter your email" />
                <p v-if="form.errors.email" class="text-danger">
                    {{ form.errors.email }}
                </p>
            </b-form-group>

            <b-form-group label="Password" class="mb-3">
                <b-form-input type="password" id="example-password" name="password" v-model="form.password" placeholder="Enter your password" />
                <p v-if="form.errors.password" class="text-danger">
                    {{ form.errors.password }}
                </p>
            </b-form-group>

            <div class="d-flex justify-content-between mb-3">
                <b-form-checkbox v-model="form.remember">Remember me</b-form-checkbox>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-muted border-bottom border-dashed">
                    Forgot Password?
                </Link>
            </div>

            <div class="d-grid">
                <b-button variant="primary" type="submit" :disabled="form.processing">Login</b-button>
            </div>
        </b-form>

        <p class="text-danger fs-14 mb-4">
            Don't have an account?
            <Link :href="route('register')" class="fw-semibold text-dark ms-1">Sign Up !</Link>
        </p>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const error = ref('');

const form = useForm({
    email: 'demo@user.com',
    password: 'password',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>
