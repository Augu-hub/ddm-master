<template>
    <AuthLayout show-footer footer-class="mt-3">
        <div class="mx-auto text-center">
            <h3 class="fw-semibold mb-2">Ooop's !</h3>
            <img :src="err500" alt="error 500 img" height="230" class="my-3" />
            <h3 class="fw-bold text-primary lh-base mt-3">Server Error</h3>
            <h5 class="fw-bold text-dark lh-base mt-2">Ohh Noo ! Seem Like Our Servers Are Lost</h5>
            <p class="text-muted fs-12 mb-3">
                Our internal server has gone on a uninformed vacation. And, we are trying our best to bring it back online.
            </p>
            <Link href="/" class="btn btn-primary">Back To Home <i class="ti ti-home ms-1"></i></Link>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import err500 from '@/images/error/error-500.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
