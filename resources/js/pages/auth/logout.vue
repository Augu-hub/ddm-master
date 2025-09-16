<template>
    <AuthLayout show-footer>
        <Head title="Logout" />

        <h3 class="fw-semibold mb-4">You are Logged Out</h3>

        <div class="d-flex align-items-center mb-3 gap-2 text-start">
            <img :src="avatar1" alt="" class="avatar-xl img-thumbnail rounded" />
            <div>
                <h3 class="fw-semibold text-dark">Hi ! Dhanoo K.</h3>
                <p class="mb-0">Thank you for using Ocen Admin</p>
            </div>
        </div>

        <div class="mb-3 text-start">
            <div class="bg-success-subtle fw-medium mb-0 rounded p-2" role="alert">
                <p class="text-success mb-0">
                    You have been successfully logged out of your account. To continue using our services, please log in again with your credentials.
                    If you encounter any issues, feel free to contact our support team for assistance.
                </p>
            </div>
        </div>
        <div class="d-grid">
            <b-button variant="primary" type="submit">Support Center</b-button>
        </div>
        <p class="text-danger fs-14 my-3">
            Back to
            <Link :href="route('login')" class="text-dark fw-semibold ms-1">Login !</Link>
        </p>
    </AuthLayout>
</template>

<script setup lang="ts">
import avatar1 from '@/images/users/avatar-1.jpg';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
