<template>
    <b-card no-body>
        <b-card-body>
            <h5 class="text-muted fs-13 text-uppercase">{{ item.label }}</h5>
            <div class="d-flex align-items-center justify-content-center my-2 gap-2 py-1">
                <div class="user-img fs-42 flex-shrink-0">
                    <span class="avatar-title text-bg-primary rounded-circle fs-22">
                        <Icon :icon="item.icon"></Icon>
                    </span>
                </div>
                <h3 class="fw-bold mb-0">{{ item.prefix }}{{ item.value }}{{ item.suffix }}</h3>
            </div>
            <p class="text-muted mb-0">
                <span v-if="item.growth < 0" class="text-danger me-2">
                    <i class="ti ti-caret-down-filled"></i>
                    {{ Math.abs(item.growth) }}%
                </span>

                <span v-else class="text-success me-2"><i class="ti ti-caret-up-filled"></i> {{ item.growth }}%</span>

                <span v-if="item.duration" class="text-nowrap">Since last {{ item.duration }}</span>
            </p>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import type { StatisticType } from '@/pages/dashboards/sales/components/types';
import { Icon } from '@iconify/vue';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<StatisticType>,
        required: true,
    },
});
</script>
