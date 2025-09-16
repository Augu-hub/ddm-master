<template>
    <AuthLayout show-footer>
        <Head title="Confirm Mail" />

        <h3 class="fw-semibold mb-2">Check Your Email Address</h3>

        <p class="text-muted mb-4">Please enter the 6-digit code sent to abc@xyz.com to proceed</p>

        <div class="d-flex justify-content-center mb-3 gap-2">
            <a href="#!" class="btn btn-soft-danger avatar-lg"><i class="ti ti-brand-google-filled fs-24"></i></a>
            <a href="#!" class="btn btn-soft-success avatar-lg"><i class="ti ti-brand-apple fs-24"></i></a>
            <a href="#!" class="btn btn-soft-primary avatar-lg"><i class="ti ti-brand-facebook fs-24"></i></a>
            <a href="#!" class="btn btn-soft-info avatar-lg"><i class="ti ti-brand-linkedin fs-24"></i></a>
        </div>

        <p class="fs-13 fw-semibold">Or Login With Email</p>

        <b-form class="mb-3 text-start">
            <b-form-group lable="Enter 6 Digit Code" class="mb-3">
                <b-form-input type="number" id="email-code" placeholder="CODE" />
            </b-form-group>

            <div class="d-grid mb-3">
                <b-button variant="primary" type="submit">Continue</b-button>
            </div>
            <p class="mb-0 text-center">
                Don't received code yet? <a href="#!" class="link-primary fw-semibold text-decoration-underline">Send Again</a>
            </p>
        </b-form>

        <p class="text-danger fs-14 mb-4">
            Back To
            <Link href="/" class="fw-semibold text-dark ms-1">Home !</Link>
        </p>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
