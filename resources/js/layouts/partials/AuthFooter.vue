<template>
    <p v-bind="$attrs" class="mb-0">
        {{ currentYear }}
        © {{ appName }} - By
        <span class="fw-bold text-decoration-underline text-uppercase text-reset fs-12">
            {{ author }}
        </span>
    </p>
</template>

<script setup lang="ts">
import { appName, author, currentYear } from '@/helpers';
</script>
