<template>
    <div v-if="title" class="page-title-head d-flex align-items-sm-center flex-sm-row flex-column gap-2">
        <Head :title="title" />

        <div class="flex-grow-1">
            <h4 class="fs-18 fw-semibold mb-0">{{ title }}</h4>
        </div>

        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item">
                    <Link href="/">{{ appName }}</Link>
                </li>
                <li v-if="subtitle" class="breadcrumb-item">
                    <Link v-if="subtitleRoute" :href="subtitleRoute">{{ subtitle }}</Link>
                    <template v-else>{{ subtitle }}</template>
                </li>
                <li class="breadcrumb-item active">{{ title }}</li>
            </ol>
        </div>
    </div>

    <div v-else class="page-title-head d-flex align-items-sm-center flex-sm-row flex-column">
        <h4 class="fs-18 fw-semibold mb-0">Welcome</h4>
    </div>
</template>

<script setup lang="ts">
import { appName } from '@/helpers';
import { Head, Link } from '@inertiajs/vue3';
type PropsType = {
    title?: string;
    subtitle?: string;
    subtitleRoute?: string;
};

defineProps<PropsType>();
</script>
