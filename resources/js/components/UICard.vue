<template>
    <b-card no-body>
        <b-card-header class="border-bottom border-0 border-dashed">
            <h4 class="header-title" :class="titleClass">{{ title }}</h4>
        </b-card-header>
        <b-card-body>
            <p class="text-muted" v-if="caption">{{ caption }}</p>
            <slot />
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
type PropsType = {
    title: string;
    caption?: string;
    titleClass?: string;
};

defineProps<PropsType>();
</script>
