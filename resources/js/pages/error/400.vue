<template>
    <AuthLayout show-footer footer-class="mt-3">
        <div>
            <h3 class="fw-semibold mb-2">Ooops !</h3>
            <p class="text-muted">something exciting is coming your way soon</p>
        </div>

        <div class="mx-auto text-center">
            <img :src="err400" alt="error 400 img" height="230" />

            <h2 class="fw-bold text-primary lh-base mt-3">Bed Request !</h2>
            <h4 class="fw-bold text-dark lh-base mt-2">Your Browser Sent An Invalid Request</h4>
            <p class="text-muted fs-12 mb-3">
                The server could not understand the request due to invalid syntax. Please check your input and try again.
            </p>
            <Link href="/" class="btn btn-primary">Back To Home <i class="ti ti-home ms-1"></i></Link>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import err400 from '@/images/error/error-400.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
