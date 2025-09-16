<template>
    <AuthLayout show-footer>
        <Head title="2FA" />
        <h3 class="fw-semibold mb-2">Set up your two-factor authentication.</h3>

        <div class="bg-body-secondary my-3 rounded border border-dashed p-3 text-center">
            <img :src="qrCode" alt="" class="" height="130" />
            <p class="mb-0 mt-3">Scan this code with your Google authenticator app to continue this process.</p>
        </div>

        <b-form class="mb-3 text-start">
            <label class="form-label" for="code">Verification Code</label>
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
        </b-form>

        <p class="text-danger fs-14 mb-4">
            Back To
            <Link href="/" class="fw-semibold text-dark ms-1">Home !</Link>
        </p>
    </AuthLayout>
</template>

<script setup lang="ts">
import qrCode from '@/images/png/qr-code.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
