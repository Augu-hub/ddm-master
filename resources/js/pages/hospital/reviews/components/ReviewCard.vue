<template>
    <b-card no-body class="overflow-hidden">
        <b-card-header class="d-flex align-items-center border-bottom bg-light-subtle gap-2 border-dashed">
            <img :src="item.image" alt="" class="avatar-xl rounded-circle border-light border border-2" />
            <div>
                <p class="text-dark fw-medium fs-15 mb-1">{{ item.name }}</p>
                <p class="text-muted mb-0">{{ item.specialistIn }}</p>
            </div>
            <div class="text-lg-end text-md-end text-sm-end ms-auto">
                <div class="d-inline-flex align-items-center fs-5 flex-grow-1 mb-2">
                    <span v-for="i in new Array(Math.floor(item.overallRating))" class="ti ti-star-filled text-warning"></span>
                    <span v-if="!Number.isInteger(item.overallRating)" class="ti ti-star-half-filled text-warning"></span>
                    <template v-if="item.overallRating < 5">
                        <span v-for="i in new Array(5 - Math.ceil(item.overallRating))" class="ti ti-star text-warning"></span>
                    </template>
                </div>
                <p class="fw-medium mb-0">Rating {{ item.overallRating }}</p>
            </div>
        </b-card-header>
        <b-card-body>
            <h4>{{ abbreviatedNumber(item.totalReviews) }} Reviews and ratings</h4>

            <template v-for="(review, idx) in item.reviews">
                <div :class="idx != item.reviews.length - 1 ? 'my-3' : 'mb-3'">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fs-15 fw-medium mb-0">{{ review.name }}</p>
                            <div class="d-inline-flex align-items-center fs-14 flex-grow-1 mt-0">
                                <span v-for="i in new Array(Math.floor(review.rating))" class="ti ti-star-filled text-warning"></span>
                                <span v-if="!Number.isInteger(review.rating)" class="ti ti-star-half-filled text-warning"></span>
                                <template v-if="review.rating < 5">
                                    <span v-for="i in new Array(5 - Math.ceil(review.rating))" class="ti ti-star text-warning"></span>
                                </template>
                            </div>
                        </div>
                        <div>
                            <p class="fs-13 fw-medium text-muted mb-0">{{ review.timestamp }}</p>
                        </div>
                    </div>
                    <p class="my-2">{{ review.comment }}</p>
                    <div>
                        <a href="#!" class="fs-13 link-reset me-2"><i class="ti ti-thumb-up fs-16"></i> {{ review.likes }}</a>
                        <a href="#!" class="fs-13 link-reset me-3"><i class="ti ti-thumb-down fs-16"></i> {{ review.dislikes }}</a>
                    </div>
                </div>
                <hr v-if="idx != item.reviews.length - 1" />
            </template>
            <a href="#!" class="link-primary fw-medium">View More Review</a>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import { abbreviatedNumber } from '@/helpers/numbers';
import type { DoctorReviewType } from '@/pages/hospital/reviews/components/data';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<DoctorReviewType>,
        required: true,
    },
});
</script>
