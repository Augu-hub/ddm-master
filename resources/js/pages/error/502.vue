<template>
    <AuthLayout show-footer footer-class="mt-3">
        <div class="mx-auto text-center">
            <h3 class="fw-semibold mb-2">Ooop's !</h3>
            <img :src="err502" alt="error 502 img" height="280" />
            <h3 class="fw-bold text-primary lh-base mt-3">Service Overloaded !</h3>
            <h5 class="fw-bold text-dark lh-base mt-2">Server Error. Please Refresh Or Try Again Later.</h5>
            <p class="text-muted fs-12 mb-3">The server received an invalid response from the upstream server. Please try again later.</p>
            <Link href="/" class="btn btn-primary">Back To Home <i class="ti ti-home ms-1"></i></Link>
        </div>
        <a target=""></a>
    </AuthLayout>
</template>

<script setup lang="ts">
import err502 from '@/images/error/error-502.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
