<template>
    <b-card no-body>
        <img :src="product.image" alt="product images" class="img-fluid" />

        <b-card-body class="border-top border-dashed">
            <h5 class="text-primary fw-medium">{{ product.category }}</h5>
            <div>
                <Link :href="product.route" class="fw-semibold fs-16 text-dark">{{ product.name }}</Link>
            </div>
            <h5 class="my-1">
                Size :

                <span v-for="(size, idx) in product.size" :key="idx">{{ size }}<span v-if="idx != product.size.length - 1">, </span></span>
            </h5>
            <div class="d-inline-flex align-items-center fs-16 flex-grow-1 mt-1">
                <span v-for="i in new Array(Math.floor(product.rating))" :key="i" class="ti ti-star-filled text-warning"></span>
                <span v-if="!Number.isInteger(product.rating)" class="ti ti-star-half-filled text-warning"></span>
                <template v-if="product.rating < 5">
                    <span v-for="i in new Array(5 - Math.ceil(product.rating))" :key="i" class="ti ti-star text-warning"></span>
                </template>

                <span class="fs-14 ms-1">{{ abbreviatedNumber(product.totalReview) }} Reviews </span>
            </div>
        </b-card-body>

        <div class="card-footer d-flex align-items-center justify-content-between border-top flex-wrap border-dashed">
            <h4 class="fw-semibold text-danger d-flex align-items-center mb-0 gap-2">
                <span class="text-muted text-decoration-line-through">{{ currency }}{{ product.price }}</span>
                {{ currency }} {{ (product.price - calculateDiscount(product)).toFixed(2) }}
            </h4>

            <button class="btn btn-soft-primary fs-20 px-2" @click="updateQuantity(product, 1)">
                <Icon icon="solar:cart-3-bold-duotone" />
            </button>
        </div>

        <span class="position-absolute end-0 top-0 p-2">
            <button type="button" class="btn btn-icon btn-light rounded-circle" @click="toggleToWishlist(product)">
                <Icon v-if="isInWishlist(product)" icon="solar:heart-angle-bold-duotone" class="fs-22 text-danger" />
                <Icon v-else icon="solar:heart-angle-bold-duotone" class="fs-22" />
            </button>
        </span>

        <span v-if="product.tag" class="position-absolute start-0 top-0 p-2">
            <b-badge variant="danger" class="fs-11">{{ product.tag }}</b-badge>
        </span>
    </b-card>
</template>

<script setup lang="ts">
import { currency } from '@/helpers';
import { abbreviatedNumber } from '@/helpers/numbers';
import { calculateDiscount } from '@/helpers/shop';
import type { ProductType } from '@/types/shop';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';

import { useShoppingStore } from '@/stores/shop';

defineProps({
    product: {
        type: Object as PropType<ProductType>,
        required: true,
    },
});

const { isInWishlist, toggleToWishlist, isInCart, updateQuantity } = useShoppingStore();
</script>
