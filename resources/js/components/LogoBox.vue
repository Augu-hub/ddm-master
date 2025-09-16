<template>
    <Link href="/" :class="customClass ?? 'logo'">
        <template v-if="authBrand">
            <img :src="logoDark" alt="dark logo" :height="height" class="logo-dark" />
            <img :src="logoLight" alt="logo light" :height="height" class="logo-light" />
        </template>

        <template v-else>
            <span class="logo-light">
                <span class="logo-lg"><img :src="logoLight" alt="logo" /></span>
                <span class="logo-sm"><img :src="logoSm" alt="small logo" /></span>
            </span>
            <span class="logo-dark">
                <span class="logo-lg"><img :src="logoDark" alt="dark logo" /></span>
                <span class="logo-sm"><img :src="logoSm" alt="small logo" /></span>
            </span>
        </template>
    </Link>
</template>

<script setup lang="ts">
import logoDark from '@/images/logo-dark.png';
import logoSm from '@/images/logo-sm.png';
import logoLight from '@/images/logo.png';
import { Link } from '@inertiajs/vue3';

type PropsType = {
    customClass?: string;
    height?: number;
    authBrand?: boolean;
};

defineProps<PropsType>();
</script>
