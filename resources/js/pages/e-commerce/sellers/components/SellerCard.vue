<template>
    <b-card no-body>
        <b-card-body>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <div class="avatar-xl bg-light d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <img :src="item.image" alt="" class="flex-shrink-0" height="30" width="70" />
                </div>
                <div>
                    <h4 class="text-dark fw-semibold">{{ item.name }}</h4>
                    <div class="d-inline-flex align-items-center fs-18 flex-grow-1">
                        <span v-for="i in new Array(Math.floor(item.rating))" class="ti ti-star-filled text-warning"></span>
                        <span v-if="!Number.isInteger(item.rating)" class="ti ti-star-half-filled text-warning"></span>
                        <template v-if="item.rating < 5">
                            <span v-for="i in new Array(5 - Math.ceil(item.rating))" class="ti ti-star text-warning"></span>
                        </template>

                        <span class="fs-14 fw-medium ms-1">{{ abbreviatedNumber(item.totalRating) }}</span>
                    </div>
                </div>
                <div class="ms-lg-auto">
                    <a href="#!" class="btn btn-primary btn-sm">Message</a>
                </div>
            </div>
            <p class="fw-medium my-3">" {{ item.about }} "</p>
            <p class="fw-medium d-flex align-items-center mb-1 gap-2">
                <Icon icon="solar:map-point-search-bold" class="fs-20 text-danger" />
                : <span class="fw-medium">{{ item.address }} </span>
            </p>
            <p class="text-dark fw-medium d-flex align-items-center mb-3 gap-2">
                <Icon icon="solar:letter-bold" class="fs-20 text-danger" />
                : <a href="#!" class="link-primary fw-medium">{{ item.email }}</a>
            </p>

            <div class="border-end-0 border-start-0 mx-n3 border border-dashed p-2">
                <b-row class="g-2 text-center">
                    <b-col lg="4" cols="4" class="border-end">
                        <h4 class="mb-1">{{ abbreviatedNumber(item.inStockItems) }}</h4>
                        <p class="text-muted mb-0">Item Stock</p>
                    </b-col>
                    <b-col lg="4" cols="4" class="border-end">
                        <h4 class="mb-1">+{{ abbreviatedNumber(item.sells) }}</h4>
                        <p class="text-muted mb-0">Sells</p>
                    </b-col>
                    <b-col lg="4" cols="4">
                        <h4 class="mb-1">{{ item.brand }}</h4>
                        <p class="text-muted mb-0">Brand</p>
                    </b-col>
                </b-row>
            </div>
            <b-row class="align-items-center justify-content-between my-4 text-center">
                <b-col lg="8" class="border-end">
                    <ApexChart :chart="item.revenueChart" class="apex-charts pe-lg-3" />
                </b-col>
                <b-col lg="4">
                    <h3 class="fw-semibold mb-1">{{ currency }}{{ item.revenue }}</h3>
                    <p class="text-muted fs-14 mb-0">Revenue</p>
                </b-col>
            </b-row>
            <div class="hstack gap-1">
                <a href="#!" class="btn btn-primary w-100">Show Profile</a>
                <a href="#!" class="btn btn-light w-100">Edit Profile</a>
            </div>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import { currency } from '@/helpers';
import { abbreviatedNumber } from '@/helpers/numbers';
import type { SellerType } from '@/pages/e-commerce/sellers/components/data';
import { Icon } from '@iconify/vue';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<SellerType>,
        required: true,
    },
});
</script>
