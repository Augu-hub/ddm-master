<template>
    <b-card no-body>
        <b-card-body>
            <b-row class="justify-content-between">
                <b-col sm="5">
                    <Icon :icon="item.icon" class="fs-36 text-muted" />
                    <h3 class="fw-bold mb-0 mb-1 mt-2">{{ item.prefix }}{{ item.value }}{{ item.suffix }}</h3>
                    <p class="text-muted">{{ item.label }}</p>
                    <b-badge :variant="null" class="fs-12" :class="item.growth < 0 ? 'badge-soft-danger' : 'badge-soft-success'">
                        <i :class="item.growth < 0 ? 'ti ti-arrow-badge-down' : 'ti ti-arrow-badge-up'"></i>
                        {{ Math.abs(item.growth) }}%
                    </b-badge>
                </b-col>

                <b-col sm="7" class="d-flex flex-column text-end">
                    <Link :href="item.url" class="link-reset text-decoration-underline link-offset-2 fw-medium pb-2"> View Details </Link>
                    <div class="mt-auto text-end">
                        <ApexChart :chart="item.chart" />
                    </div>
                </b-col>
            </b-row>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import type { StatisticType } from '@/pages/dashboards/e-wallet/components/types';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<StatisticType>,
        required: true,
    },
});
</script>
