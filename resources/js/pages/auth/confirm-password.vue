<template>
    <AuthLayout show-footer>
        <Head title="Confirm password" />

        <h3 class="fw-semibold mb-2">Confirm password</h3>

        <b-form @submit.prevent="submit" class="mb-3 text-start">
            <b-form-group label="Password" class="mb-3">
                <b-form-input v-model="form.password" type="password" placeholder="Enter your password" />
                <p v-if="form.errors.password" class="text-danger">
                    {{ form.errors.password }}
                </p>
            </b-form-group>

            <div class="d-grid mb-2">
                <b-button variant="primary" type="submit" :disabled="form.processing">Confirm Password</b-button>
            </div>
        </b-form>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';

import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>
