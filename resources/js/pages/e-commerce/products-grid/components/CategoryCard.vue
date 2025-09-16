<template>
    <b-card no-body>
        <b-card-body class="text-center">
            <div class="avatar-lg bg-light d-flex align-items-center justify-content-center mx-auto mb-2 rounded">
                <Icon :icon="item.icon" class="fs-32 text-warning" />
            </div>
            <a href="#!" class="text-dark fs-16 fw-medium">{{ item.name }}</a>
            <p class="mb-0 mt-1">{{ item.totalItems }} Items Available</p>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import type { CategoryType } from '@/pages/e-commerce/products-grid/components/data';
import { Icon } from '@iconify/vue';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<CategoryType>,
        required: true,
    },
});
</script>
