<template>
    <AuthLayout>
        <div>
            <h3 class="fw-semibold text-dark">Ooop's !</h3>
        </div>

        <img :src="maintenance" alt="" class="img-fluid mt-3" height="320" />

        <h5 class="fw-bold fs-20 text-dark lh-base my-3">We are Under Scheduled Maintenance</h5>

        <Link href="/" class="btn btn-primary">Back To Home <i class="ti ti-home ms-1"></i></Link>
    </AuthLayout>
</template>

<script setup lang="ts">
import maintenance from '@/images/png/maintenance.png';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
