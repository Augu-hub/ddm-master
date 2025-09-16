<template>
    <b-card no-body class="card-pricing">
        <b-card-body class="p-4">
            <h3 class="fw-semibold">{{ plan.name }}</h3>
            <p class="fs-5 text-muted">{{ plan.description }}</p>
            <div class="price">
                <h1 class="display-5 fw-bold">
                    {{ currency }}{{ plan.price }} <span v-if="plan.duration" class="text-body-tertiary fs-4 fw-medium"> / {{ plan.duration }}</span>
                </h1>
            </div>
            <b-row v-if="plan.features" class="mt-3">
                <b-col v-for="(item, idx) in chunkArray(plan.features, 3)" :key="idx" lg="6">
                    <ul class="d-flex flex-column list-unstyled fs-16 gap-2">
                        <li v-for="feature in item">
                            <i
                                :class="`${feature.icon} ${feature.icon === 'ti ti-circle-check-filled' && 'text-primary'} `"
                                class="fs-4 me-1 align-middle"
                            ></i>
                            {{ feature.label }}
                        </li>
                    </ul>
                </b-col>
            </b-row>
            <b-row class="justify-content-between align-items-center mt-3">
                <b-col lg="5" v-if="plan.benefits">
                    <p v-for="(benefit, idx) in plan.benefits" class="fs-5" :class="idx != plan.benefits.length - 1 ? 'mb-1' : 'mb-0'">
                        - {{ benefit }}
                    </p>
                </b-col>
                <b-col lg="4">
                    <div class="price-btn">
                        <a href="#!" class="btn btn-primary">Start Free Trial</a>
                    </div>
                </b-col>
            </b-row>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import { currency } from '@/helpers';
import { chunkArray } from '@/helpers/array';
import type { PricingPlanType } from '@/pages/pages/pricing/components/data';
import type { PropType } from 'vue';

defineProps({
    plan: {
        type: Object as PropType<PricingPlanType>,
        required: true,
    },
});
</script>
