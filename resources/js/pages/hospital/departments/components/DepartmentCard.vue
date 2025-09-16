<template>
    <b-card no-body>
        <img :src="item.image" alt="" class="img-fluid mx-auto" width="300" />
        <b-card-body class="border-top border-dashed">
            <h4 class="fw-medium text-dark">{{ item.name }}</h4>
            <p class="mb-0 mt-2">{{ item.about }} <a href="#!" class="link-primary fw-medium">Show More</a></p>
            <div class="d-inline-flex align-items-center fs-18 flex-grow-1 mt-2">
                <span v-for="i in new Array(Math.floor(item.rating))" class="ti ti-star-filled text-warning"></span>
                <span v-if="!Number.isInteger(item.rating)" class="ti ti-star-half-filled text-warning"></span>
                <template v-if="item.rating < 5">
                    <span v-for="i in new Array(5 - Math.ceil(item.rating))" class="ti ti-star text-warning"></span>
                </template>

                <span class="fs-14 ms-1">{{ abbreviatedNumber(item.totalReviews) }} Reviews </span>
            </div>
            <div class="d-flex align-items-center mt-2 flex-wrap gap-2">
                <p class="fw-medium fs-14 mb-0">Best Experience Doctor :</p>

                <div class="avatar-group">
                    <template v-for="(doctor, idx) in item.doctors.slice(0, 3)" :key="idx">
                        <div v-if="doctor.image" class="avatar" v-b-tooltip.top="doctor.name">
                            <img :src="doctor.image" alt="" class="rounded-circle avatar-sm" />
                        </div>

                        <div v-else class="avatar avatar-sm" v-b-tooltip.top="doctor.name">
                            <span v-if="doctor.name" class="avatar-title bg-info rounded-circle fw-bold">
                                {{ getFirstCharacter(doctor.name) }}
                            </span>
                        </div>
                    </template>

                    <div
                        v-if="item.doctors.length > 2"
                        class="avatar avatar-sm"
                        data-bs-custom-class="tooltip-danger"
                        v-b-tooltip.top="`${item.doctors.length - 3} more`"
                    >
                        <span class="avatar-title bg-danger rounded-circle fw-bold"> {{ item.doctors.length - 3 }}+ </span>
                    </div>
                </div>
            </div>
        </b-card-body>
        <div class="card-footer border-top hstack gap-1 border-dashed">
            <a href="#!" class="btn btn-primary w-100">View More Report</a>
            <a href="#!" class="btn btn-dark d-inline-flex align-items-center justify-content-center avatar-md rounded"
                ><i class="ti ti-edit fs-20"></i
            ></a>
        </div>
    </b-card>
</template>

<script setup lang="ts">
import { getFirstCharacter } from '@/helpers/change-casing';
import { abbreviatedNumber } from '@/helpers/numbers';
import type { DepartmentType } from '@/pages/hospital/departments/components/data';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<DepartmentType>,
        required: true,
    },
});
</script>
