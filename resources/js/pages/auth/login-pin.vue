<template>
    <AuthLayout show-footer>
        <Head title="Login With Pin" />

        <h3 class="fw-semibold mb-2">Login With Pin</h3>

        <p class="text-muted mb-4">
            We sent you a code , please enter it below to verify your number <span class="text-primary fw-medium">+ (94) 883-630-646</span>
        </p>

        <div class="d-flex justify-content-center mb-3 gap-2">
            <a href="#!" class="btn btn-soft-danger avatar-lg"><i class="ti ti-brand-google-filled fs-24"></i></a>
            <a href="#!" class="btn btn-soft-success avatar-lg"><i class="ti ti-brand-apple fs-24"></i></a>
            <a href="#!" class="btn btn-soft-primary avatar-lg"><i class="ti ti-brand-facebook fs-24"></i></a>
            <a href="#!" class="btn btn-soft-info avatar-lg"><i class="ti ti-brand-linkedin fs-24"></i></a>
        </div>

        <p class="fs-13 fw-semibold">Or</p>

        <form class="mb-3 text-start">
            <label class="form-label" for="code">Enter 6 Digit Code</label>
            <div class="d-flex mb-3 mt-1 gap-2">
                <input type="text" maxlength="1" class="form-control text-center" />
                <input type="text" maxlength="1" class="form-control text-center" />
                <input type="text" maxlength="1" class="form-control text-center" />
                <input type="text" maxlength="1" class="form-control text-center" />
                <input type="text" maxlength="1" class="form-control text-center" />
                <input type="text" maxlength="1" class="form-control text-center" />
            </div>
            <div class="d-grid mb-3">
                <b-button variant="primary" type="submit">Continue</b-button>
            </div>
            <p class="mb-0 text-center">
                Don't received code yet? <a href="#!" class="link-primary fw-semibold text-decoration-underline">Send Again</a>
            </p>
        </form>

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
