<template>
    <VerticalLayout>
        <PageTitle title="Pricing One" subtitle="Pricing" />

        <b-row class="justify-content-center">
            <b-col xxl="9">
                <div class="text-center">
                    <h3 class="mb-2">Our Plans and Pricing</h3>
                    <p class="text-muted w-50 m-auto">
                        We have plans and prices that fit your business perfectly. Make your client site a success with our products.
                    </p>
                </div>

                <b-row class="mt-sm-5 align-items-center justify-content-center mb-3 mt-3">
                    <b-col lg="6" v-for="(plan, idx) in pricingPlans1" :key="idx">
                        <PricingPlanCard1 :plan="plan" />
                    </b-col>
                </b-row>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import PricingPlanCard1 from '@/pages/pages/pricing/components/PricingPlanCard1.vue';
import { pricingPlans1 } from '@/pages/pages/pricing/components/data';
</script>
