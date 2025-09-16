<template>
    <b-card no-body>
        <b-card-header class="border-bottom">
            <b-card-title tag="h5" class="mb-0">Shipment & Details</b-card-title>
        </b-card-header>
        <b-card-body>
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                    <img :src="digitalOcean" alt="" class="avatar-md" />
                </div>

                <div>
                    <p class="text-dark fw-medium fs-16 mb-1">American Franklin Simon</p>
                    <p class="mb-0">dhanookapns142@armyspy.com</p>
                </div>
            </div>
            <b-row class="justify-content-between my-3">
                <b-col lg="5">
                    <p class="fs-15 mb-1">Recipient</p>
                    <p class="fw-semibold text-dark fs-15 mb-0">Dhanoo K.</p>
                </b-col>
                <b-col lg="7">
                    <p class="fs-15 mb-1">Delivery Address</p>
                    <p class="fw-semibold text-dark fs-15 mb-0">1890 Uitsig Grahamstad USA</p>
                </b-col>
            </b-row>
            <b-row class="justify-content-between mt-4">
                <b-col lg="5">
                    <p class="fs-15 mb-1">Phone Number</p>
                    <p class="fw-semibold text-dark fs-15 mb-0">+ 727-456-6512</p>
                </b-col>
                <b-col lg="7">
                    <p class="fs-15 mb-1">Payment ID</p>
                    <b-badge :variant="null" pill class="bg-light-subtle text-dark fs-13 fw-medium border px-2 py-1"
                        >#PY26356-NT <a href="#!" class="ms-1"><i class="ti ti-copy"></i></a
                    ></b-badge>
                </b-col>
            </b-row>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import digitalOcean from '@/images/brands/digital-ocean.svg';
</script>
