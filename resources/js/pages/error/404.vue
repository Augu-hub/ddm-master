<template>
    <AuthLayout show-footer footer-class="mt-3">
        <div class="mx-auto text-center">
            <h3 class="fw-semibold mb-2">Ooop's !</h3>
            <img :src="err404" alt="error 403 img" height="180" class="my-3" />
            <h2 class="fw-bold text-primary lh-base mt-3">Page Not Found !</h2>
            <h4 class="fw-bold text-dark lh-base mt-2">Something's Missing...! This Page Is Not Available</h4>
            <p class="text-muted fs-12 mb-3">sorry, we can't find the page you're looking for We suggest you to go homepage</p>
            <Link href="/" class="btn btn-primary">Back To Home <i class="ti ti-home ms-1"></i></Link>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import err404 from '@/images/error/error-404.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
