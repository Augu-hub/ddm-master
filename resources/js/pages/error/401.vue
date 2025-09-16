<template>
    <AuthLayout show-footer footer-class="mt-3">
        <div class="mx-auto text-center">
            <h3 class="fw-semibold mb-2">Hold Up !</h3>

            <img :src="err401" alt="" class="mb-2 mt-3" height="230" />

            <h2 class="fw-bold text-primary lh-base mt-3">Error Unauthorized !</h2>

            <h4 class="fw-bold text-dark lh-base mt-2">Access To Allowed Only For Registered User</h4>

            <p class="text-muted fs-15 mb-3">Sorry, but you are not authorized to view this page.</p>

            <Link href="/" class="btn btn-primary">Back To Home <i class="ti ti-home ms-1"></i></Link>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import err401 from '@/images/error/error-401.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
