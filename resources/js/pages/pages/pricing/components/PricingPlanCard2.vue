<template>
    <b-card no-body class="card-pricing h-100">
        <b-card-header class="border-bottom border-dashed p-3">
            <h3 class="fw-semibold">{{ plan.name }}</h3>
            <h5 class="text-muted fw-normal mb-0 mt-2">{{ plan.description }}</h5>
        </b-card-header>
        <b-card-body class="p-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted fs-3 fw-bold">{{ currency }}</span>
                <h1 class="display-5 fw-bold mb-0">{{ plan.price }}</h1>
                <div class="d-block">
                    <p class="fw-semibold fs-5 mb-0">One-time payment</p>
                    <p class="text-muted fw-semibold fs-5 mb-0">plus local taxes</p>
                </div>
            </div>
            <template v-if="plan.features">
                <h4 class="text-primary fw-semibold my-3">What's Included :</h4>
                <ul class="d-flex flex-column list-unstyled fs-16 fw-medium gap-2">
                    <li v-for="feature in plan.features">
                        <i class="ti ti-checks text-primary fs-4 me-1 align-middle"></i>
                        {{ feature.label }}
                    </li>
                </ul>
            </template>
        </b-card-body>

        <b-card-footer>
            <a href="#!" :class="plan.isPopular ? 'btn-primary' : 'btn-outline-primary'" class="btn w-100">Buy Now</a>
        </b-card-footer>

        <span
            v-if="plan.isPopular"
            class="position-absolute translate-middle bg-primary-subtle text-primary rounded-pill fw-semibold start-50 top-0 px-3 py-1"
        >
            Most Popular
        </span>
    </b-card>
</template>

<script setup lang="ts">
import { currency } from '@/helpers';
import type { PricingPlanType } from '@/pages/pages/pricing/components/data';
import type { PropType } from 'vue';

defineProps({
    plan: {
        type: Object as PropType<PricingPlanType>,
        required: true,
    },
});
</script>
