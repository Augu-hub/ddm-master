<template>
    <b-card no-body>
        <b-card-body class="text-center">
            <b-row class="justify-content-between mb-3">
                <b-col lg="4" cols="4">
                    <div class="d-flex align-items-center fs-16 gap-1">
                        <span class="fw-medium">{{ item.rating }} </span>
                        <span class="ti ti-star-filled text-warning"></span>
                    </div>
                </b-col>
                <b-col lg="4" cols="4">
                    <img :src="item.image" alt="" class="avatar-xl rounded" />
                </b-col>
                <b-col lg="4" cols="4" class="text-end">
                    <div class="d-flex align-items-center justify-content-end fs-16 gap-1">
                        <span class="fw-medium">{{ item.distance }}</span>
                        <span class="ti ti-map-pin-filled text-primary"></span>
                    </div>
                </b-col>
            </b-row>
            <h4>{{ item.name }}</h4>
            <p class="text-muted mb-0">{{ item.specialistIn }}</p>
            <div class="d-flex justify-content-center mt-3 gap-2">
                <a href="#!" class="btn btn-soft-danger d-inline-flex align-items-center justify-content-center rounded p-1">
                    <Icon icon="solar:chat-round-call-bold-duotone" class="fs-22" />
                </a>
                <a href="#!" class="btn btn-soft-primary d-inline-flex align-items-center justify-content-center rounded p-1">
                    <Icon icon="solar:outgoing-call-rounded-bold-duotone" class="fs-22" />
                </a>
                <a href="#!" class="btn btn-soft-success d-inline-flex align-items-center justify-content-center rounded p-1">
                    <Icon icon="solar:videocamera-bold-duotone" class="fs-22" />
                </a>
            </div>
            <div class="mt-3 text-start">
                <p class="fw-medium fs-14 d-flex align-items-center mb-1">
                    Location : <span class="fs-13 fw-normal ms-auto">{{ item.address }}</span>
                </p>
                <p class="fw-medium fs-14 d-flex align-items-center mb-0">
                    Email Address :
                    <a href="#!" class="fs-13 fw-normal ms-auto">{{ item.email }}</a>
                </p>
            </div>
        </b-card-body>
        <b-card-footer class="border-top hstack gap-1 border-dashed">
            <a href="#!" class="btn btn-outline-primary w-100">Show All Services</a>
            <a href="#!" class="btn btn-primary w-100">Get Appointment</a>
        </b-card-footer>
    </b-card>
</template>

<script setup lang="ts">
import type { ContactType } from '@/pages/hospital/contacts/components/data';
import { Icon } from '@iconify/vue';
import type { PropType } from 'vue';

defineProps({
    item: {
        type: Object as PropType<ContactType>,
        required: true,
    },
});
</script>
