<template>
    <div class="auth-bg d-flex min-vh-100 justify-content-center align-items-center">
        <b-row class="g-0 justify-content-center m-xxl-5 px-xxl-4 w-100 m-3">
            <b-col xl="4" lg="5" md="6">
                <b-card no-body class="p-xxl-4 h-100 mb-0 overflow-hidden p-3 text-center">
                    <LogoBox auth-brand :height="24" custom-class="auth-brand mb-3" />

                    <slot />

                    <AuthFooter v-if="showFooter" :class="footerClass" />
                </b-card>
            </b-col>
        </b-row>
    </div>
</template>

<script setup lang="ts">
import LogoBox from '@/components/LogoBox.vue';
import AuthFooter from '@/layouts/partials/AuthFooter.vue';
import { onMounted, onUnmounted } from 'vue';

const body = document.body;

defineProps({
    showFooter: {
        type: Boolean,
        default: false,
    },
    footerClass: {
        type: String,
    },
});

onMounted(() => {
    if (body) body.classList.add('h-100');
});

onUnmounted(() => {
    if (body) body.classList.remove('h-100');
});
</script>
