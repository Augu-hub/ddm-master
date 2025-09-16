<template>
    <div class="bg-light rounded-3 bg-opacity-50 p-3">
        <div class="d-flex align-items-center gap-2">
            <img :src="doctor.image" alt="user-image" class="avatar-xl rounded-circle me-1" />
            <div>
                <h4>{{ doctor.name }}</h4>
                <p v-if="doctor.specialistIn" class="text-muted">{{ doctor.specialistIn }}</p>
                <p v-if="doctor.overallRating" class="fs-14 m-0">
                    <i class="ti ti-star-filled text-warning"></i> {{ doctor.overallRating }} &bull;
                    <a v-if="doctor.totalReviewCount" href="#" class="link-reset fw-medium">
                        {{ doctor.totalReviewCount }}+ Reviews <i class="ti ti-arrow-right"></i>
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { DoctorType } from '@/pages/dashboards/clinic/components/types';
import type { PropType } from 'vue';

defineProps({
    doctor: {
        type: Object as PropType<DoctorType>,
        required: true,
    },
});
</script>
