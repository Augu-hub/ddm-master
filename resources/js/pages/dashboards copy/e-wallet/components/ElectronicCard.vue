<template>
    <b-card no-body class="rounded-4" :class="`bg-${item.variant}`">
        <b-card-body>
            <span class="text-white-50 display-5 mt-n1 float-end"><i class="ti ti-wifi"></i></span>
            <h4 class="text-white">{{ item.owner.name }}</h4>

            <b-row class="align-items-center mt-4">
                <b-col cols="3" v-for="x in [1, 2, 3]" :key="x" class="fs-10 text-white">
                    <i v-for="y in [1, 2, 3, 4]" class="ti ti-circle-filled" :key="y"></i>
                </b-col>

                <b-col cols="3" class="fs-16 fw-bold text-white">
                    <span v-for="i in stringOrNumberToArray(item.lastDigits)" :key="i">{{ i }}</span>
                </b-col>
            </b-row>

            <b-row class="align-items-center mt-4">
                <b-col cols="4">
                    <p class="text-white-50 mb-1">Expiry Date</p>
                    <h5 class="my-0 text-white">{{ item.expiryDate }}</h5>
                </b-col>

                <b-col cols="4">
                    <p class="text-white-50 mb-1">CVV</p>
                    <h5 class="my-0 text-white">{{ item.cvv ? item.cvv : 'XXX' }}</h5>
                </b-col>
                <b-col cols="4">
                    <div class="text-end">
                        <img :src="item.provider.image" alt="" height="16" class="me-1" />
                    </div>
                </b-col>
            </b-row>
        </b-card-body>
    </b-card>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h5 class="text-muted">Balance:</h5>
            <h4 class="fs-18">{{ currency }}{{ item.balance }}</h4>
        </div>
        <Link :href="item.route" class="link-reset text-decoration-underline link-offset-2 fw-semibold pb-2"> View Details </Link>
    </div>
</template>

<script lang="ts" setup>
import { currency } from '@/helpers';
import { stringOrNumberToArray } from '@/helpers/array';
import type { ElectronicCardType } from '@/pages/dashboards/e-wallet/components/types';
import { Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<ElectronicCardType>,
        required: true,
    },
});
</script>
