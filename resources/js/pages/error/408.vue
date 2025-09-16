<template>
    <AuthLayout show-footer footer-class="mt-3">
        <div class="mx-auto text-center">
            <h3 class="fw-semibold mb-2">Oooh No !</h3>
            <img :src="err408" alt="error 408 img" height="230" />
            <h3 class="fw-bold text-primary lh-base mt-3">Error 408 Request Time Out</h3>
            <h5 class="fw-bold text-dark lh-base mt-2">The Server Timed Out Waiting For The Client's Request.</h5>
            <p class="text-muted fs-12 mb-3">The server did not receive a complete request message within the time that it was prepared to wait.</p>
            <Link href="/" class="btn btn-primary">Back To Home <i class="ti ti-home ms-1"></i></Link>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import err408 from '@/images/error/error-408.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
