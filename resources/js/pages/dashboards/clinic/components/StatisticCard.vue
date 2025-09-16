<template>
    <b-card no-body>
        <b-card-body>
            <Link v-if="item.url" :href="item.url" class="text-muted mt-n1 fs-18 float-end"><i class="ti ti-external-link"></i> </Link>
            <h5 class="text-muted fs-13 text-uppercase">{{ item.label }}</h5>
            <div class="d-flex align-items-center my-3 gap-2">
                <div v-if="item.icon" class="avatar-md flex-shrink-0">
                    <span class="avatar-title bg-primary-subtle text-primary fs-22 rounded">
                        <i :class="item.icon"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-0">
                    {{ item.prefix }}{{ item.value }}{{ item.suffix }}
                    <b-badge
                        v-if="item.badge"
                        class="fw-medium fs-12 ms-2"
                        :class="item.badge.variant ? `text-bg-${item.badge.variant}` : 'text-bg-success'"
                    >
                        {{ item.badge.text }}
                    </b-badge>
                </h3>
            </div>

            <template v-if="item.subStatistic">
                <p v-for="(stat, idx) in item.subStatistic" :class="idx != item.subStatistic.length - 1 ? 'mb-1' : 'mb-0'">
                    <span v-if="index" class="text-primary me-1">
                        <i v-if="index % 2 == 0" class="ti ti-point-filled"></i>
                        <i v-else class="ti ti-minus"></i>
                    </span>

                    <span v-else class="text-primary me-1">
                        <i class="ti ti-point-filled"></i>
                    </span>

                    <span class="text-muted text-nowrap">{{ stat.label }}</span>
                    <span class="float-end"
                        ><b>{{ stat.prefix }}{{ stat.value }}{{ stat.suffix }}</b></span
                    >
                </p>
            </template>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import type { StatisticType } from '@/pages/dashboards/clinic/components/types';
import { Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<StatisticType>,
        required: true,
    },
    index: Number,
});
</script>
